<?
require_once("inc/common.inc.php");
require_once("inc/html.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");

require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");

require_once("inc/appserver.inc.php");

PreviewModal::HandleRequestWithPhoneText();
?>