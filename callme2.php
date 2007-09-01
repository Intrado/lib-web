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

$specialtask = new SpecialTask($_GET['taskid']);

if($specialtask->getData('progress')=="Done") {

	if($specialtask->getData("message1")){
		redirect('callme3.php?taskid=' . $specialtask->id);
	} else {
		$specialtask->setData('error', 'No messages saved');
		$specialtask->update();
	}
} else {
	$progress = $specialtask->getData('progress');
	$currnum = $specialtask->getData('count');

}



$TITLE = 'Call Me';

include_once('popup.inc.php');

startWindow("Recording session in progress");

if ($error = $specialtask->getData('error')) {
	?>
		<div style="text-align: center; width: 100%; padding: 3px;">
			<span style="color: red;">There was an error during the call: <?=$error?>.</span><br>
			Please check the phone number and <a href="callme.php?origin=<?=$specialtask->getData('origin')?>">try again.</a>
		</div>
	<?
} else {
?>
<div style="text-align: center; width: 100%; padding: 3px;">
	<h3>Recording session with <?= Phone::format($specialtask->getData("phonenumber")) ?> in progress</h3>
	<h3> Progress: <?=$progress?></h3>
	<h3> Message Number: <?=$currnum?></h3>
	<img src="img/progressbar.gif?date=<?= time() ?>">
<div>
	<meta http-equiv="refresh" content="2;url=callme2.php?taskid=<?= $_GET['taskid'] ?>">
<?
}
endWindow();

include_once('popupbottom.inc.php');
?>