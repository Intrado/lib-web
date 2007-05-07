<?
require_once("common.inc.php");
require_once("../inc/securityhelper.inc.php");
require_once("../obj/Job.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../inc/utils.inc.php");

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
	return QuickQuery("select value from setting where name = '$name'");
}


if (! (userOwns("list",DBSafe($_SESSION['newjob']['list'])) &&
		userOwns("message",DBSafe($_SESSION['newjob']['message'])) &&
		customerOwns("jobtype",DBSafe($_SESSION['newjob']['jobtypeid'])))) {
	exit();
}

$job = Job::jobWithDefaults();

$defaultname = "IP Phone - " . date("F jS, Y g:i a");
$job->name = ($_SESSION['newjob']['name'] ? $_SESSION['newjob']['name'] : $defaultname);
$job->description = ($_SESSION['newjob']['desc'] ? $_SESSION['newjob']['desc'] : $defaultname);

$job->jobtypeid = $_SESSION['newjob']['jobtypeid'];
$job->listid = $_SESSION['newjob']['list'];
$job->setOptionValue("retry",$_SESSION['newjob']['retries']);

$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($_SESSION['newjob']['numdays'] - 1) * 86400));

$job->type="phone";
$job->phonemessageid = $_SESSION['newjob']['message'];

$job->create();

echo mysql_error();

chdir("../");

//kick off the job
$job->runNow();

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