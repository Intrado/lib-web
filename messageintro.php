<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");

require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");



////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$messages = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");

$values = array("" => "Select a Message");
foreach($messages as $message)
	$values[$message->id] = $message->name;

			
$formdata = array(
	"uploadmessage" => array(
		"label" => _L("Upload intro message"),
		"value" => "none",
		"validators" => array(
			array("ValRequired")
		),
		"control" => array("SelectMenu",
			 "values"=>$values
		),
		"helpstep" => 1
	)
);

$helpsteps = array (
	_L("Select a message to upload as an intro message for all phone messages"),
);

$buttons = array(submit_button(_L("Done"),"submit","accept"),
		icon_button(_L("Cancel"),"cross",null,"settings.php"));

				
$form = new Form("introform", $formdata, $helpsteps, $buttons);
$form->ajaxsubmit = true;

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
		$msgid = $postdata['uploadmessage'] + 0;
		
    	// copy the message to schoolmessanger account
		$newmsg = new Message($msgid);
		if($USER->id != $newmsg->userid)
			exit(); // illigal request  
		$newmsg->id = null;
		$newmsg->userid = 1;
		$newmsg->deleted = 1;
		$newmsg->name = "intro_english";
		$newmsg->description = "intro message. store in school messanger account";
		$newmsg->create();

		// copy the parts
		$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
		foreach ($parts as $part) {
			$newpart = new MessagePart($part->id);
			$newpart->id = null;
			$newpart->messageid = $newmsg->id;
			$newpart->create();
		}
		
		// Delete old intro
		QuickUpdate("delete message m, messagepart p FROM message m, messagepart p where m.name='intro_english' and m.id!=" . $newmsg->id . " and m.id = p.messageid");	
											
        //save data here
		if ($ajax)
			$form->sendTo("settings.php");
		else
			redirect("settings.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "admin:settings";
$TITLE = _L('Message Intro Manager');

include_once("nav.inc.php");


startWindow(_L("Settings"));
echo $form->render();
endWindow();

include_once("navbottom.inc.php");
?>