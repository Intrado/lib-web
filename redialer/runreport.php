<?
setlocale(LC_ALL, 'en_US.UTF-8');

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
@ $_DBPASS = $argv[7];


$params = array("filename" => $filename);

require_once("../inc/db.inc.php");

// NOTE the global dbcon is read/write it may update the reportsubscription record
// but the report generator will attempt open a readonly dbcon, which will fail due to no sessionid, this is ok because it then defaults back to the global read/write dbcon
// OK for autoreport to use read/write dbcon because the job just completed and reportdata may not all be synced to the db slave

global $_dbcon;
$_dbcon = DBConnect($_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME)
	or die("Could not connect to database: ".$_DBNAME);

require_once("../inc/auth.inc.php");
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
require_once("../obj/CallsReport.obj.php");
require_once("../obj/ContactChangeReport.obj.php");
require_once("../obj/Person.obj.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Email.obj.php");
require_once("../obj/Sms.obj.php");
require_once("../obj/User.obj.php");
require_once("../obj/Rule.obj.php");
require_once("../obj/FieldMap.obj.php");
require_once("../obj/Job.obj.php");
require_once("../inc/reportutils.inc.php");
require_once("../inc/reportgeneratorutils.inc.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../inc/date.inc.php");
require_once("../inc/formatters.inc.php");
require_once("XML/RPC.php");

$timezone = QuickQuery("select value from setting where name='timezone'");
if($timezone){
	@date_default_timezone_set($timezone);
	QuickUpdate("set time_zone='" . $timezone . "'");
}


if($type == "subscription"){
	$subscription = new ReportSubscription($id);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	switch($options['reporttype']){

		case 'contactchangereport':
			$generator = new ContactChangeReport();
			break;
		case 'contacthistory':
			$generator = new CallsReport();
			break;
		case 'surveyreport':
			$generator = new SurveyReport();
			break;
		case 'jobsummaryreport':
			$generator = new JobSummaryReport();
			break;
		case 'emaildetail':
		case 'phonedetail':
		case 'smsdetail':
		case 'notcontacted':
			$generator = new JobDetailReport();
			break;
	}
	$USER = new User($subscription->userid);
	$generator->userid = $subscription->userid;
} else if($type == "job"){
	$instance = new ReportInstance();
	$job = new Job($id+0);
	$options = array();
	$count = QuickQuery("select sum(numcontacts) from reportperson where jobid = ?", false, array($id));
	if($job->questionnaireid == null){
		if($count > 33000){
			$generator = new JobSummaryReport();
			$options['reporttype'] = 'jobsummaryreport';
			$options['sorrymessage'] =
				"Notice: The contact details for this job exceeds the page limit for an emailed autoreport.  See notification summary information above.  To view contact details, log into your web account and access the Phone Log or Email Log report.";
		} else {
			$generator = new JobAutoReport();
			$options['reporttype'] = 'jobautoreport';
		}
	} else {
		$generator = new SurveyReport();
		$options['reporttype'] = 'surveyreport';
	}


	$options['jobid'] = $id;
	$options['subname'] = $job->name;
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
$result = "";
if($options['reporttype'] == 'phonedetail' || $options['reporttype'] == 'emaildetail'){
	$result = $generator->testSize();

}
if($result == ""){
	$generator->generateQuery(true); // hackPDF to set g01-g10 as f21-f30
	echo "finished generating query\n";
	$result = $generator->generate($params);
}
echo $result;

// if success, and subscription, then update the lastrun field
if("success" == $result) {
	if ("subscription" == $type) {
		QuickUpdate("update reportsubscription set lastrun=now() where id=?", false, array($id));
	}
	exit(0);
}
if($result != "success" || $result != "failure"){
	exit(-2);
}
exit(-1);
?>
