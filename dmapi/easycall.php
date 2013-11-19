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
				<field name="dummy" type="menu" timeout="2000">
					<prompt repeat="3">
					<? switch (getCustomerSystemSetting('_productname')) {
						case "AutoMessenger":?>
							<audio cmid="file://prompts/IntroExtensionAm.wav" />
							<? break;
						case "Skylert":?>
							<audio cmid="file://prompts/IntroExtensionSl.wav" />
							<? break;
						default: ?>
							<audio cmid="file://prompts/IntroExtensionSm.wav" />
					<? } ?>

						<audio cmid="file://prompts/IntroExtesionPt2.wav" />

					<? foreach($easycall->extensionDigits() as $digit) { ?>
						<audio cmid="file://prompts/inbound/<?= $digit ?>.wav" />
					<? } ?>

					<? foreach($easycall->extensionDigits() as $digit) { ?>
						<audio cmid="file://prompts/dtmf/<?= $digit ?>.wav" />
					<? } ?>

						<audio cmid="file://prompts/Intro2.wav" />
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
