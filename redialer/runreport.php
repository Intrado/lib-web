<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

$redialerdir = dirname(__FILE__);
$basedir = dirname($redialerdir);
$incdir = "$basedir/inc";
$objdir = "$basedir/obj";
require_once("{$incdir}/settingsloader.inc.php");

if ($argc < 7) {
	echo "Usage: { <reportsubscriptionid> | <jobid> } { subscription | job } <filename> <dbhost> <dbname> <dbuser> <dbpassword>";
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

require_once("{$incdir}/db.inc.php");

// NOTE the global dbcon is read/write it may update the reportsubscription record
// but the report generator will attempt open a readonly dbcon, which will fail due to no sessionid, this is ok because it then defaults back to the global read/write dbcon
// OK for autoreport to use read/write dbcon because the job just completed and reportdata may not all be synced to the db slave

global $_dbcon;
$_dbcon = DBConnect($_DBHOST, $_DBUSER, $_DBPASS, $_DBNAME);
// or die() returns with exit code 0 so the calling java program does not detect any error
if ($_dbcon == false) {
	echo("Could not connect to database: ".$_DBNAME);
	exit(-2);
}

require_once("{$incdir}/utils.inc.php");
require_once("{$incdir}/memcache.inc.php");
require_once("{$incdir}/auth.inc.php");
require_once("{$incdir}/DBMappedObject.php");
require_once("{$incdir}/DBMappedObjectHelpers.php");
require_once("{$incdir}/DBRelationMap.php");
require_once("{$objdir}/ReportInstance.obj.php");
require_once("{$objdir}/ReportSubscription.obj.php");
require_once("{$objdir}/ReportGenerator.obj.php");
require_once("{$objdir}/JobAutoReport.obj.php");
require_once("{$objdir}/SurveyReport.obj.php");
require_once("{$objdir}/JobSummaryReport.obj.php");
require_once("{$objdir}/JobDetailReport.obj.php");
require_once("{$objdir}/CallsReport.obj.php");
require_once("{$objdir}/ContactChangeReport.obj.php");
require_once("{$objdir}/PhoneOptOutReport.obj.php");
require_once("{$objdir}/SmsStatusReport.obj.php");
require_once("{$objdir}/Person.obj.php");
require_once("{$objdir}/Phone.obj.php");
require_once("{$objdir}/Email.obj.php");
require_once("{$objdir}/Sms.obj.php");
require_once("{$objdir}/User.obj.php");
require_once("{$objdir}/Rule.obj.php");
require_once("{$objdir}/FieldMap.obj.php");
require_once("{$objdir}/Language.obj.php");
require_once("{$objdir}/Job.obj.php");
require_once("{$incdir}/reportutils.inc.php");
require_once("{$incdir}/reportgeneratorutils.inc.php");
require_once("{$objdir}/Access.obj.php");
require_once("{$objdir}/Permission.obj.php");
require_once("{$incdir}/date.inc.php");
require_once("{$incdir}/formatters.inc.php");
require_once("XML/RPC.php");

$timezone = QuickQuery("select value from setting where name='timezone'");
if($timezone){
	@date_default_timezone_set($timezone);
	QuickUpdate("set time_zone='" . $timezone . "'");
}

if($type == "subscription"){
	$subscription = new ReportSubscription($id);
	$USER = new User($subscription->userid);

	// load customer/user locale 
	//this needs the USER object to already be loaded
	require_once("../inc/locale.inc.php");

	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	switch($options['reporttype']){
		case 'contactchangereport':
			$generator = new ContactChangeReport();
			break;
		case 'phoneoptoutreport':
			$generator = new PhoneOptOutReport();
			break;
		case 'smsstatus':
			$generator = new SmsStatusReport();
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
		case 'devicedetail':
		case 'notcontacted':
			$generator = new JobDetailReport();
			break;
	}
	$generator->userid = $subscription->userid;
} else if($type == "job"){
	$instance = new ReportInstance();
	$job = new Job($id+0);
	$USER = new User($job->userid);

	// load customer/user locale 
	//this needs the USER object to already be loaded
	require_once("../inc/locale.inc.php");
	
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
	$generator->userid = $job->userid;
}

$_SESSION['access'] = new Access($USER->accessid);

if(!isset($generator)){
	exit("Bad report type, corresponding generator not found\n");
}

$instanceparams = $instance->getParameters();
if (isset($instanceparams['format']))
	$generator->format = $instanceparams['format'];
else
	$generator->format = 'pdf'; // default

$generator->reportinstance = $instance;
$result = "";
if($options['reporttype'] == 'phonedetail' || $options['reporttype'] == 'emaildetail'){
	$result = $generator->testSize();

}
if($result == ""){
	$generator->generateQuery(true); // hackPDF to set g01-g10 as f21-f30
	$result = $generator->generate($params);
}

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
