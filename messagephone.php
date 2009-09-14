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
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageBody.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValDuplicateNameCheck.val.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone')) {
	redirect('./');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['deleteid'])) {
	//...
}
if (isset($_GET['id'])) {
	if($_GET['id'] == "new")
		$_SESSION['messageid'] = NULL;
	else
		setCurrentMessage($_GET['id']);
	redirect("messagephone.php");
}




////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$audiofiles = DBFindMany('AudioFile', "from audiofile where userid = $USER->id and deleted != 1 order by name");
$ttslanguages = Voice::getTTSLanguages();
$languages = array();
foreach($ttslanguages as $ttslanguage) {
	$languages[$ttslanguage] = $ttslanguage;
}

$messagename = "";
$messagedescription = "";
$messagebody = "";
$messagevoice = array("language" => "English","gender" => "Female");
$permanent = 0;


if(isset($_SESSION['messageid'])) {
	$message = new Message($_SESSION['messageid']);
	$messagename = $message->name;
	$messagedescription = $message->description;
	$permanent = $message->permanent;
	$message->readHeaders();
	$parts = DBFindMany("MessagePart","from messagepart where messageid=$message->id order by sequence");
	$messagebody = $message->format($parts);
	$messagevoice = QuickQueryRow("select language, gender from messagepart p, ttsvoice t where p.messageid=? and p.voiceid = t.id order by p.sequence limit 1",true,false,array($message->id));
}



$insertfields = FieldMap::getAuthorizedMapNames();

$formdata = array(
	"messagename" => array(
		"label" => _L('Message Name'),
		"fieldhelp" => _L('The name of your message goes here. The best names describe the message content.'),
		"value" => $messagename,
		"validators" => array(
			array("ValRequired","ValLength","min" => 3,"max" => 50),
			array("ValDuplicateNameCheck","type" => "phone")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"fieldhelp" => _L('Enter an optional description.'),
		"value" => $messagedescription,
		"validators" => array(),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),	
	"autoexpire" => array(
		"label" => _L('Auto Expire'),
		"fieldhelp" => _L('Automatically delete this message after %1$s months.', getSystemSetting('softdeletemonths', "6")),
		"value" => $permanent,
		"validators" => array(),
		"control" => array("RadioButton", "values" => array(0 => "Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",1 => "No (Keep forever)")),
		"helpstep" => 1
	),
	"&nbsp;"
	,
	"language" => array(
		"label" => _L('Language'),
		"fieldhelp" => _L('Select the appropriate text-to-speech language for the language in your message. Note: This is not for message translation.'),
		"value" => ucfirst($messagevoice["language"]),
		"validators" => array(array("ValRequired")),
		"control" => array("SelectMenu","values" => $languages),
		"helpstep" => 2
	),
	"voice" => array(
		"label" => _L('Preferred Voice'),
		"fieldhelp" => _L('Choose the gender of the text-to-speech voice.'),
		"value" => ucfirst($messagevoice["gender"]),
		"validators" => array(array("ValRequired")),
		"control" => array("RadioButton","values" => array ("Female" => "Female","Male" => "Male")),
		"helpstep" => 2
	),
	"messagebody" => array(
		"label" => _L('Message Content'),
		"fieldhelp" => _L('Enter the message you would like to send. Helpful tips for successful messages can be found at the Help link in the upper right corner.'),
		"value" => $messagebody,
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max" => 4000),
			array("ValMessageBody")
		),
		"control" => array("MessageBody", "audiofiles" => $audiofiles,"fields" => $insertfields),
		"helpstep" => 3
	)
);

$helpsteps = array (
	_L('Enter a name for your message. The best names are descriptive and allow the message to be easily reused later. You can also optionally enter a description for your message.'),
	_L('Select the language and gender of the text-to-speech voice.'). "<br><br> <b>". _L('Note'). ":</b> ". _L('The text-to-speech voice you choose should match the language of the message. This is not for translation.'),
	_L('Type your message in the Message Content field. Additional tips for successful messages can be found at the Help link in the upper right corner.'). "<br><br>". _L("If you would like to personalize your message with the recipient's data, set the cursor at the point in the message where the data should be inserted, choose the field you would like to use, and click enter. You should also enter a default value for any contacts who are missing data for the field you have selected."). "<br><br>". _L('Tip'). ": ". _L('For the best sounding text-to-speech, use good punctuation and simple sentences where possible.')
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

		
		$voiceid = QuickQuery("select id from ttsvoice where language=? and gender=?",false,array($postdata["language"],$postdata["voice"]));
		
		if($voiceid === false ) {
			if($postdata["voice"] == "Female") {
				$voiceid = QuickQuery("select id from ttsvoice where language=? and gender='Male'",false,array($postdata["language"]));
			} else if($postdata["voice"] == "Male") {
				$voiceid = QuickQuery("select id from ttsvoice where language=? and gender='Female'",false,array($postdata["language"]));	
			}
		}
		if($voiceid	=== false)
			$voiceid = 1; // default to english	
		
			
		$message = new Message($_SESSION['messageid']);
		$message->readHeaders();
		$message->type = "phone";
		
		//check that the message->userid == user->id so that there is no chance of hijacking
		if ($message->id && !userOwns("message",$message->id) || $message->deleted ) {
			exit("nope!"); //TODO
		}
		$errors = array();	
		$parts = $message->parse($postdata["messagebody"],$errors,$voiceid);

		$message->name = trim($postdata["messagename"]);
		$message->description = trim($postdata["description"]);
		$message->permanent = $postdata["autoexpire"]!=1?0:1;
		$message->userid = $USER->id;
		$message->modifydate = QuickQuery("select now()");
		$message->stuffHeaders();
		$message->update();
		
		//update the parts
		QuickUpdate("delete from messagepart where messageid=$message->id");
		foreach ($parts as $part) {
			if(!isset($part->voiceid))
				$part->voiceid = $voiceid;
			$part->messageid = $message->id;
			$part->create();
		}
		
		if ($ajax)
			$form->sendTo("messages.php");
		else
			redirect("messages.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_somefield ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = _L("notifications").":"._L("messages");
$TITLE = _L('Phone Message Builder: ') . (isset($_SESSION['messageid'])? escapehtml($message->name) : _L("New Message") );
$ICON = "phone.gif";

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody","ValDuplicateNameCheck")); ?>
</script>
<?

startWindow(_L('Message'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>