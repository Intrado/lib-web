<?
include_once("../inc/settings.ini.php");
include_once("../inc/utils.inc.php");
include_once("../obj/Job.obj.php");
include_once("../obj/User.obj.php");
include_once("../obj/SpecialTask.obj.php");
include_once("../obj/Message.obj.php");
include_once("../obj/MessagePart.obj.php");
include_once("../obj/AudioFile.obj.php");

$specialtask = new specialtask($SESSIONDATA['specialtaskid']);

if($REQUEST_TYPE == "new"){
	$ERROR .= "Got new when wanted result";
} else if($REQUEST_TYPE == "result"){

	$SESSIONDATA = null;
	?> <ok /> <?

} else {
	?>
		<voice>
			<message>
				<audio cmid="file://prompts/GoodBye.wav" />
				<hangup />
			</message>
		</voice>
	<?
}
?>