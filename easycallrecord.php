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


$specialtask = new SpecialTask($_SESSION['easycallid']);
$progress = $specialtask->getData("progress");
$error = $specialtask->getData("error");
if($progress == "Done") {
	redirect("easycallsubmit.php");
} else {
	$currlang = $specialtask->getData("currlang");

	if($error == "callended") {
		$errormsg = "The phone call ended earlier than expected. Please check your phone connectivity";
	} else if ($error == "messagesremain"){
		$errormsg = "The phone call ended earlier than expected. There are still more messages to be recorded";
	} else if($error == "saveerror"){
		$errormsg = "There was an error saving the message.";
	}
}

$TITLE = 'EasyCall';

include_once('popup.inc.php');

startWindow("Recording session in progress");
if (!$error) {
?>
<div style="text-align: center; width: 100%; padding: 3px;">

	<h3>Recording session with <?= Phone::format($specialtask->getData("phonenumber")) ?></h3>
	<h3> Progress: <?=$progress?></h3>
	<h3> Language: <?=$currlang?></h3>
	<img src="img/progressbar.gif?date=<?= time() ?>">
	<hr>
	<img src="img/bug_important.gif" > You should receive a call shortly. After you save your message(s) and hangup, you will need to <b>Confirm &amp; Submit</b> your job in the next screen.

</div>
	<meta http-equiv="refresh" content="2;url=easycallrecord.php">

<?

} else {
?>
	<div style="text-align: center; width: 100%; padding: 3px;">
		<span style="color: red;">There was an error during the call: <?=$errormsg?>.</span><br>
		Please check the phone number and <a href="easycallstart.php?retry=<?= $specialtask->id ?>">try again</a>
	</div>
<?
}

endWindow();

include_once('popupbottom.inc.php');
?>