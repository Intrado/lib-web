<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/form.inc.php");
require_once("inc/reportutils.inc.php");
require_once("obj/UserSetting.obj.php");
require_once("obj/ReportGenerator.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!($USER->authorize('createreport') || $USER->authorize('viewsystemreports'))) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$clear = 0;

if(isset($_GET['clear']) && $_GET['clear']){
	unset($_SESSION['report']['options']);
	unset($_SESSION['reportid']);
	$clear = 1;
}

if($clear)
	redirect();


if(isset($_GET['reportid'])){
	$reportid = $_GET['reportid'] +0;
	if(!userOwns("reportsubscription", $reportid)){
		redirect('unauthorized.php');
	}
	$_SESSION['reportid'] = $reportid;
	$subscription = new ReportSubscription($reportid);
	$instance = new ReportInstance($subscription->reportinstanceid);
	$options = $instance->getParameters();

	$_SESSION['saved_report'] = true;
	$_SESSION['report']['options'] = $options;
} else {
	$options= isset($_SESSION['report']['options']) ? $_SESSION['report']['options'] : array();
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$_SESSION['saved_report'] = true;
	} else {
		$_SESSION['saved_report'] = false;
	}
}

$f="reports";
$s="contactchange";
$reload=0;

if(CheckFormSubmit($f, $s) || CheckFormSubmit($f, "save") || CheckFormSubmit($f, "view"))
{
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check

		$startdate = TrimFormData($f, $s, "startdate");
		$enddate = TrimFormData($f, $s, "enddate");

		if(GetFormData($f, $s, "relativedate") != "xdays") {
			PutFormData($f, $s, 'xdays',"", "number");
		} else {
			TrimFormData($f, $s,'xdays');
		}
		
		$radio = GetFormData($f, $s, "radioselect");
		
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($startdate)){
			error('Beginning Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if((GetFormData($f, $s, "relativedate") == "daterange") && !strtotime($enddate)){
			error('Ending Date is not in a valid format.  February 1, 2007 would be 02/01/07');
		} else if((GetFormData($f, $s, "relativedate") == "xdays") && GetFormData($f, $s, "xdays") == ""){
			error('You must enter a number for X days');
		} else {
			$options = array();
			$options['reldate'] = GetFormData($f, $s, "relativedate");

			if($options['reldate'] == "xdays"){
				$options['lastxdays'] = GetFormData($f, $s, "xdays")+0;
			} else if($options['reldate'] == "daterange"){
				$options['startdate'] = $startdate;
				$options['enddate'] = $enddate;
			}
			foreach($options as $index => $option){
				if($option === "")
					unset($options[$index]);
			}

			$options['reporttype'] = "contactchangereport";
			$_SESSION['report']['options'] = $options;
			ClearFormData($f);
			if(CheckFormSubmit($f, "save"))
				redirect("reportedit.php");
			if(CheckFormSubmit($f, "view"))
				redirect("reportcontactchangesummary.php");
		}
	}
} else {
	$reload=1;
}


if($reload){
	ClearFormData($f, $s);
	PutFormData($f, $s, "relativedate", isset($options['reldate']) ? $options['reldate'] : "today");
	PutFormData($f, $s, 'xdays', isset($options['lastxdays']) ? $options['lastxdays'] : "", "number");
	PutFormData($f, $s, "startdate", isset($options['startdate']) ? $options['startdate'] : "", "text");
	PutFormData($f, $s, "enddate", isset($options['enddate']) ? $options['enddate'] : "", "text");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";

$TITLE = "Contact Information Changes";
if(isset($_SESSION['reportid'])){
	$subscription = new ReportSubscription($_SESSION['reportid']);
	$TITLE .= " - " . escapehtml($subscription->name);
}
include_once("nav.inc.php");
NewForm($f);

buttons( button('Back', null, "reports.php"), submit($f, "view", "View Report"),submit($f, "save", "Save/Schedule"));

//--------------- Select window ---------------
startWindow("Select ".help('ReportContactChange_Select'), NULL, false);
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Report Options:</th>
		<td class="bottomBorder">
			<table>
				<tr>
					<td>
						<div id="daterange" style="display:block">
<?
					dateOptions($f, $s, "daterangetbl");
?>
						</div>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>

<?
endWindow();
buttons();
EndForm();
include_once("navbottom.inc.php");
?>
<script type="text/javascript" src="script/datepicker.js"></script>
