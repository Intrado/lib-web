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
require_once("obj/MessageAttachment.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/MessageBody.fi.php");
require_once("obj/ValMessageBody.val.php");
require_once("obj/ValDuplicateNameCheck.val.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendemail')) {
	redirect('./');
}

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	if($_GET['id'] == "new") {
		$_SESSION['messageid'] = NULL;
		if (isset($_SESSION['emailattachment'])) {
			unset($_SESSION['emailattachment']);
		}
	}
	else
		setCurrentMessage($_GET['id']);
	redirect("messageemail.php");
} 


////////////////////////////////////////////////////////////////////////////////
// Optional Form Items And Validators
////////////////////////////////////////////////////////////////////////////////

class EmailAttach extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '
			<input id="' . $n . '" name="' . $n . '" type="hidden"></ input>  
			<table>
			<tr>
				<td></td>
				<td valign="top">
					<div id="uploadedfiles"></div>
				</td>
				</tr>
				<tr><td valign="top">
					<div id="upload_process" style="display: none;"><img src="img/ajax-loader.gif" /></div>
				</td>
					<td align="top">
						<iframe id="my_attach" src="emailattachment.php" style="width:100%;height:60px;border:0px;"></iframe>	
					</td>
				</tr>
				<tr><td>
				</td>
					<td align="top">
						<div id="uploaderror"></div>
					</td>
				</tr>	
			</table>	';
		$str .= '<script>	
			     	function startUpload(){
						$(\'upload_process\').show();	
 						return true;
					}
					function stopUpload(success,transport,errormessage){
						setTimeout ("$(\'upload_process\').hide();", 500 );
						var result = transport.evalJSON();
						var str = "";
						var contentids = Array();
						var i = 0;
						for(var contentid in result) {
							var onclick = "removeAttachment(" + contentid + ");";	
							str += result[contentid][1] + "&nbsp;(Size: " + Math.round(result[contentid][0]/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>Remove</a><br />";
							contentids[i] = contentid;
							i++;
						}
						$("' . $n . '").value = contentids.toJSON();
						$("uploadedfiles").innerHTML = str;	
						$("uploaderror").innerHTML = errormessage;
						form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
 						return true;
					}
					
					function removeAttachment(contentid) {
						new Ajax.Request(\'emailattachment.php?delete=\' + contentid, {
							method:\'get\',
							onSuccess: function (transport) {
								var result = transport.responseJSON;
								var str = "";
								var contentids = Array();
								var i = 0;
								for(var contentid in result) {
									var onclick = "removeAttachment(" + contentid + ");";
									str += result[contentid][1] + "&nbsp;(Size: " + Math.round(result[contentid][0]/1024) + "k)&nbsp;<a href=\'#\' onclick=\'" + onclick + "return false;\'>Remove</a><br />";
									contentids[i] = contentid;
									i++;
								}
								$("' . $n . '").value = contentids.toJSON();
								$("uploadedfiles").innerHTML = str;			
								form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));		
							}
						});
					}
					</script>';
		return $str;
	}
}

class ValEmailAttach extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}				
		if(count($value) > 3)
			return "Max 3 attachments allowed. Please remove one attachment.";
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				checkval = value.evalJSON();
				if(checkval.length > 3)
					return "Max 3 attachments allowed. Please remove one attachment.";
				return true;
			}';
	}
}



////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////
$messagebody = '';

if(isset($_SESSION['messageid'])) {
	$message = new Message($_SESSION['messageid']);
	$message->readHeaders();	
	$parts = DBFindMany("MessagePart","from messagepart where messageid=$message->id order by sequence");
	$messagebody = $message->format($parts);
	
	$attachments = DBFindMany("messageattachment","from messageattachment where not deleted and messageid=" . DBSafe($_SESSION['messageid']));
	foreach ($attachments as $attachment) {
		$_SESSION['emailattachment'][$attachment->contentid] = array(
					"contentid" => $attachment->contentid,
					"filename" => $attachment->filename,
					"size" => $attachment->size,
					"exists" => true
		);
	}
} else {
	$message = new Message();
	$message->fromname = $USER->firstname . " " . $USER->lastname;
	$useremails = explode(";", $USER->email);
	$message->fromemail = $useremails[0];	
}



$insertfields = FieldMap::getAuthorizedMapNames();

