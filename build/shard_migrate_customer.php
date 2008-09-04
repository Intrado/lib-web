<?
//////////////////////////////////////
// migrate a customer into this shard database (could be from a commsuite or moving between shards)
// Usage: php shard_migrate_customer.php <customer ID> <customerdata.sql> <shardhost> <shard user> <shard pass>
//////////////////////////////////////

include_once("relianceutils.php");

$customerid = "";
$customerdatafile = "";
$shardhost = "";
$dbuser = "";
$dbpass = "";


if (isset($argv[1])) {
	$customerid = $argv[1] + 0;
	if ($customerid == 0)
		die("invalid customer id");
} else {
	echo "please provide the customer ID";
	exit();
}

if (isset($argv[2])) {
	$customerdatafile = $argv[2];
	if (!file_exists($customerdatafile)) {
		echo "file does not exist : ".$customerdatafile;
		exit();
	}
} else {
	echo "please provide the customer sql data file";
	exit();
}

if (!$shardhost && isset($argv[3])) {
	$shardhost = $argv[3];
} else {
	echo "please provide the shard hostname or IP";
	exit();
}

if(!$dbuser && isset($argv[4])){
	$dbuser = $argv[4];
	if (!$dbpass && isset($argv[5])) 
		$dbpass = $argv[5];
} else {
	$confirm = "n";
	while($confirm != "y"){
		echo "\nEnter DB User:\n";
		$dbuser = trim(fread(STDIN, 1024));
		echo "\nEnter DB Pass:\n";
		$dbpass = trim(fread(STDIN, 1024));
		echo "DBUSER: " . $dbuser . "\n";
		echo "DBPASS: " . $dbpass . "\n";
		$confirm = generalMenu(array("Is this information correct?", "y or n"), array("y", "n"));
	}
}

echo "Connecting to mysql...\n";
$custdb = mysql_connect($shardhost, $dbuser, $dbpass)
	or die("Failed to connect to database");
echo "connection ok\n";

$customerdbname = "c_".$customerid;

mysql_select_db($customerdbname);

$confirm = "n";
while($confirm != "y"){
	$confirm = generalMenu(array("Are you sure you want to migrate customer database ".$customerdbname."?", "y or n"), array("y", "n"));
	if ($confirm == "n") exit();
}

/////////////////////////////////////
// check active jobs
echo "Checking for active jobs\n";
$res = mysql_query("select count(*) from job where status in ('processing', 'procactive', 'active')", $custdb);
$count = mysql_fetch_row($res);
if($count[0] > 0){
	echo "There are active jobs.  Exiting script.\n";
	exit();
}
echo "No active jobs found.\n";


//////////////////////////////////////
// drop triggers
echo("drop triggers\n");
$sqlqueries = explode("$$$",file_get_contents("../db/droptriggers.sql"));
foreach ($sqlqueries as $query) {
	if (trim($query)) {
		$query = str_replace('_$CUSTOMERID_', $customerid, $query);
		mysql_query($query,$custdb);
		// "if exists" not recognized, mysql extension
		// ignore failure caused by not exists
	}
}

//////////////////////////////////////
// truncate customer tables

echo("Backing up to $customerdbname_backup.sql");
exec("mysqldump -u $dbuser -p$dbpass --no-create-info $customerdbname > $customerdbname_backup.sql");

echo "Removing shard bits\n";

mysql_select_db("aspshard");
$tablearray = array("importqueue", "jobstatdata", "qjobperson", "qjobtask", "specialtaskqueue", "qreportsubscription", "qjobsetting", "qschedule", "qjob");
foreach ($tablearray as $t) {
	echo (".");
	$query = "delete from ".$t." where customerid=$customerid";
	if (!mysql_query($query,$custdb)) {
		echo("Failed to execute statement \n$query\n\n : " . mysql_error($custdb));
	}
}

