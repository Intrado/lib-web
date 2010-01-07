<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/html.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/table.inc.php");
include_once("inc/form.inc.php");
include_once("inc/date.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/JobLanguage.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/MessageGroup.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/MessagePart.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Language.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/formatters.inc.php");
include_once("obj/Rule.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Sms.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

$JOBTYPE = "normal";

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentJob($_GET['id']);
	redirect();
}

$jobid = $_SESSION['jobid'];
if ($jobid != NULL) {
	$job = new Job($_SESSION['jobid']);
}

$jobtype = new JobType($job->jobtypeid);

$totalpersons = 0;
$ismultilist = false;
$multilistids = QuickQueryList("select listid from joblist where jobid=".$job->id);
if (count($multilistids) > 0) {
	if (count($multilistids) > 1)
		$ismultilist = true;
		
	$multilist = array();
	$multirenderedlist = array();
	foreach ($multilistids as $listid) {
		$nextlist = new PeopleList($listid);
		$nextrenderedlist = new RenderedList($nextlist);
		$nextrenderedlist->calcStats();
		$multilist[] = $nextlist;
		$multirenderedlist[] = $nextrenderedlist;
		$totalpersons += $nextrenderedlist->total;
		$list = $nextlist; // used by single list display
	}
}

$blocksubmit = false;
if ($totalpersons == 0) {
	$blocksubmit = true;
	error("The list you've selected does not have any people in it","Click 'Modify Job Settings' to return to the Job configuration page");
}
$warnearly = $SETTINGS['feature']['warn_earliest'] ? $SETTINGS['feature']['warn_earliest'] : "7:00 am";
$warnlate = $SETTINGS['feature']['warn_latest'] ? $SETTINGS['feature']['warn_latest'] : "9:00 pm";
if( ( (strtotime($job->starttime) > strtotime($warnlate)) || (strtotime($job->endtime) < strtotime($warnearly))
	|| (strtotime($job->starttime) < strtotime($warnearly)) || (strtotime($job->endtime) > strtotime($warnlate)) ) && $job->phonemessageid != null)
	{
		error("WARNING: The call window for this job is set for: ". date("g:i a", strtotime($job->starttime)) . " - " . date("g:i a", strtotime($job->endtime)));
		error("These times fall outside the range of typical calling hours");
	}
