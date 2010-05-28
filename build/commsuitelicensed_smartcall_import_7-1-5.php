<?
//////////////////////////////////////
// Import a customer into this database (From a CommSuite with Flex Appliance)
// Into Staging 7.1.5 customer database 'csimport', ready for 7.5 upgrade_databases script
//
// EDIT VARIABLES AT TOP OF SCRIPT, avoid entering on command line or prompts
//////////////////////////////////////

$customerdatafile = "donotdelete_c_XXX_ASP_7-1-2.sql"; // file exported from old commsuite
$shardhost = "127.0.0.1:3306"; // you want to run on the db machine for the 'mysql < importfile' to work
$dbuser = "root";
$dbpass = "";

require_once('../inc/db.inc.php');

///////////////////////
// main program
$csimportdbname = 'csimport';
$customer715file = 'customer_7-1-5.sql';

// connect to db
echo "Connecting to database...\n";
$custdb = mysql_connect($shardhost, $dbuser, $dbpass)
	or die("Failed to connect to database");
echo "connection ok\n";

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
$password_arg = "";
if ($dbpass != "") {
	$password_arg = "-p $dbpass";
}
$host_port = "-h " . $shardhost;
if (strpos($shardhost,":") !== false) {
	list($host,$port) = explode(":",$shardhost);
	$host_port = "-h $host -P $port";
}
exec("mysql $host_port -u $dbuser $password_arg $csimportdbname < $customerdatafile", $output, $return_var);

if ($return_var) {
	echo "import failed with return var ".$return_var."\n";
	die();
}

echo("!!!DONE!!!\n");
?>
