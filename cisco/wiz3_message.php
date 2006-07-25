<?
require_once("common.inc.php");
require_once("../obj/Message.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if(isset($_GET['list'])) {
	$_SESSION['newjob']['list'] = $_GET['list'];
}

if(isset($_GET['info'])) {
	header("Location: $URL/wiz2b_listinfo.php");
	exit();
}

$messages = DBFindMany("Message","from message where type='phone' and userid=$USER->id and deleted=0 order by name");


header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title>SchoolMessenger - Message</Title>
<Prompt>Please select your message</Prompt>


<? if ($USER->authorize('starteasy')) { ?>
	<MenuItem>
	<Name>**Call Me to Record**</Name>
	<URL><?= htmlentities($URL . "/wiz4_priority.php?message=callme") ?></URL>
	</MenuItem>
<? } ?>

<? foreach ($messages as $message) { ?>
	<MenuItem>
	<Name><?= htmlentities($message->name) ?></Name>
	<URL><?= htmlentities($URL . "/wiz4_priority.php?message=" . $message->id) ?></URL>
	</MenuItem>
<? } ?>

<SoftKeyItem>
<Name>Select</Name>
<URL>SoftKey:Select</URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= htmlentities($URL . "/wiz2_list.php") ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>

</CiscoIPPhoneMenu>