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
if (!$USER->authorize('managesystem')) {
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
$languages = QuickQueryMultiRow("select * from language");
$values = array();

if($id) {
	$targetedmesssage = QuickQueryRow("select messagekey, overridemessagegroupid, targetedmessagecategoryid from targetedmessage where id=?",false,false,array($id));
} else {
	$targetedmesssage = false;
}

if(isset($targetedmesssage[1])) {
	$languagemessages = QuickQueryList("select m.languagecode, p.txt from message m, messagepart p
			where m.messagegroupid = ? and
					m.id = p.messageid", true,false,array($targetedmesssage[1]));
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

	$formdata[$code] = array(
		"label" => $language[1],
		"value" => "",
		"validators" => array(
			array("ValLength","min" => 3,"max" => 150)
		),
		"control" => array("TextField","size" => 50, "maxlength" => 150),
		"helpstep" => 2
	);
	if(isset($languagemessages[$code]) && $languagemessages[$code] != "") {
		// Populate the form with message data and complete with default data

		//error_log("custom" . $code);
		$formdata[$code]["value"] = $languagemessages[$code];
		//$formdata[$code]["value"] = "Custom Data " . (isset($languagemessages[$code])?$languagemessages[$code]:"");
	} else {
		//error_log("default" . $code);
		// Populate with default data
		$filename = "messagedata/" . $code . "/targetedmessage.php";
		if(file_exists($filename))
			include_once($filename);

		if(isset($messagedatacache[$code]) && isset($messagedatacache[$code][$targetedmesssage[0]])) {
			$formdata[$code]["value"] = $messagedatacache[$code][$targetedmesssage[0]];
		} // else no default data found value is set to empty
	}
}

$helpsteps = array (
	_L('Category'),
	_L('Type the Message in as many languages as you are able to.')
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

			$messagegroupid = false;
			//save data here
			foreach($languages as $language) {
				$code = $language[2];
				$newvalue = $postdata[$code];
				$message = false;
				//
				if($targetedmesssage[1]) {
					$message = DBFind("Message", "from message m where m.messagegroupid = ? and languagecode = ?",false, array($targetedmesssage[1],$code));
					$messagegroupid = $targetedmesssage[1];
				} else {
					if(	isset($messagedatacache[$code]) &&
						isset($messagedatacache[$code][$targetedmesssage[0]]) &&
						$messagedatacache[$code][$targetedmesssage[0]] == $newvalue
						) {
							// There is a default value for this message/language and the value has not changed

							continue;
					} else {
						if($newvalue != '' && !$messagegroupid) {
							// create a new message group

							$messagegroup = new MessageGroup();
							$messagegroup->userid =  $USER->id;
							$messagegroup->name = "Custom Classroom";
							$messagegroup->description = '';
							$messagegroup->modified = date("Y-m-d H:i:s", time());
							$messagegroup->deleted = 1;
							$messagegroup->create();
							$messagegroupid = $messagegroup->id;

							if(isset($targetedmesssage[0])) {
								error_log("update targetedmessage set overridemessagegroupid = $messagegroupid where messagekey = " . $targetedmesssage[0]);
								QuickUpdate("update targetedmessage set overridemessagegroupid = ? where messagekey = ?",false,array($messagegroupid,$targetedmesssage[0]));
							} else {
								error_log("insert into targete table $code");
								QuickUpdate("insert into targetedmessage (messagekey,targetedmessagecategoryid,overridemessagegroupid) values (?,?,?)",false,array(substr($newvalue, 0, 10) . "-" .  $messagegroup->modified,$postdata["category"],$messagegroupid));
							}
						}
					}
				}
				if($messagegroupid) {
					if($message === false) {
						// create a new message
						$message = new Message();
						$message->messagegroupid = $messagegroupid;
						$message->userid = $USER->id;
						$message->name = "Custom Classroom";
						$message->description = '';
						$message->type = 'email';
						$message->subtype = 'plain';
						$message->data = '';
						$message->modifydate = date("Y-m-d H:i:s", time());
						$message->deleted = 1;
						$message->autotranslate = 'none';
						$message->languagecode = $code;
						$message->create();

						$messagepart = new MessagePart();
						$messagepart->messageid = $message->id;
						$messagepart->type = 'T';
						$messagepart->txt = $newvalue;
						$messagepart->sequence = 0;
						$messagepart->create();
					} else {
						$message->modifydate = date("Y-m-d H:i:s", time());
						$message->update();
						$messagepart = DBFind("MessagePart","from messagepart where messageid = ? and sequence = 0",false,array($message->id));
						if($messagepart) {
							$messagepart->txt = $newvalue;
							$messagepart->update();
						}
					}
				}
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
