<?
//settings

$customerid = 2;
$srcshard = 2; //remember on the asp shard2 actually has shardid=1
$destshard = 1;

//authserver db info
$authhost = "10.25.25.68";
$authuser = "root";
$authpass = "";


//----------------------------------------------------------------------

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");

//----------------------------------------------------------------------

//connect to authserver db, and each shard
echo "connecting to authserver db\n";
$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");

echo "connecting to source shard\n";
$query = "select dbhost,dbusername,dbpassword from shard where id=$srcshard";
list($srchost,$srcuser,$srcpass) = QuickQueryRow($query,false,$authdb) or die("Can't query shard info:" . mysql_error()); 
$srcsharddb = DBConnect($srchost,$srcuser,$srcpass,"aspshard") or die("Can't connect to shard:" . mysql_error());

echo "connecting to dest shard\n";
$query = "select dbhost,dbusername,dbpassword from shard where id=$destshard";
list($desthost,$destuser,$destpass) = QuickQueryRow($query,false,$authdb) or die("Can't query shard info:" . mysql_error()); 
$destsharddb = DBConnect($desthost,$destuser,$destpass,"aspshard") or die("Can't connect to shard:" . mysql_error());

//sanity checks
echo "doing sanity checks\n";
//customer has active jobs

$query = "select count(*) from qjob where status in ('processing', 'procactive', 'active')";
if (QuickQuery($query,$srcsharddb))
	die("There are active jobs!");

//max last login
if (QuickQuery("select max(lastlogin) > now() - interval 1 hour from c_$customerid.user where login != 'schoolmessenger'",$srcsharddb))
	die("A user has logged in less than an hour ago!");

//customer db exists on source
if (!QuickQuery("show databases like 'c_$customerid'",$srcsharddb))
	die("Customer databases doesn't exist on source shard");

//customer db already exists on dest
if (QuickQuery("show databases like 'c_$customerid'",$destsharddb))
	die("Customer databases already exists on target shard");

//----------------------------------------------------------------------

//backup the existing customer db
$backupfilename = "c_$customerid.xfer.sql";
$cmd = "nice mysqldump -h $srchost -u $srcuser -p$srcpass --skip-triggers c_$customerid > $backupfilename";
echo "Backing up customer data\n$cmd\n";
$result = exec($cmd,$output,$retval);

if ($retval != 0)
	die("Problem backing up data for transfer\n" . implode("\n",$output));

//create a db, user, etc for the customer database on the shard
echo "creating destination DB\n";
$newdbname = "c_$customerid";
$newpass = genpassword();

$query = "create database $newdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
QuickUpdate($query,$destsharddb) or die ("Failed to create new DB $newdbname : " . mysql_error($destsharddb));
mysql_select_db($newdbname,$destsharddb) or die ("Failed select db $newdbname : " . mysql_error($destsharddb));

QuickUpdate("drop user '$newdbname'", $destsharddb); //ensure mysql credentials match our records, which it won't if create user fails because the user already exists
QuickUpdate("create user '$newdbname' identified by '$newpass'", $destsharddb);
QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $destsharddb);

//load customer db into new shard
$cmd = "nice mysql -h $desthost -u $destuser -p$destpass c_$customerid < $backupfilename";
echo("loading customer data\n$cmd\n");
$result = exec($cmd,$output,$retval);
if ($retval != 0)
	die("Problem loading transfer data\n" . implode("\n",$output));


//verify the tables by doing a checksum

mysql_select_db("c_$customerid",$srcsharddb);
mysql_select_db("c_$customerid",$destsharddb);

$customertables = QuickQueryList("show tables",false,$srcsharddb);

$anyerrors = 0;
foreach ($customertables as $t) {
	echo "Check $t ";
	if (verify_table($srcsharddb,$destsharddb,$t)) {
		 echo "OK\n";
	} else {
		echo "##### HASH MISMATCH #####\n";
		$anyerrors++;
	}
}

if ($anyerrors)
	die("################## HASH MISMATCH DETECTED##################\n\n");

mysql_select_db("aspshard",$srcsharddb);
//mysql_select_db("aspshard",$destsharddb); leave this set to customer db for queries below

//----------------------------------------------------------------------
// copy job/schedule/reportsubscription to shard

echo "copying shard records\n";

$timezone = QuickQuery("select value from setting where name='timezone'",$destsharddb);

// reportsubscription
echo ("Copy reportsubscriptions\n");
$query = "INSERT INTO aspshard.qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) select id, ".$customerid.", userid, type, daysofweek, dayofmonth, time, '".$timezone."', nextrun, email from reportsubscription";
mysql_query($query,$destsharddb)
	or die ("Failed to execute statement \n$query\n\nfor c_$customerid : " . mysql_error($destsharddb));