echo "Truncating all customer tables.\n";
mysql_select_db($customerdbname);
$customertables = array(
	"access",
	"address",
	"audiofile",
	"blockednumber",
	"contactpref",
	"content",
	"custdm",
	"destlabel",
	"dmcalleridroute",
	"dmroute",
	"email",
	"fieldmap",
	"import",
	"importfield",
	"importjob",
	"job",
	"joblanguage",
	"jobsetting",
	"jobstatdata",
	"jobstats",
	"jobtype",
	"jobtypepref",
	"language",
	"list",
	"listentry",
	"message",
	"messageattachment",
	"messagepart",
	"permission",
	"person",
	"persondatavalues",
	"phone",
	"portalperson",
	"portalpersontoken",
	"reportcontact",
	"reportinstance",
	"reportperson",
	"reportsubscription",
	"rule",
	"schedule",
	"setting",
	"sms",
	"specialtask",
	"surveyquestion",
	"surveyquestionnaire",
	"surveyresponse",
	"surveyweb",
	"systemstats",
	"ttsvoice",
	"user",
	"userjobtypes",
	"userrule",
	"usersetting",
	"voicereply");
	
foreach ($customertables as $t) {
	echo (".");
	$query = "truncate table $t";
	if (!mysql_query($query,$custdb)) {
		echo("Failed to execute statement \n$query\n\n : " . mysql_error($custdb));
	}
	
}
echo "\n";

//////////////////////////////////////
// import data

echo("import customer data\n");

exec("mysql -u $dbuser -p$dbpass $customerdbname < $customerdatafile");

//////////////////////////////////////
// copy job/schedule/reportsubscription to shard

$query = "select value from setting where name='timezone'";
$res = mysql_query($query,$custdb);
if (!$res) die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));
$timezone = "'".mysql_result($res, 0)."'";

// reportsubscription
echo ("copy reportsubscriptions\n");
$query = "INSERT ignore INTO aspshard.qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) select id, ".$customerid.", userid, type, daysofweek, dayofmonth, time, ".$timezone.", nextrun, email from reportsubscription";
mysql_query($query,$custdb)
	or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));

// jobsetting
echo ("copy repeating jobs and settings\n");
$query = "INSERT ignore INTO aspshard.qjobsetting (customerid, jobid, name, value) SELECT ".$customerid.", jobid, name, value FROM jobsetting WHERE jobid in (select id from job where status='repeating')";
mysql_query($query,$custdb)
	or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));

// repeating job
$query = "INSERT ignore INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)" .
         " select id, ".$customerid.", userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, ".$timezone.", startdate, enddate, starttime, endtime, 'repeating', jobtypeid, thesql from job where status='repeating'";
mysql_query($query,$custdb)
	or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));

// schedule
$query = "INSERT ignore INTO aspshard.qschedule (id, customerid, daysofweek, time, nextrun, timezone) select id, ".$customerid.", daysofweek, time, nextrun, ".$timezone." from schedule";
mysql_query($query,$custdb)
	or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));

// future job
$query = "INSERT ignore INTO aspshard.qjob (id, customerid, userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, timezone, startdate, enddate, starttime, endtime, status, jobtypeid, thesql)" .
         " select id, ".$customerid.", userid, scheduleid, listid, phonemessageid, emailmessageid, printmessageid, smsmessageid, questionnaireid, ".$timezone.", startdate, enddate, starttime, endtime, 'scheduled', jobtypeid, thesql from job where status='scheduled'";
mysql_query($query,$custdb)
	or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));

//if another schoolmessenger user exists rename it
$query = "update user set login='schoolmessenger_old' where login='schoolmessenger";
mysql_query($query,$custdb);

//////////////////////////////////////
// create triggers
echo("create triggers\n");
$sqlqueries = explode("$$$",file_get_contents("../db/createtriggers.sql"));
foreach ($sqlqueries as $query) {
	if (trim($query)) {
		$query = str_replace('_$CUSTOMERID_', $customerid, $query);
		mysql_query($query,$custdb)
			or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));
	}
}


echo("!!!DONE!!!\n");
?>
