<?
// Needs some GET request arguments. Either:
//	 id, where id is the message id to be edited
// or:
//   mgid, where mgid is the messagegroup that will own this message
//   languagecode, where languagecode is the language of the message to be created
//   subtype, where subtype is either "plain" or "html" depending on the type of email to be created

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
require_once("obj/ValMessageBody.val.php");
require_once("obj/EmailMessageEditor.fi.php");
require_once("obj/HtmlTextArea.fi.php");
require_once("obj/PreviewButton.fi.php");

// appserver and thrift includes
require_once("inc/appserver.inc.php");

require_once("inc/editmessagecommon.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendemail"))
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

// set the message bits
if ($message) {
	// if the user doesn't own this message, unauthorized!
	if (!userOwns("message", $message->id))
		redirect('unauthorized.php');
	
	// get the parent message group for this message
	$messagegroup = new MessageGroup($message->messagegroupid);
	// use the message's language code
	$languagecode = $message->languagecode;
	// emails need a subtype to tell if it's plain or html
	$subtype = $message->subtype;
	
} else {
	// not editing an existing message, check session data for new message bits
	if (isset($_SESSION['editmessage']['messagegroupid']) && 
			isset($_SESSION['editmessage']['languagecode'])) {
		
		$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
		$languagecode = $_SESSION['editmessage']['languagecode'];
		if (isset($_SESSION['editmessage']['subtype']) && $_SESSION['editmessage']['subtype'] == "plain")
			$subtype = "plain";
		else
			$subtype = "html";
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

if ($USER->authorize('forcestationery') && !isset($_SESSION['editmessage']['stationeryid']))
	redirect('unauthorized.php');

PreviewModal::HandleRequestWithEmailText();
	
////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

// get value from passed message, or default some values if not set
// relys on including form having a $message and $messagegroup object already created.
$fromname = $USER->firstname . " " . $USER->lastname;
$fromemail = $USER->email;
$subject = "";
$attachments = array();
$text = "";
$fromstationery = false;

if ($message) {
	// get the specific bits from the message if it exists
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$message->readHeaders();
	
	$text = Message::format($parts);
	
	$fromname = $message->fromname;
	$fromemail = $message->fromemail;
	$subject = $message->subject;
	$fromstationery = $message->fromstationery;
	
	// get the attachments
	$msgattachments = DBFindMany("MessageAttachment", "from messageattachment where messageid = ?", false, array($message->id));
	foreach ($msgattachments as $msgattachment) {
		permitContent($msgattachment->contentid);
		$attachments[$msgattachment->contentid] = array("name" => $msgattachment->filename, "size" => $msgattachment->size);
	}
} else {
	$message2 = $messagegroup->getMessage("email", $subtype=="html"?"plain":"html", $languagecode);
	// Sync with other message subtype if it exists
	if ($message2) {
		$message2->readHeaders();
		$fromname = $message2->fromname;
		$fromemail = $message2->fromemail;
		$subject = $message2->subject;
		$msgattachments = DBFindMany("MessageAttachment", "from messageattachment where messageid = ?", false, array($message2->id));
		foreach ($msgattachments as $msgattachment) {
			permitContent($msgattachment->contentid);
			$attachments[$msgattachment->contentid] = array("name" => $msgattachment->filename, "size" => $msgattachment->size);
		}
	}
	
	if (isset($_SESSION['editmessage']['stationeryid'])) {
		$stationery = new MessageGroup($_SESSION['editmessage']['stationeryid']);
		if ($stationery->type == "stationery" &&
			$emailstationery = $stationery->getMessage("email", $subtype, "en")) {
			$emailstationeryparts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($emailstationery->id));
				
			$fromstationery = true;
			$text = Message::format($emailstationeryparts);
		}
	}
}

$language = Language::getName($languagecode);

$formdata = array($messagegroup->name. " (". $language. ")");

$helpsteps[] = array(_L("Enter the name this email will appear as coming from."));
$formdata["fromname"] = array(
	"label" => _L('From Name'),
	"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
	"value" => $fromname,
	"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 50)
			),
	"control" => array("TextField","size" => 25, "maxlength" => 50),
	"helpstep" => 1
);

$helpsteps[] = array(_L("Enter the address where you would like to receive replies."));
$formdata["from"] = array(
	"label" => _L("From Email"),
	"fieldhelp" => _L('This is the address the email is coming from. Recipients will also be able to reply to this address.'),
	"value" => $fromemail,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255),
		array("ValEmail", "domain" => getSystemSetting('emaildomain'))
		),
	"control" => array("TextField","max"=>255,"min"=>3,"size"=>35),
	"helpstep" => 2
);

$helpsteps[] = _L("Enter the subject of the email here.");
$formdata["subject"] = array(
	"label" => _L("Subject"),
	"fieldhelp" => _L('The Subject will appear as the subject line of the email.'),
	"value" => $subject,
	"validators" => array(
		array("ValRequired"),
		array("ValLength","max" => 255)
	),
	"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
	"helpstep" => 3
);

$helpsteps[] = _L("You may attach up to three files that are up to 2MB each. For greater security, only certain types ".
	"of files are accepted.<br><br><b>Note:</b> Some email accounts may not accept attachments above a certain size and may reject your message.");
