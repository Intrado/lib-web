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

if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");	
}

$specialtask = new SpecialTask($_REQUEST['taskid']);

if($afid = $specialtask->getData('audiofileid')) {

	//we got it

	//is the origin from the audio file editor?
	if ($specialtask->getData('origin') == "audio") {
		redirect("audio.php");
	} else {

		//then make a message and select it, then close the window
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

				//select the new message in the main form

	}

}



$TITLE = 'Call Me';

include_once('popup.inc.php');

startWindow("Recording session in progress");

if ($specialtask->getData('error') == 'true') {
	echo "There was an error trying to call you. Please try again";
} else if ($afid) {
?>
	<script language="javascript">
		var success = insertAndSelectItem('message','<?= $message->name ?>','<?= $message->id ?>');
		if (success)
			alert("Your message has been saved and should be automatically selected in the menu");
		else
			alert("Your message has been saved. You may need to reload the page for it to appear in the menu");
		
		<?
			if ($specialtask->getData('origin') == 'message') {
				print 'window.opener.document.location.reload; window.close()';	
			} else {
				print 'window.close()';	
			}
		?>
	</script>
<?

} else {
?>
<div style="text-align: center; width: 400px; padding: 3px;">
	<h3>Recording session with <?= Phone::format($specialtask->getData("phonenumber")) ?> in progress</h3>
	<img src="img/progressbar.gif?date=<?= time() ?>">
<div>
	<meta http-equiv="refresh" content="2;url=callme2.php?taskid=<?= $_REQUEST['taskid'] ?>&toggle=<?= !$_REQUEST['toggle'] ?>">
<?
}
endWindow();

include_once('popupbottom.inc.php');
?>