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
require_once("obj/ValTimeWindowCallEarly.val.php");
require_once("obj/ValTimeWindowCallLate.val.php");

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

<script> 
	orgid = 123;
	userid = <? print_r($_SESSION['user']->id); ?>;
	twitterReservedChars = <? print_r(mb_strlen(" http://". getSystemSetting("tinydomain"). "/") + 6); ?>;
</script>



	<div class="wrapper">
	
	<!-- <div class="main_activity"> -->

	<div class="window newbroadcast">
		<div class="window_title_wrap">

			<h2>New Broadcast</h2>
			<ul class="msg_steps cf">
				<li class="active"><a id="tab_1" ><span class="icon">1</span> Subject &amp; Recipients</a></li>
				<li><a id="tab_2" data-active="true"><span class="icon">2</span> Message Content</a></li>
				<li><a id="tab_3" data-active="true"><span class="icon">3</span> Review &amp; Send</a></li>
			</ul>

		</div>
		
		<div class="window_body_wrap">

		<form name="broadcast">
		<input type="hidden" name="broadcast_formsnum" value="3c2390b24625ab63506a642ad6bf19bb" />

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
Validator::load_validators(array("ValSmsText","ValTimeWindowCallEarly","ValTimeWindowCallLate","ValTimePassed"));
?>
</script>


<script src="script/jquery.1.7.2.min.js"></script>
<script src="script/jquery.json-2.3.min.js"></script>


<script src="script/message_sender.js"></script>
<script src="script/message_sender.validation.js"></script>
<script src="script/message_sender.global.js"></script>

<script src="script/jquery.timer.js"></script>
<script src="script/jquery.easycall.js"></script>


<script src="script/bootstrap-modal.js"></script>


<script src="script/datepicker.js"></script>
<script type="text/javascript">
var dpck_fieldname = new DatePicker({
	relative:"scheduledate",
	keepFieldEmpty:true,
	language:"en",
	enableCloseOnBlur:1,
	topOffset:20,
	zindex: 99999
	,dateFilter:DatePickerUtils.noDatesBefore(0)
});
</script>

<script src="script/speller/spellChecker.js"></script>
<script src="script/easycall.js.php"></script>
<script src="script/niftyplayer.js.php"></script>
<script src="script/ckeditor/ckeditor_basic.js"></script>
<script src="script/htmleditor.js"></script>
