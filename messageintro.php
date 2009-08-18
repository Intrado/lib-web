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
// Form Data
////////////////////////////////////////////////////////////////////////////////
class IntroSelect extends FormItem {
	function render ($value) {
		static $renderscript = true;
		
		$n = $this->form->name."_".$this->name;
		
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = "";
		$count = 0;
		$str .= '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml(json_encode($value)).'"/>';
		
		$str .= '<div id="introwidgetedit'.$n.'">';
		
		$str .= '<table><tr>';
		foreach ($this->args['values'] as $key => $selectbox) {
			$str .= '<td>';
			if($key == "user")
				$str .= '<select  id="' . $n . $key .'" '.$size .' onchange="loaduser(\'' . $n .'user\',\'' . $n . 'message\');updatevalue(\''.$n.'\');">';
			else if($key == "message")
				$str .= '<select  id="' . $n . $key .'" '.$size .' onchange="updatemessage(\''.$n.'\');">';
			else
				$str .= '<select  id="' . $n . $key .'"  '.$size .' onchange="updatevalue(\''.$n.'\');">';
			foreach ($selectbox as $selectvalue => $selectname) {
				$checked = (isset($value[$key]) && $value[$key] == $selectvalue);
				$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>';
			}
			$str .= '</select></td>';
			$count++;
		}	
		$str .= '<td>';
		$str .= '<div id="' . $n . 'play" style="' . ((isset($value["message"]) && $value["message"] != "")?"display:block;":"display:none;") . ' ">' 
				. icon_button(_L("Play"),"fugue/control","
				var content = $('" . $n . "message').getValue();
					if(content != '')
						popup('previewmessage.php?id=' + content, 400, 400);") . '</div>';
		$str .= '</td></tr></table>';
		$str .= '</div>';
		
		if($renderscript) {
			$str .= '<script>
					function updatemessage(item) {
						updatevalue(item);form_do_validation($("' . $this->form->name . '"),$(item));
						var sel = $(item + "message");							
						if (sel.options[sel.selectedIndex].value > 0) { 
							$(item + "play").show();
						} else {
							$(item + "play").hide();
						}
					}	
			</script>';
		}
		
		
		return $str;
	}
}

class ValIntroSelect extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		$errortext = "";
		if (!isset($value["message"]) || $value["message"] == "")	
			$errortext .= " is required ";
		else if ( 1 != QuickQuery('select count(*) from message where id=? and type=\'phone\'', false, array($value["message"]))) {
			$errortext .= " message can not be found";
		}
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

$messages = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");

$languages = QuickQueryList("select name from language where name != 'English'");

$messageselect = array("" => "System Default Intro");
foreach($messages as $message)
	$messageselect[$message->id] = $message->name;

	
if($IS_COMMSUITE)
	$users = DBFindMany("User","from user where enabled and deleted=0 order by lastname, firstname");
/*CSDELETEMARKER_START*/
else
	$users = DBFindMany("User","from user where enabled and deleted=0 and login != 'schoolmessenger' order by lastname, firstname");
/*CSDELETEMARKER_END*/
	
$userselect = array("" => "Select Messages From User (Optional)");
foreach($users as $user) {
	$userselect[$user->id] = $user->firstname ." " . $user->lastname;
}	

$messagevalues = array("user" => $userselect, "message" => $messageselect);
//$languagevalues = array("language" => $languageselect,"user" => $userselect, "message" => $messageselect);


$defaultintro = DBFind("Message","from message m, prompt p where p.type='intro' and language is null and p.messageid = m.id and m.type='phone'","m");
$emergencyintro = DBFind("Message","from message m, prompt p where p.type='emergencyintro' and language is null and p.messageid = m.id and m.type='phone'","m");



$defaultmessages = $messagevalues;
if($defaultintro) {
	$defaultmessages["message"][$defaultintro->id] = $defaultintro->name;
}
$emergencymessages = $messagevalues;
$emergencymessages["message"][""] = "System Emergency Intro";
if($emergencyintro) {
	$emergencymessages["message"][$emergencyintro->id] = $emergencyintro->name;
}

$formdata = array(
	"Default Intro",
	"defaultmessage" => array(
		"label" => _L("General"),
		"fieldhelp" => _L('This is the introduction which plays before non-emergency messages. See the Guide for content suggestions.'),
		"value" => array("message" => ($defaultintro === false?"":$defaultintro->id)),
		"validators" => array(),
		"control" => array("IntroSelect",
			 "values"=>$defaultmessages
		),
		"helpstep" => 1
	),
	"emergencymessage" => array(
		"label" => _L("Emergency"),
		"fieldhelp" => _L('This is the introduction which plays before an emergency message. See the Guide for content suggestions.'),
		"value" => array("message" => ($emergencyintro === false?"":$emergencyintro->id)),
		"validators" => array(),
		"control" => array("IntroSelect",
			 "values"=>$emergencymessages
		),
		"helpstep" => 1
	)
);

$helpsteplanguages = array();;
$helpstepindex = 2;

foreach($languages as $language) {	
	$defaultintro = DBFind("Message","from message m, prompt p where p.type='intro' and language=? and p.messageid = m.id and m.type='phone'","m",array($language));
	
	// TODO Fix a better way of adding the set message rather than copying the array in a for loop like this. 
	$defaultmessages = $messagevalues;
	$messageid = "";
	if($defaultintro) {
		$defaultmessages["message"][$defaultintro->id] = $defaultintro->name;
		$messageid = $defaultintro->id;
	}
	$formdata[] = $language . " " . _L("Intro"); // New section for each language
	$formdata[$language . "default"] = array(
		"label" => _L("General"),
		"fieldhelp" => _L('This is the introduction which plays before non-emergency messages. See the Guide for content suggestions.'),
		"value" => array("message" => $messageid),
		"validators" => array(),
		"control" => array("IntroSelect",
			 "values"=>$defaultmessages
		),
		"helpstep" => $helpstepindex
	);
	$emergencyintro = DBFind("Message","from message m, prompt p where p.type='emergencyintro' and language=? and p.messageid = m.id and m.type='phone'","m",array($language));
	$emergencymessages = $messagevalues;
	$emergencymessages["message"][""] = "System Emergency Intro";
	$messageid = "";
	if($emergencyintro) {
		$emergencymessages["message"][$emergencyintro->id] = $emergencyintro->name;
		$messageid = $emergencyintro->id;
	}
	$formdata[$language . "emergency"] = array(
		"label" => _L("Emergency"),
		"fieldhelp" => _L('This is the introduction which plays before an emergency message. See the Guide for content suggestions.'),
		"value" => array("message" => $messageid),
		"validators" => array(),
		"control" => array("IntroSelect",
			 "values"=>$emergencymessages
		),
		"helpstep" => $helpstepindex
	);
	
	$helpsteptext[$helpstepindex] = $language;
	$helpstepindex++;
}

$helpsteps = array (
	_L('These intro messages will play before all phone messages. The best intro messages contain a brief greeting and instructs the user to press "1" to hear the message. You should also let recipients know that they can press pound to place the call on hold. For example, <p>"<em>This is an important message from Springfield Independent School District. To hear this message now press 1. To place this call on hold press the pound key</em>".</p> Additionally, the Emergency intro message should state that the message is an emergency rather than simply "important."')
);

$buttons = array(submit_button(_L("Done"),"submit","tick"),
		icon_button(_L("Cancel"),"cross",null,"settings.php"));

$form = new Form("introform", $formdata, $helpsteps, $buttons);

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
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
		
