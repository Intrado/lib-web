<?
require_once("common.inc.php");
require_once("../obj/PeopleList.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (isset($_GET['jobname'])) {
	$_SESSION['newjob']['name'] = $_GET['jobname'];
}
if (isset($_GET['desc'])) {
	$_SESSION['newjob']['desc'] = $_GET['desc'];
}
if (isset($_GET['numdays'])) {
	$_SESSION['newjob']['numdays'] = $_GET['numdays'];
}
if (isset($_GET['retries'])) {
	$_SESSION['newjob']['retries'] = $_GET['retries'];
}



$lists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name");

header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title>SchoolMessenger - Lists</Title>
<Prompt>Please select your list</Prompt>

<?
foreach ($lists as $list) {
?>
	<MenuItem>
	<Name><?= htmlentities($list->name) ?></Name>
	<URL><?= $URL . "/wiz3_message.php?list=" . $list->id ?></URL>
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
<URL><?= htmlentities($URL . "/wiz1_job.php?edit=true") ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>


<SoftKeyItem>
<Name>Get Info</Name>
<URL>QueryStringParam:info=true</URL>
<Position>4</Position>
</SoftKeyItem>




</CiscoIPPhoneMenu>
