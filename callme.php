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
require_once("obj/Phone.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Get requests
////////////////////////////////////////////////////////////////////////////////
if(isset($_GET['origin']))
	$origin = $_GET['origin'];
else
	$origin = "messages";

////////////////////////////////////////////////////////////////////////////////
// Custom Form Items
////////////////////////////////////////////////////////////////////////////////
class CallMe extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$nophone = _L("Phone Number");
		$defaultphone = escapehtml((isset($this->args['phone']) && $this->args['phone'])?Phone::format($this->args['phone']):$nophone);
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
		<div>
			<div id="'.$n.'_messages" style="padding: 6px; white-space:nowrap"></div>
			<div id="'.$n.'_altlangs" style="clear: both; padding: 5px; display: none"></div>
		</div>
		';
		// include the easycall javascript object and setup to record
		$str .= '<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript">
				var msgs = '.$value.';
				// Load default. it is a special case
				new Easycall(
					"'.$this->form->name.'",
					"'.$n.'",
					"Default",
					"'.((isset($this->args['min']) && $this->args['min'])?$this->args['min']:"10").'",
					"'.((isset($this->args['max']) && $this->args['max'])?$this->args['max']:"10").'",
					"'.$defaultphone.'",
					"'.$nophone.'",
					"CallMe"
				).load();
			</script>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Custom Validators
////////////////////////////////////////////////////////////////////////////////
class ValCallMeMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return "$this->label "._L("is not allowed for this user account");
		$values = json_decode($value);
		if ($value == "{}")
			return "$this->label "._L("has messages that are not recorded");
		if (!$values->Default)
			return "$this->label "._L("has messages that are not recorded");
		$msg = new Message($values->Default +0);
		if ($msg->userid !== $USER->id)
			return "$this->label "._L("has invalid message values");
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$helpsteps = array ();

$formdata = array(
	"messagename" => array(
		"label" => _L('Message Name'),
		"value" => "",
		"validators" => array(
			array("ValLength", "max" => 30)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 30),
		"helpstep" => 1
	),
	"callme" => array(
		"label" => _L('Voice Recording'),
		"value" => "",
		"validators" => array(
			array("ValCallMeMessage"),
			array("ValRequired")
		),
		"control" => array(
			"CallMe",
			"phone" => Phone::format($USER->phone),
			"max" => getSystemSetting('easycallmax',10),
			"min" => getSystemSetting('easycallmin',10)
		),
		"helpstep" => 1
	)
);
$helpsteps[0] = _L('Enter a message name and a phone number. Then click the Call Me To Record button. You will be prompted to record a new audio message over the phone. Once you complete this process, click the Save button');


$buttons = array(submit_button(_L("Save"),"submit","tick"),icon_button(_L('Cancel'),"cross",null,"$origin.php"));
$form = new Form("callme",$formdata,$helpsteps,$buttons);
$form->ajaxsubmit = true;

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();

$datachange = false;
$errors = false;
//check for form submission
if ($button = $form->getSubmit()) {
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response
	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		if ($postdata['messagename']) {
			$value = json_decode($postdata['callme']);
			$message = new Message($value->Default + 0);
			$messagename = trim($postdata["messagename"])?trim($postdata["messagename"]):"Call Me" . " - " . date("M j, Y G:i:s");
			if(QuickQuery("Select count(*) from message where userid=? and not deleted and name =?", false, array($USER->id, $messagename)))
				$messagename = $messagename . " - " . date("M j, Y G:i:s");

			$message->name = $messagename;
			$message->update();
			$afid = QuickQuery("select audiofileid from messagepart where messageid=? limit 1", false, array($message->id));
			$audiofile = new AudioFile($afid);
			$audiofile->name = $messagename;
			$audiofile->update();
		}
		if ($ajax)
			$form->sendTo("$origin.php");
		else
			redirect("$origin.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = 'Call Me';

include_once('nav.inc.php');

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValCallMeMessage")); ?>
</script>
<?

startWindow(_L('Message Information'));
echo $form->render();
endWindow();
include_once('navbottom.inc.php');
?>
