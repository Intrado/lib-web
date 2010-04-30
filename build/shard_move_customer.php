<?
//settings

//authserver db info
$authhost = "10.25.25.68";
$authuser = "root";
$authpass = "";

// interupt file, stops execution. Will wait till current move operation completes
// go to working directory and execute: touch <stopfilename>
$stopfile = "stop_shard_move_customer";

// where is the createtriggers sql file?
$createtriggerssql = "../db/createtriggers.sql";
if (!file_exists($createtriggerssql))
	die("Cannot find createtriggers sql file: $createtriggerssql\n");

if ($argc < 4)
exit("need args: sourceshard destshard customerid [customerid] [customerid] ...\n");

array_shift($argv); //ignore this script

$srcshard = array_shift($argv); //remember on the asp shard2 actually has shardid=1
$destshard = array_shift($argv);

$customerids = $argv;
$successful = array();

echo "\nMoving customerids: [" . implode(", ", $customerids) . "] from shard id $srcshard to shard id $destshard\n\npress enter to continue";

fgets(STDIN);

//----------------------------------------------------------------------

$SETTINGS = parse_ini_file("../inc/settings.ini.php",true);

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../manager/managerutils.inc.php");

//----------------------------------------------------------------------

//connect to authserver db, and each shard
echo "connecting to authserver db\n";
$authdb = DBConnect($authhost,$authuser,$authpass,"authserver");

echo "connecting to dest shard\n";
$query = "select dbhost,dbusername,dbpassword,isfull from shard where id=$destshard";
list($desthost,$destuser,$destpass,$destisfull) = QuickQueryRow($query,false,$authdb) or die("Can't query shard info:" . errorinfo($authdb));

// check destination shard to see if it is set to full
if ($destisfull)
	die("Destination shard says it's full. Check authserver->shard->isfull for destination shard.\n");

$destsharddb = DBConnect($desthost,$destuser,$destpass,"aspshard") or die("Can't connect to shard:" . $desthost);

echo "connecting to source shard\n";
$query = "select dbhost,dbusername,dbpassword from shard where id=$srcshard";
list($srchost,$srcuser,$srcpass) = QuickQueryRow($query,false,$authdb) or die("Can't query shard info:" . errorinfo($authdb));
$srcsharddb = DBConnect($srchost,$srcuser,$srcpass,"aspshard") or die("Can't connect to shard:" . $srchost);