		$messagevalues = json_decode($postdata['defaultmessage']);
		if(isset($messagevalues->message) && strlen($messagevalues->message) > 0) {
			$msgid = $messagevalues->message + 0;
			$newmsg = new Message($msgid);
			if(!$newmsg->deleted) {		// if deleted the old value is still the intro
				$newmsg->id = null;
				$newmsg->deleted = 1;
				$newmsg->name = $newmsg->name . " (Default General Intro Copy)";	
				$newmsg->create();
				// copy the parts
				$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
				foreach ($parts as $part) {
					$newpart = new MessagePart($part->id);
					$newpart->id = null;
					$newpart->messageid = $newmsg->id;
					$newpart->create();
				}
			} 
			QuickUpdate("delete from prompt where type='intro' and language is null;");	
			QuickUpdate("insert into prompt (type, messageid) values ('intro',?)",false,array($newmsg->id));			
		} else {
			QuickUpdate("delete from prompt where type='intro' and language is null;");			
		}
		
		$emergencyvalues = json_decode($postdata['emergencymessage']);
		if(isset($emergencyvalues->message) && strlen($emergencyvalues->message) > 0) {	
			$msgid = $emergencyvalues->message + 0;
			$newmsg = new Message($msgid);
			if(!$newmsg->deleted) {// if deleted the old value is still the intro
				$newmsg->id = null;
				$newmsg->deleted = 1;
				$newmsg->name = $newmsg->name . " (Default Emergency Intro Copy)";	
				$newmsg->create();
				// copy the parts
				$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
				foreach ($parts as $part) {
					$newpart = new MessagePart($part->id);
					$newpart->id = null;
					$newpart->messageid = $newmsg->id;
					$newpart->create();
				}			
			}
			QuickUpdate("delete from prompt where type='emergencyintro' and language is null;");	
			QuickUpdate("insert into prompt (type, messageid) values ('emergencyintro',?)",false,array($newmsg->id));
		} else {
			QuickUpdate("delete from prompt where type='emergencyintro' and language is null;");	
		}
		
