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
if(isset($_SESSION['messageid'])) {
	$message = new Message($_SESSION['messageid']);
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
		"fieldhelp" => "",
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
		"value" => $message->description,
		"validators" => array(),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"message" => array(
		"label" => _L("SMS Message"),
		"value" => $messagebody,
		"fieldhelp" => "Short text message that can be sent to mobile phones. Can not be longer than 160 characters.",
		"validators" => array(
			array("ValRequired"),
			array("ValLength","max"=>160)
		),
		"control" => array("TextArea","rows"=>10,"counter"=>160),
		"helpstep" => 2
	)
);

$helpsteps = array (
	_L('Use a discriptive name to be able to easaly find your message later.'),
	_L('Type your message') . '<ul><li>' . _L('Whom is it from.') . '<li>' . _L('Who is it for.') . '<li>' . _L('Keep it clear and simple') . '</ul>'
	
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
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
<? Validator::load_validators(array("ValDuplicateNameCheck")); ?>
</script>
<?

startWindow(_L('Message'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>