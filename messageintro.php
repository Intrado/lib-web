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
				$str .= '<select  id="' . $n . $key .'" '.$size .' onchange="loaduser(\'' . $n . '\');updatevalue(\''.$n.'\');">';
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
		$defaultrequest = isset($this->args['defaultfile']) ? ''.$this->args['defaultfile'].'' : "";		
		$str .= '<div id="' . $n . 'play">' 
				. icon_button(_L("Play"),"fugue/control","
				var content = $('" . $n . "message').getValue();
					if(content != '')
						popup('previewmessage.php?id=' + content, 400, 400,'preview');
					else
						popup('previewmessage.php?mediafile=" . urlencode($defaultrequest) . "', 400, 400,'preview');") . '</div>';
		$str .= '</td></tr></table>';
		$str .= '</div>';
		
		if($renderscript) {
			$str .= '<script>
					function updatemessage(item) {
						updatevalue(item);form_do_validation($("' . $this->form->name . '"),$(item));
					}	
			</script>';
		}
		
		
		return $str;
	}
}

class ValIntroSelect extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}		
		
		$errortext = "";
		if (isset($value["message"]) && $value["message"] != "") {
			if ( 1 != QuickQuery('select count(*) from message where id=? and type=\'phone\'', false, array($value["message"]))) {
				$errortext .= "Message can not be found";
			} else {
				$audiodata = QuickQueryRow("select group_concat(mp.txt SEPARATOR ' ') as text, sum(length(c.data)) as audiobytes 
					from message m left join messagepart mp on (mp.messageid=m.id) left join audiofile af on (af.id=mp.audiofileid) left join content c on (c.id=af.contentid) 
					where m.id=? group by m.id",true,false, array($value["message"]));
		
				$ttswords = isset($audiodata["text"])?str_word_count($audiodata["text"]):0;					
				if($audiodata["audiobytes"] < 100000 && $ttswords < 10 && ($audiodata["audiobytes"] < 50000 && $ttswords < 5)) { // Aproximately 5 seconds of audio
					$errortext .= "Message is too short to be a intro message.";
				} 
			}		
		}				
		if ($errortext)
			return $errortext;
		else
			return true;
	}
}

$messages = DBFindMany("Message","from message where userid=" . $USER->id ." and deleted=0 and type='phone' order by name");

$languages = QuickQueryList("select name from language where name != 'English'");

$messageselect = array("" => "System Default Intro");
foreach($messages as $message)
	$messageselect[$message->id] = $message->name;

	
if($IS_COMMSUITE)
	$users = DBFindMany("User","from user where enabled and deleted=0 order by firstname, lastname");
/*CSDELETEMARKER_START*/
else
	$users = DBFindMany("User","from user where enabled and deleted=0 and login != 'schoolmessenger' order by firstname, lastname");
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

$allowedjobtypes = QuickQueryRow("select sum(jt.systempriority = 1) as Emergency, sum(jt.systempriority != 1) as Other from jobtype jt where jt.deleted = 0 and jt.issurvey = 0",true);


$formdata = array();


$formdata[] = "Default Intro";
if($allowedjobtypes["Other"] > 0) {
	$formdata["defaultmessage"] = array(
			"label" => _L("General"),
			"fieldhelp" => _L('This is the introduction which plays before non-emergency messages. See the Guide for content suggestions.'),
			"value" => array("message" => ($defaultintro === false?"":$defaultintro->id)),
			"validators" => array(array("ValIntroSelect")),
			"control" => array("IntroSelect",
				"values"=>$defaultmessages,
				"defaultfile" => "DefaultIntro.wav"
			),
			"helpstep" => 1
	);
}
if($allowedjobtypes["Emergency"] > 0) {
	$formdata["emergencymessage"] = array(
			"label" => _L("Emergency"),
			"fieldhelp" => _L('This is the introduction which plays before an emergency message. See the Guide for content suggestions.'),
			"value" => array("message" => ($emergencyintro === false?"":$emergencyintro->id)),
			"validators" => array(array("ValIntroSelect")),
			"control" => array("IntroSelect",
				"values"=>$emergencymessages,
				"defaultfile" => "EmergencyIntro.wav"
			),
			"helpstep" => 1
	);
}
$helpsteplanguages = array();;
$helpstepindex = 2;

