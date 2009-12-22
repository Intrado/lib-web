<?php
// TODO, FormJS BUG: enter a validation error in callme, switch to email tab, then switch back to phone tab, notice there is a javascript error about 'variable msg is null'
// TODO, Usability BUG: If the session is timed out, the page will show alert() messages for each form that is submitted, making it annoying..
// TODO, Usability BUG: If multiple forms have validation errors, submitting the page will cause multiple alerts() to appear, making it annoying..
// TODO, Usability Question: Should a user be able to edit a deleted messagegroup?
// TODO, Usability: If the phone language does not have a tts voice, show a warning that the defaultlanguage voice will be used.


////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/translate.inc.php");
require_once("obj/Form.obj.php");
require_once("obj/FormTabber.obj.php");
require_once("obj/FormSplitter.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageBody.fi.php");
require_once("obj/Validator.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/MessageGroup.obj.php");

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

///////////////////////////////////////////////////////////////////////////////
// Authorization:
// Kick the user out only if he does not have permission to create any message at all (phone, email, sms).
///////////////////////////////////////////////////////////////////////////////
$cansendphone = $USER->authorize('sendphone');
$cansendemail = $USER->authorize('sendemail');
$cansendsms = getSystemSetting('_hassms', false) && $USER->authorize('sendsms');
$cansendmultilingual = $USER->authorize('sendmulti');

if (!$cansendphone && !$cansendemail && !$cansendsms) {
	unset($_SESSION['messagegroupid']);
	redirect('unauthorized.php');
}

///////////////////////////////////////////////////////////////////////////////
// Request processing:
// For a new messagegroup, it is first created in the database as deleted and non-permanent
// in case the user does not submit the form. Once the form is submitted, the
// messagegroup is set as not deleted; the permanent flag is toggled by the user.
///////////////////////////////////////////////////////////////////////////////
if (isset($_GET['messagegroupid'])) {
	unset($_SESSION['messagegroupid']);
	if ($messagegroupid = $_GET['messagegroupid'] + 0) {
		if (userOwns('messagegroup', $messagegroupid))
			$_SESSION['messagegroupid'] = $messagegroupid;
		else
			redirect('unauthorized.php');
	} else { // URL: ?messagegroupid=new
		// Continue below, where a new messagegroup is created because the session variable is not set.
	}
}

if (isset($_SESSION['messagegroupid'])) {
	$messagegroup = new MessageGroup($_SESSION['messagegroupid']);
}

///////////////////////////////////////////////////////////////////////////////
// Constants.
///////////////////////////////////////////////////////////////////////////////
$readonly = false;
$defaultvoicegender = 'female';
$defaultautotranslate = 'none';
$defaultlanguagecode = 'en'; // TODO: Mkae sure this is the correct language code for english.

$datafields = FieldMap::getAuthorizedMapNames();

// Get both global audiofiles and ones assigned to this messagegroup.
// Global audiofiles are not assigned to any messagegroup.
$audiofiles = DBFindMany('AudioFile2', "from audiofile where userid = $USER->id and (messagegroupid = ? or not messagegroupid) and deleted != 1 order by name", false, array(isset($messagegroup) ? $messagegroup->id : 0));

$customerlanguages = $cansendmultilingual ? QuickQueryList("select code, name from language", true) : QuickQueryList("select code, name from language where code=?", true, false, array($defaultlanguagecode));
$ttslanguages = $cansendmultilingual ? Voice::getTTSLanguages() : array();
unset($ttslanguages[$defaultlanguagecode]);
// NOTE: The customer may have a custom name for a particular language code.
$customertranslationlanguages = $cansendmultilingual ? array_intersect_key($customerlanguages, getTranslationLanguages()) : array();
unset($ttslanguages[$defaultlanguagecode]);

///////////////////////////////////////////////////////////////////////////////
// Data Gathering:
// $destinations is a tree that is populated according to the user's permissions; it contains destination types, subtypes, and languages.
///////////////////////////////////////////////////////////////////////////////
$destinations = array();

if ($cansendphone) {
	$destinations['phone'] = array(
		'subtypes' => array('voice'),
		'languages' => $customerlanguages
	);
}

if ($cansendemail) {
	$destinations['email'] = array(
		'subtypes' => array('html', 'plain'),
		'languages' => $customerlanguages
	);
}

if ($cansendsms) {
	$destinations['sms'] = array(
		'subtypes' => array('plain'),
		'languages' => array_splice($customerlanguages, $defaultlanguagecode, 1)
	);
}

