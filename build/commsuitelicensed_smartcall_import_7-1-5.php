<?
//////////////////////////////////////
// Import a customer into this database (From a CommSuite with Flex Appliance)
// Into Staging 7.1.5 customer database, ready for 7.5 upgrade_databases script
//
// EDIT VARIABLES AT TOP OF SCRIPT, avoid entering on command line or prompts
//////////////////////////////////////

$customerdatafile = "donotdelete_c_XXX_ASP_7-1-2.sql"; // file exported from old commsuite
$shardhost = "localhost"; // you want to run on the db machine for the 'mysqldump' to work
$dbuser = "";
$dbpass = "";

require_once('../inc/db.inc.php');

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
$csimportdbname = 'csimport';
$customer715file = 'customer_7-1-5.sql';

// connect to db
echo "Connecting to database...\n";
$custdb = mysql_connect($shardhost, $dbuser, $dbpass)
	or die("Failed to connect to database");
echo "connection ok\n";

// confirm before continue
$confirm = "n";
while($confirm != "y"){
	$confirm = generalMenu(array("Are you sure you want to import into database ".$csimportdbname."?", "y or n"), array("y", "n"));
	if ($confirm == "n") exit();
}

// drop database, recreate with new tables
echo "Drop and Create ".$csimportdbname." database\n";
mysql_query("drop database ".$csimportdbname, $custdb);
mysql_query("create database ".$csimportdbname, $custdb);

// select csimport db
mysql_select_db($csimportdbname);

// create tables in csimport
$sqlqueries = explode("$$$",file_get_contents($customer715file));
foreach ($sqlqueries as $sqlquery) {
	if (trim($sqlquery)){
		mysql_query($sqlquery,$custdb)
			or die ("Failed to execute sql:\n$sqlquery\n" . mysql_error($custdb));
	}
}

// connect again for DBMO use
$pdo = DBConnect($shardhost, $dbuser, $dbpass, $csimportdbname)
	or die("Failed to connect to database using PDO");

echo $csimportdbname." database ready for import\n";

//////////////////////////////////////
// drop triggers
echo("Drop triggers\n");
mysql_query("START TRANSACTION",$custdb);
$triggerarray = array("insert_repeating_job", "update_job", "delete_job", "insert_jobsetting", "update_jobsetting", "delete_jobsetting", 
						"insert_schedule", "update_schedule", "delete_schedule", "insert_reportsubscription", "update_reportsubscription",
						"delete_reportsubscription", "insert_joblist", "update_joblist", "delete_joblist");
foreach ($triggerarray as $triggername) {
	$query = "drop trigger if exists " . $triggername;
	mysql_query($query,$custdb);
}
$procedurearray = array("start_import", "start_specialtask");
foreach ($procedurearray as $procedurename) {
	$query = "drop procedure if exists " . $procedurename;
	mysql_query($query,$custdb);
}
mysql_query("COMMIT",$custdb);


//////////////////////////////////////
// now truncate the tables
echo "Truncating all customer tables\n";
mysql_select_db($csimportdbname);
$customertables = array(
	"access",
	"address",
	"audiofile",
	"blockeddestination",
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
	"reportarchive",
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
	//"ttsvoice",
	"user",
	"userjobtypes",
	"userrule",
	"usersetting",
	"voicereply");
	
$rollback = false;
mysql_query("START TRANSACTION",$custdb);
foreach ($customertables as $t) {
	echo (".");
	$query = "truncate table $t";
	if (!mysql_query($query,$custdb)) {
		echo("Failed to execute statement \n$query\n\n : " . mysql_error($custdb));
		$rollback = true;
		break;
	}
}
echo "\n";
if ($rollback) {
	mysql_query("ROLLBACK", $custdb);
	die("rollback and quit\n");
}
mysql_query("COMMIT",$custdb);


//////////////////////////////////////
// import data
echo("Import customer data\n");
exec("mysql -u $dbuser -p$dbpass $csimportdbname < $customerdatafile", $output, $return_var);
if ($return_var) {
	echo "import failed with return var ".$return_var."\n";
	die();
}

echo("!!!DONE!!!\n");
?>