foreach($languages as $language) {	
	$formdata[] = $language . " " . _L("Intro"); // New section for each language	
	if($allowedjobtypes["Other"] > 0) {
		
		$defaultintro = DBFind("Message","from message m, prompt p where p.type='intro' and language=? and p.messageid = m.id and m.type='phone'","m",array($language));
		
		// TODO Fix a better way of adding the set message rather than copying the array in a for loop like this. 
		$defaultmessages = $messagevalues;
		$messageid = "";
		if($defaultintro) {
			$defaultmessages["message"][$defaultintro->id] = $defaultintro->name;
			$messageid = $defaultintro->id;
		}
		$formdata[$language . "default"] = array(
			"label" => _L("General"),
			"fieldhelp" => _L('This is the introduction which plays before non-emergency messages. See the Guide for content suggestions.'),
			"value" => array("message" => $messageid),
			"validators" => array(array("ValIntroSelect")),
			"control" => array("IntroSelect",
				 "values"=>$defaultmessages,
				 "defaultfile" => "$language/DefaultIntro.wav",
			),
			"helpstep" => $helpstepindex
		);
	}
	if($allowedjobtypes["Emergency"] > 0) {
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
			"validators" => array(array("ValIntroSelect")),
			"control" => array("IntroSelect",
				 "values"=>$emergencymessages,
				 "defaultfile" => "$language/EmergencyIntro.wav",
			),
			"helpstep" => $helpstepindex
		);
	}
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
		if(isset($postdata['defaultmessage'])) {	
			$messagevalues = json_decode($postdata['defaultmessage']);
			if(isset($messagevalues->message) && strlen($messagevalues->message) > 0) {
				$msgid = $messagevalues->message + 0;
				$newmsg = new Message($msgid);
				
				if(!$newmsg->deleted) {		// if deleted the old value is still the intro
					$newmsg->id = null;
					$newmsg->deleted = 1;
					$newmsg->name = $newmsg->name . " (Copy)";	
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
		}
		if(isset($postdata['emergencymessage'])) {
			$emergencyvalues = json_decode($postdata['emergencymessage']);
			if(isset($emergencyvalues->message) && strlen($emergencyvalues->message) > 0) {	
				$msgid = $emergencyvalues->message + 0;
				$newmsg = new Message($msgid);
				if(!$newmsg->deleted) {// if deleted the old value is still the intro
					$newmsg->id = null;
					$newmsg->deleted = 1;
					$newmsg->name = $newmsg->name . " (Copy)";	
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
						$newmsg->name = $newmsg->name . " (Copy)";							
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
						$newmsg->name = $newmsg->name . " (Copy)";													
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

	var defaulttext = $(id + 'message').options[0].text;
	if(defaulttext == undefined)
		defaulttext = "Select a Message";
	
	if (response) {	
		var output = '<option value=\"\">' + defaulttext + '</option>';//'<select id=\"defaultintro\" name=\"loaduserselect\">n';
		for (var i in response) {
			output += '    <option value=\"' + i + '\">' + response[i] + '</option>\n'
		}	
		$(id + 'message').update(output);
	} else {
		$(id + 'message').update('<option value=\"\">' + defaulttext + '</option>');
	}
	$(id + 'play').hide();
}
function loaduser(id) {
	var request = 'ajax.php?ajax&type=Messages&messagetype=phone';
	
	if($(id + 'user').getValue() != '')
		request += '&userid=' + $(id + 'user').getValue();
	cachedAjaxGet(request,setvalues,id);
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