		foreach($languages as $language) {
			$insertquery = "";
			if(isset($postdata[$language . 'default'])) {				
				$languagevalues = json_decode($postdata[$language . 'default']);
				if(isset($languagevalues->message) && strlen($languagevalues->message) > 0) {
					$msgid = $languagevalues->message + 0;
					$newmsg = new Message($msgid);
					if(!$newmsg->deleted) {
						$newmsg->id = null;
						$newmsg->deleted = 1;
						$newmsg->name = $newmsg->name . " ($language General Intro Copy)";							
						$newmsg->create();
						// copy the parts
						$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
						foreach ($parts as $part) {
							$newpart = new MessagePart($part->id);
							$newpart->id = null;
							$newpart->messageid = $newmsg->id;
							$newpart->create();
						}
					}
					QuickUpdate("delete from prompt where type='intro' and language=?;",false,array($language));
					QuickUpdate("insert into prompt (type, messageid,language) values ('intro',?,?)",false,array($newmsg->id,$language));
				} else {
					QuickUpdate("delete from prompt where type='intro' and language=?;",false,array($language));			
				}
			}	
						
			if(isset($postdata[$language . 'emergency'])) {				
				$languagevalues = json_decode($postdata[$language . 'emergency']);
				if(isset($languagevalues->message) && strlen($languagevalues->message) > 0) {
					$msgid = $languagevalues->message + 0;
					$newmsg = new Message($msgid);
					if(!$newmsg->deleted) {
						$newmsg->id = null;
						$newmsg->deleted = 1;
						$newmsg->name = $newmsg->name . " ($language Emergecny Intro Copy)";													
						$newmsg->create();
						// copy the parts
						$parts = DBFindMany("MessagePart", "from messagepart where messageid=$msgid");
						foreach ($parts as $part) {
							$newpart = new MessagePart($part->id);
							$newpart->id = null;
							$newpart->messageid = $newmsg->id;
							$newpart->create();
						}
					}
					QuickUpdate("delete from prompt where type='emergencyintro' and language=?;",false,array($language));								
					QuickUpdate("insert into prompt (type, messageid,language) values ('emergencyintro',?,?)",false,array($newmsg->id,$language));
				} else {
					QuickUpdate("delete from prompt where type='emergencyintro' and language=?;",false,array($language));							
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
		$(id).update(output);
	} else {
		$(id).update('<option value=\"\">Select a Message</option>');
	}
}
function loaduser(sourceid,targetid) {
	var request = 'ajax.php?ajax&type=Messages&messagetype=phone';
	
	if($(sourceid).getValue() != '')
		request += '&userid=' + $(sourceid).getValue();
	cachedAjaxGet(request,setvalues,targetid);
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
}

</script>
<?
startWindow(_L("Intro Settings"));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
