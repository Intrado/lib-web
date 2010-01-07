<?
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");

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
	$specialtask->setData("progress", "Call Ended");
	$specialtask->setData("error", "callended");
	$specialtask->update();
	?>
		<ok />
	<?
	$_SESSION = array();
} else {
	$BFXML_VARS['continue'] = true;
	forwardToPage("easycall2.php");
	return;
}


?>
