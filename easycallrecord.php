<?

//polls the specialtask until it's finished or timeout occurs

include_once('inc/common.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');

// AUTHORIZATION //////////////////////////////////////////////////
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

$specialtask = new SpecialTask($_REQUEST['taskid']);
if($afid = $specialtask->getData('audiofileid')) {

	//we got it

	//then make a message
	$message = new Message();
	$message->name = $specialtask->getData('name');
	$message->type = "phone";
	$message->userid = $USER->id;
	$message->create();

	$part = new MessagePart();
	$part->messageid = $message->id;
	$part->type = "A";
	$part->audiofileid = $afid;
	$part->sequence = 0;

	$part->create();

	$specialtask->setData('messageid',$message->id);
	$specialtask->update();

	redirect("easycallsubmit.php?taskid=" . $_REQUEST['taskid']);
} else {
	$specialtask->lastcheckin = date("Y-m-d H:i:s");
	$specialtask->update();
}

$error = $specialtask->getData('error');


$TITLE = 'EasyCall';

include_once('popup.inc.php');

startWindow("Recording session in progress");
if (!$error) {
?>
<div style="text-align: center; width: 400px; padding: 3px;">

	<h3>Recording session with <?= Phone::format($specialtask->getData("phonenumber")) ?> in progress</h3>
	<img src="img/progressbar.gif?date=<?= time() ?>">
	<hr>
	<img src="img/bug_important.gif" > You should receive a call shortly. After you save your message and hangup, you will need to <b>Confirm &amp; Submit</b> your job in the next screen.

</div>
	<meta http-equiv="refresh" content="2;url=easycallrecord.php?taskid=<?= $_REQUEST['taskid'] ?>&toggle=<?= !$_REQUEST['toggle'] ?>">
<?
} else {
?>
	<div style="text-align: center; width: 400px; padding: 3px;">
		<span style="color: red;">There was an error during the call.</span><br>
		Please check the phone number and <a href="easycallstart.php?retry=<?= $specialtask->id ?>">try again</a></span>
	</div>
<?
}

endWindow();

include_once('popupbottom.inc.php');
?>