<?
//////////////////////////////////////
// Migrate a customer into this shard database (From a CommSuite with Flex Appliance)
//
// EDIT VARIABLES AT TOP OF SCRIPT, avoid entering on command line or prompts
//////////////////////////////////////

$customerid = ""; // asp customer id exists before we migrate the old commsuite data in
$customerdatafile = ""; // file exported from old commsuite
$shardhost = ""; // you want to run on the db machine for the 'mysqldump' to work
$dbuser = "";
$dbpass = "";


/////////////
// include
include_once("../inc/db.inc.php");
include_once("../manager/managerutils.inc.php");

/////////////
// functions
function echoarray($somearray){
	foreach($somearray as $line){
		echo $line . "\n";
	}
}

function generalmenu($questions = array(), $validresponses = array()){
	echoarray($questions);
	$response = fread(STDIN, 1024);
	$response = trim($response);
	while(!in_array($response, $validresponses)){
		echo "\nThat was not an option\n";
		$response = fread(STDIN, 1024);
		$response = trim($response);
	}
	return $response;
}

///////////////////////
// main program

echo "Connecting to aspshard...\n";
$custdb = mysql_connect($shardhost, $dbuser, $dbpass)
	or die("Failed to connect to database");
echo "connection ok\n";


$customerid = mysql_real_escape_string($customerid, $custdb);
$customerdbname = "c_".$customerid;

mysql_select_db($customerdbname);

$pdo = DBConnect($shardhost, $dbuser, $dbpass, $customerdbname)
	or die("Failed to connect to database using PDO");

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
echo "No active jobs found\n";


//////////////////////////////////////
// backup data
$backupfilename = $customerdbname . "_backup.sql";
echo("Backing up to $backupfilename \n");
exec("mysqldump -u$dbuser -p$dbpass $customerdbname > $backupfilename", $output, $return_var);
if ($return_var) {
	echo "mysqldump failed with return var ".$return_var."\n";
	die();
}


//////////////////////////////////////
// drop triggers
echo("Drop triggers\n");
$sqlqueries = explode("$$$",file_get_contents("../db/droptriggers.sql"));
foreach ($sqlqueries as $query) {
	if (trim($query)) {
		$query = str_replace('_$CUSTOMERID_', $customerid, $query);
		mysql_query($query,$custdb);
		// "if exists" not recognized, mysql extension
		// ignore failure caused by not exists
	}
}


///////////////////////////////////////
// remove any data from shard (customer may have been active for a bit in testing)
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
echo ("\n");

//////////////////////////////////////
// save some customer settings that we do not want to overwrite, will rewrite them after import
// if from commsuite, we want to save these settings because commsuite 5.2 did not have them
echo ("Saving a few settings to restore after import\n");

mysql_select_db($customerdbname);

$settings = array();
$settings['surveyurl'] = "";
$settings['autoreport_replyname'] = "";
$settings['autoreport_replyemail'] = "";
$settings['inboundnumber'] = "";
$settings['_supportemail'] = "";
$settings['_supportphone'] = "";
$settings['emaildomain'] = "";

foreach ($settings as $name => $value) {
	//$settings[$name] = QuickQuery("select value from setting where name='$name'", $custdb);
	$res = mysql_query("select value from setting where name='$name'", $custdb);
	$row = mysql_fetch_row($res);
	$settings[$name] = $row[0];
	echo "$name is $settings[$name] \n";
}


//////////////////////////////////////
// now truncate the tables
echo "Truncating all customer tables\n";
mysql_select_db($customerdbname);
$customertables = array(
	"access",
	"address",
	"audiofile",
	"blockednumber",
	"contactpref",
	"content",
	// custdm
	// customercallstats
	"destlabel",
	// dmcalleridroute
	// dmroute
	// dmschedule
	"email",
	"enrollment",
	"fieldmap",
	"groupdata",
	"import",
	"importfield",
	"importjob",
	"importlogentry",
	"job",
	"joblanguage",
	"jobsetting",
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
	"reportgroupdata",
	"reportinstance",
	"reportperson",
	"reportsubscription",
	"rule",
	"schedule",
	"setting",
	"sms",
	"specialtask",
	"subscriber",
	"subscriberpending",
	"surveyquestion",
	"surveyquestionnaire",
	"surveyresponse",
	"surveyweb",
	"systemmessages",
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
echo("Import customer data\n");
exec("mysql -u $dbuser -p$dbpass $customerdbname < $customerdatafile", $output, $return_var);
if ($return_var) {
	echo "import failed with return var ".$return_var."\n";
	die();
}

/////////////////////////////////////
// build the default schoolmessenger profile and user
// reset saved customer settings
//if another schoolmessenger user exists rename it
$query = "update user set login='schoolmessenger_old' where login='schoolmessenger";
mysql_query($query,$custdb);

// create default customer data (was lost in truncation)
createSMUserProfile($pdo);

// reset saved customer settings
foreach ($settings as $name => $value) {
	$query = "delete from setting where name='$name'";
	mysql_query($query)
		or die("Failure to execute query $query ". mysql_error());
	$name = DBSafe($name, $custdb);
	$value = DBSafe($value, $custdb);
	$query = "insert into setting (name, value) values ('$name', '$value')";
	mysql_query($query)
		or die("Failure to execute query $query ". mysql_error());
}


//////////////////////////////////////
// copy job/schedule/reportsubscription to shard

$query = "select value from setting where name='timezone'";
$res = mysql_query($query,$custdb);
if (!$res) die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));
$timezone = "'".mysql_result($res, 0)."'";

// reportsubscription
echo ("Copy reportsubscriptions\n");
$query = "INSERT ignore INTO aspshard.qreportsubscription (id, customerid, userid, type, daysofweek, dayofmonth, time, timezone, nextrun, email) select id, ".$customerid.", userid, type, daysofweek, dayofmonth, time, ".$timezone.", nextrun, email from reportsubscription";
mysql_query($query,$custdb)
	or die ("Failed to execute statement \n$query\n\nfor $customerdbname : " . mysql_error($custdb));

// jobsetting
echo ("Copy repeating jobs and settings\n");
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


//////////////////////////////////////
// create triggers
echo("Create triggers\n");
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
