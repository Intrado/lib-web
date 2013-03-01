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
require_once("obj/TargetedMessage.obj.php");
require_once("obj/TargetedMessageCategory.obj.php");


///////////////////////////////////////////////////////////////////////////////
// Authorization:/
///////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}

if (!isset($_GET['messagekey']) || !isset($_GET['languagecode'])) {
	redirect('unauthorized.php');
}

Query("BEGIN");

$language = DBFind("language", "from language where code=?",false,array($_GET['languagecode']));
if (!$language) {
	redirect('unauthorized.php');
}

$messagegroup = null;

// Find override messagegroup
$targetedmessage = DBFind("targetedmessage","from targetedmessage where messagekey = ?",false,array($_GET['messagekey']));
if ($targetedmessage) {
	if ($targetedmessage->overridemessagegroupid) {
		$messagegroup = new MessageGroup($targetedmessage->overridemessagegroupid);
	}
} else {
	notice("Unknown Classroom Message");
	redirect("classroommessagemanager.php");
}

$emailmessage = null;
$phonemessage = null;
$defaultmessagetext = "";

if ($targetedmessage) {
	$filename = "messagedata/" . $language->code . "/targetedmessage.php";
	if(file_exists($filename))
		include_once($filename);
	
	if(isset($messagedatacache[$language->code]) && isset($messagedatacache[$language->code][$targetedmessage->messagekey])) {
		$defaultmessagetext = $messagedatacache[$language->code][$targetedmessage->messagekey];
	} // else no default data found value is set to empty
}

// If messagegroup was not found create default override messagegroup
if (!$messagegroup) {
	$messagegroup = new MessageGroup();
	$messagegroup->userid = $USER->id;
	$messagegroup->name = "Custom Classroom";
	$messagegroup->description = '';
	$messagegroup->modified = date("Y-m-d H:i:s", time());
	$messagegroup->deleted = 1;
	$messagegroup->permanent = 1;
	$messagegroup->create();

} else {
	$emailmessage = DBFind("Message", "from message where messagegroupid = ? and type = 'email' and languagecode=?",false,array($messagegroup->id,$language->code));
	$phonemessage = DBFind("Message", "from message where messagegroupid = ? and type = 'phone' and languagecode=?",false,array($messagegroup->id,$language->code));
}

if ($defaultmessagetext != "" && !$emailmessage) {
	// create a new email message
	$emailmessage = new Message();
	$emailmessage->messagegroupid = $messagegroup->id;
	$emailmessage->userid = $USER->id;
	$emailmessage->name = "Custom Classroom";
	$emailmessage->description = '';
	$emailmessage->type = 'email';
	$emailmessage->subtype = 'plain';
	$emailmessage->data = '';
	$emailmessage->modifydate = date("Y-m-d H:i:s", time());
	$emailmessage->autotranslate = 'none';
	$emailmessage->languagecode = $language->code;
	$emailmessage->create();
	
	$emailmessagepart = new MessagePart();
	$emailmessagepart->messageid = $emailmessage->id;
	$emailmessagepart->type = 'T';
	$emailmessagepart->txt = $defaultmessagetext;
	$emailmessagepart->sequence = 0;
	$emailmessagepart->create();
}


$targetedmessage->overridemessagegroupid = $messagegroup->id;
$targetedmessage->update();

Query("COMMIT");

setCurrentMessageGroup($messagegroup->id);
$url = "classroommessageeditlanguage.php?mgid={$messagegroup->id}&languagecode={$language->code}";
redirect($url);

?>