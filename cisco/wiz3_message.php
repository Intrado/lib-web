<?
require_once("common.inc.php");
require_once("../obj/MessageGroup.obj.php");

if (!$USER->authorize('sendphone')) {
	header("Location: $URL/index.php");
	exit();
}

if (isset($_GET['list'])) {
	$listid = $_GET['list'] + 0;
	if (userOwns("list", $listid) || isSubscribed("list", $listid)) {
		$_SESSION['newjob']['list'] = $listid;
	} else {
		header("Location: $URL/index.php");
		exit();
	}
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

// get all the message groups
$messages = QuickQueryMultiRow(
		"(select mg.id as id, (mg.name +0) as digitsfirst, mg.name as name
		from messagegroup mg
		where mg.userid = ? 
			and mg.type = 'notification'
			and not mg.deleted)
		UNION
		(select mg.id as id, (mg.name +0) as digitsfirst, mg.name as name
		from publish p
		inner join messagegroup mg on
			(p.messagegroupid = mg.id)
		where p.userid = ?
			and p.action = 'subscribe'
			and p.type = 'messagegroup'
			and not mg.deleted)
		order by digitsfirst, name, id
		limit 29 offset $min", true, false, array($USER->id, $USER->id));


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
	<Name><?= htmlentities($message['name']) ?></Name>
	<URL><?= htmlentities($URL . "/wiz4_priority.php?message=" . $message['id']) ?></URL>
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