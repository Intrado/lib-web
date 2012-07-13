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
require_once("obj/FieldMap.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/ValMessageBody.val.php");
require_once("inc/appserver.inc.php");

require_once("inc/editmessagecommon.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hasfeed', false) || !$USER->authorize('feedpost'))
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
// Form Data
////////////////////////////////////////////////////////////////////////////////
// get value from passed message, or default some values if not set
$subject = "";
$text = "";
if ($message) {
	// get the specific bits from the message if it exists
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$message->readHeaders();

	$text = Message::format($parts);

	$subject = $message->subject;
}

$language = Language::getName(Language::getDefaultLanguageCode());
$formdata = array(
	$messagegroup->name. " (". $language. ")",
	"subject" => array(
		"label" => _L("Headline"),
		"fieldhelp" => _L('This will appear as the title of the feed item.'),
		"value" => $subject,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 255)),
		"control" => array("TextField","max"=>255,"min"=>3,"size"=>45),
		"helpstep" => 1
	),
	"message" => array(
		"label" => _L("Feed Message"),
		"fieldhelp" => _L("Enter the content of the feed item here."),
		"value" => $text,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 32000),
			array("ValMessageBody", "messagegroupid" => $messagegroup->id)),
		"control" => array("TextArea", "rows"=>10, "cols"=>50, "spellcheck" => true),
		"helpstep" => 2
	)
);


$helpsteps = array (
	_L('The Headline will appear as the title of the feed item.'),
	_L('The content of the feed item should be entered here.')
);

$buttons = array(submit_button(_L('Done'),"submit","tick"));
$form = new Form("feedform",$formdata,$helpsteps,$buttons);

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
		Query("BEGIN");
		
		$messagegroup->modified = date("Y-m-d H:i:s", time());
		$messagegroup->update(array("modified"));
				
		// if this is not an edit of an existing message
		if (!$message) {
			// does there already exist a feed message? if so, edit it
			$message = $messagegroup->getMessage("post", "feed", Language::getDefaultLanguageCode());
			// doesn't exist? create a new message
			if (!$message)
				$message = new Message();
		}
		
		$message->messagegroupid = $messagegroup->id;
		$message->type = "post";
		$message->subtype = "feed";
		$message->autotranslate = 'none';
		$message->name = $messagegroup->name;
		$message->description = "Feed Message";
		$message->userid = $USER->id;
		$message->modifydate = date("Y-m-d H:i:s");
		$message->languagecode = Language::getDefaultLanguageCode();
		$message->subject = $postdata["subject"];
		
		$message->stuffHeaders();
		
		if ($message->id)
			$message->update();
		else
			$message->create();
		
		// TODO: this isn't really right... need a special parse that won't check for field inserts?
		$message->recreateParts($postdata['message'], null, null);
		
		$messagegroup->updateDefaultLanguageCode();
		
		Query("COMMIT");
		
		// expire feeds categories pointing at this message group.
		$categories = QuickQueryList("select destination from jobpost where type = 'feed' and jobid in (select id from job where messagegroupid = ?)", false, false, array($messagegroup->id));
		if (count($categories))
		expireFeedCategories($CUSTOMERURL, $categories);
		
		if ($ajax)
			$form->sendTo(getEditMessageSendTo($messagegroup->id));
		else
			redirect(getEditMessageSendTo($messagegroup->id));
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L("Feed Message Editor");

include_once("nav.inc.php");

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