if ((strtotime($job->enddate) <= strtotime("today")) && (strtotime($job->endtime) < strtotime("now"))) {
	$blocksubmit = true;
	error("The end time has passed","Click 'Modify Job Settings' to return to the Job configuration page");
} else if ((strtotime($job->enddate) <= strtotime("today")) && (strtotime($job->endtime) < strtotime("now")+1800)) {
	$blocksubmit = true;
	error("The end time can not be less than 30 minutes from now","Click 'Modify Job Settings' to return to the Job configuration page");
}
if($jobtype->systempriority == 1){
	error("........................................");// Spacing for readability between error messages
	error("WARNING:  Emergency Notifications are reserved for situations that are time-critical and require action such as school closures and temporary changes to transportation schedules or that have immediate, severe or likely impact on safety");
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


function displayMultilist() {
	global $multilist, $multirenderedlist, $totalpersons;
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
			<th>List</th>
			<th>Total People</th>
		</tr>
<?
$count = 0;
foreach($multilist as $mlist) {
	$rlist = $multirenderedlist[$count++];
?>
			<tr valign="middle">
				<td><?= escapehtml($mlist->name) ?>
				</td>
				<td>
					<?= $rlist->total ?>
				</td>
			</tr>
<?
}
?>
			<tr>
				<td class="topBorder">TOTAL:</td>
				<td class="topBorder"><span style="font-weight:bold; font-size: 120%;"><?= number_format($totalpersons) ?></span></td>
			</tr>
	</table>
<?
}

////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs";
$TITLE = "Review and Confirm Selections";
$DESCRIPTION = "After verifying job settings click Submit Job";

$f = "notification";
$s = "send";

include_once("nav.inc.php");

if (!$blocksubmit)
	buttons(button('Save For Later', null, 'jobs.php'),
			button('Modify Job Settings',null, 'job.php'),
			button('Submit Job',null, 'jobsubmit.php?jobid=' . $_SESSION['jobid']));
else {
	buttons(button('Modify Job Settings',null, 'job.php'));
}

startWindow("Confirmation &amp; Submit");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td class="bottomBorder" width="30%" >Job Name</td>
					<td class="bottomBorder" ><?= escapehtml($job->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Description</td>
					<td class="bottomBorder" ><?= escapehtml($job->description); ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="bottomBorder" >Job Type</td>
					<td class="bottomBorder" >
						<table>
							<tr>
								<td width="30%"><?= escapehtml($jobtype->name); ?></td>
<?
								if($jobtype->systempriority == 1 && getSystemSetting('_dmmethod', "asp")=='hybrid'){
?>
									<td style="color:red"><?="High capacity emergency call routing"?></td>
<?
								}
?>

							</tr>
						</table>
				</tr>
<?				if ($ismultilist) {
?>
				<tr>
					<td class="bottomBorder" >List selections</td>
					<td class="bottomBorder" ><? displayMultilist(); ?></td>
				</tr>

<?				} else {
?>
				<tr>
					<td class="bottomBorder" >List</td>
					<td class="bottomBorder" ><?= escapehtml($list->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Total people in list:</td>
					<td class="bottomBorder" ><span style="font-weight:bold; font-size: 120%;"><?= number_format($totalpersons) ?></span></td>
				</tr>
<?				}
?>
				<tr>
					<td class="bottomBorder" >Start date</td>
					<td class="bottomBorder" ><?= escapehtml(date("F jS, Y", strtotime($job->startdate))); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Number of days to run</td>
					<td class="bottomBorder" ><?= 1+ (strtotime($job->enddate) - strtotime($job->startdate))/86400 ?></td>
				</tr>
				<tr>
					<td colspan="2">Delivery window:</td>
				<tr>
					<td class="bottomBorder" >&nbsp;&nbsp;Earliest</td>
					<td class="bottomBorder" ><?= escapehtml(date("g:i a", strtotime($job->starttime))); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >&nbsp;&nbsp;Latest</td>
					<td class="bottomBorder" ><?= escapehtml(date("g:i a", strtotime($job->endtime))); ?></td>
				</tr>
				<tr>
					<td>Email a report when the job completes</td>
					<td><input type="checkbox" disabled <?= $job->isOption("sendreport") ? "checked":"" ?>>Report</td>
				</tr>
			</table>
		</td>
	</tr>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Message:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >
<?
						$message = new MessageGroup($job->messagegroupid);
						echo escapehtml($message->name);
?>
					</td>
					<td>
						<table border=0 cellpadding=3 cellspacing=0>
							<tr>
							<td>
							<div id='jobedit_messagegrid'></div>
				<script type="text/javascript">
					document.observe('dom:loaded', function() {
						load_messageinfo();
					});
					function load_messageinfo() {
						var request = 'ajax.php?ajax&type=messagegrid&id=<?=$job->messagegroupid?>';
						cachedAjaxGet(request,function(result) {
							var response = result.responseJSON;

							var str = '<table style=\'border-width:1px;\'>';
							response.headers.each(function(title) {
								str += '<th>' + title + '</th>';
							});
							response.data.each(function(item) {
								str += '<tr>';
									str += '<td>' + item.language + '</td>';
								if(response.headers[item.Phone])
									str += '<td>' + (item.Phone!=0?'<img src=\'img/icons/accept.gif\' />':'') + '</td>';
								if(response.headers[item.Email])
									str += '<td>' + (item.Email!=0?'<img src=\'img/icons/accept.gif\' />':'') + '</td>';
								if(response.headers[item.SMS])
									str += '<td>' + (item.SMS!=0?'<img src=\'img/icons/accept.gif\' />':'') + '</td>';
								str += '</tr>';
							});
							str += '</table>';
							$('jobedit_messagegrid').update(str);
						});
					}
				</script>
							</td>
							</tr>
						</table>
					</td>
				</tr>
			</table>
		</td>
	</tr>

	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Message:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
<? if($USER->authorize('leavemessage')) { ?>

					<tr>
						<td class="bottomBorder" width="30%" > Allow call recipients to leave a message</td>
						<td class="bottomBorder" ><input type="checkbox" disabled <?= $job->isOption("leavemessage") ? "checked":"" ?>>Accept Voice Responses</td>
					</tr>
<?
}
if ($USER->authorize("messageconfirmation")){
?>
					<tr>
						<td width="30%"> Allow message confirmation by recipients</td>
						<td><input type="checkbox" disabled <?= $job->isOption("messageconfirmation") ? "checked":"" ?>>Request Message Confirmation</td>
					</tr>
<?
}
?>
			</table>
		</td>
	</tr>
</table>

<?
endWindow();
buttons();

include_once("navbottom.inc.php");

?>
