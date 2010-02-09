<?
require_once("common.inc.php");
require_once("../obj/Person.obj.php");
require_once("../obj/PeopleList.obj.php");
require_once("../obj/Message.obj.php");
require_once("../obj/JobType.obj.php");
require_once("../obj/Language.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if(isset($_GET['jobtypeid'])) {
	$_SESSION['newjob']['jobtypeid'] = $_GET['jobtypeid'];
}

if(!isset($_SESSION['newjob']['language']['Default'])){
	$_SESSION['newjob']['language']['Default'] = 1;
	$_SESSION['newjob']['language']['totallangcount'] = 1;
}

if(isset($_GET['language'])){
	if(isset($_SESSION['newjob']['language'][$_GET['language']]) && $_SESSION['newjob']['language'][$_GET['language']] == 1) {
		$_SESSION['newjob']['language'][$_GET['language']] = 0;
		$_SESSION['newjob']['language']['totallangcount']--;
	} else {
		$_SESSION['newjob']['language'][$_GET['language']] = 1;
		$_SESSION['newjob']['language']['totallangcount']++;
	}
}



$languages = DBFindMany("Language","from language order by name");

header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title><?=$_SESSION['productname']?> - Languages</Title>

<MenuItem>
<Name><?= htmlentities("Default-English - Selected")?></Name>
</MenuItem>

<?
$morelangs = 0;
foreach ($languages as $language) {

	if($language->name == "English"){
		continue;
	}
	if(isset($_SESSION['newjob']['language'][$language->name]) && $_SESSION['newjob']['language'][$language->name] == 1) {
		$used = " - Selected";
	} else {
		$used = "";
	}
?>
	<MenuItem>
	<Name><?= htmlentities($language->name . $used) ?></Name>
	<URL><?= $URL . "/wiz4b_languages.php?language=" . $language->name?></URL>
	</MenuItem>
<?
}
?>

<Prompt>Please select your languages</Prompt>

<SoftKeyItem>
<Name>Continue</Name>
<URL><?= htmlentities($URL . "/wiz5_confirm.php") ?></URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= htmlentities($URL . "/wiz4_priority.php") ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Select</Name>
<URL>SoftKey:Select</URL>
<Position>4</Position>
</SoftKeyItem>



</CiscoIPPhoneMenu>