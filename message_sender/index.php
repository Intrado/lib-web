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
require_once("obj/ValSmsText.val.php");
require_once("obj/ValTtsText.val.php");
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");
require_once("obj/EmailAttach.val.php");

require_once("obj/Email.obj.php");
require_once("obj/MessageAttachment.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Validators
////////////////////////////////////////////////////////////////////////////////

class ValJobName extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		$jobcount = QuickQuery("select count(id) from job where not deleted and userid=? and name=? and status in ('new','scheduled','processing','procactive','active')", false, array($USER->id, $value));
		if ($jobcount)
			return "$this->label: ". _L('There is already an active notification with this name. Please choose another.');
		return true;
	}
}

class ValTimePassed extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		$timediff = (time() - strtotime($requiredvalues[$args['field']] . " " . $value));
		if ($timediff > 0)
			return "$this->label: ". _L('Must be in the future.');
		return true;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Form Data
////////////////////////////////////////////////////////////////////////////////

$formdata = array(
	_L('Template Section 1'), // Optional
	"subject" => array(
		"label" => "Subject",
		"value" => "",
		"validators" => array(
			array("ValRequired"),
			array("ValLength","min" => 3, "max" => 30),
			array("ValJobName","type"=> "job")
		)
	),

		"emailmessagetext" => array(
		"label" => "emailmessagetext",
		"value" => "",
		"validators" => array(
			array("ValMessageBody"),
			array("ValLength","max" => 256000)
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),


	//=========================================================================================
	"SCHEDULE OPTIONS",
	//=========================================================================================
	"scheduledate" => array(
		"label" => "scheduledate",
		"value" => "",
		"validators" => array(
			// TODO: date validation
		),
		"control" => array("TextField"),
		"helpstep" => 1
	),
	"schedulecallearly" => array(
		"label" => "Start Time",
		"value" => "",
		"validators" => array(
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
			array("ValTimeWindowCallEarly", "calllatefield" => "schedulecalllate")
		),
		"control" => array("TextField"),
		"requires" => array("schedulecalllate", "scheduledate"),
		"helpstep" => 1
	),
	"schedulecalllate" => array(
		"label" => "End Time",
		"value" => "",
		"validators" => array(
			array("ValTimeCheck", "min" => $ACCESS->getValue('callearly'), "max" => $ACCESS->getValue('calllate')),
			array("ValTimeWindowCallLate", "callearlyfield" => "schedulecallearly"),
			array("ValTimePassed", "field" => "scheduledate")
		),
		"control" => array("TextField"),
		"requires" => array("schedulecallearly", "scheduledate"),
		"helpstep" => 1
	)

);


$buttons = array();
$form = new Form("broadcast",$formdata,$buttons, "vertical");

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
		
		//save data here	
		error_log('Data Saved');
		
		Query("COMMIT");
		if ($ajax)
			$form->sendTo("start.php");
		else
			redirect("start.php");
	}
}
// Moved from message_sender.php 

include("nav.inc.php");

?>

<link href="css/newui_datepicker.css" type="text/css" rel="stylesheet" />

<script> 
	userid = <? print_r($_SESSION['user']->id); ?>;
	fbAppId = <? print_r($SETTINGS['facebook']['appid']); ?>;
	twitterReservedChars = <? print_r(mb_strlen(" http://". getSystemSetting("tinydomain"). "/") + 6); ?>;
</script>

	<div class="wrapper">
	
	<!-- <div class="main_activity"> -->

	<div class="window newbroadcast">
		<div class="window_title_wrap">

			<h2><?
			echo (isset($_SESSION['message_sender']['template']['subject'])?("Broadcast Template: ". $_SESSION['message_sender']['template']['subject']):"New Broadcast")?>
			</h2>
			
			<ul class="msg_steps cf">
				<li class="active"><a id="tab_1" ><span class="icon">1</span> Subject &amp; Recipients</a></li>
				<li><a id="tab_2"><span class="icon">2</span> Message Content</a></li>
				<li><a id="tab_3"><span class="icon">3</span> Review &amp; Send</a></li>
			</ul>

		</div>
		
		<div class="window_body_wrap">

		<form name="broadcast">
		<input type="hidden" name="broadcast_formsnum" value="this should be overwritten with real serial number" />

<? include("message_sender/section_one.inc.php"); ?>

<? include("message_sender/section_two.inc.php"); ?>

<? include("message_sender/section_three.inc.php"); ?>

		</form>
		
		</div><!-- /window_body_wrap -->
		
	</div><!-- endwindow newbroadcast -->
	

	<div class="main_aside">
		<div class="help">
			<h3>Need Help?</h3>
			<p>Visit the <a href="">help section</a> or call (800) 920-3897. Also be sure to <a href="">give us feedback</a> about the new version.</p>
		</div>
	</div><!-- end main_aside-->
	
