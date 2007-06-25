<?

if ($argc < 6)
	exit("Usage: reportsubscription id, filename, dbhost, db, user, dbpass,");

$id = $argv[1];
$filename = $argv[2];
$host = $argv[3];
$db = $argv[4];
$user = $argv[5];
$pass = $argv[6];

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
require_once("XML/RPC.php");


$reportfile = "";
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

if(!isset($generator)){
	exit("Bad report type, corresponding generator not found\n");
}

$generator->format = "pdf";
$generator->reportinstance = $instance;
$generator->userid = $subscription->userid;
echo "finished configuring generator\n";
$generator->generateQuery();
echo "finished generating query\n";
$generator->setReportFile();
echo "finished setting report file to use\n";
$result = $generator->runPDF($params);

echo $result;

?>