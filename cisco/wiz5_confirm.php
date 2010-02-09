<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/JobType.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/ListEntry.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");
include_once("../obj/Language.obj.php");
require_once("../obj/Organization.obj.php");
require_once("../obj/Section.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if(isset($_GET['jobtypeid'])) {
	$_SESSION['newjob']['jobtypeid'] = $_GET['jobtypeid'];
}

if ($_SESSION['newjob']['message'] == "callme") {
	$messagename = "-Not yet recorded-";
} else {
	$message = new Message($_SESSION['newjob']['message']);
	$messagename = $message->name;
}
$list = new PeopleList($_SESSION['newjob']['list']);
$jobtype = new JobType($_SESSION['newjob']['jobtypeid']);


$renderedlist = new RenderedList($list);
$renderedlist->calcStats();

$languages = DBFindMany("Language","from language order by name");

header("Content-type: text/xml");
?>
<CiscoIPPhoneText>
<Title><?=$_SESSION['productname']?> - Confirm</Title>
<Prompt>Please review your job</Prompt>
<Text><?
echo "Job:" . (($_SESSION['newjob']['name']) ? $_SESSION['newjob']['name'] : "(Automatic)") . "\r\n";
echo "Message:" . $messagename . "\r\n";
echo "List:" . $list->name . "\r\n";
echo "Total people in list:" . $renderedlist->total . "\r\n";
echo "Priority:" . $jobtype->name . "\r\n";
if($_SESSION['newjob']['easycall']) {
	echo "Languages:Default - English\r\n";
	foreach($languages as $language){
		if(isset($_SESSION['newjob']['language'][$language->name]) && $_SESSION['newjob']['language'][$language->name] == 1)
			echo "\t\t\t\t\t\t" . $language->name . "\r\n";
	}
}


?>
</Text>

<? if ($_SESSION['newjob']['easycall']) { ?>

	<SoftKeyItem>
	<Name>CallMe</Name>
	<URL><?= $URL . "/wiz6b_easycall.php" ?></URL>
	<Position>1</Position>
	</SoftKeyItem>

<? } else { ?>
	<SoftKeyItem>
	<Name>Send</Name>
	<URL><?= $URL . "/wiz6_jobprocess.php" ?></URL>
	<Position>1</Position>
	</SoftKeyItem>
<? } ?>

<SoftKeyItem>
<Name>Back</Name>
<? if ($_SESSION['newjob']['easycall']) { ?>
	<URL><?= $URL . "/wiz4b_languages.php" ?></URL>
<? } else { ?>
	<URL><?= $URL . "/wiz4_priority.php" ?></URL>
<? } ?>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= $URL . "/main.php" ?></URL>
<Position>3</Position>
</SoftKeyItem>



</CiscoIPPhoneText>