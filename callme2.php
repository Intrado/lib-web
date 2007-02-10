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

if($specialtask->getData('progress')=="Done") {
	redirect('callme3.php?taskid=' . $specialtask->id);
	//we got it
} else if ($specialtask->getData('progress') =="Hung Up"){
	

 
} else {
	$progress = $specialtask->getData('progress');
	$currnum = $specialtask->getData('count');
	
}



$TITLE = 'Call Me';

include_once('popup.inc.php');

startWindow("Recording session in progress");

if ($specialtask->getData('error') == 'true') {
	echo "There was an error trying to call you. Please try again";
} else {
?>
<div style="text-align: center; width: 400px; padding: 3px;">
	<h3>Recording session with <?= Phone::format($specialtask->getData("phonenumber")) ?> in progress</h3>
	<h3> Progress: <?=$progress?></h3>
	<h3> Message Number: <?=$currnum?></h3>
	<img src="img/progressbar.gif?date=<?= time() ?>">
<div>
	<meta http-equiv="refresh" content="2;url=callme2.php?taskid=<?= $_REQUEST['taskid'] ?>&toggle=<?= !$_REQUEST['toggle'] ?>">
<?
}
endWindow();

include_once('popupbottom.inc.php');
?>