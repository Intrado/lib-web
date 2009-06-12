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


class IntroSelect extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;

		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = "";
		$count = 0;
		$str .= '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		$str .= '<div id="introwidgetedit'.$n.'" style="display:none;">';
		foreach ($this->args['values'] as $key => $selectbox) {
			if($key == "user")
				$str .= '<select  id="' . $n . $key .'" '.$size .' onchange=loaduser(\'' . $n .'user\',\'' . $n . 'message\');updatevalue(\''.$n.'\');>';
			else
				$str .= '<select  id="' . $n . $key .'"  '.$size .' onchange=updatevalue(\''.$n.'\');>';
			foreach ($selectbox as $selectvalue => $selectname) {
				$checked = $value == $selectvalue;
				$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>';
			}
			$str .= '</select>&nbsp;';
			$count++;
		}		
		$str .= icon_button(_L("Play"),"fugue/control","
				var content = $('" . $n . "message').getValue();
					if(content.message != '')
						popup('previewmessage.php?id=' + content, 400, 400);") 
			   . icon_button(_L("Ok"),"tick","updatevalue('$n');
			  				form_do_validation($('" . $this->form->name . "'), $('" . $n . "'));
			 				 $('introwidgetedit" .$n. "').hide();
			 				 $('introwidgetblocked" .$n. "').show();
			 				 return false;");
			 				 
		$str .= '</div>';
		$str .= '<div id="introwidgetblocked'.$n.'">';
		
		
		$str .= '<span id="introwidget'.$n.'"></span>';
		
		$str .= '<div id="introplay'.$n.'">' . icon_button(_L("Play"),"fugue/control","
				var content = $('" . $n . "').getValue().evalJSON();
					if(content.message != '')
						popup('previewmessage.php?id=' + content.message, 400, 400);") 
			  . '</div>'. icon_button(_L("Load"),"fugue/arrow_045","$('introwidgetedit" .$n. "').show();$('introwidgetblocked" .$n. "').hide();return false;") ;
		$str .= '</div>';
		// ' . (isset($this->args['values']["language"])?"$(introitem+\"language\").value":"\"\"") . ';
		$str .= '<script>showinfo(\'' .$n . '\');</script>';
		return $str;
	}
}

class ValIntroSelect extends Validator {
	function validate ($value, $args) {
		
		if(is_numeric($value))
			return true;
		$checkval = json_decode($value);
		$errortext = "";
		if (!isset($checkval) || !isset($checkval->message))	
			$errortext .= " is required ";
		if ($errortext)
			return $this->label . $errortext;
		else
			return true;
	}
	
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				vals = value.evalJSON();
				var errortext = "";
				if (vals.message == "")
					errortext += " please pick a message";
				if (errortext)
					return errortext;
					
				return true;
			}';
	}
}

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
$languages = QuickQueryList("select name from language");

$messageselect = array("" => "Select a Message");
foreach($messages as $message)
	$messageselect[$message->id] = $message->name;
	
$languageselect = array("" => "Select Language");
$i = 1;
foreach($languages as $language) {
	$languageselect[$language] = $language;
	$i++;
}
	

if($IS_COMMSUITE)
	$users = DBFindMany("User","from user where enabled and deleted=0 order by lastname, firstname");
/*CSDELETEMARKER_START*/
else
	$users = DBFindMany("User","from user where enabled and deleted=0 and login != 'schoolmessenger' order by lastname, firstname");
/*CSDELETEMARKER_END*/
	
$userselect = array("" => "Select User");
foreach($users as $user) {
	$userselect[$user->id] = $user->firstname ." " . $user->lastname;
}	

$defaultvalues = array("user" => $userselect, "message" => $messageselect);

$languagevalues = array("language" => $languageselect,"user" => $userselect, "message" => $messageselect);



$formdata = array(
	"Required Intro",
	"defaultmessage" => array(
		"label" => _L("Default Intro"),
		"value" => '{"message":"' .getSystemSetting('intromessageid_default'). '"}',
		"validators" => array(array("ValIntroSelect")),
		"control" => array("IntroSelect",
			 "values"=>$defaultvalues
		),
		"helpstep" => 1
	),
	"emergencymessage" => array(
		"label" => _L("Emergency Intro"),
		"value" => '{"message":"' .getSystemSetting('intromessageid_emergency'). '"}',
		"validators" => array(array("ValIntroSelect")),
		"control" => array("IntroSelect",
			 "values"=>$defaultvalues
		),
		"helpstep" => 1
	)
//	,
//	"Language Options"
);
/*
for($i = 1; $i < 10; $i++) {
	$formdata["digit$i"] = array(
		"label" => _L("Digit $i"),
		"value" => '{"message":""}',
		"validators" => array(),
		"control" => array("IntroSelect",
			 "values"=>$languagevalues
		),
		"helpstep" => 2
	);
}
*/

