<?
require_once("common.inc.php");
require_once("../obj/MessageGroup.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if(isset($_GET['list'])) {
	$_SESSION['newjob']['list'] = $_GET['list']+0;
}

if(isset($_GET['info'])) {
	header("Location: $URL/wiz2b_listinfo.php");
	exit();
}

if(isset($_GET['messcount'])) {
	$min = $_GET['messcount'] + 1;
	$max = $_GET['messcount'] + 29;
} else {
	$min = 0;
	$max = 29;
}
if($min - 30 <= 0){
	$back = -1;
} else {
	$back = $min - 30;
}

$messages = DBFindMany("MessageGroup","from messagegroup where type='notification' and userid=$USER->id and deleted=0 order by name
	limit 29 offset $min");

header("Content-type: text/xml");

?>
<CiscoIPPhoneMenu>
<Title><?=$_SESSION['productname']?> - Message</Title>
<Prompt>Please select your message</Prompt>


<? if ($USER->authorize('starteasy')) { ?>
	<MenuItem>
	<Name>**Call Me to Record**</Name>
	<URL><?= htmlentities($URL . "/wiz4_priority.php?message=callme") ?></URL>
	</MenuItem>
<? } ?>

<?

foreach ($messages as $message) {
?>
	<MenuItem>
	<Name><?= htmlentities($message->name) ?></Name>
	<URL><?= htmlentities($URL . "/wiz4_priority.php?message=" . $message->id) ?></URL>
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
<URL><?= htmlentities($URL . "/wiz2_list.php") ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Cancel</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>3</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Mor Msg</Name>
<URL><?= htmlentities($URL . "/wiz3_message.php?messcount=". $max)  ?></URL>
<Position>4</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Bck Msg</Name>
<URL><?= htmlentities($URL . "/wiz3_message.php?messcount=". $back)  ?></URL>
<Position>5</Position>
</SoftKeyItem>


</CiscoIPPhoneMenu>