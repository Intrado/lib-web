<?
include_once("../inc/utils.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");

$specialtask = new specialtask($_SESSION['specialtaskid']);
$phone = $specialtask->getData('phonenumber');
$callerid = $specialtask->getData('callerid');

if($REQUEST_TYPE == "new") {
	$specialtask->setData("progress", "Calling");
	$specialtask->update();
	?>
	<voice>
		<dial callerid="<?=$callerid?>" amdhint="disable"><?=$phone?></dial>

		<message name="intro">
			<field name="dummy" type="menu" timeout="10000">
				<prompt repeat="1">
					<audio cmid="file://prompts/Intro.wav" />
				</prompt>
				<timeout>
					<audio cmid="file://prompts/GoodBye.wav" />
					<hangup />
				</timeout>
			</field>
		</message>
	</voice>
	<?
} else if($REQUEST_TYPE == "result") {
	$specialtask->status = "done";
	$specialtask->setData("progress", "Done");
	$specialtask->update();
	?>
		<ok />
	<?
	$_SESSION = array();
} else {
	$BFXML_VARS['continue'] = true;
	forwardToPage("callme2.php");
	return;
}
?>
