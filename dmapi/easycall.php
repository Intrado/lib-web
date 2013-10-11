<?
require_once("../inc/utils.inc.php");
require_once("../obj/SpecialTask.obj.php");
require_once("../obj/dmapi/EasyCall.obj.php");

if($REQUEST_TYPE !== "new" && $REQUEST_TYPE != "result") {
	$BFXML_VARS['continue'] = true;
	forwardToPage("easycall2.php");
	return;
}


$easycall = new EasyCall(new specialtask($_SESSION['specialtaskid']));

if($REQUEST_TYPE == "new") {
	$easycall->startCall();

	?>
	<voice>
		<dial callerid="<?=$easycall->callerid()?>" amdhint="disable"><?=$easycall->phone()?></dial>
		<? if($easycall->hasExtension()) { ?>
			<message name="extensionintro">
				<field name="dummy" type="menu" timeout="10000">
					<prompt repeat="1">
						<tts>Hello, Please direct this call to extension</tts>

						<? foreach($easycall->extensionDigits() as $digit) { ?>
							<audio cmid="file://prompts/inbound/<?= $digit ?>.wav" />
						<? } ?>

						<? foreach($easycall->extensionDigits() as $digit) { ?>
							<audio cmid="file://prompts/dtmf/<?= $digit ?>.wav" />
						<? } ?>

						<tts>If this is the correct extension, press 1 and follow the prompts to record your message.</tts>
					</prompt>

					<choice digits="1">
						<goto message="intro" />
					</choice>

					<timeout>
						<audio cmid="file://prompts/GoodBye.wav" />
						<hangup />
					</timeout>
				</field>
			</message>

		<? } ?>

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

	$easycall->endCall();

	$_SESSION = array();
	?>
		<ok />
	<?
}
?>