// jobsetting
echo ("Copy repeating jobs and settings\n");
$query = "INSERT INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT ".$customerid.", jobid, name, value FROM jobsetting WHERE jobid in (select id from job where status='repeating')";
mysql_query($query,$destsharddb)
	or die ("Failed to execute statement \n$query\n\nfor c_$customerid : " . mysql_error($destsharddb));

// repeating job
$query = "INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)" .
         " select id, ".$customerid.", userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, '".$timezone."', startdate, enddate, starttime, endtime, 'repeating', jobtypeid, thesql from job where status='repeating'";
mysql_query($query,$destsharddb)
	or die ("Failed to execute statement \n$query\n\nfor c_$customerid : " . mysql_error($destsharddb));

// schedule
$query = "INSERT INTO aspshard.qschedule (id, customerid, daysofweek, time, nextrun, timezone) select id, ".$customerid.", daysofweek, time, nextrun, '".$timezone."' from schedule";
mysql_query($query,$destsharddb)
	or die ("Failed to execute statement \n$query\n\nfor c_$customerid : " . mysql_error($destsharddb));

// future job
$query = "INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)" .
         " select id, ".$customerid.", userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, '".$timezone."', startdate, enddate, starttime, endtime, 'scheduled', jobtypeid, thesql from job where status='scheduled'";
mysql_query($query,$destsharddb)
	or die ("Failed to execute statement \n$query\n\nfor c_$customerid : " . mysql_error($destsharddb));


// create triggers
echo("Create triggers\n");
$sqlqueries = explode("$$$",file_get_contents("../db/createtriggers.sql"));
foreach ($sqlqueries as $query) {
	if (trim($query)) {
		$query = str_replace('_$CUSTOMERID_', $customerid, $query);
		mysql_query($query,$destsharddb)
			or die ("Failed to execute statement \n$query\n\nfor c_$customerid : " . mysql_error($destsharddb));
	}
}

//----------------------------------------------------------------------

echo "Transfer successful. setting authserver records and removing old database\n";

mysql_select_db("aspshard",$srcsharddb); //ensure src shard connection is set to aspshard database

//update authserver to point to new customer info
//update shardid and password
$query = "update customer set shardid=$destshard, dbpassword='" . DBSafe($newpass,$authdb) . "' where id=$customerid";
if (QuickUpdate($query,$authdb) === false)
	echo "Problem updating customer table:" . mysql_error($authdb) . "\n";

//remove shard tables from old shard
echo "deleting old shard records:";
$tablearray = array("importqueue", "jobstatdata", "qjobperson", "qjobtask", "specialtaskqueue", "qreportsubscription", "qjobsetting", "qschedule", "qjob");
foreach ($tablearray as $t) {
	echo ".";
	$query = "delete from ".$t." where customerid=$customerid";
	if (!mysql_query($query, $srcsharddb)) {
		echo "Failed to execute statement \n$query\n\n : " . mysql_error($srcsharddb) . "\n";
	}
}
echo "\n";


//drop old customer user
$query = "drop user c_$customerid";
if (QuickUpdate($query,$srcsharddb) === false)
	echo "Problem dropping old customer user:" . mysql_error($srcsharddb) . "\n";
//drop old customer db
echo "Dropping old database\n";
$query = "drop database c_$customerid";
if (QuickUpdate($query,$srcsharddb) === false)
	echo "Problem updating customer table:" . mysql_error($srcsharddb) . "\n";

echo "Done!\n";
echo "/-------------------------------------------------------------\\\n";
echo "| Dont forget to restart authserver, redialer, dispatchers    |\n";
echo "| as they cache connection info                               |\n";
echo "\\-------------------------------------------------------------/\n";


function genpassword($digits = 15) {
	$passwd = "";
	$chars = "0123456789ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz";
	while ($digits--) {
		$passwd .= $chars[mt_rand(0,strlen($chars)-1)];
	}
	return $passwd;
}


function verify_table ($db1,$db2,$table) {
	$fields = QuickQueryList("describe $table", false, $db1);	
	$fieldlist = "`" . implode("`, `",$fields) . "`";
	$query = "select md5(group_concat(md5(concat_ws('#',$fieldlist)))) from $table";
	
	$hash1 = QuickQuery($query,$db1);
	$hash2 = QuickQuery($query,$db2);
	
	if ($hash1 === false || $hash2 === false)
		echo "Problem trying to hash tables:" . mysql_error();
	
	return $hash1 == $hash2;
}

?>