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
//////////////////////////////////////////////////////////////////////////////

if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}

if (!isset($_GET['languagecode']) || !isset($_GET['categoryid'])) {
	redirect('unauthorized.php');
}



Query("BEGIN");

$category = DBFind("targetedmessagecategory", "from targetedmessagecategory where id=? and not deleted",false,array($_GET['categoryid']));
if (!$category) {
	redirect('unauthorized.php');
}

$language = DBFind("language", "from language where code=?",false,array($_GET['languagecode']));
if (!$language) {
	redirect('unauthorized.php');
}


$messagegroup = null;
$targetedmessage = null;

// Find override messagegroup
if (isset($_GET['messagekey'])) {
	$targetedmessage = DBFind("targetedmessage","from targetedmessage where messagekey = ? and enabled and not deleted",false,array($_GET['messagekey']));
	if ($targetedmessage) {
		if ($targetedmessage->overridemessagegroupid) {
			$messagegroup = new MessageGroup($targetedmessage->overridemessagegroupid);
		}
	} else {
		notice("Unknown Classroom Message");
		redirect("ClassroomEdit");
	}	
} 

// If messagegroup was not found create default override messagegroup
if (!$messagegroup) {
	$defaultmessagetext = "";
	if ($targetedmessage) {
		$filename = "messagedata/" . $language->code . "/targetedmessage.php";
		if(file_exists($filename))
			include_once($filename);
			
		if(isset($messagedatacache[$language->code]) && isset($messagedatacache[$language->code][$targetedmessage->messagekey])) {
			$defaultmessagetext = $messagedatacache[$language->code][$targetedmessage->messagekey];
		} // else no default data found value is set to empty
	}
	
	$messagegroup = new MessageGroup();
	$messagegroup->userid = $USER->id;
	$messagegroup->name = "Custom Classroom";
	$messagegroup->description = '';
	$messagegroup->modified = date("Y-m-d H:i:s", time());
	$messagegroup->deleted = 1;
	$messagegroup->permanent = 1;
	$messagegroup->create();

	// create a new email message
	$message = new Message();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = $USER->id;
	$message->name = "Custom Classroom";
	$message->description = '';
	$message->type = 'email';
	$message->subtype = 'plain';
	$message->data = '';
	$message->modifydate = date("Y-m-d H:i:s", time());
	$message->autotranslate = 'none';
	$message->languagecode = $language->code;
	$message->create();
	
	$messagepart = new MessagePart();
	$messagepart->messageid = $message->id;
	$messagepart->type = 'T';
	$messagepart->txt = $defaultmessagetext;
	$messagepart->sequence = 0;
	$messagepart->create();
	
	// create a new phone message
	$message = new Message();
	$message->messagegroupid = $messagegroup->id;
	$message->userid = $USER->id;
	$message->name = "Custom Classroom";
	$message->description = '';
	$message->type = 'phone';
	$message->subtype = 'voice';
	$message->data = '';
	$message->modifydate = date("Y-m-d H:i:s", time());
	$message->autotranslate = 'none';
	$message->languagecode = $language->code;
	$message->create();
	
	$messagepart = new MessagePart();
	$messagepart->messageid = $message->id;
	$messagepart->type = 'T';
	$messagepart->txt = $defaultmessagetext;
	$messagepart->voiceid = Voice::getPreferredVoice($language->code, "female");
	$messagepart->sequence = 0;
	$messagepart->create();
}

if (!$targetedmessage) {
	$targetedmessage = new TargetedMessage();
	$targetedmessage->messagekey = "custom-" .  $messagegroup->id;
	$targetedmessage->targetedmessagecategoryid = $category->id;
	$targetedmessage->overridemessagegroupid = $messagegroup->id;
	$targetedmessage->create();
}

Query("COMMIT");

setCurrentMessageGroup($messagegroup->id);
$url = "classroommessageeditlanguage.php?mgid={$messagegroup->id}&languagecode={$language->code}";
error_log($url);
redirect($url);

?>