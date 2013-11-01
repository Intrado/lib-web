<?

require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Language.obj.php");
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");


require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("inc/previewfields.inc.php");
require_once("obj/PreviewModal.obj.php");
require_once("inc/appserver.inc.php");
require_once("obj/Publish.obj.php");
require_once("obj/StationerySelector.fi.php");

require_once("inc/editmessagecommon.inc.php");

///////////////////////////////////////////////////////////////////////////////
// Authorization:/
//////////////////////////////////////////////////////////////////////////////
$cansendemail = $USER->authorize('sendemail');

if (!$cansendemail) {
	redirect('unauthorized.php');
} 

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////
setEditMessageSession();

// get the messagegroup and/or the message
if (isset($_SESSION['editmessage']['messageid']))
	redirect('unauthorized.php');

// not editing an existing message, check session data for new message bits
if (isset($_SESSION['editmessage']['messagegroupid']) &&
		isset($_SESSION['editmessage']['languagecode'])) {

	$messagegroup = new MessageGroup($_SESSION['editmessage']['messagegroupid']);
	$languagecode = $_SESSION['editmessage']['languagecode'];
	if (isset($_SESSION['editmessage']['subtype']) && $_SESSION['editmessage']['subtype'] == "plain")
		$subtype = "plain";
	else
		$subtype = "html";
} else {
	// missing session data!
	redirect('unauthorized.php');
}

// if the user doesn't own the parent message group, unauthorized!
if (!userOwns("messagegroup", $messagegroup->id) || $messagegroup->deleted)
	redirect('unauthorized.php');

// invalid language code specified?
if (!in_array($languagecode, array_keys(Language::getLanguageMap())))
	redirect('unauthorized.php');

// no multi lingual and not default language code
if (!$USER->authorize("sendmulti") && $languagecode != Language::getDefaultLanguageCode())
	redirect('unauthorized.php');


////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$helpsteps = array();
$formdata = array();
$helpstepnum = 1;
$helpsteps[] = _L("The dropdown menu contains all of the stationery currently available to you. Select the stationery you wish to use from the dropdown menu and click Next to continue.");


$args = array($USER->id);




// Get all possible published stationery
///////////////////////////////////////////////////////

$subscribesql = "";
$cansubscribe = in_array("messagegroup", explode("|", $ACCESS->getValue('subscribe')));
if ($cansubscribe) {
	$data = Publish::getSubscribableItems("messagegroup","stationery");
	if (count($data["items"])) {
		$subscribesql = " or mg.id in (" . repeatWithSeparator("?", ",", count($data["items"])) . ")";
		foreach($data["items"] as $row) {
			$args[] = $row["id"];
		}
	}
}



// get the user's owned and subscribed messages
$stationery = array();
$query = "select mg.id,mg.name as name,(mg.name +0) as digitsfirst	from messagegroup mg
where (mg.userid=? $subscribesql)
 and mg.type = 'stationery' and not mg.deleted";

$stationery = QuickQueryList($query,true,false,$args);

if (count($stationery) == 1) {
	//fast forward with the sole stationery
	$stationeryids =  array_keys($stationery);
	redirect("editmessageemail.php?id=new&subtype={$subtype}&languagecode={$languagecode}&mgid={$messagegroup->id}&stationeryid={$stationeryids[0]}");
}

$formdata["stationery"] = array(
		"label" => _L("Stationery"),
		"fieldhelp" => _L("Select which stationery you would like to use."),
		"value" => "",
		"validators" => array(
				array("ValRequired"),
				array("ValInArray", "values" => array_keys($stationery))
		),
		"control" => array("StationerySelector", "values" => $stationery),
		"helpstep" => $helpstepnum++
);

$buttons = array(submit_button(_L('Next'),"submit","arrow_right"),icon_button(_L('Cancel'),"cross",null,"mgeditor.php"));

$form = new Form("mgstationeryselector",$formdata,$helpsteps,$buttons);

////////////////////////////////////////////////////////////////////////////////
// Form Data Handling
////////////////////////////////////////////////////////////////////////////////

//check and handle an ajax request (will exit early)
//or merge in related post data
$form->handleRequest();
//check for form submission
if ($button = $form->getSubmit()) { //checks for submit and merges in post data
	$ajax = $form->isAjaxSubmit(); //whether or not this requires an ajax response

	if ($form->checkForDataChange()) {
		$datachange = true;
	} else if (($errors = $form->validate()) === false) { //checks all of the items in this form
		$postdata = $form->getData(); //gets assoc array of all values {name:value,...}
		
		$redirectto = "editmessageemail.php?id=new&subtype={$subtype}&languagecode={$languagecode}&mgid={$messagegroup->id}&stationeryid={$postdata["stationery"]}";
		if ($ajax)
			$form->sendTo($redirectto);
		else
			redirect($redirectto);
	}
}
////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:messages";
$TITLE = _L('Stationery Editor');

include_once("nav.inc.php");

startWindow(_L('Select Stationery'));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>