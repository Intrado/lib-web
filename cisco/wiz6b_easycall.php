<?
require_once("common.inc.php");
include_once("../inc/securityhelper.inc.php");
include_once("../obj/SpecialTask.obj.php");
require_once("../obj/Phone.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (isset($_GET['dn'])) {

	if (! (userOwns("list",DBSafe($_SESSION['newjob']['list'])) &&
			userOwns("message",DBSafe($_SESSION['newjob']['message'])) &&
			customerOwns("jobtype",DBSafe($_SESSION['newjob']['jobtypeid'])))) {
		exit();
	}

	$_SESSION['dn'] = Phone::parse($_GET['dn']);

	$defaultname = "IP Phone - " . date("F jS, Y g:i a");
	$_SESSION['newjob']['name'] = ($_SESSION['newjob']['name'] ? $_SESSION['newjob']['name'] : $defaultname);
	$_SESSION['newjob']['desc'] = ($_SESSION['newjob']['desc'] ? $_SESSION['newjob']['desc'] : $defaultname);
	$_SESSION['newjob']['retries'] = $job->maxcallattempts = max($_SESSION['newjob']['retries'],$ACCESS->getValue('callmax'));

	//put in the special task

	$task = new SpecialTask();
	$task->type = 'EasyCall';

	$task->setData('phonenumber', $_SESSION['dn']);
	$task->setData('name', $_SESSION['newjob']['name']);
	$task->setData('jobdesc', $_SESSION['newjob']['desc']);

	$task->setData('origin', "cisco");
	$task->setData('userid', $USER->id);
	$task->setData('listid', $_SESSION['newjob']['list']);
	$task->setData('jobtypeid', $_SESSION['newjob']['jobtypeid']);

	$task->setData('jobdays',$_SESSION['newjob']['numdays']);
	$task->setData('jobretries',$_SESSION['newjob']['retries']);

	$task->lastcheckin = date("Y-m-d H:i:s");
	$task->create();

header("Content-type: text/xml");
?>
<CiscoIPPhoneText>
<Title>SchoolMessenger - CallMe</Title>
<Prompt> </Prompt>

<Text>
You should recieve a call shortly.
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

<?

} else {
header("Content-type: text/xml");
?>
<CiscoIPPhoneInput>
<Title>SchoolMessenger - CallMe</Title>
<Prompt>Please enter your Phone #</Prompt>
<URL><?= htmlentities($URL . "/wiz6b_easycall.php") ?></URL>

<InputItem>
<DisplayName>Call Me at</DisplayName>
<QueryStringParam>dn</QueryStringParam>
<DefaultValue><?= (isset($_SESSION['dn']) ? $_SESSION['dn'] : "") ?></DefaultValue>
<InputFlags>N</InputFlags>
</InputItem>



<SoftKeyItem>
<Name>Submit</Name>
<URL>SoftKey:Submit</URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>&lt;&lt;</Name>
<URL>SoftKey:&lt;&lt;</URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= htmlentities($URL . "/wiz5_confirm.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>



</CiscoIPPhoneInput>

<? } ?>