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
require_once("obj/PhoneOptOutReport.obj.php");
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
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
$fields = $ffields + $gfields;

unset($_SESSION['report']['edit']);
$redirect = 0;

if(!isset($_SESSION['report']['options'])){
	redirect("reports.php");
}

if($redirect)
	redirect();

$ordering = PhoneOptOutReport::getOrdering();
$ordercount=3;

$pagestartflag=0;
$pagestart=0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart']+0;
	$pagestartflag=1;
}

$options = $_SESSION['report']['options'];
$options["pagestart"] = $pagestart;

if(!isset($_SESSION['reportid']))
	$_SESSION['saved_report'] = false;

if(!isset($_SESSION['report']['fields'])){
	foreach($fields as $field){
		$fieldnum = $field->fieldnum;
		$usersetting = DBFind("UserSetting", "from usersetting where name = '" . DBSafe($field->fieldnum) . "' and userid = '$USER->id'");
		$_SESSION['report']['fields'][$fieldnum] = false;
		if($usersetting!= null && $usersetting->value == "true"){
			$_SESSION['report']['fields'][$fieldnum] = true;
		}
	}
}

$activefields = array();
foreach($fields as $field){
	// used in csv
	if(isset($_SESSION['report']['fields'][$field->fieldnum]) && $_SESSION['report']['fields'][$field->fieldnum]){
		$activefields[] = $field->fieldnum;
	}
}
$options['activefields'] = implode(",",$activefields);
$options['format'] = 'csv'; // (most reports use PDF by default), this one does not support it, only CSV
$instance = new ReportInstance();

if(isset($_SESSION['reportid'])){
	$_SESSION['saved_report'] = true;
} else {
	$_SESSION['saved_report'] = false;
}

$_SESSION['report']['options'] = $options;

$options['pagestart'] = $pagestart;

$instance->setParameters($options);
$reportgenerator = new PhoneOptOutReport();
$reportgenerator->reportinstance = $instance;
$reportgenerator->userid = $USER->id;

if(isset($_GET['csv']) && $_GET['csv']){
	$reportgenerator->format = "csv";
} else if(isset($_GET['pdf']) && $_GET['pdf']){
	$reportgenerator->format = "pdf";
} else {
	$reportgenerator->format = "html";
}


$f="reports";
$s="phoneoptout";
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
			$submit=1;
			$options = $instance->getParameters();
			$hideinprogress = GetFormData($f, $s, "hideinprogress");
			if($hideinprogress)
				$options['hideinprogress'] = "true";
			else
				$options['hideinprogress'] = "false";
			for($i=1; $i<=$ordercount; $i++){
				$options["order$i"] = DBSafe(GetFormData($f, $s, "order$i"));
			}
			$_SESSION['report']['options']= $options;

			if(CheckFormSubmit($f, "save")){
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

if($reload){
	ClearFormData($f);
	for($i=1;$i<=$ordercount;$i++){
		$order="order$i";
		if($i==1){
			if(!isset($options[$order])){
				if(isset($_SESSION['reportid']))
					$orderquery = "";
				else
					$orderquery = "rp.pkey";
			} else
				$orderquery = $options[$order];
			PutFormData($f, $s, $order, $orderquery);
		} else {
			PutFormData($f, $s, $order, isset($options[$order]) ? $options[$order] : "");
		}
	}
	$hideinprog = 0;
	if(isset($options['hideinprogress']) && $options['hideinprogress'] == 'true')
		$hideinprog = 1;

	PutFormData($f, $s, "hideinprogress", $hideinprog, "bool", "0", "1");

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
	$TITLE = "Phone Opt-Out Results";
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$TITLE .= " - " . escapehtml($subscription->name);
	}
	if(isset($options['reldate'])){
		list($startdate, $enddate) = getStartEndDate($options['reldate'], $options);
		$DESCRIPTION = "From: " . date("m/d/Y", $startdate) . " To: " . date("m/d/Y", $enddate);
	}

	include_once("nav.inc.php");
	NewForm($f);

	$back = icon_button("Back", "fugue/arrow_180", "window.location.href='reportphoneoptout.php'");
	
	buttons($back, submit($f, $s, "Refresh", null, "arrow_refresh"), submit($f, "save", "Save/Schedule"));
	startWindow("Display Options", "padding: 3px;", "true");
	?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort By:</th>
			<td class="bottomBorder" >
<?
				selectOrderBy($f, $s, $ordercount, $ordering);
?>
			</td>
		<tr><th align="right" class="windowRowHeader bottomBorder">Output Format:</th>
			<td class="bottomBorder"><a href="reportphoneoptoutsummary.php/report.csv?csv=true">CSV</a></td>
		</tr>
	</table>
	<?
	endWindow();
	?>
	<br>
	<?

	if(isset($reportgenerator)){
		$reportgenerator->runHtml();
	}
	buttons();
	endForm();
	include_once("navbottom.inc.php");
}
?>
