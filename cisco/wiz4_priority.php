<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/JobType.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (isset($_GET['message'])) {
	$messagegroupid = $_GET['message'] + 0;
	if (userOwns("messagegroup", $messagegroupid) || isSubscribed("messagegroup", $messagegroupid)) {
		$_SESSION['newjob']['message'] = $messagegroupid;
	} else {
		header("Location: $URL/index.php");
		exit();
	}

	if ($_GET['message'] == "callme") {
		if (!$USER->authorize('starteasy')) {
			header("Location: $URL/wiz3_message.php");
			exit();
		}
		$_SESSION['newjob']['easycall'] = true;
	} else {
		$_SESSION['newjob']['easycall'] = false;
	}
}

$VALIDJOBTYPES = JobType::getUserJobTypes();

header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title><?=$_SESSION['productname']?> - Priority</Title>
<Prompt>Please select a priority</Prompt>

<?
foreach ($VALIDJOBTYPES as $jobtype) {
?>
	<MenuItem>
	<Name><?= htmlentities($jobtype->name) ?></Name>
<?
		if($_SESSION['newjob']['easycall']){
			?><URL><?= $URL . "/wiz4b_languages.php?jobtypeid=" . $jobtype->id ?></URL><?
		} else {
			?><URL><?= $URL . "/wiz5_confirm.php?jobtypeid=" . $jobtype->id ?></URL><?
		}
?>
	</MenuItem>
<?
}
?>


<SoftKeyItem>
<Name>Select</Name>
<URL>SoftKey:Select</URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= htmlentities($URL . "/wiz3_message.php") ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>




</CiscoIPPhoneMenu>