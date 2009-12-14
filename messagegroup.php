<?php
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/MessageBody.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once('obj/MessageGroup.obj.php');
require_once('obj/MessageGroupForm.obj.php');
require_once('obj/traslationitem.obj.php');
require_once('obj/MessageAttachment.obj.php');
require_once("obj/EmailAttach.fi.php");
require_once("obj/EmailAttach.val.php");

// CONSTANTS.
$formSettings = array();

// REQUEST PROCESSING.
$isNew = true;
$isReadOnly = false;

if ($isNew)
	$messageGroup = new MessageGroup();

// FORM INTIIALIZATION.
$buttons = array(
	submit_button(_L('Cancel'),"cancel","arrow_refresh"),
	submit_button(_L('Save'),"save","tick")
);

$form = new MessageGroupForm('messagegroupform', null, $buttons, $messageGroup, $formSettings);

// FORM VALIDATION HANDLING.
$form->handleRequest();

// FORM SUBMISSION HANDLING.
if (!$isReadOnly && $button = $form->getSubmit()) {
	// $ajax indicates whether or not this request requires an ajax response.
	$ajax = $form->isAjaxSubmit();

	if (!$form->checkForDataChange() && !$form->validate()) {

		if ($button == 'save') {
			/////////////////////////////////////////////////
			// SAVE.
			/////////////////////////////////////////////////

			Query('BEGIN');
				$form->save();
			Query('COMMIT');

			// Let the User Know What Happened.
			if ($ajax)
				$form->sendTo('messagegroup.php');
			else
				redirect('messagegroup.php');
		} else if ($button == 'cancel') {
			/////////////////////////////////////////////////
			// CANCEL.
			/////////////////////////////////////////////////

			if ($ajax)
				$form->sendTo('messagegroup.php');
			else
				redirect('messagegroup.php');
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = _L("notifications").":"._L("messages");
$TITLE = _L('Message Group Builder: ') . (!$isNew ? escapehtml($messageGroup->name) : _L("New Message Group") );
$ICON = "email.gif";

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody","ValDuplicateNameCheck","ValCallMeMessage", "ValEmailAttach", "ValTranslation")); ?>
</script>
<?

startWindow(_L('Message Group'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");

?>
