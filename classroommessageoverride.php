<?

require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");


require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("inc/appserver.inc.php");

///////////////////////////////////////////////////////////////////////////////
// Authorization:/
//////////////////////////////////////////////////////////////////////////////
$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');

if (!$cansendphone && !$cansendemail) {
	redirect('unauthorized.php');
} 
if (!isset($_GET['languagecode'])) {
	redirect('unauthorized.php');
	
}


Query("BEGIN");

$messagegroup = new MessageGroup();
$messagegroup->userid =  $USER->id;
$messagegroup->name = "Custom Classroom";
$messagegroup->description = '';
$messagegroup->modified = date("Y-m-d H:i:s", time());
$messagegroup->deleted = 1;
$messagegroup->permanent = 1;
$messagegroup->create();

Query("COMMIT");

setCurrentMessageGroup($messagegroup->id);
error_log("classroommessageeditlanguage.php?mgid={$messagegroup->id}&languagecode={$_GET['languagecode']}");
redirect("classroommessageeditlanguage.php?mgid={$messagegroup->id}&languagecode={$_GET['languagecode']}");

?>