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
require_once("obj/AudioFile.obj.php");
require_once("obj/Voice.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/FieldMap.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone')) {
	redirect('./');
}


////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['deleteid'])) {
	//...
}
if (isset($_GET['id'])) {
	if($_GET['id'] == "new")
		$_SESSION['messageid'] = NULL;
	else
		setCurrentMessage($_GET['id']);
	redirect("messagephone.php");
}




////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class MessageWidget extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;	
	
		$str = '
			<div>
			<table>
				<tr>
					<td valign="top" rowspan="5">
						<textarea id="'.$n.'" name="'.$n.'" rows="12" cols="50" />'.escapehtml($value).'</textarea>	'
					 .  icon_button(_L("Play"),"fugue/control","var content = $('" . $n . "').getValue();
																	var language = $('" . $this->form->name . "_language').getValue();
																	var voice = 'Female';
																	if($('" . $this->form->name . "_voice-2').checked) {
																		voice = 'Male';
																	}
																	if(content != '')
																		popup('previewmessage.php?text=' + encodeURIComponent(content) + '&language=' + encodeURIComponent(language) + '&gender=' + encodeURIComponent(voice), 400, 400);")
				. '</td>
				</tr>
				<tr>	
					<td valign="top" colspan="2">
						<b>Insert Audio Recording:</b>	
					</td>
				</tr>
				<tr>
					<td valign="top" class="bottomBorder">
						<select id="'.$n.'recording" name="'.$n.'recording">
							<option value="">-- Select an Audio File --</option>';				
		foreach($this->args['audiofiles'] as $audiofile) {
			$str .= '							<option value="' . $audiofile->id . '">' . escapehtml($audiofile->name) . '</option>';
		}					
		$str .= '		</select>'
						. icon_button(_L("Insert"),"fugue/arrow_turn_180","
									var sel = $('" . $n . "recording');							
									if (sel.options[sel.selectedIndex].value > 0) { 
										textInsert('{{' + sel.options[sel.selectedIndex].text + '}}', $('$n'));}") 
						. icon_button(_L("Play"),"fugue/control","
									var sel = $('" . $n . "recording');	
									if(sel.selectedIndex >= 1) { 
										popup('previewaudio.php?close=1&id=' + sel.options[sel.selectedIndex].value, 400, 400);}")						
						. '
					</td>	

				</tr>
				<tr>
					<td valign="top">
						<b>Insert Data Field:</b> 	
					</td>
				</tr>
				<tr>					
					<td valign="top">
						<table border="0" cellpadding="1" cellspacing="0" style="font-size: 9px; margin-top: 5px;">
							<tr>
								<td>
									<span style="font-size: 9px;">Default&nbsp;Value:</span><br />
									<input id="'.$n.'datadefault" name="'.$n.'datavalue" type="text" size="10" value=""/>
								</td>
								<td>
									<span style="font-size: 9px;">Data&nbsp;Field:</span><br />
									<select id="'.$n.'datafield" name="'.$n.'language">
										<option value="">-- Select a Field --</option>';								
		foreach($this->args['fields'] as $field)
		{
			$str .= '							<option value="' . $field . '">' . $field . '</option>';
		}
		$str .=	'						</select>
								</td>
							</tr>
						</table>	
									'. icon_button(_L("Insert"),"fugue/arrow_turn_180","
												sel = $('" . $n . "datafield'); def = $('" . $n . "datadefault').value; textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n'));") 
									. '		
												
				</td>
				</tr>
			</table>
		</div>';
		return $str;
	}
}

class ValMessageWidget extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$message = new Message();
		$errors = array();	
		$message->parse($value,$errors);  // Fill in with voice id later
		if (count($errors) > 0)	{			
			//error('There was an error parsing the message', $errors);
			$str = "There was an error parsing the message: ";
			foreach($errors as $error)
			{
				$str .= "\n" . $error;
			}
			
			return $str;
		} else {
			return true;
		}
	}
}

class ValMessageName extends Validator {
	
