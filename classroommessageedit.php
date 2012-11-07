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
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/TargetedMessage.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/TargetedMessageCategory.obj.php");




////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}


class TargetedLanguageEdit extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		$value = SmartTruncate($value, 30);
		
		$str .= '<input id="'.$n.'-display" name="'.$n.'-display" type="text" value="'.escapehtml($value).'" size="30" maxlength="30" disabled />';
		
		$str .= icon_button("Edit", "pencil",false,$this->args["editlink"])  . icon_button("Reset", "cross",false,$this->args["editlink"]);		
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION["targetedmessageid"] = null;
		if (isset($_GET['categoryid'])) {
			$_SESSION["targetedmessagecategoryid"] = $_GET['categoryid'];
		}
	} else {
		$_SESSION["targetedmessageid"] = $_GET['id'] + 0;
	}
	redirect("classroommessageedit.php");
}


$targetedmessage = null;
$targetedmessagecategory = null;

if(isset($_SESSION["targetedmessageid"])) {
	$targetedmessage = DBFind("TargetedMessage", "from targetedmessage where id=?",false,array($_SESSION["targetedmessageid"]));
	if (!$targetedmessage) {
		redirect('unauthorized.php');
	}
	$targetedmessagecategory = new TargetedMessageCategory($targetedmessage->targetedmessagecategoryid);
} else {
	// New Custome Targeted Message, Create blank MessageGroup and Targeted Message
	if (isset($_SESSION["targetedmessagecategoryid"])) {
		$targetedmessagecategory = DBFind("targetedmessagecategory", "from targetedmessagecategory where id=? and not deleted",false,array($_SESSION["targetedmessagecategoryid"]));
	
		if(!$targetedmessagecategory) {
			redirect('unauthorized.php');
		}
	}
	
	$messagegroup = new MessageGroup();
	$messagegroup->userid = $USER->id;
	$messagegroup->name = "Custom Classroom";
	$messagegroup->description = '';
	$messagegroup->modified = date("Y-m-d H:i:s", time());
	$messagegroup->deleted = 1;
	$messagegroup->permanent = 1;
	$messagegroup->create();
	
	$targetedmessage = new TargetedMessage();
	$targetedmessage->messagekey = "custom-" .  $messagegroup->id;
	$targetedmessage->targetedmessagecategoryid = $targetedmessagecategory->id;
	$targetedmessage->overridemessagegroupid = $messagegroup->id;
	$targetedmessage->deleted = 1;// Undelete once valid
	$targetedmessage->create();
}


if(isset($targetedmessage->overridemessagegroupid)) {
	$languagemessages = QuickQueryList("select m.languagecode, p.txt from message m, messagepart p
			where m.messagegroupid = ? and m.id = p.messageid and m.type='email'", true,false,array($targetedmessage->overridemessagegroupid));
}

if(!isset($messagedatacache)) { 
	$messagedatacache = array();
}

$formdata = array(_L("Default language"));

$languages = Language::getLanguageMap();
$defaultcode = Language::getDefaultLanguageCode();
$defaultlanguage = Language::getName(Language::getDefaultLanguageCode());
unset($languages[$defaultcode]);
$languages = array($defaultcode => $defaultlanguage) + $languages;

foreach($languages as $code => $languagename) {
	$value = "";

	if ($targetedmessage->overridemessagegroupid) {
		$editlink = "classroommessageeditlanguage.php?mgid={$targetedmessage->overridemessagegroupid}&languagecode=$code&targetmessagekey={$targetedmessage->messagekey}";
	} else {
		$editlink = "classroommessageoverride.php?languagecode=$code&targetmessagekey={$targetedmessage->messagekey}";
	}

	if(isset($languagemessages[$code]) && $languagemessages[$code] != "") {
		$value = $languagemessages[$code];
	} else {
		// Try to find default value
		$filename = "messagedata/" . $code . "/targetedmessage.php";
		if(file_exists($filename))
			include_once($filename);

		if(isset($messagedatacache[$code]) && isset($messagedatacache[$code][$targetedmessage->messagekey])) {
			$value = $messagedatacache[$code][$targetedmessage->messagekey];
		}
	}

	
	
	$formdata[$code] = array(
			"label" => $languagename,
			"value" => $value,
			"validators" => array(),
			"control" => array("TargetedLanguageEdit","editlink"=>$editlink),
			"helpstep" => 1
	);
	
	if ($code == $defaultcode) {
		$formdata[$code]["validators"][] = array("ValRequired");
		$formdata[] = _L("Other languages");
	}

	//echo $languagename  . '<p class="translate_text">'.escapehtml($value) . icon_button("Edit", "pencil",false,$editlink) . '</p> <br/>';
}
$helpsteps = array (
		_L('Type the message as it should appear in your Classroom Messaging email. You will need to enter the translated versions of the message. If you are unable to enter a translated version, English will be used by default.')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
		icon_button(_L('Cancel'),"cross",null,"classroommessagemanager.php"));
$form = new Form("classroom",$formdata,$helpsteps,$buttons);


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


		Query("COMMIT");
		if ($ajax)
			$form->sendTo("classroommessagemanager.php");
		else
			redirect("classroommessagemanager.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:settings";
$TITLE = _L('Classroom Message Edit');

include_once("nav.inc.php");

startWindow(_L('Language Variations for Classroom Message'));


echo $form->render();



//echo icon_button(_L('Done'),"tick",null,"classroommessagemanager.php");
endWindow();
include_once("navbottom.inc.php");
?>