$buttons = array(submit_button(_L("Done"),"submit","tick"),
		icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("introform", $formdata, null, $buttons);
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
		
		$defaultvalues = json_decode($postdata['defaultmessage']);
		$msgid = $defaultvalues->message + 0;
		$newmsg = new Message($msgid);
		if($newmsg->deleted) {// if deleted the old value is still the intro
				//TODO chack to see if it is the same as it was before 
				setSystemSetting('intromessageid_default', $newmsg->id);				
		} else {
			
			$newmsg->id = null;
			$newmsg->deleted = 1;
			$newmsg->name = "default_intro";	
			$newmsg->create();
			// copy the parts
			$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
			foreach ($parts as $part) {
				$newpart = new MessagePart($part->id);
				$newpart->id = null;
				$newpart->messageid = $newmsg->id;
				$newpart->create();
			}
			setSystemSetting('intromessageid_default', $newmsg->id);
		}
		
		$emergencyvalues = json_decode($postdata['emergencymessage']);
		$msgid = $emergencyvalues->message + 0;
		$newmsg = new Message($msgid);
		if($newmsg->deleted) {// if deleted the old value is still the intro
			//TODO chack to see if it is the same as it was before 		
			setSystemSetting('intromessageid_emergency', $newmsg->id);
		} else {
			$newmsg->id = null;
			$newmsg->deleted = 1;
			$newmsg->name = "emergency_intro";	
			$newmsg->create();
			// copy the parts
			$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
			foreach ($parts as $part) {
				$newpart = new MessagePart($part->id);
				$newpart->id = null;
				$newpart->messageid = $newmsg->id;
				$newpart->create();
			}			
			setSystemSetting('intromessageid_emergency', $newmsg->id);
		}
     			
		for($i = 0; $i <= 9;$i++ ) {	
			
			if(isset($postdata['digit' . $i])) {				
				$digitvalues = json_decode($postdata['digit' . $i]);
				if(isset($digitvalues->message) && isset($digitvalues->language)) {
					$msgid = $digitvalues->message + 0;
					$languageid = $digitvalues->language + 0;
					$newmsg = new Message($msgid);
					if($newmsg->deleted)
						//TODO Set db values
						error_log("set digit value not implemented");
					else {
						$newmsg->id = null;
						$newmsg->deleted = 1;
						$newmsg->name = 'intro_digit_' . $i;	
						$newmsg->create();
						// copy the parts
						$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
						foreach ($parts as $part) {
							$newpart = new MessagePart($part->id);
							$newpart->id = null;
							$newpart->messageid = $newmsg->id;
							$newpart->create();
						}
						//TODO Set db values
						error_log("set digit value not implemented");
						
					}
				}
			}		
		}
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

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValIntroSelect")); ?>

function setvalues(result,id) {
	var response = result.responseJSON;
	if (response) {	
		var output = '<option value=\"\">Select a Message</option>';//'<select id=\"defaultintro\" name=\"loaduserselect\">n';
		for (var i in response) {
			output += '    <option value=\"' + i + '\">' + response[i] + '</option>\n'
		}	
		$(id).innerHTML = output;
	} else {
		$(id).innerHTML = '<option value=\"\">Select a Message</option>';
	}
}
function loaduser(sourceid,targetid) {
	var request = 'ajax.php?ajax&type=Messages&messagetype=phone';
	
	if($(sourceid).getValue() != '')
		request += '&userid=' + $(sourceid).getValue();
	cachedAjaxGet(request,setvalues,targetid);
}	
function showinfo(id) {
		var message = $(id).value.evalJSON();
		if(message.message == "") {
			note = "Message is not";
			$('introplay' + id).hide();
		} else {
			note = "Message is set ";
			if(!(message.language == undefined || message.language == ""))
				note += " with language: " + message.language;
			$('introplay' + id).show();
		}
		$('introwidget' + id ).innerHTML = note; 		
}
function updatevalue(id) {
		var language = "";
		if($(id+"language")!=null)
			language = $(id+"language").value;
		$(id).value = Object.toJSON({
				"language": language,
				"user": $(id+"user").value,
				"message": $(id+"message").value
		});
		showinfo(id);		
}

</script>
<?
startWindow(_L("Intro Settings"));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
