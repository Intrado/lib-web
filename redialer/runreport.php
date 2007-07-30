<?

if ($argc < 7) {
	echo "Usage: reportsubscriptionid | jobid, type 'subscription' | 'job', filename, dbhost, dbname, dbuser, dbpass";
	exit(-1);
}

$id = $argv[1];
$type = $argv[2];
$filename = $argv[3];
$_DBHOST = $argv[4];
$_DBNAME = $argv[5];
$_DBUSER = $argv[6];
$_DBPASS = $argv[7];


$params = array("filename" => $filename);

$_dbcon = mysql_connect($_DBHOST, $_DBUSER, $_DBPASS) or die("Could not connect to: ". $_DBHOST);
mysql_select_db($_DBNAME, $_dbcon) or die("Could not select db: " . $_DBNAME);

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("../obj/ReportInstance.obj.php");
require_once("../obj/ReportSubscription.obj.php");
require_once("../obj/ReportGenerator.obj.php");
require_once("../obj/JobAutoReport.obj.php");
require_once("../obj/SurveyReport.obj.php");
require_once("../obj/JobSummaryReport.obj.php");
require_once("../obj/JobDetailReport.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Job.obj.php");
require_once("../inc/reportutils.inc.php");
require_once("../inc/reportgeneratorutils.inc.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("XML/RPC.php");

if($type == "subscription"){
	$subscription = new ReportSubscription($id);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	switch($options['reporttype']){
		
		case 'attendance':
		case 'emergency':
		case 'undelivered':
		case 'callsreport':
			$generator = new CallsReport();
			break;
		case 'surveyreport':
			$generator = new SurveyReport();
			break;
		case 'jobsummaryreport':
			$generator = new JobSummaryReport();
			break;
		case 'jobdetailreport':
			$generator = new JobDetailReport();
			break;
	}
	$USER = new User($subscription->userid);
	$generator->userid = $subscription->userid;
} else if($type == "job"){
	$instance = new ReportInstance();
	$job = new Job($id);
	$options = array();
	if($job->questionnaireid == null){
		$generator = new JobAutoReport();
		$options['reporttype'] = 'jobautoreport';
	} else {
		$generator = new SurveyReport();
		$options['reporttype'] = 'surveyreport';
	}
	$options['jobid'] = $id;
	$instance->setParameters($options);
	$USER = new User($job->userid);
	$generator->userid = $job->userid;
}

$_SESSION['access'] = new Access($USER->accessid);

if(!isset($generator)){
	exit("Bad report type, corresponding generator not found\n");
}

$generator->format = "pdf";
$generator->reportinstance = $instance;
echo "finished configuring generator\n";
$generator->generateQuery();
echo "finished generating query\n";
$generator->setReportFile();
echo "finished setting report file to use\n";
$result = $generator->runPDF($params);
echo $result;

// if success, and subscription, then update the lastrun field
if("success" == $result) {
	if ("subscription" == $type) {
		QuickUpdate("update reportsubscription set lastrun=now() where id=" . DBSafe($id)); // TODO handle timezone
	}
	exit(0);
}
exit(-1);
?>