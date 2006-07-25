<?
require_once("common.inc.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/JobType.obj.php");
include_once("../obj/Rule.obj.php");
include_once("../obj/ListEntry.obj.php");
include_once("../obj/RenderedList.obj.php");
include_once("../obj/FieldMap.obj.php");

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

/*
$usersql = "p.customerid=" . $USER->customerid;

//get and compose list rules
$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
		and le.ruleid=r.id and le.listid='" . $list->id .  "' order by le.sequence", "r");
if (count($listrules) > 0)
	$listsql = "1" . Rule::makeQuery($listrules, "pd");
else
	$listsql = "0";//dont assume anyone is in the list if there are no rules

//get all the rules based people that dont have remove records
//then union with all the manual add people
$query = "
select count(*)
from person p left join persondata pd on (p.id=pd.personid)
left join listentry le on (p.id=le.personid and le.listid='" . $list->id .  "')
where $usersql and $listsql and le.type is null
";

$total = QuickQuery ($query);

$query = "
select count(*)
from person p left join persondata pd on (p.id=pd.personid)
, listentry le
where le.listid='" . $list->id .  "' and $usersql and p.id=le.personid and le.type='A'
";


$total += QuickQuery ($query);
*/

header("Content-type: text/xml");
?>
<CiscoIPPhoneText>
<Title>SchoolMessenger - Confirm</Title>
<Prompt>Please review your job</Prompt>
<Text><?
echo "Job:" . (($_SESSION['newjob']['name']) ? $_SESSION['newjob']['name'] : "(Automatic)") . "\r\n";
echo "Message:" . $messagename . "\r\n";
echo "List:" . $list->name . "\r\n";
echo "Total people in list:" . $renderedlist->total . "\r\n";
echo "Priority:" . $jobtype->name . "\r\n";

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
<URL><?= $URL . "/wiz4_priority.php" ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= $URL . "/main.php" ?></URL>
<Position>3</Position>
</SoftKeyItem>



</CiscoIPPhoneText>