$formdata["attachments"] = array(
	"label" => _L('Attachments'),
	"fieldhelp" => _L("You may attach up to three files that are up to 2MB each. For greater security, certain file ".
		"types are not permitted. Be aware that some email accounts may not accept attachments above a certain size and may reject your message."),
	"value" => ($attachments?json_encode($attachments):"{}"),
	"validators" => array(array("ValEmailAttach")),
	"control" => array("EmailAttach"),
	"helpstep" => 4
);


// MESSAGE BODY
if ($subtype == 'plain') {
	// For plain text emails, use a plain textarea
	$messagecontrol = array("EmailMessageEditor", "subtype" => $subtype);
	if ($languagecode == "en") {
		$messagecontrol['spellcheck'] = true;
	}
} else {
	// HTML emails will use CKEditor 4
	// valid editor_mode's are 'plain', 'normal', 'full', and 'inline'
	$messagecontrol = array("HtmlTextArea", "subtype" => $subtype, "rows" => 20, 'editor_mode' => 'full');
}
$messagecontrol['editor_mode'] = $fromstationery?'inline':'normal';

$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. For ".
	"helpful message tips and ideas, click the Help link in the upper right corner of the screen.<br><br>If you would ".
	"like to insert dynamic data fields, such as the recipient's name, move the cursor to the location where the data ".
	"should be inserted, select the data field, and click 'Insert'. It's a good idea to enter a default value in the ".
	"Default Value field for each insert. This value will be displayed in the event of a recipient having no data in your chosen field.");
$formdata["message"] = array(
	"label" => _L("Email Message"),
	"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at ".
		"the Help link in the upper right corner.'),
	"value" => $text,
	"validators" => array(
		array("ValRequired"),
		array("ValMessageBody", "messagegroupid" => $messagegroup->id),
		array("ValLength","max" => 256000)
	),
	"control" => $messagecontrol,
	"helpstep" => 5
);
$helpsteps[] = _L("Click the preview button to view of your message.");

$formdata["preview"] = array(
	"label" => "",
	"value" => "",
	"validators" => array(),
	"control" => array("PreviewButton",
		"subtype" => $subtype,
		"language" => $languagecode,
		"fromnametarget" => "fromname",
		"fromtarget" => "from",
		"subjecttarget" => "subject",
		"texttarget" => "message",
	),
	"helpstep" => 6
);


$buttons = array(submit_button(_L('Done'),"submit","tick"));
$form = new Form("emaileedit",$formdata,$helpsteps,$buttons);

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
		if ($message &&
				$postdata['fromname'] == $fromname && 
				$postdata['from'] == $fromemail &&
				$postdata['subject'] == $subject &&
				json_decode($postdata['attachments'], true) == $attachments &&
				$postdata['message'] == $text) {
			// DO NOT UPDATE MESSAGE!
		} else if ($button != 'inpagesubmit') {
			Query("BEGIN");
			
			$messagegroup->modified = date("Y-m-d H:i:s", time());
			$messagegroup->update(array("modified"));
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
			$message->subject = $postdata["subject"];
			$message->fromname = $postdata["fromname"];
			$message->fromemail = $postdata["from"];
			$message->fromstationery = isset($_SESSION['editmessage']['stationeryid'])?$_SESSION['editmessage']['stationeryid']:0;
						
			$message->stuffHeaders();
			
			if ($message->id)
				$message->update();
			else
				$message->create();
						
			// create the message parts
			$message->recreateParts($postdata['message'], null, false);
			
			// if there are message attachments, attach them
			$attachments = json_decode($postdata['attachments']);
			if ($attachments == null)
				$attachments = array();
			
			savaAttachments($attachments,$message);
			
			// Sync with other message subtype if it exists
			$message2 = $messagegroup->getMessage("email", $subtype=="html"?"plain":"html", $languagecode);
			if ($message2) {
				$message2->subject = $message->subject;
				$message2->fromname = $message->fromname;
				$message2->fromemail = $message->fromemail;
				$message2->stuffHeaders();
				$message2->update();
				
				savaAttachments($attachments,$message2);
			}
			
			$messagegroup->updateDefaultLanguageCode();
			
			Query("COMMIT");
		}
		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo(getEditMessageSendTo($messagegroup->id));
		else
			redirect(getEditMessageSendTo($messagegroup->id));
	}
}

function savaAttachments($attachments,$message) {
	// check for existing attachments
	$existingattachments = QuickQueryList("select contentid, id from messageattachment where messageid = ?", true, false, array($message->id));
	$existingattachmentstokeep = array();
	if ($attachments) {
		foreach ($attachments as $cid => $details) {
			// check if this is already attached.
			if (isset($existingattachments[$cid])) {
				$existingattachmentstokeep[$existingattachments[$cid]] = true;
				continue;
			} else {
				$msgattachment = new MessageAttachment();
				$msgattachment->messageid = $message->id;
				$msgattachment->contentid = $cid;
				$msgattachment->filename = $details->name;
				$msgattachment->size = $details->size;
				$msgattachment->create();
			}
		}
	}
	// remove attachments that are no longer attached
	foreach ($existingattachments as $cid => $attachmentid) {
		if (!isset($existingattachmentstokeep[$attachmentid])) {
			$attachment = new MessageAttachment($attachmentid);
			if ($attachment)
				QuickUpdate("delete from messageattachment where id = ?", false, array($attachment->id));
		}
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
if ($subtype == "plain")
	$TITLE = "Plain Email Editor";
else
	$TITLE = "Advanced Email Editor";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody", "ValEmailAttach")); ?>
</script>

<?
PreviewModal::includePreviewScript();

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
