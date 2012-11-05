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



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!getSystemSetting('_hastargetedmessage', false) || !$USER->authorize('manageclassroommessaging')) {
	redirect('unauthorized.php');
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION["targetedmessageid"] = null;
	} else {
		$_SESSION["targetedmessageid"] = $_GET['id'] + 0;
	}
	redirect("classroommessageedit.php");
}

$id = $_SESSION["targetedmessageid"];

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$value = "";
$languages = QuickQueryMultiRow("select id,name,code from language");
$values = array();

if($id) {
	$targetedmesssage = QuickQueryRow("select messagekey, overridemessagegroupid, targetedmessagecategoryid from targetedmessage where id=?",false,false,array($id));
} else {
	$targetedmesssage = false;
}

if(isset($targetedmesssage[1])) {
	$languagemessages = QuickQueryList("select m.languagecode, p.txt from message m, messagepart p
			where m.messagegroupid = ? and m.id = p.messageid and m.type='email'", true,false,array($targetedmesssage[1]));
}

$categories = QuickQueryList("select id, name from targetedmessagecategory where deleted = 0",true);
$categories = $categories?(array("" => "-- Select a Category --") + $categories):array("" => "-- Select a Category --");

$formdata = array();

if(!isset($messagedatacache)) { 
	$messagedatacache = array();
}


$formdata["category"] = array(
	"label" => _L("Category"),
	"value" => isset($targetedmesssage[2])?$targetedmesssage[2]:"",
	"validators" => array(
		array("ValRequired"),
		array("ValInArray", "values" => array_keys($categories))
	),
	"control" => array("SelectMenu","values" => $categories),
	"helpstep" => 1
);

foreach($languages as $language) {
	$code = $language[2];
	$value = "";
	if(isset($languagemessages[$code]) && $languagemessages[$code] != "") {
		// Populate the form with message data and complete with default data
		$value = $languagemessages[$code];
	} else {
		$filename = "messagedata/" . $code . "/targetedmessage.php";
		if(file_exists($filename))
			include_once($filename);
	
		if(isset($messagedatacache[$code]) && isset($messagedatacache[$code][$targetedmesssage[0]])) {
			$value = $messagedatacache[$code][$targetedmesssage[0]];
		} // else no default data found value is set to empty
	}
	
	// if has overridemessagegroupid
	if (isset($targetedmesssage[1])) {
		$editlink = "classroommessageeditlanguage.php?mgid={$targetedmesssage[1]}&languagecode=$code";
		if (isset($targetedmesssage[0]))
			$editlink .= "&targetmessagekey={$targetedmesssage[0]}";
	} else {
		$editlink = "classroommessageoverride.php?languagecode=$code&messagekey={$targetedmesssage[0]}&categoryid=$targetedmesssage[2]";
	}
	
	$formdata[$code] = array(
			"label" => $language[1],
			"control" => array("FormHtml","html"=>'<p class="translate_text">'.escapehtml($value) . icon_button("Edit", "pencil",false,$editlink) . '</p>'),
			"helpstep" => 2
	);
}

$helpsteps = array (
	_L('Select which category this message belongs in. This should allow teachers to find the message easily.'),
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
			if($targetedmesssage[0]) {
				QuickUpdate("update targetedmessage set targetedmessagecategoryid = ? where messagekey = ?",false,array($postdata["category"],$targetedmesssage[0]));
			}


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
endWindow();
include_once("navbottom.inc.php");
?>
