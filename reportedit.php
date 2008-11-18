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
require_once("inc/reportutils.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/form.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}



$options = array();


if(isset($_GET['reportid'])){
	$reportid = $_GET['reportid']+0;
	if(!userOwns("reportsubscription",$reportid)){
		redirect('unauthorized.php');
	}
	$subscription = new ReportSubscription($reportid);
	$instanceid = $subscription->reportinstanceid;
	if($instanceid)
		$instance = new ReportInstance($instanceid);
	else{
		error_log("Subscription exists without instance; ID=" . $subscription->id);
		$instance = new ReportInstance();
	}
	$options = $instance->getParameters();
	$_SESSION['report']['options'] = $options;
	$_SESSION['reportid'] = $reportid;
	redirect();
} else {
	$options = $_SESSION['report']['options'];
	if(isset($_SESSION['reportid'])){
		$subscription = new ReportSubscription($_SESSION['reportid']);
		$instance = new ReportInstance($subscription->reportinstanceid);
	} else {
		$subscription = new ReportSubscription();
		$subscription->createDefaults(report_name($options['reporttype']));
		$instance = new ReportInstance();
	}
}

$reload=0;
$f="reports";
$s="scheduler";

if(CheckFormSubmit($f, $s))
{
	if(CheckFormInvalid($f))
	{
		print '<div class="warning">Form was edited in another window, reloading data.</div>';
		$reload = 1;
	}
	else
	{
		MergeSectionFormData($f, $s);
		
		$emaillist = GetFormData($f, $s, "email");
		$emaillist = preg_replace('[,]' , ';', $emaillist);
		$emaillist = trim($emaillist,"\t\n\r\0\x0B,; ");
		
		TrimFormData($f, $s,'name');
		TrimFormData($f, $s,'description');
		TrimFormData($f, $s,'date');		
		
		$radio = GetFormData($f, $s, "radio");
		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if($radio == "runonce" && !strtotime(GetFormData($f, $s, "date"))){
			error('That date was invalid');
		} else if($radio != "none" && !$emaillist){
			error('An email address is required');
		} else if($bademaillist = checkemails($emaillist)) {
			error("These emails are invalid", $bademaillist);
		} else {
			$subscription->time = date("H:i", strtotime(GetFormData($f, $s, "time")));
			$subscription->type = 'notscheduled';
			$subscription->email = $emaillist;

			switch($radio){
				case 'runonce':
					$subscription->nextrun = date("Y-m-d", strtotime(GetFormData($f, $s, "date"))) ." " . $subscription->time;
					$subscription->daysofweek = null;
					$subscription->dayofmonth = null;
					$subscription->type = 'once';
					break;
				case 'dow':
					$dow = array();
					for($i=1; $i<8; $i++){
						if(GetFormData($f, $s, "dow$i"))
							$dow[] = $i;
					}
					$subscription->daysofweek = implode(",", $dow);
					$subscription->dayofmonth = null;
					$subscription->type = 'weekly';
					break;
				case 'dom':
					$subscription->dayofmonth = GetFormData($f, $s, "dom");
					$subscription->daysofweek = null;
					$subscription->type = 'monthly';
					break;
			}
			$subscription->nextrun = $subscription->calcNextRun();

			$subscription->userid = $USER->id;
			$subscription->name = GetFormData($f, $s, "name");
			$subscription->description = GetFormData($f, $s, "description");
			$options['subname'] = $subscription->name;
			$options['description'] = $subscription->description;
			$instance->setParameters($options);
			$instance->update();
			$subscription->reportinstanceid=$instance->id;
			$subscription->update();
			ClearFormData($f);
			redirect("reports.php");
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
	if(isset($subscription)){
		$dows = $subscription->daysofweek;
		$dowarray = explode(",", $dows);
		$dom = $subscription->dayofmonth;
		if($subscription->type == 'once')
			$rundate = date("M j, Y", strtotime($subscription->nextrun));
	}
	for($i=1; $i<8;$i++){
		PutFormData($f, $s, "dow$i", in_array($i, $dowarray) ? "1" : "0", "bool", "0", "1");
	}
	PutFormData($f, $s, "dom", $dom ? $dom : "1" );
	PutFormData($f, $s, "subscription", isset($subscription) ? $subscription->id : "");
	PutFormData($f, $s, "date", $rundate, "text");
	PutFormData($f, $s, "description", isset($subscription) ? $subscription->description : "", "text", "nomin", "nomax");
	PutFormData($f, $s, "name", isset($subscription) ? $subscription->name : "", "text", "nomin", "nomax", true);
	PutFormData($f, $s, "email", isset($subscription) ? $subscription->email : $USER->email, "text");

	$radio = "none";
	if($rundate){
		$radio= "runonce";
	} else if($dows){
		$radio= "dow";
	} else if($dom){
		$radio= "dom";
	}
	PutFormData($f, $s, "radio", $radio);

	if(isset($subscription->time)){
		$settime = date("g:i a", strtotime($subscription->time));
	}
	PutFormData($f, $s, "time",  isset($settime) ? $settime : "8:00 am");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:reports";
$TITLE = "Saved/Scheduled Report";

include("nav.inc.php");
NewForm($f);
if(isset($_SESSION['report']['edit']))
	$back = button("Back", "window.history.go(-1)");
else {
	$fallbackUrl = "reports.php";
	$back = button("Back", "location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
}
buttons($back, submit($f, $s, "Save"));
startWindow("Report Details ".help('ReportEdit_ReportDetails'));
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Report:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="3" cellspacing="0">
				<tr><td>Type:</td><td><?=report_name($options['reporttype'])?></td></tr>
				<tr><td>Report Name:</td><td><? NewFormItem($f, $s, 'name', 'text', '50')?></td></tr>
				<tr><td>Description:</td><td><? NewFormItem($f, $s, 'description', 'text', '50')?></td></tr>

			</table>
		</td>
	</tr>
	<tr valign="top"><th align="right" class="windowRowHeader">Schedule:</th>
		<td>
			<table  border="0" cellpadding="3" cellspacing="0">
				<tr>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "none", "id=radio_none' onclick='hide(\"schedule\")'")?> None</td>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "runonce", "id='radio_date' onclick='show(\"schedule\");show(\"date\");show(\"date2\");hide(\"weekly\");hide(\"monthly\");hide(\"weekly2\");hide(\"monthly2\")'")?>Run Once</td>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "dow", "id='radio_dow' onclick='show(\"schedule\");show(\"weekly\");show(\"weekly2\");hide(\"date\");hide(\"monthly\");hide(\"date2\");hide(\"monthly2\")'")?>Daily/Weekly</td>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "dom", "id='radio_dom' onclick='show(\"schedule\");show(\"monthly\");show(\"monthly2\");hide(\"weekly\");hide(\"date\");hide(\"weekly2\");hide(\"date2\")'")?>Monthly</td>
				</tr>
			</table>
			<table>
				<tr>
					<td>
						<table id='schedule'>
							<tr align="left">
								<td>Time: </td>
								<td><? time_select($f,$s,"time"); ?></td>
							</tr>
							<tr>
								<td>
									<div id='date'>Date:</div>
									<div id='weekly'>Day(s):</div>
									<div id='monthly'>Day of Month</div>
								</td>
								<td>
									<div id='date2'><? NewFormItem($f, $s, 'date', 'text', '25',NULL,"onfocus=\"this.select();lcs(this,false,true)\" onclick=\"event.cancelBubble=true;this.select();lcs(this,false,true)\"")?></div>
									<div id='weekly2'>
										<table border="0" cellpadding="2" cellspacing="1" class="list">
											<tr class="listHeader" align="left" valign="bottom">
												<th>Su</th>
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
									</div>
									<div id='monthly2'>
										<?
											NewFormItem($f, $s, 'dom', 'selectstart');
											NewFormItem($f, $s, 'dom', 'selectoption', " -- Day of Month -- ", "");
											for($i=1; $i<=28;$i++){
												NewFormItem($f, $s, 'dom', 'selectoption', "$i", "$i");
											}
											NewFormItem($f,$s, 'dom', 'selectoption', "Last Day of the Month", "-1");
											NewFormItem($f, $s, 'dom', 'selectend');
										?>
									</div>
								</td>
							</tr>
							<tr>
								<td>Email(s):</td>
								<td><? NewFormItem($f, $s, 'email', 'text', 72, 10000)?></td>
							</tr>
						</table>
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
<script SRC="script/calendar.js"></script>
<script>
<?
	switch($radio){
		case 'none':
			?>hide("schedule");<?
			break;
		case 'runonce':
			?>show("schedule");show("date");show("date2");hide("weekly");hide("monthly");hide("weekly2");hide("monthly2");<?
			break;
		case 'dow':
			?>show("schedule");show("weekly");show("weekly2");hide("date");hide("monthly");hide("date2");hide("monthly2");<?
			break;
		case 'dom':
			?>show("schedule");show("monthly");show("monthly2");hide("weekly");hide("date");hide("weekly2");hide("date2");<?
			break;
	}
?>
</script>