</div><!-- end wrapper -->

<? include("message_sender/modals.inc.php"); ?>
<script type="text/javascript">
<?
// Some of these are defined in jobwizard.inc.php 
Validator::load_validators(array(
	"ValSmsText","ValTimeWindowCallEarly","ValTimeWindowCallLate","ValTimePassed","ValTtsText"
));
?>
</script>


<script src="script/jquery.1.7.2.min.js"></script>
<script src="script/jquery.json-2.3.min.js"></script>
<script src="script/ckeditor/ckeditor_basic.js"></script>
<script src="script/htmleditor.js"></script>

<script type="text/javascript">
	var subject = <?echo (isset($_SESSION['message_sender']['template']['subject'])?("'". str_replace("'", "\'", $_SESSION['message_sender']['template']['subject']). "'"):"''")?>;
	var lists = <?echo (isset($_SESSION['message_sender']['template']['lists'])?$_SESSION['message_sender']['template']['lists']:'[]')?>;
	var jtid = <?echo (isset($_SESSION['message_sender']['template']['jobtypeid'])?$_SESSION['message_sender']['template']['jobtypeid']:0)?>;
	var mgid = <?echo (isset($_SESSION['message_sender']['template']['messagegroupid'])?$_SESSION['message_sender']['template']['messagegroupid']:0)?>;
</script>

<!-- 
<script src="script/message_sender.js"></script>
<script src="script/message_sender.validation.js"></script>
<script src="script/message_sender.global.js"></script>
<script src="script/message_sender.loadmessage.js"></script>
 -->
 
<script src="script/message_sender_global.js"></script>
<script src="script/message_sender_permission.js"></script>
<script src="script/message_sender_content_saver.js"></script>
<script src="script/message_sender_content.js"></script>
<script src="script/message_sender_step.js"></script>
<script src="script/message_sender_validate.js"></script>
<script src="script/message_sender_submit.js"></script>
<script src="script/message_sender.loadmessage.js"></script>
<script src="script/message_sender_base.js"></script>

<script src="script/jquery-datepicker.js"></script>

<script src="script/jquery.timer.js"></script>
<script src="script/jquery.moment.js"></script>
<script src="script/jquery.easycall.js"></script>
<script src="script/jquery.translate.js"></script>

<script src="script/bootstrap-modal.js"></script>
<!-- <script src="script/bootstrap-tooltip.js"></script> -->
<script src="script/bootstrap-dropdown.js"></script>

<script type="text/javascript">
		$("msgsndr_tts_message").observe("change", textAreaPhone_storedata.curry("messagePhoneText_message"));
		$("msgsndr_tts_message").observe("blur", textAreaPhone_storedata.curry("messagePhoneText_message"));
		$("msgsndr_tts_message").observe("keyup", textAreaPhone_storedata.curry("messagePhoneText_message"));
		$("msgsndr_tts_message").observe("focus", textAreaPhone_storedata.curry("messagePhoneText_message"));
		$("msgsndr_tts_message").observe("click", textAreaPhone_storedata.curry("messagePhoneText_message"));
		// $("messagePhoneText_message-female").observe("click", textAreaPhone_storedata.curry("messagePhoneText_message"));
		// $("messagePhoneText_message-male").observe("click", textAreaPhone_storedata.curry("messagePhoneText_message"));

		var textAreaPhone_keyupTimer = null;
		function textAreaPhone_storedata(formitem, event) {
			var form = event.findElement("form");
			if (textAreaPhone_keyupTimer) {
				window.clearTimeout(textAreaPhone_keyupTimer);
			}
			textAreaPhone_keyupTimer = window.setTimeout(function () {
					var val = $(formitem).value.evalJSON();
					val.text = $("msgsndr_tts_message").value;
					val.gender = ($(formitem+"-female").checked?"female":"male");
					$(formitem).value = Object.toJSON(val);
					//form_do_validation(form, $(formitem));
				},
				event.type == "keyup" ? 500 : 100
			);
		}

</script>

<script src="script/speller/spellChecker.js"></script>
<script src="script/easycall.js.php"></script>
<script src="script/niftyplayer.js.php"></script>


<script src="script/message_sender.previewmodal.js"></script>
<script src="script/message_sender.emailattach.js"></script>
<script src="script/message_sender.facebook.js"></script>
<script src="script/message_sender_listbuilder.js"></script>

<script src="script/datepicker.js"></script>

