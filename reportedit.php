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
		error('Form was edited in another window, reloading data');
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
		
		$dom = (int)GetFormData($f, $s, "dom");
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
		} else if($radio == "dom" && ($dom == 0 || $dom<-1 || $dom>28)) {	
			error("The day of the month must be set");
		} else {
			$subscription->time = date("H:i", strtotime(GetFormData($f, $s, "time")));
			$subscription->modifydate = QuickQuery("select now()");
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
		if($subscription->type == 'once') {
			if ($subscription->nextrun)
				$rundate = date("M j, Y", strtotime($subscription->nextrun));
			else {
				$rundate = "";
				$subscription->type = "notscheduled";
			}
		}
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

	switch($subscription->type) {
		case "notscheduled":
			$radio = "none";
			break;
		case "once":
			$radio = "runonce";
			break;
		case "weekly":
			$radio = "dow";
			break;
		case "monthly":
			$radio = "dom";
			break;
		default:
			$radio = "none";
			break;
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
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "none", "id=radio_none' onclick='$(\"schedule\").hide()'")?> None</td>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "runonce", "id='radio_date' onclick='$(\"schedule\").show();$(\"date\").show();$(\"date2\").show();$(\"weekly\").hide();$(\"monthly\").hide();$(\"weekly2\").hide();$(\"monthly2\").hide()'")?>Run Once</td>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "dow", "id='radio_dow' onclick='$(\"schedule\").show();$(\"weekly\").show();$(\"weekly2\").show();$(\"date\").hide();$(\"monthly\").hide();$(\"date2\").hide();$(\"monthly2\").hide()'")?>Daily/Weekly</td>
					<td><? NewFormItem($f, $s, "radio", "radio", NULL, "dom", "id='radio_dom' onclick='$(\"schedule\").show();$(\"monthly\").show();$(\"monthly2\").show();$(\"weekly\").hide();$(\"date\").hide();$(\"weekly2\").hide();$(\"date2\").hide()'")?>Monthly</td>
				</tr>
			</table>
			<table>
				<tr>
					<td>
						<table style="display:<?if($radio!=="none"){echo('block');}else{echo('none');}?>" id='schedule'>
							<tr align="left">
								<td>Time: </td>
								<td><? time_select($f,$s,"time"); ?></td>
							</tr>
							<tr>
								<td>
									<div style="display:<?if($radio=="runonce"){echo('block');}else{echo('none');}?>" id='date'>Date:</div>
									<div style="display:<?if($radio=="dow"){echo('block');}else{echo('none');}?>" id='weekly'>Day(s):</div>
									<div style="display:<?if($radio=="dom"){echo('block');}else{echo('none');}?>" id='monthly'>Day of Month</div>
								</td>
								<td>
									<div style="display:<?if($radio=="runonce"){echo('block');}else{echo('none');}?>" id='date2'><? NewFormItem($f, $s, 'date', 'text', '25',NULL,"onfocus=\"this.select();pickDate(this,false,true)\" onclick=\"event.cancelBubble=true;\"")?></div>
									<div style="display:<?if($radio=="dow"){echo('block');}else{echo('none');}?>" id='weekly2'>
										<table border="0" cellpadding="2" cellspacing="1" class="list schedule">
											<tr class="listHeader" align="left" valign="bottom">
												<th>Su</th>
												<th>Mo</th>
												<th>Tu</th>
												<th>We</th>
												<th>Th</th>
												<th>Fr</th>
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
									<div style="display:<?if($radio=="dom"){echo('block');}else{echo('none');}?>" id='monthly2'>
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
<script SRC="script/datepicker.js"></script>
<script>
<?
	switch($radio){
		case 'none':
			?>hide("schedule");<?
			break;
		case 'runonce':
			?>$("schedule").show();$("date").show();$("date2").show();$("weekly").hide();$("monthly").hide();$("weekly2").hide();$("monthly2").hide();<?
			break;
		case 'dow':
			?>$("schedule").show();$("weekly").show();$("weekly2").show();$("date").hide();$("monthly").hide();$("date2").hide();$("monthly2").hide();<?
			break;
		case 'dom':
			?>$("schedule").show();$("monthly").show();$("monthly2").show();$("weekly").hide();$("date").hide();$("weekly2").hide();$("date2").hide();<?
			break;
	}
?>
</script>