$formdata = array(
	"messagename" => array(
		"label" => _L('Message Name'),
		"fieldhelp" => "",
		"value" => $message->name,
		"validators" => array(
			array("ValRequired","ValLength","min" => 3,"max" => 50),
			array("ValDuplicateNameCheck","type" => "email")
		),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"description" => array(
		"label" => _L('Description'),
		"value" => $message->description,
		"validators" => array(),
		"control" => array("TextField","size" => 30, "maxlength" => 51),
		"helpstep" => 1
	),
	"&nbsp;",
	"fromname" => array(
		"label" => _L('From Name'),
		"value" => $message->fromname,
		"validators" => array(array("ValRequired","ValLength","min" => 3,"max" => 50)),
		"control" => array("TextField","size" => 25, "maxlength" => 51),
		"helpstep" => 2
	),
	"fromemail" => array(
		"label" => _L('From Email'),
		"value" => $message->fromemail,
		"validators" => array(
					array("ValRequired"),
					array("ValEmail","domain" => getSystemSetting('emaildomain'))),
		"control" => array("TextField","size" => 40, "maxlength" => 200),
		"helpstep" => 2
	),
	"subject" => array(
		"label" => _L('Subject'),
		"fieldhelp" => "Enter the subject, the from name and from e-mail address as you wish them to appear to e-mail message recipients.",
		"value" => $message->subject,
		"validators" => array(array("ValRequired","ValLength","min" => 1,"max" => 50)),
		"control" => array("TextField","size" => 50, "maxlength" => 100),
		"helpstep" => 2
	),	
	"attachements" => array(
		"label" => _L('Attachments'),
		"fieldhelp" => "You may attach up to three files that are up to 2048kB each. For greater security, certain file types are not permitted.",
		"value" => "",
		"validators" => array(array("ValEmailAttach")),
		"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
		"helpstep" => 3
	),
	"messagebody" => array(
		"label" => _L('Message Body'),
		"fieldhelp" => "The body of your e-mail can contain text as well as dynamic data elements. Carriage returns and line feeds can be used for formatting. To insert data fields, place the cursor in the desired location, and then select from the available field options to the right.",
		"value" => $messagebody,
		"validators" => array(
			array("ValRequired"),
			array("ValMessageBody")
		),
		"control" => array("MessageBody","fields" => $insertfields,"playbutton" => false),
		"helpstep" => 4
	)
);

$helpsteps = array (
	_L('Set a discriptive name to be able to easaly find your message later.'),
	'<ul><li>' . _L('Use your own name and email') . '<li>' . _L('Always set a discriptive Subject') . '</ul>',
	'<ul><li>' . _L('Attach files up to 2 MB') . '<li>' . _L('Mention the  attachments in the Message body') . '</ul>',
	_L('Type your message') . '<ul><li>' . _L('Introduce yourself') . '<li>' . _L('Keep it simple') . '<li>' . _L('Refer to attachments') . '</ul>'
	//. _L('Insert Example') . '<ul><li>' . _L('Choose First Name field') . '<li>' . _L('Default field will be used when the field is not available') . '</ul>'  
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
			
		$message = new Message($_SESSION['messageid']);
		$message->readHeaders();
		$message->type = "email";
		
		//check that the message->userid == user->id so that there is no chance of hijacking
		if ($message->id && !userOwns("message",$message->id) || $message->deleted ) {
			exit("nope!"); //TODO
		}
		$parts = $message->parse($postdata["messagebody"]);

		$message->name = trim($postdata["messagename"]);
		$message->description = trim($postdata["description"]);
		$message->modifydate = QuickQuery("select now()");
		$message->subject = trim($postdata["subject"]);
		$message->fromname = trim($postdata["fromname"]);
		$message->fromemail = trim($postdata["fromemail"]);
		$message->userid = $USER->id;
		$message->stuffHeaders();
		$message->update();
		
		//update the parts
		QuickUpdate("delete from messagepart where messageid=$message->id");
		foreach ($parts as $part) {
			$part->messageid = $message->id;
			$part->create();
		}
			//see if there is an uploaded file and add it to this email
		if (isset($_SESSION['emailattachment'])) {
			$attachmentcount = 0;
			foreach($_SESSION['emailattachment'] as $emailattachments) {
				if(!isset($emailattachments['exists']) && $attachmentcount < 3) {	
					$msgattachment = new MessageAttachment();
					$msgattachment->messageid = $message->id;
					$msgattachment->contentid = $emailattachments['contentid'];
					$msgattachment->filename = $emailattachments['filename'];
					$msgattachment->size = $emailattachments['size'];
					$msgattachment->create();	
					error_log("created new attachment");
				}
				$attachmentcount++;
			}
			unset($_SESSION['emailattachment']);
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


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = _L("notifications").":"._L("messages");
$TITLE = _L('Email Message Builder: ') . (isset($_SESSION['messageid'])? escapehtml($message->name) : _L("New Message") );
$ICON = "email.gif";

include_once("nav.inc.php");

?>
<script type="text/javascript">
<? Validator::load_validators(array("ValMessageBody","ValDuplicateNameCheck","ValEmailAttach")); ?>
</script>
<?

startWindow(_L('Message'));
echo $form->render();
endWindow();
include_once("navbottom.inc.php");
?>