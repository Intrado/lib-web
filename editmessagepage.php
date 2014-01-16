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
require_once("obj/MessageAttachment.obj.php");
require_once("obj/ContentAttachment.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/EmailAttach.val.php");
require_once("obj/EmailAttach.fi.php");
require_once("obj/HtmlTextArea.fi.php");

require_once("inc/editmessagecommon.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
// page posting is allowed if facebook, twitter or feed is allowed
if (!(getSystemSetting('_hasfacebook', false) && $USER->authorize('facebookpost')) &&
		!(getSystemSetting('_hastwitter', false) && $USER->authorize('twitterpost')) &&
		!(getSystemSetting('_hasfeed', false) && $USER->authorize('feedpost')))
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
if (!userOwns("messagegroup", $messagegroup->id))
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

$text = "";
$attachments = array();
if ($message) {
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
	// get the attachments
	$msgattachments = $message->getContentAttachments();
	foreach ($msgattachments as $msgattachment)
		$attachments[$msgattachment->contentid] = array("name" => $msgattachment->filename, "size" => $msgattachment->size);
}

$language = Language::getName(Language::getDefaultLanguageCode());

$helpsteps = array();
$formdata = array($messagegroup->name. " (". $language. ")");


// PAGE BODY
$helpsteps[] = _L("<p>Page messages allow you to share messages which are too large for social media sites. You may use this feature to create".
	" a web page with your message and then post a link to the web page on your social media pages.".
	"</p><p>Page messages may be viewed by anyone who can view your social media pages. For that reason, dynamic data fields may not be included.".
	" You may include audio in your Page message by adding Page Media from the Message Editor.");

$formdata["message"] = array(
	"label" => _L("Page Message"),
	"fieldhelp" => _L("Enter the message that you would like to have appear on the web page."),
	"value" => $text,
	"validators" => array(
		array("ValRequired"),
		array("ValMessageBody", "messagegroupid" => $messagegroup->id)
	),
	"control" => array("HtmlTextArea", 'editor_mode' => 'plain'),
	"helpstep" => 1
);


// ATTACHMENTS
$helpsteps[] = _L("<p>You may attach up to five files, such as PDFs, to your Page for recipients to download.".
	" People who view your page will be able to download these files from links that are automatically ".
	"generated in the Page.</p><p>Files may not exceed 50MB in size.</p>");

$formdata["attachments"] = array(
	"label" => _L('Attachments'),
	"fieldhelp" => _L("You may attach up to five files that are up to 50MB each. For greater security, certain file types are not permitted."),
	"value" => ($attachments?json_encode($attachments):"{}"),
	"validators" => array(array("ValEmailAttach", "maxattachments" => 5)),
	"control" => array("EmailAttach", "maxattachmentsize" => 50*1024*1024),
	"helpstep" => 2
);

		
$buttons = array(submit_button(_L('Done'),"submit","tick"));
$form = new Form("pagemessage",$formdata,$helpsteps,$buttons,"vertical");

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
		
		Query("BEGIN");
		
		$messagegroup->modified = date("Y-m-d H:i:s", time());
		$messagegroup->update(array("modified"));
		
		// if this is not an edit of an existing message
		if (!$message) {
			// does there already exist a page message? if so, edit it
			$message = $messagegroup->getMessage("post", "page", Language::getDefaultLanguageCode());
			// doesn't exist? create a new message
			if (!$message)
				$message = new Message();
		}
		
		$message->messagegroupid = $messagegroup->id;
		$message->type = "post";
		$message->subtype = "page";
		$message->autotranslate = 'none';
		$message->name = $messagegroup->name;
		$message->description = "Page Message";
		$message->userid = $USER->id;
		$message->modifydate = date("Y-m-d H:i:s");
		$message->languagecode = Language::getDefaultLanguageCode();
		
		if ($message->id)
			$message->update();
		else
			$message->create();
		
		// TODO: this isn't really right... need a special parse that won't check for field inserts?
		$message->recreateParts($postdata['message'], null, null);

		// if there are message attachments, attach them
		$attachments = json_decode($postdata['attachments'], true);
		if ($attachments == null) 
			$attachments = array();
		$message->replaceContentAttachments($attachments);
		
		$messagegroup->updateDefaultLanguageCode();
		
		Query("COMMIT");
		
		// remove the editors session data
		unset($_SESSION['editmessage']);
		
		if ($ajax)
			$form->sendTo(getEditMessageSendTo($messagegroup->id));
		else
			redirect(getEditMessageSendTo($messagegroup->id));
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = "Page Message Editor";

include_once("nav.inc.php");

// Optional Load Custom Form Validators
?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody", "ValEmailAttach")); ?>
</script>
<?

startWindow($messagegroup->name);
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