///////////////////////////////////////////////////////////////////////////////
// Formdata
// TODO: If $readonly, make everything disabled, and don't submit the form.
///////////////////////////////////////////////////////////////////////////////
$destinationlayoutforms = array();
foreach ($destinations as $type => $destination) {
	
	$subtypelayoutforms = array();
	foreach ($destination['subtypes'] as $subtype) {
		
		$messageformsplitters = array();

		if (count($destination['languages']) > 1) {
			$messageformsplitters[] = array(
				"name" => $type . $subtype . 'autotranslate',
				"title" => "Autotranslate",
				"formdata" => array(
					"sourcetext" => array(
						"label" => _L('Voice Recording'),
						"value" => "",
						"validators" => array(),
						"control" => array(
							"CallMe",
							"phone" => Phone::format($USER->phone),
							"max" => getSystemSetting('easycallmax',10),
							"min" => getSystemSetting('easycallmin',10)
						),
						"helpstep" => 1
					)
				)
			);
		}
		
		foreach ($destination['languages'] as $languagecode => $languagename) {
			$messageformname = $type . $subtype . $languagecode;
			
			// Information to pull from the database.
			$autotranslate;
			$messagebody;
			$sourcetext;
			
			// Get the existing message for this type-subtype-languagecode.
			if ($mastermessage = DBFind('Message2', "from message where autotranslate in ('none', 'overridden') and type = ? and subtype = ? and languagecode = ? and userid = ? and messagegroupid = ? and deleted != 1", false, array($type, $subtype, $languagecode, $USER->id, $messagegroup->id))
			) {
				$autotranslate = $mastermessage->autotranslate;
				$messagebody = $mastermessage->format(DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($mastermessage->id)));
			} else {
				$autotranslate = $defaultautotranslate;
				$messagebody = '';
			}
			
			if ($sourcemessage = DBFind('Message2', "from message where autotranslate = 'source' and type = ? and subtype = ? and languagecode = ? and userid = ? and messagegroupid = ? and deleted != 1", false, array($type, $subtype, $languagecode, $USER->id, $messagegroup->id))
			) {
				$sourcetext = $sourcemessage->format(DBFindMany("MessagePart","from messagepart where messageid=? order by sequence", false, array($sourcemessage->id)));
			} else {
				$sourcetext = '';
			}
		
			$formdata = array("messagebody" => array(
				"label" => ucfirst($languagename),
				"value" => json_encode(array(
					"enabled" => true,
					"text" => $messagebody,
					"override" => false,
					"gender" => 'female'
				)),
				"validators" => array(),
				"control" => array("MessageBody2",
					"phone" => $type == 'phone',
					"language" => strtolower($languagename), // TODO: Update MessageBody to take languagecode.
					"sourcetext" => $sourcetext,
					"multilingual" => $type != 'sms',
					"subtype" => $subtype
				),
				"transient" => false,
				"helpstep" => 2
			));
			
			if ($type == 'email') {
				$accordionsplitter = new FormSplitter("", "", "accordion", array(), array(
					"test",
					"test",
					"test"
				));
			} else if ($type == 'phone') {
				$accordionsplitter = new FormSplitter("", "", "accordion", array(), array(
					array(
						"name" => $messageformname . "audio",
						"title" => "Audio",
						"formdata" => array(
							"callme" => array(
								"label" => _L('Voice Recording'),
								"value" => "",
								"validators" => array(
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
						)
					),
					"test",
					"test"
				));
			} else if ($type == 'sms') {
				$accordionsplitter = new FormSplitter("", "", "accordion", array(), array(
					"test",
					"test",
					"test"
				));
			}
			$messageformsplitters[] = new FormSplitter($messageformname, $languagename, "verticalsplit", array(), array(
				array("title" => "", "formdata" => $formdata), // TODO: Change the wording for this title.
				$accordionsplitter
			));
		}
		
		if (count($messageformsplitters) > 1) {
			$subtypelayoutforms[] = new FormTabber($type . $subtype, ucfirst($subtype), "verticaltabs", $messageformsplitters);
		} else if (count($messageformsplitters) == 1) {
			$messageformsplitters[0]->title = ucfirst($subtype);
			$subtypelayoutforms[] = $messageformsplitters[0];
		}
	}
	
	if (count($subtypelayoutforms) > 1) {
		if ($type == 'email') {
			$emailheadersformdata = array();
			$emailheadersformdata['emailsubject'] = array(
				"label" => _L('Subject'),
				"value" => "",
				"validators" => array(
					array("ValRequired","ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 51),
				"helpstep" => 1
			);
			$emailheadersformdata['emailfromname'] = array(
				"label" => _L('From Name'),
				"value" => "",
				"validators" => array(),
				"control" => array("TextField","size" => 30, "maxlength" => 51),
				"helpstep" => 1
			);
			$emailheadersformdata['emailfromaddress'] = array(
				"label" => _L('From Address'),
				"value" => "",
				"validators" => array(array("ValRequired")),
				"control" => array("TextField","size" => 30, "maxlength" => 51),
				"helpstep" => 1
			);

			$destinationlayoutforms[] = new FormSplitter("emailheaders", ucfirst($type), "horizontalsplit", array(), array(
				array("title" => "", "formdata" => $emailheadersformdata),
				new FormTabber("", "", "horizontaltabs", $subtypelayoutforms)
			));
		} else {
			$destinationlayoutforms[] = new FormTabber($type, ucfirst($type), "horizontaltabs", $subtypelayoutforms);
		}
	} else if (count($subtypelayoutforms) == 1) { // Phone, Sms.
		$subtypelayoutforms[0]->title = ucfirst($type);
		$destinationlayoutforms[] = $subtypelayoutforms[0];
	} // TODO: Need another form for Summary tab.
}

// Summary Tab.
$destinationlayoutforms[] = array(
	"name" => "summary",
	"title" => "Summary",
	"formdata" => array(
		'summary' => array(
			"label" => _L('Summary!'),
			"value" => "wassup",
			"validators" => array(),
			"control" => array("FormHtml","html" => "Hi, this is the summary page!"),
			"renderoptions" => array("icon" => false, "label" => false, "errormessage" => false),
			"helpstep" => 1
		)
	)
);

//////////////////////////////////////////////////////////
// Finalize the formsplitter.
//////////////////////////////////////////////////////////
if (!$readonly) {
	$buttons = array(icon_button(_L("Done"),"tick", "form_submit_all(null, 'done');", null), icon_button(_L("Cancel"),"cross",null,"start.php"));
} else {
	$buttons = array();
}

$messagegroupsplitter = new FormSplitter("messagegroupbasics", "", "horizontalsplit", $buttons, array(
	array("title" => "", "formdata" => array(
		'name' => array(
			"label" => _L('Message Group Name'),
			"value" => "",
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		),
		'description' => array(
			"label" => _L('Message Group Description'),
			"value" => "",
			"validators" => array(),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		)
	)),
	new FormTabber("destinationstabber", "", "horizontaltabs", $destinationlayoutforms)
));

///////////////////////////////////////////////////////////////////////////////
// Ajax
///////////////////////////////////////////////////////////////////////////////
$messagegroupsplitter->handleRequest();

///////////////////////////////////////////////////////////////////////////////
// Submit
///////////////////////////////////////////////////////////////////////////////
if (!isset($messagegroup)) {
	$messagegroup = new MessageGroup();
	$messagegroup->userid = $USER->id;
	$messagegroup->name = 'new messagegroup';
	$messagegroup->description = 'new messagegroup';
	$messagegroup->modified =  makeDateTime(time());
	$messagegroup->deleted = 1; // Set to deleted in case the user does not submit the form.
	$messagegroup->permanent = 0; // Set to non-permanent in case the user does not submit the form.
	
	if ($messagegroup->create()) {
		$_SESSION['messagegroupid'] = $messagegroup->id;
	} else {
		redirect('unauthorized.php'); // TODO: Something went wrong.. redirect somewhere?
	}
}
	
if (($button = $messagegroupsplitter->getSubmit()) && !$readonly) {
	$form = $messagegroupsplitter->getSubmittedForm();
	
	if ($form) {
		$ajax = $form->isAjaxSubmit();
		
		switch($button) {
			case 'tab':
			case 'done': {
				if ($form->name == 'messagegroupbasics') {
					$postdata = $form->getData();
					
					$messagegroup->name = $postdata['name'];
					$messagegroup->description = $postdata['description'];
				} else {
					// Go through each subtype... etc.
				}
				
				if ($ajax)
					$form->sendTo('');
			} break;
		
			case 'cancel': {
			} break;
		}
	} else {
		;// TODO: handle this?
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = 'Message Group Editor';

include_once('nav.inc.php');
?>

<script src="script/accordion.js" type="text/javascript"></script>
<script type="text/javascript">
	<?php Validator::load_validators(array("ValCallMeMessage")); ?>
</script>

<?php
startWindow(_L('Message Group Editor'));
	echo '<div id="formswitchercontainer">' . $messagegroupsplitter->render() . '</div>';
?>

<script type="text/javascript">
	form_init_splitter($('formswitchercontainer'));
</script>

<?php
endWindow();
include_once('navbottom.inc.php');
?>