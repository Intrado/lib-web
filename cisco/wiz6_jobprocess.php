<?
require_once("common.inc.php");
require_once("../obj/Phone.obj.php");
require_once("../obj/Job.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Access.obj.php");
require_once("../obj/Permission.obj.php");
require_once("../inc/utils.inc.php");
require_once("../inc/date.inc.php");


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

$job = Job::jobWithDefaults();

$defaultname = "IP Phone - " . date("F jS, Y g:i a");
$job->name = ($_SESSION['newjob']['name'] ? $_SESSION['newjob']['name'] : $defaultname);
$job->description = ($_SESSION['newjob']['desc'] ? $_SESSION['newjob']['desc'] : $defaultname);

$job->jobtypeid = $_SESSION['newjob']['jobtypeid'];
$job->setOptionValue("maxcallattempts",$_SESSION['newjob']['retries']);

$job->enddate = date("Y-m-d", strtotime($job->startdate) + (($_SESSION['newjob']['numdays'] - 1) * 86400));

$job->type="notification";
$job->messagegroupid = $_SESSION['newjob']['message'];

$job->create();

QuickUpdate("insert into joblist (jobid, listid) values (?,?)", false, array($job->id, $_SESSION['newjob']['list']));

chdir("../");

//kick off the job
$job->runNow();

unset($_SESSION['newjob']);

header("Content-type: text/xml");

?>
<CiscoIPPhoneText>
<Title><?=$_SESSION['productname']?> - Sent</Title>
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