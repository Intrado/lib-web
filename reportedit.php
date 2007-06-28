<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportSubscription.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/form.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

$reload=0;
$f="reports";
$s="scheduler";

$options = array();

if(isset($_REQUEST['reportid'])){
	$reportsubscription = new ReportSubscription($_REQUEST['reportid']+0);
	$instanceid = $reportsubscription->reportinstanceid;
	if($instanceid)
		$reportinstance = new ReportInstance($instanceid);
	else{
		error_log("Subscription exists without instance");
		$reportinstance = new ReportInstance();
	}
	$options = $reportinstance->getParameters();
} else {
	$reportsubscription = new ReportSubscription();
	$reportinstance = new ReportInstance();
}
if(CheckFormSubmit($f, $s))
{
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reloadform = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if(GetFormData($f, $s, "radio") == 1 && !strtotime(GetFormData($f, $s, "date"))){
			error('That date was invalid');
		} else {


			$options['reporttype'] = GetFormData($f, $s, "reporttype");
			$radio = GetFormData($f, $s, "radio");
			switch($radio){
				case '1':
					$reportsubscription->date = date("Y-m-d", strtotime(GetFormData($f, $s, "date")));
					$reportsubscription->dow = null;
					$reportsubscription->dom = null;
					break;
				case '2':
					$dow = array();
					for($i=1; $i<8; $i++){
						if(GetFormData($f, $s, "dow$i"))
							$dow[] = $i;
					}
					$reportsubscription->dow = implode(",", $dow);
					$reportsubscription->date = null;
					$reportsubscription->dom = null;
					break;
				case '3':
					$reportsubscription->dom = GetFormData($f, $s, "dom");
					$reportsubscription->date = null;
					$reportsubscription->dow = null;
					break;
			}
			if ($reportsubscription->date == null &&
				$reportsubscription->dow == null &&
				$reportsubscription->dom == null) {
					$reportsubscription->time = null;
					$reportsubscription->nextrun = null;
			} else {
				$reportsubscription->time = date("H:i", strtotime(GetFormData($f, $s, "time")));
				$reportsubscription->nextrun = $reportsubscription->calcNextRun();
			}
			$reportinstance->setParameters($options);
			$reportinstance->update();

			$reportsubscription->userid = $USER->id;
			$reportsubscription->name = GetFormData($f, $s, "name");
			$reportsubscription->description = GetFormData($f, $s, "description");
			$reportsubscription->reportinstanceid = $reportinstance->id;

			$reportsubscription->update();
			redirect("reportsavedoptions.php?reportid=$reportsubscription->id");
		}
	}
} else {
	$reload=1;
}

