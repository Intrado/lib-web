<?php
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
require_once("obj/FormSwitcher.obj.php");
require_once("obj/MessageGroup.obj.php");

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
					"CallMe",
					"easycall"
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
// Custom Forms
////////////////////////////////////////////////////////////////////////////////
class AudioForm extends SwitchableForm {
	function AudioForm($formname, $messageGroup) {
		global $USER;

		$this->messageGroup = $messageGroup;

		$formdata = array(
			"callme" => array(
				"label" => _L('Voice Recording'),
				"value" => "",
				"validators" => array(
					array("ValCallMeMessage")
				),
				"control" => array(
					"CallMe",
					"phone" => Phone::format($USER->phone),
					"max" => getSystemSetting('easycallmax',10),
					"min" => getSystemSetting('easycallmin',10)
				),
				"renderoptions" => array(
					"icon" => false,
					"label" => false,
					"errormessage" => true
				),
				"helpstep" => 1
			)
		);
		parent::SwitchableForm($formname,$formdata);
	}

	// TODO: Make the correct authorization checks.
	function authorized() {
		global $USER;

		return $USER->authorize("starteasy");
	}

	function save() {
	}
}

class MessageGroupBasicsForm extends SwitchableForm {
	var $messageGroup;

	function MessageGroupBasicsForm($formname, $messageGroup) {
		$this->messageGroup = $messageGroup;

		$formdata = array();

		$formdata['name'] = array(
			"label" => _L('Message Group Name'),
			"value" => $this->messageGroup->name,
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata['description'] = array(
			"label" => _L('Message Group Description'),
			"value" => $this->messageGroup->description,
			"validators" => array(),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		parent::SwitchableForm($formname,$formdata);
	}

	// TODO: Make the correct authorization checks.
	function authorized() {
		global $USER;
	}

	function save() {
	}
}

$messageGroup = new MessageGroup();

$formstructure = array(
	'_form' => new MessageGroupBasicsForm('messagegroupbasics', $messageGroup),
	'destinations' => array(
		'_layout' => 'horizontaltabs',
		'phone' => array(
			'_title' => 'Phone',
			'languages' => array(
				'_layout' => 'verticaltabs',
				'en' => array(
					'_title' => 'English',
					'_icon' => 'img/icons/diagona/16/160.gif',
					'_layout' => 'verticalsplit',
					'messagebody' => 'TESTESTES',
					'tools' => array(
						'_layout' => 'accordion',
						'audio' => array(
							'_title' => 'Audio',
							'_form' => new AudioForm('phoneenvoiceaudio', $messageGroup),
							'_parentformname' => 'messagegroupbasics'
						),
						'datafields' => array(
							'_title' => 'Data Fields'
						)
					)
				),
				'es' => array(
					'_title'=> 'Spanish',
					'_layout' => 'verticalsplit',
					'tools' => array(
						'_layout' => 'accordion',
						'audio' => array(),
						'datafields' => array(),
						'translation' => array()
					)
				)
			)
		),
		'email' => array(
			'_title' => 'Email',
			'emailheaders' => '',
			'subtypes' => array(
				'_layout' => 'horizontaltabs',
				'html' => array(
					'_title' => 'Html',
					'languages' => array(
						'_layout' => 'verticaltabs',
						'en' => array(
							'_title' => 'English',
							'_layout' => 'verticalsplit',
							'messagebody' => '',
							'tools' => array(
								'_layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
							)
						),
						'es' => array(
							'_layout' => 'verticalsplit',
							'_title'=> 'Spanish',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
								'translation' => array()
							)
						)
					)
				),
				'plain' => array(
					'_title' => 'Plain',
					'languages' => array(
						'_layout' => 'verticaltabs',
						'en' => array(
							'_title' => 'English',
							'_layout' => 'verticalsplit',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
							)
						),
						'es' => array(
							'_title'=> 'Spanish',
							'_layout' => 'verticalsplit',
							'tools' => array(
								'layout' => 'accordion',
								'attachments' => array(),
								'datafields' => array(),
								'translation' => array()
							)
						)
					)
				)
			)
		),
		'sms' => array(
			'_title' => 'SMS'
		),
		'summary' => array(
			'_title' => 'Summary'
		)
	)
);

$formswitcher = new FormSwitcher('messagegroup', $formstructure);

$formswitcher->handleRequest();



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = 'Message Group Editor';

include_once('nav.inc.php');
?>

<script type="text/javascript">
	<?php Validator::load_validators(array("ValCallMeMessage")); ?>
</script>
<script src="script/accordion.js" type="text/javascript"></script>

<?php
startWindow(_L('Message Group Editor'));
	echo $formswitcher->render();
endWindow();
include_once('navbottom.inc.php');
?>