	var $onlyserverside = true;
	function validate ($value, $args) {	
		global $USER;
		$existsid = QuickQuery("select id from message where name=? and type='phone' and userid=? and deleted=0",false,array($value,$USER->id));		
		if($existsid && $existsid != $_SESSION['messageid']) {
			return "A message named $value already exists";
		}
		return true;
	
	}
	
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$audiofiles = DBFindMany('AudioFile', "from audiofile where userid = $USER->id and deleted != 1 order by name");
$ttslanguages = Voice::getTTSLanguages();
$languages = array();
foreach($ttslanguages as $ttslanguage) {
	$languages[$ttslanguage] = $ttslanguage;
}

$messagename = "";
$messagedescription = "";
$messagebody = "";
$messagevoice = array("language" => "English","gender" => "Female");


if(isset($_SESSION['messageid'])) {
	$message = new Message($_SESSION['messageid']);
	$messagename = $message->name;
	$messagedescription = $message->description;
	$message->readHeaders();
	$parts = DBFindMany("MessagePart","from messagepart where messageid=$message->id order by sequence");
	$messagebody = $message->format($parts);
	$messagevoice = QuickQueryRow("select language, gender from messagepart p, ttsvoice t where p.messageid=? and p.voiceid = t.id order by p.sequence limit 1",true,false,array($message->id));
}



$insertfields = FieldMap::getAuthorizedMapNames();

$formdata = array(
	"messagename" => array(
		"label" => _L('Message Name'),
		"value" => $messagename,
		"validators" => array(
			array("ValRequired","ValLength","min" => 3,"max" => 50),
			array("ValMessageName","userid" => $USER->id)
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"value" => $messagedescription,
		"validators" => array(),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"language" => array(
		"label" => _L('Language'),
		"value" => ucfirst($messagevoice["language"]),
		"validators" => array(array("ValRequired")),
		"control" => array("SelectMenu","values" => $languages),
		"helpstep" => 2
	),
	"voice" => array(
		"label" => _L('Preferred Voice'),
		"value" => ucfirst($messagevoice["gender"]),
		"validators" => array(array("ValRequired")),
		"control" => array("RadioButton","values" => array ("Female" => "Female","Male" => "Male")),
		"helpstep" => 2
	),
	"messagewidget" => array(
		"label" => _L('Message Content'),
		"value" => $messagebody,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageWidget")
		),
		"control" => array("MessageWidget", "audiofiles" => $audiofiles,"fields" => $insertfields),
		"helpstep" => 3
	)
);

$helpsteps = array (
	_L('Set a discriptive name to be able to easaly find your message later.'),
	_L('The text to speach voice will need to know what language is used'),
	_L('Type your messge. You may use the included toos to construct an advanced message with field insersts')
);

$buttons = array(submit_button(_L('Save'),"submit","tick"),
				icon_button(_L('Cancel'),"cross",null,"start.php"));
$form = new Form("phonemessage",$formdata,$helpsteps,$buttons);

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

		
		$voiceid = QuickQuery("select id from ttsvoice where language=? and gender=?",false,array($postdata["language"],$postdata["voice"]));
		
		if($voiceid === false ) {
			if($postdata["voice"] == "Female") {
				$voiceid = QuickQuery("select id from ttsvoice where language=? and gender='Male'",false,array($postdata["language"]));
			} else if($postdata["voice"] == "Male") {
				$voiceid = QuickQuery("select id from ttsvoice where language=? and gender='Female'",false,array($postdata["language"]));	
			}
		}
		if($voiceid	=== false)
			$voiceid = 1; // default to english	
		
			
		$message = new Message($_SESSION['messageid']);
		$message->readHeaders();
		$message->type = "phone";
		
		//check that the message->userid == user->id so that there is no chance of hijacking
		if ($message->id && !userOwns("message",$message->id) || $message->deleted ) {
			exit("nope!"); //TODO
		}
		$errors = array();	
		$parts = $message->parse($postdata["messagewidget"],$errors);

		$message->name = trim($postdata["messagename"]);
		$message->description = trim($postdata["description"]);
		$message->userid = $USER->id;

		$message->stuffHeaders();
		$message->update();
		
		//update the parts
		QuickUpdate("delete from messagepart where messageid=$message->id");
		foreach ($parts as $part) {
			$part->voiceid = $voiceid;
			$part->messageid = $message->id;
			$part->create();
		}
		
		if ($ajax)
			$form->sendTo("messages.php");
		else
			redirect("messages.php");
	}
}

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_somefield ($obj, $field) {
	return $obj->$field;
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "Notification:PhoneMessage";
$TITLE = _L('Phone Message Builder: New Message');

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageWidget","ValMessageName")); ?>
</script>
<?

startWindow(_L('Message'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>