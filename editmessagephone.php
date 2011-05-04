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
require_once("obj/Voice.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Phone.obj.php");

// form items/validators
require_once("obj/FormItem.obj.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/PhoneMessageEditor.fi.php");
require_once("obj/InpageSubmitButton.fi.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
global $USER;
if (!$USER->authorize("sendphone"))
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['id']) && $_GET['id'] != "new") {
	// this is an edit for an existing message
	$_SESSION['editmessage'] = array("messageid" => $_GET['id']);
	redirect("editmessagephone.php");
} else if (isset($_GET['languagecode']) && isset($_GET['mgid'])) {
	$_SESSION['editmessage'] = array(
		"messagegroupid" => $_GET['mgid'],
		"languagecode" => $_GET['languagecode']);
	redirect("editmessagephone.php");
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
if (!userOwns("messagegroup", $messagegroup->id))
	redirect('unauthorized.php');

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

$text = "";
$gender = "";
if ($message) {
	$parts = DBFindMany("MessagePart", "from messagepart where messageid = ? order by sequence", false, array($message->id));
	$text = Message::format($parts);
	// find the gender
	foreach ($parts as $part) {
		if ($part->voiceid) {
			$voice = new Voice($part->voiceid);
			$gender = $voice->gender;
			break;
		}
	}
}

$language = Language::getName($languagecode);

$formdata = array($messagegroup->name. " (". $language. ")");

// upload audio needs this session data
$_SESSION['messagegroupid'] = $messagegroup->id;

$formdata = array(
	$messagegroup->name. " (". $language. ")",
	"message" => array(
		"label" => _L("Advanced Message"),
		"value" => $text,
		"validators" => array(array("ValRequired")),
		"control" => array("PhoneMessageEditor", "langcode" => $languagecode, "messagegroupid" => $messagegroup->id),
		"helpstep" => 1
	),
	"gender" => array(
		"label" => _L("Gender"),
		"value" => $gender,
		"validators" => array(array("ValRequired")),
		"control" => array("RadioButton", "values" => array("female" => _L("Female"), "male" => _L("Male"))),
		"helpstep" => 2
	),
	"preview" => array(
		"label" => "",
		"value" => "",
		"validators" => array(),
		"control" => array("InpageSubmitButton", "name" => "preview", "icon" => "fugue/control"),
		"helpstep" => 3
	)
);

$helpsteps = array(_L("TODO: Help me!"),
				_L("TODO: Help me!"),
				_L("TODO: Help me!"));
		
$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"mgeditor.php?id=".$messagegroup->id));
$form = new Form("phoneadvanced",$formdata,$helpsteps,$buttons,"vertical");

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
		if ($postdata['message'] == $text && $postdata['gender'] == $gender) {
			// DO NOT UPDATE MESSAGE!
		} else {
			Query("BEGIN");
			
			// if this is an edit for an existing message
			if ($message) {
				// delete existing messages
				QuickUpdate("update message set deleted = 1 
						where messagegroupid = ?
						and type = 'phone'
						and languagecode = ?", false, array($messagegroup->id, $languagecode));
			} else {
				// new message
				$message = new Message();
			}
			
			$message->messagegroupid = $messagegroup->id;
			$message->type = "phone";
			$message->subtype = "voice";
			$message->autotranslate = ($languagecode == "en")?'none':'overridden';
			$message->name = $messagegroup->name;
			$message->description = Language::getName($languagecode);
			$message->userid = $USER->id;
			$message->modifydate = date("Y-m-d H:i:s");
			$message->languagecode = $languagecode;
			$message->deleted = 0;
			
			if ($message->id)
				$message->update();
			else
				$message->create();
						
			// create the message parts
			$message->recreateParts($postdata['message'], null, $postdata['gender']);
					
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
$TITLE = "Advanced Phone Message Editor";

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