<?

if ($argc < 7) {
	echo "Usage: reportsubscriptionid | jobid, type 'subscription' | 'job', filename, dbhost, dbname, dbuser, dbpass";
	exit(-1);
}

$id = $argv[1];
$type = $argv[2];
$filename = $argv[3];
$host = $argv[4];
$db = $argv[5];
$user = $argv[6];
$pass = $argv[7];

$params = array("host" => "jdbc:mysql://" . $host . "/" . $db,
				"user" => $user,
				"pass" => $pass,
				"filename" => $filename);

$_dbcon = mysql_connect($host, $user, $pass);
mysql_select_db($db, $_dbcon);

require_once("../inc/db.inc.php");
require_once("../inc/DBMappedObject.php");
require_once("../inc/DBRelationMap.php");
require_once("../inc/utils.inc.php");
require_once("../obj/ReportInstance.obj.php");
require_once("../obj/ReportSubscription.obj.php");
require_once("../obj/ReportGenerator.obj.php");
require_once("../obj/CallsReport.obj.php");
require_once("../obj/JobReport.obj.php");
require_once("../obj/SurveyReport.obj.php");
require_once("../obj/ContactsReport.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Job.obj.php");
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
		case 'jobreport':
			$generator = new JobReport();
			break;
		case 'contactsreport':
			$generator = new ContactsReport();
			break;
	}
	$generator->userid = $subscription->userid;
} else if($type == "job"){
	$instance = new ReportInstance();
	$job = new Job($id);
	$options = array();
	if($job->questionnaireid == null){
		$generator = new JobReport();
		$options['reporttype'] = 'jobreport';
	} else {
		$generator = new SurveyReport();
		$options['reporttype'] = 'surveyreport';
	}
	$options['jobid'] = $id;
	$instance->setParameters($options);
	$generator->userid = $job->userid;
}



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
if("success" == $result && $type == "subscription") {
	QuickUpdate("update reportsubscription set lastrun=now() where id=" . DBSafe($id)); // TODO handle timezone
} else {
	exit(-1);
}

?>