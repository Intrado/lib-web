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
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if (getSystemSetting('_hassms', false) && $USER->authorize("sendsms") === false) {
	redirect('./');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION['messageid'] = NULL;
	}
	else
		setCurrentMessage($_GET['id']);
	redirect("messagesms.php");
} 

////////////////////////////////////////////////////////////////////////////////
// Form Items And Validators
////////////////////////////////////////////////////////////////////////////////


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$messagebody = '';
$permanent = 0;


if(isset($_SESSION['messageid'])) {
	$message = new Message($_SESSION['messageid']);
	$permanent = $message->permanent;
	$message->readHeaders();	
	$part = DBFind("MessagePart","from messagepart where messageid=?order by sequence limit 1",false,array($message->id));
	if($part)
		$messagebody = $part->txt;
	//$messagebody = QuickQuery("select txt from messagepart where messageid=? order by sequence limit 1",false,array($message->id));
} else {
	$message = new Message();	
}


$formdata = array(
	"messagename" => array(
		"label" => _L('Message Name'),
		"fieldhelp" => _L('The name of your message goes here. The best names describe the message content.'),
		"value" => $message->name,
		"validators" => array(
			array("ValRequired","ValLength","min" => 3,"max" => 50),
			array("ValDuplicateNameCheck","type" => "sms")
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
	"autoexpire" => array(
		"label" => _L('Auto Expire'),
		"fieldhelp" => _L('Selecting Yes will allow the system to delete this message after %1$s months if it is not associated with any active jobs.', getSystemSetting('softdeletemonths', "6")),
		"value" => $permanent,
		"validators" => array(),
		"control" => array("RadioButton", "values" => array(0 => "Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",1 => "No (Keep forever)")),
		"helpstep" => 1
	),
	"message" => array(
		"label" => _L("SMS Message"),
		"value" => $messagebody,
		"fieldhelp" => "Short text message that can be sent to mobile phones. These messages cannot be longer than 160 characters.",
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max"=>160),
			array("ValRegExp","pattern" => "^[a-zA-Z0-9\x20\x09\x0a\x0b\x0C\x0d\x2a\x5e\<\>\?\,\.\/\{\}\|\~\!\@\#\$\%\&\(\)\_\+\']*$")
		),
		"control" => array("TextArea","rows"=>10,"counter"=>160),
		"helpstep" => 2
	)
);

$helpsteps = array (
	_L('Enter a name for your message. The best names are descriptive and allow the message to be easily reused later. You can also optionally enter a description for your message.<br><br>If this message may be deleted after %1$s months of inactivity, select Yes in the Auto Expire section. If you need this message to be stored forever, select No.', getSystemSetting('softdeletemonths', "6")),
	_L('Enter your message here. Remember, an SMS message is limited to 160 characters. Try to keep your message clear and simple. Make sure to indicate who you are and who your intended audience is.') 	
 );

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"messages.php"));
$form = new Form("smsform",$formdata,$helpsteps,$buttons);

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
		$message->type = "sms";
		
		$message->name = trim($postdata["messagename"]);
		$message->description = trim($postdata["description"]);
		$message->permanent = $postdata["autoexpire"]!=1?0:1;
		$message->modifydate = QuickQuery("select now()");
		$message->userid = $USER->id;
		$message->stuffHeaders();
		$message->update();
		
		if(!isset($part) || !$part) {
			$part = new MessagePart();
		}
		$part->messageid = $message->id;
		$part->txt = trim($postdata["message"]);
		$part->type="T";
		$part->sequence = 0;
		$part->update();
		
		//save data here	
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
$TITLE = _L('SMS Message Builder: ') . (isset($_SESSION['messageid'])? escapehtml($message->name) : _L("New Message") );
$ICON = "sms.gif";

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValDuplicateNameCheck","ValRegExp")); ?>
</script>
<?

startWindow(_L('Message'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>