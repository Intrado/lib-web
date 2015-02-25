<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/rulesutils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/reportgeneratorutils.inc.php");

require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/SmsStatusReport.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Sms.obj.php");
require_once("obj/Language.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!($USER->authorize('viewsystemreports'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Functions
////////////////////////////////////////////////////////////////////////////////
//index 5 is type
function fmt_dst_src($row, $index){
	if($row[$index] != null)
		return escapehtml(destination_label($row[5], $row[$index]));
	else
		return "";
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

unset($_SESSION['report']['edit']);
if(isset($_GET['reportid'])){
	$_SESSION['reportid'] = $_GET['reportid']+0;
	if(!userOwns("reportsubscription", $_SESSION['reportid'])){
		redirect("unauthorized.php");
	}
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();
	$_SESSION['report']['options'] = $options;
	redirect();
}

if (isset($_SESSION['report']) && isset($_SESSION['report']['options'])) {
	$options = $_SESSION['report']['options'];
} else {
	$options = array();
}

if(!isset($_SESSION['reportid']))
	$_SESSION['saved_report'] = false;

$instance = new ReportInstance();

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}

$_SESSION['report']['options'] = $options;

if(isset($_GET["csv"]) && $_GET["csv"]){
	$options["reporttype"] = "csv";
} elseif (isset($_GET["summary"]) && $_GET["summary"]) {
	$options["reporttype"] = "summary";
} elseif (isset($_GET["sms"]) && $_GET["sms"]) {
	$options["reporttype"] = "view";
	$options["sms"] = (string) $_GET["sms"];
} elseif (array_key_exists("pagestart", $_GET)) {
	$options["reporttype"] = "paged";
	$options["pagestart"] = (int) $_GET["pagestart"];
} else {
	$options["reporttype"] = "html";
}

$instance->setParameters($options);
$reportgenerator = new SmsStatusReport();
$reportgenerator->reportinstance = $instance;
$reportgenerator->userid = $USER->id;

if(isset($_GET["csv"]) && $_GET["csv"]){
	$reportgenerator->set_format("csv");
} else {
	$reportgenerator->set_format("html");
}

$f="reports";
$s="smsstatus";
$reload = 0;
$submit=0;

if(CheckFormSubmit($f,$s) || CheckFormSubmit($f, "save"))
{
	//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else {
			$submit = 1;

			if(CheckFormSubmit($f, "save")){
				$options = $instance->getParameters();
				$options["reporttype"] = "smsstatus";
				$_SESSION['report']['options']= $options;
				$_SESSION['report']['edit'] = 1;
				ClearFormData($f);
				redirect("reportedit.php");
			}
			redirect();
		}
	}
} else {
	$reload = 1;
}

if ($reload) {
	ClearFormData($f);
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$error = false;
if($reportgenerator->format != "html"){

	// PDF not supported at this time, leave code in for later just in case
	if($reportgenerator->format == "pdf"){
		if($result = $reportgenerator->testSize()){
			error($result);
			$error = true;
		} else {
			$reportgenerator->generate();
		}
	} else {
		$reportgenerator->generate();
	}
}

if($error || $reportgenerator->format == "html"){
	$reportgenerator->format = "html";
	$reportgenerator->generateQuery();
	$PAGE = "reports:reports";
	$TITLE = _L("SMS Opt-In Status");
	
	include_once("nav.inc.php");
	NewForm($f);

	$back = icon_button("Back", "fugue/arrow_180", "location.href='reports.php'");
	buttons($back, submit($f, $s, "Refresh", null, "arrow_refresh"), submit($f, "save", "Save/Schedule"));
	startWindow("Display Options", "padding: 3px;", "true");
	
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader"><p>Output Format:</p></th>
			<td>
				Search for a specific SMS number:
				<?
				NewForm($f);
				NewFormItem($f, $s, "sms", "tel");
				NewFormItem($f, "view", null, "submit");
				EndForm();
				?>
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader"><p>&nbsp;</p></th>
			<td>
				<a href="reportsmsstatus.php?summary=true">Summarize counts</a> of SMS numbers per status
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader"><p>&nbsp;</p></th>
			<td>
				<a href="reportsmsstatus.php/report.csv?csv=true">CSV download</a> of all SMS status data
			</td>
		</tr>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder"><p>&nbsp;</p></th>
			<td class="bottomBorder">
				<a href="reportsmsstatus.php?pagestart=0">View paged table</a> of all SMS status data
			</td>
		</tr>
	</table>
	<?
	endWindow();
	?>
	<br>
	<?

	if (isset($reportgenerator)) {
		$reportgenerator->runHtml();
	}
	buttons();
	endForm();
	include_once("navbottom.inc.php");
}
?>
