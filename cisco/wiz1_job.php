<?
require_once("common.inc.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (!isset($_GET['edit'])) {
	$_SESSION['newjob'] = array();

	$_SESSION['newjob']['name'] = "";
	$_SESSION['newjob']['desc'] = "";
	$_SESSION['newjob']['numdays'] = min($ACCESS->getValue('maxjobdays'), $USER->getSetting("maxjobdays","2"));
	$_SESSION['newjob']['retries'] = min($ACCESS->getValue('callmax'), $USER->getSetting("callmax","4"));
	$_SESSION['newjob']['easycall'] = false;
}

header("Content-type: text/xml");
?>
<CiscoIPPhoneInput>
<Title><?=$_SESSION['productname']?> - New Job</Title>
<Prompt>Naming your job is optional.</Prompt>
<URL><?= htmlentities($URL . "/wiz2_list.php") ?></URL>

<InputItem>
<DisplayName>Job name</DisplayName>
<QueryStringParam>jobname</QueryStringParam>
<DefaultValue><?= htmlentities($_SESSION['newjob']['name']) ?></DefaultValue>
<InputFlags>A</InputFlags>
</InputItem>

<InputItem>
<DisplayName>Description</DisplayName>
<QueryStringParam>desc</QueryStringParam>
<DefaultValue><?= htmlentities($_SESSION['newjob']['desc']) ?></DefaultValue>
<InputFlags>A</InputFlags>
</InputItem>


<InputItem>
<DisplayName>Days to run</DisplayName>
<QueryStringParam>numdays</QueryStringParam>
<DefaultValue><?= $_SESSION['newjob']['numdays'] ?></DefaultValue>
<InputFlags>N</InputFlags>
</InputItem>

<InputItem>
<DisplayName>Call retries</DisplayName>
<QueryStringParam>retries</QueryStringParam>
<DefaultValue><?= $_SESSION['newjob']['retries'] ?></DefaultValue>
<InputFlags>N</InputFlags>
</InputItem>


<?/* overide the back/exit button */?>

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
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>


</CiscoIPPhoneInput>
