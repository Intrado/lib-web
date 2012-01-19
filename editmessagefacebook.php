<?
// Needs some GET request arguments. Either:
//	 id, where id is the message id to be edited
// or:
//   mgid, where mgid is the messagegroup that will own this message

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
require_once("obj/Voice.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValMessageBody.val.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!getSystemSetting('_hasfacebook', false) || !$USER->authorize("facebookpost"))
	redirect('unauthorized.php');

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && $_GET['id'] != "new") {
	// this is an edit for an existing message
	$_SESSION['editmessage'] = array("messageid" => $_GET['id']);
	redirect("editmessagefacebook.php");
} else if (isset($_GET['mgid'])) {
	$_SESSION['editmessage'] = array("messagegroupid" => $_GET['mgid']);
	redirect("editmessagefacebook.php");
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
} else {
	// not editing an existing message, check session data for new message bits
	if (isset($_SESSION['editmessage']['messagegroupid'])) {
		$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
	} else {
		// missing session data!
		redirect('unauthorized.php');
	}
}

// if the user doesn't own the parent message group, unauthorized!
if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted)
	redirect('unauthorized.php');

$text = "";
if ($message) {
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
}

$language = Language::getName(Language::getDefaultLanguageCode());

$formdata = array($messagegroup->name. " (". $language. ")");

$formdata = array(
	$messagegroup->name. " (". $language. ")",
	"message" => array(
		"label" => _L("Facebook Message"),
		"fieldhelp" => _L("Enter your message for Facebook here. Messages for Facebook must be less than 420 characters in length."),
		"value" => $text,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageBody", "messagegroupid" => $messagegroup->id)),
		"control" => array("TextArea","rows"=>10,"cols"=>50,"counter"=>420,"spellcheck" => true),
		"helpstep" => 1
	)
);

$helpsteps = array(_L("Enter the message as you would like it to appear on your Facebook page. Messages are limited to 420 characters in length."));
		
$buttons = array(submit_button(_L('Done'),"submit","tick"));
$form = new Form("facebookmessage",$formdata,$helpsteps,$buttons);

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
		if ($postdata['message'] == $text) {
			// DO NOT UPDATE MESSAGE!
		} else {
			// if they didn't change anything, don't do anything
			if ($postdata['message'] == $text) {
				// DO NOT UPDATE MESSAGE!
			} else {
				Query("BEGIN");
				
				$messagegroup->modified = date("Y-m-d H:i:s", time());
				$messagegroup->update(array("modified"));
			
				// if this is not an edit of an existing message
				if (!$message) {
					// does there already exist a facebook message? if so, edit it
					$message = $messagegroup->getMessage("post", "facebook", Language::getDefaultLanguageCode());
					// doesn't exist? create a new message
					if (!$message)
						$message = new Message();
				}
				
				$message->messagegroupid = $messagegroup->id;
				$message->type = "post";
				$message->subtype = "facebook";
				$message->autotranslate = 'none';
				$message->name = $messagegroup->name;
				$message->description = "Facebook Message";
				$message->userid = $USER->id;
				$message->modifydate = date("Y-m-d H:i:s");
				$message->languagecode = Language::getDefaultLanguageCode();
				$message->deleted = 0;
				
				if ($message->id)
					$message->update();
				else
					$message->create();
				
				// create the message part
				QuickUpdate("delete from messagepart where messageid = ?", false, array($message->id));
				$messagepart = new MessagePart();
				$messagepart->messageid = $message->id;
				$messagepart->type = "T";
				$messagepart->txt = $postdata['message'];
				$messagepart->sequence = 0;
				$messagepart->create();
				
				$messagegroup->updateDefaultLanguageCode();
				
				Query("COMMIT");
			}
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
$TITLE = "Facebook Message Editor";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody")); ?>
</script>
<?

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>