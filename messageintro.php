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
require_once('inc/content.inc.php');
require_once("obj/Content.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/Voice.obj.php");
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
				$str .= '<select  id="' . $n . $key .'" '.$size .' onchange="loaduser(\'' . $n . '\');updatemessage($(\''.$n.'\'));">';
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
		$str .= '<div>' 
				. icon_button(_L("Play"),"fugue/control","
				var content = $('" . $n . "message').getValue();
					if(content.substring(0,5) == 'intro') {
						popup('previewmessage.php?id=' + content.substring(5), 400, 400,'preview');
					} else if(content != '')
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
		if (isset($value["message"]) && $value["message"] != "" && substr($value["message"],0,5) != "intro") {
			if ( 1 != QuickQuery('select count(*) from message where id=? and type=\'phone\' and languagecode=?', false, array($value["message"],$args["languagecode"]))) {
				$errortext .= "Message can not be found";
			} else if (Message::getAudioLength($value["message"],array()) < 70000) { // 70000 ~ 5 second audio
				$errortext .= "Message must be more than 5 seconds long to be an intro message.";
			}		
		}				
		if ($errortext)
			return $errortext;
		else
			return true;
	}
}


// Note that id is the message id and name is the mssage group id
$messages = QuickQueryList("select m.id as id, g.name as name,(g.name + 0) as digitsfirst
							from messagegroup g, message m where g.userid=? and g.deleted = 0 and m.messagegroupid = g.id and m.type = 'phone'
							and m.autotranslate != 'translated' and m.languagecode = 'en' order by digitsfirst",true,false,array($USER->id));

if($messages == false) {
	$messages = array("" => "English - System General Intro");
} else {
	$messages = array("" => "English - System General Intro") + $messages;
}

$languages = QuickQueryList("select name, code from language where name != 'English'",true);

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

$messagevalues = array("user" => $userselect, "message" => $messages);

$generalintro = QuickQueryRow("select m.id , m.name from message m, prompt p where p.type='intro' and p.languagecode is null and p.messageid = m.id and m.type='phone'");
$emergencyintro = QuickQueryRow("select m.id , m.name from message m, prompt p where p.type='emergencyintro' and p.languagecode is null and p.messageid = m.id and m.type='phone'");

$defaultmessages = $messagevalues;
if($generalintro)
	$defaultmessages["message"]['intro' . $generalintro[0]] = $generalintro[1];
$emergencymessages = $messagevalues;
$emergencymessages["message"][""] = "English - System Emergency Intro";
if($emergencyintro) 
	$emergencymessages["message"]['intro' . $emergencyintro[0]] = $emergencyintro[1];

$allowedjobtypes = QuickQueryRow("select sum(jt.systempriority = 1) as Emergency, sum(jt.systempriority != 1) as Other from jobtype jt where jt.deleted = 0 and jt.issurvey = 0",true);

$formdata = array();
$formdata[] = array(
	"label" => "",
	"control" => array("FormHtml",
		"html"=> _L("<h3>Important Information</h3>
				<div>These intro messages will play before all phone messages. The best intro messages contain a brief greeting and instructs the user to press \"1\" to hear the message. You should also let recipients know that they can press pound to place the call on hold.
				</div>"),
	),
	"helpstep" => 1
);

$formdata[] = "Default Intro";
if($allowedjobtypes["Other"] > 0) {
	$formdata["intro"] = array(
		"label" => _L("General"),
		"fieldhelp" => _L('This is the introduction which plays before non-emergency messages. See the Guide for content suggestions.'),
		"value" => array("message" => ($generalintro === false?"":'intro' . $generalintro[0])),
		"validators" => array(array("ValIntroSelect","languagecode" => "en")),
		"control" => array("IntroSelect",
			"values"=>$defaultmessages,
			"defaultfile" => "DefaultIntro.wav"
		),
		"helpstep" => 1
	);
}
if($allowedjobtypes["Emergency"] > 0) {
	$formdata["emergencyintro"] = array(
		"label" => _L("Emergency"),
		"fieldhelp" => _L('This is the introduction which plays before an emergency message. See the Guide for content suggestions.'),
		"value" => array("message" => ($emergencyintro === false?"":'intro' . $emergencyintro[0])),
		"validators" => array(array("ValIntroSelect", "languagecode" => "en")),
		"control" => array("IntroSelect",
			"values"=>$emergencymessages,
			"defaultfile" => "EmergencyIntro.wav"
		),
		"helpstep" => 1
	);
}
$helpsteplanguages = array();;
$helpstepindex = 2;

foreach($languages as $language => $code) {
	$formdata[] = $language . " " . _L("Intro"); // New section for each language

	// Note that id is the message id and name is the mssage group id
	$messages = QuickQueryList("select m.id as id, g.name as name,(g.name + 0) as digitsfirst
						from messagegroup g, message m where g.userid=? and g.deleted = 0 and m.messagegroupid = g.id and m.type = 'phone'
						and m.autotranslate != 'translated' and m.languagecode = ? order by digitsfirst",true,false,array($USER->id,$code));
	if($messages == false) {
		$messagevalues["message"] = array("" => "English - System General Intro");
	} else {
		$messagevalues["message"] = array("" => "English - System General Intro") + $messages;
	}

	if($allowedjobtypes["Other"] > 0) {
		$generalintro = QuickQueryRow("select m.id , m.name from message m, prompt p where p.type='intro' and p.languagecode=? and p.messageid = m.id and m.type='phone'",false,false,array($code));

		// TODO Fix a better way of adding the set message rather than copying the array in a for loop like this.
		$generalmessages = $messagevalues;

		if($language == "Spanish") {
			$generalmessages["message"][""] = "Spanish - System General Intro";
		}

		$messageid = "";
		if($generalintro) {
			$generalmessages["message"]['intro' . $generalintro[0]] = $generalintro[1];
			$messageid = 'intro' . $generalintro[0];
		}
		$formdata[$code . "intro"] = array(
			"label" => _L("General"),
			"fieldhelp" => _L('This is the introduction which plays before non-emergency messages. See the Guide for content suggestions.'),
			"value" => array("message" => $messageid),
			"validators" => array(array("ValIntroSelect", "languagecode" => $code)),
			"control" => array("IntroSelect",
				 "values"=>$generalmessages,
				 "defaultfile" => "$language/DefaultIntro.wav",
			),
			"helpstep" => $helpstepindex
		);
	}
	if($allowedjobtypes["Emergency"] > 0) {
		$emergencyintro = QuickQueryRow("select m.id , m.name from message m, prompt p where p.type='emergencyintro' and p.languagecode=? and p.messageid = m.id and m.type='phone'",false,false,array($code));

		$emergencymessages = $messagevalues;

		if($language == "Spanish") {
			$emergencymessages["message"][""] = "Spanish - System Emergency Intro";
		} else {
			$emergencymessages["message"][""] = "English - System Emergency Intro";
		}

		$messageid = "";
		if($emergencyintro) {
			$emergencymessages["message"]['intro' . $emergencyintro[0]] = $emergencyintro[1];
			$messageid = 'intro' . $emergencyintro[0];
		}
		$formdata[$code . "emergencyintro"] = array(
			"label" => _L("Emergency"),
			"fieldhelp" => _L('This is the introduction which plays before an emergency message. See the Guide for content suggestions.'),
			"value" => array("message" => $messageid),
			"validators" => array(array("ValIntroSelect", "languagecode" => $code)),
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
		$introtypes = array('intro','emergencyintro');
		foreach($introtypes as $introtype) {
			if(isset($postdata[$introtype])) {
				$messagevalues = json_decode($postdata[$introtype]);
				if(substr($messagevalues->message,0,5) != "intro") {
					if(isset($messagevalues->message) && strlen($messagevalues->message) > 0) {
						$msgid = $messagevalues->message + 0;
						$newmsg = new Message($msgid);
						$msggroupname = QuickQuery("select name from messagegroup where id = ?", false,array($newmsg->messagegroupid));

						if(!$newmsg->deleted) {		// if deleted the old value is still the intro
							$newmsg->id = null;
							$newmsg->userid = $USER->id;
							$newmsg->deleted = 1;
							$newmsg->name = ($msggroupname != false?$msggroupname:$newmsg->name) . " (Copy)";
							$newmsg->messagegroupid = null;
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
						QuickUpdate("delete from prompt where type=? and languagecode = 'en'",false,array($introtype));
						QuickUpdate("insert into prompt (type, messageid,languagecode) values (?,?,'en')",false,array($introtype,$newmsg->id));
					} else {
						QuickUpdate("delete from prompt where type=? and languagecode = 'en'",false,array($introtype));
					}
				}
			}
		}
		foreach($languages as $language => $code) {
			foreach($introtypes as $introtype) {
				if(isset($postdata[$code . $introtype])) {
					$languagevalues = json_decode($postdata[$code . $introtype]);
					if(substr($languagevalues->message,0,5) != "intro") {
						if(isset($languagevalues->message) && strlen($languagevalues->message) > 0) {
							$msgid = $languagevalues->message + 0;
							$newmsg = new Message($msgid);
							$msggroupname = QuickQuery("select name from messagegroup where id = ?", false,array($newmsg->messagegroupid));

							if(!$newmsg->deleted) {		// if deleted the old value is still the intro
								$newmsg->id = null;
								$newmsg->userid = $USER->id;
								$newmsg->deleted = 1;
								$newmsg->name = ($msggroupname != false?$msggroupname:$newmsg->name) . " (Copy)";
								$newmsg->messagegroupid = null;
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
							QuickUpdate("delete from prompt where type=? and languagecode=?",false,array($introtype,$code));
							QuickUpdate("insert into prompt (type, messageid,languagecode) values (?,?,?)",false,array($introtype,$newmsg->id,$code));
						} else {
							QuickUpdate("delete from prompt where type=? and languagecode=?",false,array($introtype,$code));
						}
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
}
function loaduser(id) {
$(id).value = Object.toJSON({"message": ""});
var request = 'ajax.php?ajax&type=Messages&messagetype=phone';

if($(id + 'user').getValue() != '')
request += '&userid=' + $(id + 'user').getValue();
cachedAjaxGet(request,setvalues,id);
}

function updatevalue(id) {
if($(id+"user") && $(id+"message")) {
$(id).value = Object.toJSON({
		"user": $(id+"user").value,
		"message": $(id+"message").value
});
}
}

</script>
<?
startWindow(_L("Intro Settings"));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>
