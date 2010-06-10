<?
require_once("common.inc.php");
include_once("../obj/SpecialTask.obj.php");
require_once("../obj/Phone.obj.php");
include_once("../obj/Language.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/Section.obj.php");


if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (isset($_GET['dn'])) {
	$_SESSION['dn'] = Phone::parse($_GET['dn']);


	// validate all required arguments exist for this job to submit
	if (!isset($_SESSION['newjob']['jobtypeid']) ||
		!isset($_SESSION['newjob']['retries']) ||
		!isset($_SESSION['newjob']['numdays']) ||
		!isset($_SESSION['newjob']['message']) ||
		!isset($_SESSION['newjob']['list']))
	{
		sleep(1);
		header("Location: $URL/main.php");
		exit();
	}

	$defaultname = "IP Phone - " . date("F jS, Y g:i a");
	$_SESSION['newjob']['name'] = ($_SESSION['newjob']['name'] ? $_SESSION['newjob']['name'] : $defaultname);
	$_SESSION['newjob']['desc'] = ($_SESSION['newjob']['desc'] ? $_SESSION['newjob']['desc'] : $defaultname);

	//put in the special task
	$task = new SpecialTask();
	$task->userid = $USER->id;
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

	//new task info
	$task->setData('progress', "Creating Call");
	$task->setData('count', '0');
	$task->setData('totalamount', $_SESSION['newjob']['language']['totallangcount']);
	$task->setData('language0', 'Default');
	$task->setData('currlang', 'Default');
	$languages = DBFindMany("Language","from language order by name");
	$i=1;
	foreach($languages as $language){
		if(isset($_SESSION['newjob']['language'][$language->name]) && $_SESSION['newjob']['language'][$language->name] == 1) {
			$task->setData('language' . $i, $language->name);
			$i++;
		}
	}
	$task->status = "queued";

	$task->lastcheckin = date("Y-m-d H:i:s");
	$task->create();
	QuickUpdate("call start_specialtask(" . $task->id . ")");

header("Content-type: text/xml");
?>
<CiscoIPPhoneText>
<Title><?=$_SESSION['productname']?> - CallMe</Title>
<Prompt> </Prompt>

<Text>
You should receive a call shortly.
</Text>

<SoftKeyItem>
<Name>Status</Name>
<URL><?= $URL . "/status.php"?></URL>
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
<Title><?=$_SESSION['productname']?> - CallMe</Title>
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