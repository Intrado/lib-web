<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
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
require_once("obj/MessageAttachment.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageBody.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/EmailAttach.val.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendemail')) {
	redirect('./');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION['messageid'] = NULL;
		if (isset($_SESSION['emailattachment'])) {
			unset($_SESSION['emailattachment']);
		}
	}
	else
		setCurrentMessage($_GET['id']);
	redirect("messageemail.php");
} 


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////




////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$messagebody = '';

if(isset($_SESSION['messageid'])) {
	$message = new Message($_SESSION['messageid']);
	$message->readHeaders();	
	$parts = DBFindMany("MessagePart","from messagepart where messageid=$message->id order by sequence");
	$messagebody = $message->format($parts);
	
	$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid=" . DBSafe($_SESSION['messageid']));
	$attachvalues = array();
	foreach ($attachments as $attachment) {
		$attachvalues[$attachment->contentid] = array("size" => $attachment->size, "name" => $attachment->filename);
	}
} else {
	$message = new Message();
	$message->fromname = $USER->firstname . " " . $USER->lastname;
	$useremails = explode(";", $USER->email);
	$message->fromemail = $useremails[0];	
}



$insertfields = FieldMap::getAuthorizedMapNames();

$formdata = array(
	"messagename" => array(
		"label" => _L('Message Name'),
		"fieldhelp" => _L('The name of your message goes here. The best names describe the message content.'),
		"value" => $message->name,
		"validators" => array(
			array("ValRequired","ValLength","min" => 3,"max" => 50),
			array("ValDuplicateNameCheck","type" => "email")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"fieldhelp" => _L('Enter an optional description.'),
		"value" => $message->description,
		"validators" => array(),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"&nbsp;",
	"fromname" => array(
		"label" => _L('From Name'),
		"fieldhelp" => _L('Recipients will see this name as the sender of the email.'),
		"value" => $message->fromname,
		"validators" => array(array("ValRequired","ValLength","min" => 3,"max" => 50)),
		"control" => array("TextField","size" => 25, "maxlength" => 51),
		"helpstep" => 2
	),
	"fromemail" => array(
		"label" => _L('From Email'),
		"fieldhelp" => _L('This is the address the email will appear to originate from. Recipients may reply to this message at this email address.'),
		"value" => $message->fromemail,
		"validators" => array(
					array("ValRequired"),
					array("ValEmail","domain" => getSystemSetting('emaildomain'))),
		"control" => array("TextField","size" => 40, "maxlength" => 200),
		"helpstep" => 2
	),
	"subject" => array(
		"label" => _L('Subject'),
		"fieldhelp" => _L("The subject will be the first thing an email recipient sees. It should be brief and descriptive."),
		"value" => $message->subject,
		"validators" => array(array("ValRequired","ValLength","min" => 1,"max" => 50)),
		"control" => array("TextField","size" => 50, "maxlength" => 100),
		"helpstep" => 2
	),	
	"attachements" => array(
		"label" => _L('Attachments'),
		"fieldhelp" => "You may attach up to three files that are up to 2048kB each. Note: Some recipients may have different size restrictions on incoming mail which can cause them to not receive your message if you have attached large files.",
		"value" => $attachvalues,
		"validators" => array(array("ValEmailAttach")),
		"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
		"helpstep" => 3
	),
	"messagebody" => array(
		"label" => _L('Message Body'),
		"fieldhelp" => _L("The body of your e-mail can contain text as well as dynamic data elements."),
		"value" => $messagebody,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageBody")
		),
		"control" => array("MessageBody","fields" => $insertfields,"playbutton" => false),
		"helpstep" => 4
	)
);

$helpsteps = array (
	_L('Enter a name for your message. The best names are descriptive and allow the message to be easily reused later. You can also optionally enter a description for your message.'),
	_L('You can specify who the email is coming from in this section. Then enter the subject line for the email which recipients will see in their inboxes.'),
	_L('You may attach files up to 2Mb in size. You should mention the attachment in the body of the email.'). "<br><br><b>". _L('Note'). ":</b>". _L('Some recipients may have size restrictions on incoming emails and may not receive emails with large attachments.'),
	_L('Type your message in the Message Body field. Additional tips for successful messages can be found at the Help link in the upper right corner.'). "<br><br>". _L("If you would like to personalize your message with the recipient's data, set the cursor at the point in the message where the data should be inserted, choose the field you would like to use, and click enter. You should also enter a default value for any contacts who are missing data for the field you have selected.")
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"messages.php"));
$form = new Form("phonemessage",$formdata,$helpsteps,$buttons);

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
			
		$message = new Message($_SESSION['messageid']);
		$message->readHeaders();
		$message->type = "email";
		
		//check that the message->userid == user->id so that there is no chance of hijacking
		if ($message->id && !userOwns("message",$message->id) || $message->deleted ) {
			exit("nope!"); //TODO
		}
		$parts = $message->parse($postdata["messagebody"]);

		$message->name = trim($postdata["messagename"]);
		$message->description = trim($postdata["description"]);
		$message->modifydate = QuickQuery("select now()");
		$message->subject = trim($postdata["subject"]);
		$message->fromname = trim($postdata["fromname"]);
		$message->fromemail = trim($postdata["fromemail"]);
		$message->userid = $USER->id;
		$message->stuffHeaders();
		$message->update();
		
		Query("BEGIN");		
		//update the parts
		QuickUpdate("delete from messagepart where messageid=$message->id");
		foreach ($parts as $part) {
			$part->messageid = $message->id;
			$part->create();
		}

		QuickUpdate("delete from messageattachment where messageid=?",false,array($_SESSION['messageid']));	
		//see if there is an uploaded file and add it to this email
		if (isset($postdata["attachements"])) {
			$emailattachments = json_decode($postdata["attachements"],true);
			
			foreach($emailattachments as $contentid => $attachment) {
				$msgattachment = new MessageAttachment();
				$msgattachment->messageid = $message->id;
				$msgattachment->contentid = $contentid;
				$msgattachment->filename = $attachment['name'];
				$msgattachment->size = $attachment['size'];
				$msgattachment->create();	
			}
		}
		Query("COMMIT");
		
		if ($ajax)
			$form->sendTo("messages.php");
		else
			redirect("messages.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = _L("notifications").":"._L("messages");
$TITLE = _L('Email Message Builder: ') . (isset($_SESSION['messageid'])? escapehtml($message->name) : _L("New Message") );
$ICON = "email.gif";

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody","ValDuplicateNameCheck","ValEmailAttach")); ?>
</script>
<?

startWindow(_L('Message'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>