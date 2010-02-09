<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
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

if(isset($_GET['listcount'])) {
	$min = $_GET['listcount'] + 1;
	$max = $_GET['listcount'] + 30;
} else {
	$min = 0;
	$max = 30;
}

if($min - 31 <= 0){
	$back = -1;
} else {
	$back = $min - 31;
}


$lists = DBFindMany("PeopleList",", (name +0) as foo from list where userid=$USER->id and deleted=0 order by foo,name
	limit 30 offset $min");


header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title><?=$_SESSION['productname']?> - Lists</Title>
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

<SoftKeyItem>
<Name>Mor Lst</Name>
<URL><?= htmlentities($URL . "/wiz2_list.php?listcount=". $max)  ?></URL>
<Position>5</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Bck Lst</Name>
<URL><?= htmlentities($URL . "/wiz2_list.php?listcount=". $back)  ?></URL>
<Position>6</Position>
</SoftKeyItem>


</CiscoIPPhoneMenu>
