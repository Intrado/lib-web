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

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/EmailMessageEditor.fi.php");
require_once("obj/InpageSubmitButton.fi.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendemail"))
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && $_GET['id'] != "new") {
	// this is an edit for an existing message
	$_SESSION['editmessage'] = array("messageid" => $_GET['id']);
	redirect("editmessageemail.php");
} else if (isset($_GET['languagecode']) && isset($_GET['mgid'])) {
	$_SESSION['editmessage'] = array(
		"messagegroupid" => $_GET['mgid'],
		"languagecode" => $_GET['languagecode']);
	
	// subtype is optional but will tell the form item if it should load the "plain" editor
	// default behavior loads the "html" editor
	if (isset($_GET['subtype']))
		$_SESSION['editmessage']['subtype'] = $_GET['subtype'];
	
	redirect("editmessageemail.php");
}

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
if (!userOwns("messagegroup", $messagegroup->id))
	redirect('unauthorized.php');

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

// no multi lingual and not default language code
if (!$USER->authorize("sendmulti") && $languagecode != Language::getDefaultLanguageCode())
	redirect('unauthorized.php');
	
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
if ($message) {
	// get the specific bits from the message if it exists
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$message->readHeaders();
	
	$text = Message::format($parts);
	
	$fromname = $message->fromname;
	$fromemail = $message->fromemail;
	$subject = $message->subject;
	
	// get the attachments
	$msgattachments = DBFindMany("MessageAttachment", "from messageattachment where not deleted and messageid = ?", false, array($message->id));
	foreach ($msgattachments as $msgattachment)
		$attachments[$msgattachment->contentid] = array("name" => $msgattachment->filename, "size" => $msgattachment->size);
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

$helpsteps[] = _L("You may attach up to three files that are up to 2MB each. For greater security, only certain types of files are accepted.<br><br><b>Note:</b> Some email accounts may not accept attachments above a certain size and may reject your message.");
$formdata["attachments"] = array(
	"label" => _L('Attachments'),
	"fieldhelp" => _L("You may attach up to three files that are up to 2MB each. For greater security, certain file types are not permitted. Be aware that some email accounts may not accept attachments above a certain size and may reject your message."),
	"value" => ($attachments?json_encode($attachments):"{}"),
	"validators" => array(array("ValEmailAttach")),
	"control" => array("EmailAttach"),
	"helpstep" => 4
);

$helpsteps[] = _L("Email message body text goes here. Be sure to introduce yourself and give detailed information. For helpful message tips and ideas, click the Help link in the upper right corner of the screen.
<br><br>If you would like to insert dynamic data fields, such as the recipient's name, move the cursor to the location where the data should be inserted, select the data field, and click 'Insert'.
It's a good idea to enter a default value in the Default Value field for each insert. This value will be displayed in the event of a recipient having no data in your chosen field.");
$formdata["message"] = array(
	"label" => _L("Email Message"),
	"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
	"value" => $text,
	"validators" => array(
		array("ValRequired"),
		array("ValMessageBody")
	),
	"control" => array("EmailMessageEditor", "subtype" => $subtype),
	"helpstep" => 5
);

$formdata["preview"] = array(
	"label" => "",
	"value" => "",
	"validators" => array(),
	"control" => array("InpageSubmitButton", "name" => "Preview with email template", "icon" => "email_open"),
	"helpstep" => 3
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
		
		if ($button == 'inpagesubmit') {
			$form->modifyElement("previewcontainer", "<script>popup('messageviewer.php', 800, 500);</script>");
			exit();
		}
		
		// if they didn't change anything, don't do anything
		if ($postdata['fromname'] == $fromname && 
				$postdata['from'] == $fromemail &&
				$postdata['subject'] == $subject &&
				json_decode($postdata['attachments'], true) == $attachments &&
				$postdata['message'] == $text) {
			// DO NOT UPDATE MESSAGE!
		} else {
			Query("BEGIN");
			
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
			$message->deleted = 0;
			$message->subject = $postdata["subject"];
			$message->fromname = $postdata["fromname"];
			$message->fromemail = $postdata["from"];
						
			$message->stuffHeaders();
			
			if ($message->id)
				$message->update();
			else
				$message->create();
						
			// create the message parts
			$message->recreateParts($postdata['message'], null, false);
			
			// check for existing attachments
			$existingattachments = QuickQueryList("select contentid, id from messageattachment where messageid = ? and not deleted", true, false, array($message->id));
			
			// if there are message attachments, attach them
			$attachments = json_decode($postdata['attachments']);
			if ($attachments == null) 
				$attachments = array();
	
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
						$msgattachment->deleted = 0;
						$msgattachment->create();
					}
				}
			}
			// remove attachments that are no longer attached
			foreach ($existingattachments as $cid => $attachmentid) {
				if (!isset($existingattachmentstokeep[$attachmentid])) {
					$attachment = new MessageAttachment($attachmentid);
					$attachment->deleted = 1;
					$attachment->update(); 
				}
			}	
			
			Query("COMMIT");
		}
		
		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo("mgeditor.php?id=".$messagegroup->id);
		else
			redirect("mgeditor.php?id=".$messagegroup->id);
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
<div id='previewcontainer'></div>
<?

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>