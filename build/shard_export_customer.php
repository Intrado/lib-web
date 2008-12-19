<?
//////////////////////////////////////
// STEP 1 in moving customer from one shard to another
//
// Use to export customer data from existing shard customer, intended to move to a different shard
//
// EDIT VARIABLES AT TOP OF SCRIPT, avoid entering on command line or prompts
//////////////////////////////////////

$customerid = "";
$shardhost = "";
$dbuser = "";
$dbpass = "";


//////////////////////////////////////
// Functions

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

/////////////////////////////////////
// Main code

echo "Connecting to mysql...\n";
$custdb = mysql_connect($shardhost, $dbuser, $dbpass)
	or die("Failed to connect to database");
echo "connection ok\n";

$customerid = mysql_real_escape_string($customerid, $custdb);
$customerdbname = "c_".$customerid;

mysql_select_db($customerdbname);

$confirm = "n";
while($confirm != "y"){
	$confirm = generalMenu(array("Are you sure you want to export customer database ".$customerdbname."?", "y or n"), array("y", "n"));
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


// dump data
echo "Extract the data\n";
$filename = "shardexportfrom_" . $customerdbname . ".sql.gz";
$cmd = "mysqldump -u$dbuser -p$dbpass --no-create-info --skip-triggers $customerdbname access address audiofile blockednumber contactpref content custdm destlabel dmcalleridroute dmroute email enrollment fieldmap groupdata import importfield importjob job joblanguage jobsetting jobstats jobtype jobtypepref language list listentry message messageattachment messagepart permission person persondatavalues phone portalperson portalpersontoken reportcontact reportgroupdata reportinstance reportperson reportsubscription rule schedule setting sms specialtask surveyquestion surveyquestionnaire surveyresponse surveyweb systemstats ttsvoice user userjobtypes userrule usersetting voicereply | gzip -c > $filename";
$result = exec($cmd);

echo "DONE creating $filename \n";

?>
