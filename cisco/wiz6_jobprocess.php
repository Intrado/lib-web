<?
require_once("common.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../obj/Job.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (count($_SESSION['newjob']) < 3) {
	sleep(1);
	header("Location: $URL/main.php");
	exit();
}

set_time_limit(0);

function getSetting($name) {
	global $USER;
	$name = DBSafe($name);
	return QuickQuery("select value from setting where customerid = $USER->customerid and name = '$name'");
}


if (! (userOwns("list",DBSafe($_SESSION['newjob']['list'])) &&
		userOwns("message",DBSafe($_SESSION['newjob']['message'])) &&
		customerOwns("jobtype",DBSafe($_SESSION['newjob']['jobtypeid'])))) {
	exit();
}

$job = new Job();

$job->listid = $_SESSION['newjob']['list'];
$job->phonemessageid = $_SESSION['newjob']['message'];

$defaultname = "IP Phone - " . date("F jS, Y g:i a");
$job->name = ($_SESSION['newjob']['name'] ? $_SESSION['newjob']['name'] : $defaultname);
$job->description = ($_SESSION['newjob']['desc'] ? $_SESSION['newjob']['desc'] : $defaultname);


$job->jobtypeid = $_SESSION['newjob']['jobtypeid'];

$job->startdate = date("Y-m-d", strtotime("today"));
$job->enddate = date("Y-m-d", strtotime("tomorrow"));

$job->starttime = $ACCESS->getValue('callearly');
$job->starttime = date("H:i", strtotime($job->starttime ? $job->starttime : "8:00 am"));

$job->endtime = $ACCESS->getValue('calllate');
$job->endtime = date("H:i", strtotime($job->endtime ? $job->endtime : "9:00 pm"));

$job->maxcallattempts = min(3,$ACCESS->getValue('callmax'));

$job->type="phone";
$job->options = "callfirst,skipduplicates,sendreport";

if (getSetting('callerid') != "")
		$job->options .= ",callerid=" . getSetting('callerid');
$job->status = "new";
$job->userid = $USER->id;
$job->createdate = QuickQuery("select now()");

$job->create();

echo mysql_error();

chdir("../");
//kick of the job
if (isset($_SERVER['WINDIR'])) {
	$cmd = "php jobprocess.php $job->id";
	pclose(popen($cmd,"r"));
} else {
	$cmd = "php jobprocess.php $job->id > /dev/null &";
	exec($cmd);
}


unset($_SESSION['newjob']);

header("Content-type: text/xml");

?>
<CiscoIPPhoneText>
<Title>SchoolMessenger - Sent</Title>
<Prompt> </Prompt>
<Text>
Your Job has been submitted.
</Text>

<SoftKeyItem>
<Name>Status</Name>
<URL><?= $URL . "/status.php" ?></URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Done</Name>
<URL><?= $URL . "/main.php" ?></URL>
<Position>4</Position>
</SoftKeyItem>

</CiscoIPPhoneText>