if($reload){
	ClearFormData($f);

	$dom = "0";
	$rundate = "";
	$dowarray = array();
	$dows = "";
	if(isset($reportsubscription)){
		$dows = $reportsubscription->dow;
		$dowarray = explode(",", $dows);
		$dom = $reportsubscription->dom;
		if($reportsubscription->date && $reportsubscription->date != "0000-00-00")
			$rundate = date("M d, Y", strtotime($reportsubscription->date));
	}
	for($i=1; $i<8;$i++){
		PutFormData($f, $s, "dow$i", in_array($i, $dowarray) ? "1" : "0", "bool", "0", "1");
	}
	PutFormData($f, $s, "dom", $dom ? $dom : "1" );
	PutFormData($f, $s, "reportsubscription", isset($reportsubscription) ? $reportsubscription->id : "");
	PutFormData($f, $s, "date", $rundate, "text");
	PutFormData($f, $s, "description", isset($reportsubscription) ? $reportsubscription->name : "", "text", "nomin", "nomax");
	PutFormData($f, $s, "name", isset($reportsubscription) ? $reportsubscription->name : "", "text", "nomin", "nomax", true);
	PutFormData($f, $s, "reporttype", isset($options['reporttype']) ? $options['reporttype'] : "", null, null, null, true );

	$radio = 0;
	if($rundate){
		$radio= 1;
	} else if($dows){
		$radio= 2;
	} else if($dom){
		$radio= 3;
	}
	PutFormData($f, $s, "radio", $radio);

	if(isset($reportsubscription->time)){
		$settime = date("g:i a", strtotime($reportsubscription->time));
	}
	PutFormData($f, $s, "time",  isset($settime) ? $settime : "8:00 am");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Report Scheduler";

include("nav.inc.php");
NewForm($f);
buttons(button('cancel',null, 'reports.php'), submit($f, $s, "Save", "submit"));
startWindow("Schedule Report");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Report:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="3" cellspacing="0">
				<tr><td>Report Name:</td><td><? NewFormItem($f, $s, 'name', 'text', '50')?></td></tr>
				<tr><td>Report Description:</td><td><? NewFormItem($f, $s, 'description', 'text', '50')?></td></tr>
				<tr><td>Report Type:</td>
					<td>
						<?
							NewFormItem($f, $s, 'reporttype', 'selectstart');
							NewFormItem($f, $s, 'reporttype', 'selectoption', " -- Select a Type -- ", "");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Job Report", "jobreport");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Survey Report", "surveyreport");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Calls Report", "callsreport");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Undelivered Calls", "undelivered");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Attendance Calls", "attendance");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Emergency Calls", "emergency");
							NewFormItem($f, $s, 'reporttype', 'selectoption', "Contacts Report", "contacts");
							NewFormItem($f, $s, 'reporttype', 'selectend');
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader">Schedule:</th>
		<td>
			<table  border="0" cellpadding="3" cellspacing="0">
				<tr ><td><? NewFormItem($f, $s, "radio", "radio", NULL, "1", "id='radio_date'")?></td><td>Date: </td><td onclick="new getObj('radio_date').obj.checked=true;"><? NewFormItem($f, $s, 'date', 'text', '25')?></td></tr>
				<tr ><td><? NewFormItem($f, $s, "radio", "radio", NULL, "2", "id='radio_dow'")?></td><td>Day of the Week: </td>
					<td onclick="new getObj('radio_dow').obj.checked=true;">
						<table border="0" cellpadding="2" cellspacing="1" class="list">
							<tr class="listHeader" align="left" valign="bottom">
								<td>Su</td>
								<th>M</th>
								<th>Tu</th>
								<th>W</th>
								<th>Th</th>
								<th>F</th>
								<th>Sa</th>
							</tr>
							<tr>
								<td><? NewFormItem($f,$s,"dow1","checkbox"); ?></td>
								<td><? NewFormItem($f,$s,"dow2","checkbox"); ?></td>
								<td><? NewFormItem($f,$s,"dow3","checkbox"); ?></td>
								<td><? NewFormItem($f,$s,"dow4","checkbox"); ?></td>
								<td><? NewFormItem($f,$s,"dow5","checkbox"); ?></td>
								<td><? NewFormItem($f,$s,"dow6","checkbox"); ?></td>
								<td><? NewFormItem($f,$s,"dow7","checkbox"); ?></td>
							</tr>
						</table>
					</td>
				</tr>
				<tr><td><? NewFormItem($f, $s, "radio", "radio", NULL, "3", "id='radio_dom'")?></td>
					<td>Day of the Month: </td>
					<td onclick="new getObj('radio_dom').obj.checked=true;"><?
						NewFormItem($f, $s, 'dom', 'selectstart');
						NewFormItem($f, $s, 'dom', 'selectoption', " -- Day of Month -- ", "");
						for($i=1; $i<=28;$i++){
							NewFormItem($f, $s, 'dom', 'selectoption', "$i", "$i");
						}
						NewFormItem($f,$s, 'dom', 'selectoption', "Last Day of the Month", "31");
						NewFormItem($f, $s, 'dom', 'selectend');
					?></td>
				</tr>
				<tr><td>Time: </td>
					<td><?
						time_select($f,$s,"time");
						?>
					</td>
				</tr>
			</table>
		</td>
	</tr>
</table>
<?
EndWindow();
buttons();
EndForm();
include("navbottom.inc.php");
?>