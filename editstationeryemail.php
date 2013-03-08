<?
// Needs some GET request arguments. Either:
//	 id, where id is the message id to be edited
// or:
//   mgid, where mgid is the messagegroup that will own this message
//   languagecode, where languagecode is the language of the message to be created

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/PreviewModal.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/EmailAttach.fi.php");
//require_once("obj/ValMessageBody.val.php");
require_once("obj/ValStationeryBody.val.php");
require_once("obj/HtmlTextArea.fi.php");
require_once("obj/PreviewButton.fi.php");
require_once("obj/ValDuplicateNameCheck.val.php");

// appserver and thrift includes
require_once("inc/appserver.inc.php");

require_once("inc/editmessagecommon.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

global $USER;
if (!$USER->authorize("sendemail") || !$USER->authorize('createstationery'))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
setEditMessageSession();

// get the messagegroup and/or the message
if (isset($_SESSION['editmessage']['messageid']))
	$message = new Message($_SESSION['editmessage']['messageid']);
else
	$message = false;

$subtype = "html"; // html subtype is the Only supported so far

// set the message bits
if ($message) {
	// if the user doesn't own this message, unauthorized!
	if (!userOwns("message", $message->id))
		redirect('unauthorized.php');
	
	// get the parent message group for this message
	$messagegroup = new MessageGroup($message->messagegroupid);
	// use the message's language code
	$languagecode = $message->languagecode;	
} else {
	// not editing an existing message, check session data for new message bits
	if (isset($_SESSION['editmessage']['messagegroupid']) && 
			isset($_SESSION['editmessage']['languagecode'])) {
		
		$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
		$languagecode = $_SESSION['editmessage']['languagecode'];
	} else {		
		// missing session data!
		redirect('unauthorized.php');
	}
}


// if the user doesn't own the parent message group, unauthorized!
if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted)
	redirect('unauthorized.php');

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

// no multi lingual and not default language code
if (!$USER->authorize("sendmulti") && $languagecode != Language::getDefaultLanguageCode())
	redirect('unauthorized.php');


PreviewModal::HandleRequestWithEmailText();
	
////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get value from passed message, or default some values if not set
// relys on including form having a $message and $messagegroup object already created.

$text = "";
if ($message) {
	// get the specific bits from the message if it exists
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	
	$text = Message::format($parts);
}

$language = Language::getName($languagecode);

$formdata = array();
$formdata["name"] = array(
	"label" => _L('Name'),
	"fieldhelp" => _L('Name '),
	"value" => $messagegroup->name,
	"validators" => array(
		array("ValRequired"),
		array("ValDuplicateNameCheck","type" => "messagegroup"),
		array("ValLength","max" => 50)
	),
	"control" => array("TextField","size" => 25, "maxlength" => 50),
);

$formdata["description"] = array(
		"label" => _L('Description'),
		"fieldhelp" => _L('Enter a description of the stationery. This is optional, but can help identify the stationery later.'),
		"value" => $messagegroup->description,
		"validators" => array(
				array("ValLength","min" => 0,"max" => 50)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 50),
);


$messagecontrol = array("HtmlTextArea", "subtype" => $subtype, "editor_mode" => "full");
if ($subtype == "plain" && $languagecode == "en")
	$messagecontrol['spellcheck'] = true;

$formdata["message"] = array(
	"label" => _L("Stationery"),
	"fieldhelp" => _L('Edit the stationery. Helpful tips for successful messages can be found at ".
		"the Help link in the upper right corner.'),
	"value" => $text,
	"validators" => array(
		array("ValRequired"),
		//array("ValMessageBody", "messagegroupid" => $messagegroup->id),
		array("ValStationeryBody", "messagegroupid" => $messagegroup->id),
		array("ValLength","max" => 256000)
	),
	"control" => $messagecontrol,
);


$buttons = array(submit_button(_L('Done'),"submit","tick"));
$form = new Form("emaileedit",$formdata,null,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response	
	
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		// if they didn't change anything, don't do anything
		if ($postdata['name'] == $messagegroup->name && 
			$postdata['description'] == $messagegroup->description &&
			$postdata['message'] == $text) {
			// DO NOT UPDATE MESSAGE!
		} else if ($button != 'inpagesubmit') {
			Query("BEGIN");
			
			$messagegroup->name = removeIllegalXmlChars($postdata['name']);
			$messagegroup->description = removeIllegalXmlChars($postdata['description']);
			$messagegroup->modified = date("Y-m-d H:i:s", time());
			
			$messagegroup->update(array("name","description","modified"));
			// if this is an edit for an existing message
			if ($message) {
				// delete existing messages
				QuickUpdate("delete from message 
						where messagegroupid = ?
						and type = 'email'
						and subtype = ?
						and languagecode = ?
						and id != ?", false, array($messagegroup->id, $subtype, $languagecode, $message->id));
			} else {
				// new message
				$message = new Message();
			}
			
			$message->messagegroupid = $messagegroup->id;
			$message->type = "email";
			$message->subtype = $subtype;
			$message->autotranslate = ($languagecode == "en")?'none':'overridden';
			$message->name = $messagegroup->name;
			$message->description = Language::getName($languagecode);
			$message->userid = $USER->id;
			$message->modifydate = date("Y-m-d H:i:s");
			$message->languagecode = $languagecode;
						
			if ($message->id)
				$message->update();
			else
				$message->create();
						
			// create the message parts
			$message->recreateParts($postdata['message'], null, false);
			
			$messagegroup->updateDefaultLanguageCode();
			
			Query("COMMIT");
		}
		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo("messages.php");
		else
			redirect("messages.php");
	}
}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = "Stationery Editor";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? /*Validator::load_validators(array("ValDuplicateNameCheck","ValMessageBody", "ValEmailAttach")); */?>
<? Validator::load_validators(array("ValDuplicateNameCheck","ValStationeryBody", "ValEmailAttach")); ?>
</script>

<?
PreviewModal::includePreviewScript();

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