// for each customer id
foreach ($customerids as $customerid) {

	// check for stop file, if it exists. terminate
	if (file_exists($stopfile)) {
		echo "\nStop file found: $stopfile\nTerminating execution!\n";
		break;
	}

	echo "\n=============== Attempting to move customerid: $customerid ===================\n";

	//ensure shard connections are set to aspshard database
	$srcsharddb->query("use aspshard");
	$destsharddb->query("use aspshard");

	//sanity checks
	echo "doing sanity checks\n";

	//customer has active jobs
	$query = "select count(*) from qjob where customerid=$customerid and status in ('processing', 'procactive', 'active', 'cancelling')";
	if (QuickQuery($query,$srcsharddb))
		dieerror("There are active jobs! customerid: $customerid\n");

	//max last login
	if (QuickQuery("select max(lastlogin) > now() - interval 1 hour from c_$customerid.user where login != 'schoolmessenger'",$srcsharddb))
		dieerror("A user has logged in less than an hour ago! customerid: $customerid\n");

	//customer db exists on source
	if (!QuickQuery("show databases like 'c_$customerid'",$srcsharddb))
		dieerror("Customer database doesn't exist on source shard customerid: $customerid\n");

	//customer db already exists on dest
	if (QuickQuery("show databases like 'c_$customerid'",$destsharddb))
		dieerror("Customer database already exists on target shard customerid: $customerid\n");

	//----------------------------------------------------------------------

	//backup the existing customer db
	$backupfilename = "c_$customerid.xfer.sql";
	$cmd = "nice mysqldump -h $srchost -u $srcuser -p$srcpass --quick --single-transaction --skip-triggers c_$customerid > $backupfilename";
	echo "Backing up customer data to: $backupfilename\n";
	$result = exec($cmd,$output,$retval);

	if ($retval != 0)
		dieerror("Problem backing up data for transfer\ncustomerid: $customerid\n" . implode("\n",$output));

	// dump all messagelink for this customer into the transfer file
	echo "adding all messagelink records to transfer file\n";
	
	if (!$fp = fopen($backupfilename, 'a'))
		dieerror("Unable to open transfer file for writing: $backupfilename\n");
	
	$query = "use aspshard;\n";
	if (!fwrite($fp, $query))
		dieerror("Failed to write to transfer file : $query\n");
	
	$query = "select * from messagelink where customerid=?";
	if ($res = Query($query, $srcsharddb, array($customerid))) {
		while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
			$query = "insert ignore into messagelink (`customerid`,`jobid`,`personid`,`createtime`,`code`) values (".$row['customerid'].",".$row['jobid'].",".$row['personid'].",".$row['createtime'].",".$row['code'].");\n";
			if (!fwrite($fp, $query))
				dieerror("Failed to write to transfer file : $query\n");
		}
	}
	
	if (!fclose($fp))
		dieerror("Failed to close transfer file : $backupfilename\n");

	//create a db, user, etc for the customer database on the shard
	echo "creating destination DB\n";
	$newdbname = "c_$customerid";
	$newpass = genpassword();
	$limitedusername = "c_".$customerid."_limited";
	$limitedpassword = genpassword();

	$query = "create database $newdbname DEFAULT CHARACTER SET utf8 COLLATE utf8_general_ci";
	QuickUpdate($query,$destsharddb) or dieerror("Failed to create new DB $newdbname : " . errorinfo($destsharddb));
	$destsharddb->query("use ".$newdbname) or dieerror("Failed select db $newdbname : " . errorinfo($destsharddb));

	//ensure mysql credentials match our records, which it won't if create user fails because the user already exists
	QuickUpdate("drop user '$newdbname'", $destsharddb);
	QuickUpdate("drop user '$limitedusername'", $destsharddb);

	// create new db user for the customer on destination shard
	QuickUpdate("create user '$newdbname' identified by '$newpass'", $destsharddb);
	QuickUpdate("grant select, insert, update, delete, create temporary tables, execute on $newdbname . * to '$newdbname'", $destsharddb);

	//load customer db into new shard
	$cmd = "nice mysql -h $desthost -u $destuser -p$destpass c_$customerid < $backupfilename";
	echo("loading customer data from: $backupfilename\n");
	$result = exec($cmd,$output,$retval);
	if ($retval != 0)
		dieerror("Problem loading transfer data\ncustomerid: $customerid\n" . implode("\n",$output));

	// create the limited user
	createLimitedUser($limitedusername, $limitedpassword, $newdbname, $destsharddb);

	//verify the tables by doing a checksum
	$srcsharddb->query("use c_$customerid");
	$destsharddb->query("use c_$customerid");

	$customertables = QuickQueryList("show tables",false,$srcsharddb);

	echo "Check tables: ";
	$anyerrors = 0;
	foreach ($customertables as $t) {
		echo "$t ";
		if (verify_table($srcsharddb,$destsharddb,$t)) {
			 echo "OK, ";
		} else {
			echo "\n##### HASH MISMATCH #####\nTrying to verify customerid: $customerid table: $t\n";
			$anyerrors++;
		}
	}

	if ($anyerrors)
		dieerror("################## HASH MISMATCH DETECTED##################\n\nTrying to verify customerid: $customerid");


	// create triggers
	// NOTE we need to create triggers before we copy all shard jobs in case one starts processing
	echo("\nCreate triggers\n");
	$sqlqueries = explode("$$$",file_get_contents($createtriggerssql));
	if (false === QuickUpdate("START TRANSACTION", $destsharddb))
		dieerror("cannot start transaction\ncustomerid: $customerid\n");
	foreach ($sqlqueries as $query) {
		if (trim($query)) {
			$query = str_replace('_$CUSTOMERID_', $customerid, $query);
			$rowcount = QuickUpdate($query,$destsharddb);
			if ($rowcount === false)
				dieerror ("Failed to execute statement \n$query\n\nfor c_$customerid : ", $destsharddb);
		}
	}
	if (false === QuickUpdate("COMMIT", $destsharddb))
		dieerror("failed to commit transaction\ncustomerid: $customerid\n");

	$srcsharddb->query("use aspshard");

	//----------------------------------------------------------------------
	// copy job/schedule/reportsubscription to shard

	echo "copying shard records\n";

	$timezone = QuickQuery("select value from setting where name='timezone'",$destsharddb);

	// reportsubscription
	echo ("Copy reportsubscriptions\n");
	if (false === QuickUpdate("START TRANSACTION", $destsharddb))
		dieerror("cannot start transaction");
	$query = "INSERT INTO aspshard.qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) select id, ".$customerid.", userid, type, daysofweek, dayofmonth, time, '".$timezone."', nextrun, email from reportsubscription";
	$rowcount = QuickUpdate($query,$destsharddb);
	if ($rowcount === false)
		dieerror ("Failed to execute statement \n$query\n\nfor c_$customerid : ", $destsharddb);
	if (false === QuickUpdate("COMMIT", $destsharddb))
		dieerror("failed to commit transaction\ncustomerid: $customerid\n");

	if (false === QuickUpdate("START TRANSACTION", $destsharddb))
		dieerror("cannot start transaction\ncustomerid: $customerid\n");

	// repeating job
	$query = "INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, timezone, startdate, enddate, starttime, endtime, status)" .
         " select id, ".$customerid.", userid, scheduleid, '".$timezone."', startdate, enddate, starttime, endtime, 'repeating' from job where status='repeating'";
	$rowcount = QuickUpdate($query,$destsharddb);
	if ($rowcount === false)
		dieerror ("Failed to execute statement \n$query\n\nfor c_$customerid : ", $destsharddb);

	// schedule
	$query = "INSERT INTO aspshard.qschedule (id, customerid, daysofweek, time, nextrun, timezone) select id, ".$customerid.", daysofweek, time, nextrun, '".$timezone."' from schedule";
	$rowcount = QuickUpdate($query,$destsharddb);
	if ($rowcount === false)
		dieerror ("Failed to execute statement \n$query\n\nfor c_$customerid : ", $destsharddb);

	// future job
	$query = "INSERT INTO aspshard.qjob (id, customerid, userid, scheduleid, timezone, startdate, enddate, starttime, endtime, status)" .
         " select id, ".$customerid.", userid, scheduleid, '".$timezone."', startdate, enddate, starttime, endtime, 'scheduled' from job where status='scheduled'";
	$rowcount = QuickUpdate($query,$destsharddb);
	if ($rowcount === false)
		dieerror ("Failed to execute statement \n$query\n\nfor c_$customerid : ", $destsharddb);

	if (false === QuickUpdate("COMMIT", $destsharddb))
		dieerror("failed to commit transaction\ncustomerid: $customerid\n");


	//----------------------------------------------------------------------

	echo "Transfer successful. setting authserver records and removing old database\n";

	$srcsharddb->query("use aspshard"); //ensure src shard connection is set to aspshard database

	//update authserver to point to new customer info
	//update shardid and password
	$query = "update customer set dbusername='" . DBSafe($newdbname,$authdb) . "', limitedusername='" . DBSafe($limitedusername, $authdb) . "', shardid=$destshard, dbpassword='" . DBSafe($newpass,$authdb) . "' where id=$customerid";
	if (QuickUpdate($query,$authdb) === false)
		dieerror("Problem updating customer table:" . errorinfo($authdb) . "\ncustomerid: $customerid\n");

	//remove this customer's shard data from old shard
	echo "deleting old shard records:";
	$tablearray = array("importqueue", "qjobperson", "qjobtask", "specialtaskqueue", "qreportsubscription", "qschedule", "qjob", "messagelink");
	foreach ($tablearray as $t) {
		echo ".";
		$query = "delete from ".$t." where customerid=$customerid";
		if (QuickUpdate($query, $srcsharddb) === false) {
			dieerror("Failed to execute statement \n$query\n\n : " . errorinfo($srcsharddb) . "\n");
		}
	}
	echo "\n";

	//drop old customer user
	$query = "drop user c_$customerid";
	if (QuickUpdate($query,$srcsharddb) === false)
		dieerror("Problem dropping old customer user:" . errorinfo($srcsharddb) . "\ncustomerid: $customerid\n");
	//drop old limited customer user
	$query = "drop user c_".$customerid."_limited";
	if (QuickUpdate($query,$srcsharddb) === false)
		dieerror("Problem dropping old limited customer user:" . errorinfo($srcsharddb) . "\ncustomerid: $customerid\n");
	//drop old customer db
	echo "Dropping old database\n";
	$query = "drop database c_$customerid";
	if (QuickUpdate($query,$srcsharddb) === false)
		dieerror("Problem dropping old customer db:" . errorinfo($srcsharddb) . "\ncustomerid: $customerid\n");

	// Made it! Customer moved successfully!
	$successful[] = $customerid;
} // done, for each customer id

