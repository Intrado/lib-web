<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("obj/DMResourceSchedule.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managesystem')) {
	redirect('unauthorized.php');
}
if(isset($_GET['dmid'])){
	$_SESSION['dmid'] = $_GET['dmid'] +0;
	redirect();
} else {
	$dmid = $_SESSION['dmid'];
}


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

/****************** main message section ******************/
$limit = "";
$max = 500;
$pagestart = 0;
if(isset($_GET['pagestart'])){
	$pagestart = $_GET['pagestart']+0;
}
$limit = " limit " . $pagestart . ", $max ";

$dmname = QuickQuery("select name from custdm where dmid=$dmid ");


$dmschedule = DBFind("DMResourceSchedule", "from dmschedule where dmid=$dmid");

if ($dmschedule === false) {
	$dmschedule = new DMResourceSchedule();
	$dmschedule->dmid = $dmid;
}
$showdetail = $dmschedule->resourcepercentage;

$f = "dmschedule";
$s = "main";
$reloadform = 0;

if(CheckFormSubmit($f,$s)) {
		//check to see if formdata is valid
	if(CheckFormInvalid($f))
	{
		error('Form was edited in another window, reloading data');
		$reloadform = 1;
	}
	else
	{

		MergeSectionFormData($f, $s);

		$starttime = strtotime(GetFormData($f, $s, "starttime"));
		$endtime = strtotime(GetFormData($f, $s, "endtime"));
		$throttle = GetFormData($f, $s, "throttle");
		$showdetail = $throttle;


		//do check
		if( CheckFormSection($f, $s) ) {
			error('There was a problem trying to save your changes', 'Please verify that all required field information has been entered properly');
		} else if ($throttle != 1 && ($starttime === -1 || $starttime === false)) {
			error('The start time is invalid');
		} else if ($throttle != 1 && ($endtime === -1 || $endtime === false)) {
			error('The end time is invalid');
		} else if ($throttle != 1 && ($endtime <= $starttime)) {
			error('The end time cannot be before or the same as the start time');
		} else if ($throttle != 1 && ($endtime-(30*60) < $starttime)){
			error('The end time must be at least 30 minutes after the start time');
		} else {
			//submit changes
			$changes = false;
			if ($dmschedule->resourcepercentage != $throttle)
				$changes = true;

			$dmschedule->resourcepercentage = $throttle;

			if ($throttle != 1) {
				if ($dmschedule->starttime != date("H:i", $starttime) ||
					$dmschedule->endtime != date("H:i", $endtime))
					$changes = true;

				$dmschedule->starttime = date("H:i", $starttime);
				$dmschedule->endtime = date("H:i", $endtime);

				$dow = array();
				for ($x = 1; $x < 8; $x++) {
					if (!GetFormData($f,$s,"dow$x")) {
						$dow[$x-1] = $x;
					}
				}
				if ($dmschedule->daysofweek != implode(",",$dow))
					$changes = true;

				$dmschedule->daysofweek = implode(",",$dow);
			}
			$dmschedule->update();

			if ($changes) {
				QuickUpdate("update custdm set routechange=1 where dmid = " . $dmid);
			}

			redirect("dms.php");
		}
	}
} else {
	$reloadform = 1;
}


if( $reloadform ) {
	ClearFormData($f);
	$scheduledows = array();
	$data = explode(",", $dmschedule->daysofweek);
	for ($x = 1; $x < 8; $x++) {
	    $scheduledows[$x] = !in_array($x,$data);
	}
	for ($x = 1; $x < 8; $x++) {
		PutFormData($f,$s,"dow$x",(isset($scheduledows[$x]) ? $scheduledows[$x] : 0),"bool",0,1);
	}

	PutFormData($f, $s, "throttle", $dmschedule->resourcepercentage, "select", 0.25, 0.75, true);
	PutFormData($f, $s, "starttime",date("g:i a", strtotime($dmschedule->starttime)), "select", "12:00 am", "11:45 pm", true);
	PutFormData($f, $s, "endtime",date("g:i a", strtotime($dmschedule->endtime)), "select", "12:00 am", "11:45 pm", true);
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE="admin:settings";
$TITLE="Resource Schedule Manager: ".escapehtml($dmname);

include_once("nav.inc.php");
NewForm($f);
buttons(submit($f, $s, 'Done'));


startWindow("Schedule");
?>
	<table border="0" cellpadding="3" cellspacing="0" width="100%">
		<tr>
			<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Options:</th>
			<td class="bottomBorder">
				<table border="0" cellpadding="2" cellspacing="0" width=100%>

				    <tr>
				    	<td width="30%">Limit Resources<? print help('Schedule_Resources_Limit', NULL, "small"); ?></td>

					     <td>
					   		<?
								NewFormItem($f, $s, 'throttle', 'selectstart', null, null, "onchange=checkSelection(this)");
								NewFormItem($f, $s, 'throttle', 'selectoption', "100% Capacity", 1.00);
								NewFormItem($f, $s, 'throttle', 'selectoption', "75% Capacity", 0.75);
								NewFormItem($f, $s, 'throttle', 'selectoption', "50% Capacity", 0.5);
								NewFormItem($f, $s, 'throttle', 'selectoption', "25% Capacity", 0.25);
								NewFormItem($f, $s, 'throttle', 'selectend');
							?>
					     </td>
			     	</tr>
			   	</table>
			    <div id='details' style=<? if ($showdetail == 1) {?>"display:none"<?} else {?>"display:block"<?}?>>
				<table border="0" cellpadding="2" cellspacing="0" width=100%>

					<tr>
						<td width="30%">Start Time<? print help('Schedule_Resources_StartTime', NULL, "small"); ?></td>
						<td><? time_select($f,$s,"starttime", NULL, NULL, NULL, NULL, NULL); ?></td>

					</tr>
					<tr>
						<td width="30%">End Time<? print help('Schedule_Resources_EndTime', NULL, "small"); ?></td>
						<td><? time_select($f,$s,"endtime", NULL, NULL, NULL, NULL, NULL); ?></td>
					</tr>
					<tr>
						<td width="30%">Do not limit<? print help('Schedule_Resources_Weekdays', NULL, "small"); ?></td>
						<td>
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
						</td>
					</tr>
				</table>

				</div>

			</td>
		</tr>
	</table>


<script>
function checkSelection(dropdown)
{
   if(dropdown.selectedIndex > 0) {
	   $('details').show();
   } else {
	   $('details').hide();
   }
}
</script>

<?
endWindow();
buttons();
EndForm();
?>
<div style="margin: 5px;">
	<img src="img/bug_lightbulb.gif" > Please reset the Flex Appliance after you save any changes.
</div>
<?
include_once("navbottom.inc.php");
?>