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
$languages = QuickQueryList("select name from language");

$values = array("" => "Select a Message");
foreach($messages as $message)
	$values[$message->id] = $message->name;
	
$selectvalues = array("" => "Select Language");
$i = 1;
foreach($languages as $language) {
	$selectvalues[$i] = $language;
	$i++;
}
	
$mapvalues = array(0 => $selectvalues, 1 => $values);

if($IS_COMMSUITE)
	$users = DBFindMany("User","from user where enabled and deleted=0 order by lastname, firstname");
/*CSDELETEMARKER_START*/
else
	$users = DBFindMany("User","from user where enabled and deleted=0 and login != 'schoolmessenger' order by lastname, firstname");
/*CSDELETEMARKER_END*/
	
$uservalues = array("" => "Select User");
	
foreach($users as $user) {
	$uservalues[$user->id] = $user->firstname ." " . $user->lastname;
}	

$formdata = array(
	"Required Intro",
	"intromessage" => array(
		"label" => _L("Intro Message"),
		"value" => "none",
		"validators" => array(array("ValRequired")),
		"control" => array("SelectMenu",
			 "values"=>$values
		),
		"helpstep" => 1
	),
	"introtype" => array(
		"label" => _L("Intro Type"),
		"value" => "none",
		"validators" => array(array("ValRequired")),
		"control" => array("RadioButton",
			 "values"=>array(0 => "Default", 1 => "Emergency")
		),
		"helpstep" => 1
	),
	"Language Options",
	"language1" => array(
		"label" => _L("Language 1"),
		"value" => "none",
		"validators" => array(),
		"control" => array("SelectMenu",
			 "values"=>$values
		),
		"helpstep" => 2
	),
	"language2" => array(
		"label" => _L("Language 2"),
		"value" => "none",
		"validators" => array(),
		"control" => array("SelectMenu",
			 "values"=>$values
		),
		"helpstep" => 2
	),
	"language3" => array(
		"label" => _L("Language 3"),
		"value" => "none",
		"validators" => array(),
		"control" => array("SelectMenu",
			 "values"=>$values
		),
		"helpstep" => 2
	)
);

$buttons = array(submit_button(_L("Upload"),"submit","fugue/arrow_045"),
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
		$msgid = $postdata['intromessage'] + 0;
		$introtype = $postdata['introtype'] + 0;
		
		$newmsg = new Message($msgid);
		$newmsg->id = null;
		$newmsg->deleted = 1;
		
		if($introtype == 1)
			$newmsg->name = "emergency_intro";
		else
			$newmsg->name = "default_intro";
			
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
		
		
		if($introtype == 1) {
			setSystemSetting('introid_emergency', $newmsg->id);			
		}
		else {
			setSystemSetting('introid_default', $newmsg->id);
		}
		
		// Delete old intro
		//QuickUpdate("delete message m, messagepart p FROM message m, messagepart p where m.name='intro_english' and m.id!=" . $newmsg->id . " and m.id = p.messageid");										
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

//startWindow(_L("Users"));
//echo $loadform->render();
//endWindow();

startWindow(_L("Settings"));
?>
<table>
<tr>
<td>
<form class="newform" id="loadusers" name="loadusers" method="POST" action="messageintro.php" style="width: 100%;">

<table width="100%">
<tr>
<td width="30%"><label class="formlabel" >Load User Messages</label></td>
<td>
	<select id=loaduserselect name="loaduserselect" onchange="loaduser();">
		<option value=""  >Select User</option>
		<option value=""  >Current User</option>
		
<? 
foreach($users as $user) {
	echo "<option value=\"" . $user->id . "\"  >" . $user->firstname . " " . $user->lastname . "</option>";
}
?>
	</select>
	
</td>
</tr>



</table>
</form>
</td>
</tr>
</table>



<? 


echo $form->render();




endWindow();



include_once("navbottom.inc.php");

?>



<script type="text/javascript">


function setvalues(result) {
	var response = result.responseJSON;
	if (response) {	
		var output = '<option value=\"\">Select a Message</option>';//'<select id=\"defaultintro\" name=\"loaduserselect\">n';
		for (var i in response) {
			output += '    <option value=\"' + i + '\">' + response[i] + '</option>\n'
		}		
		$('introform_intromessage').innerHTML = output;
		$('introform_language1').innerHTML = output;
		$('introform_language2').innerHTML = output;
		$('introform_language3').innerHTML = output;
	}
}

function loaduser() {
	var request = 'ajax.php?ajax&type=Messages&messagetype=phone';
	
	if($('loaduserselect').getValue() != '')
		request += '&userid=' + $('loaduserselect').getValue();

	quickrequest(request,setvalues);
}

loaduser();
</script>