successreport();
if (count($successful) == count($customerids))
	echo "\nAll customer ids moved successfully!\n";
else
	echo "\nSome customer ids were NOT moved!\n";

// report successful customer moves
function successreport () {
	GLOBAL $successful;
	if ($successful) {
		echo "Successfully moved customer ids: [" . implode(", ", $successful) . "]\n";
		echo "/-------------------------------------------------------------\\\n";
		echo "| Dont forget to restart authserver, redialer, dispatchers    |\n";
		echo "| as they cache connection info                               |\n";
		echo "\\-------------------------------------------------------------/\n\n";
	} else {
		echo "Moved no customers successfully\n";
	}
}

function verify_table ($db1,$db2,$table) {
	$fields = QuickQueryList("describe $table", false, $db1);
	$fieldlist = "`" . implode("`, `",$fields) . "`";
	$query = "select md5(group_concat(md5(concat_ws('#',$fieldlist)))) from $table";

	$hash1 = QuickQuery($query,$db1);
	$hash2 = QuickQuery($query,$db2);

	if ($hash1 === false || $hash2 === false)
		echo "Problem trying to hash tables:" . $query;

	return $hash1 == $hash2;
}

// report successful db moves before error occured, report error string, report dbcon error info if connection passed in
function dieerror($str, $dbcon = false) {
	echo "\n\n\n";
	successreport();
	echo "\n============== ERROR =================\n";
	echo $str . (($dbcon)?errorinfo($dbcon):"");
	echo "\n";
	die();
}

function errorinfo($dbcon) {
	$errInfo = $dbcon->errorInfo();
	$err = $errInfo[0];
	if (!isset($errInfo[2]))
		$detail = "unknown";
	else
		$detail = $errInfo[2];
	return $err . " : " . $detail;
}

?>
