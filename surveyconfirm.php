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
include_once("inc/date.inc.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/Job.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Person.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/SurveyQuestionnaire.obj.php");
include_once("obj/SurveyQuestion.obj.php");
include_once("obj/Phone.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/Rule.obj.php");
include_once("obj/ListEntry.obj.php");
include_once("obj/RenderedList.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if ((!getSystemSetting('_hassurvey', true) || !$USER->authorize('survey')) && 0) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	setCurrentSurvey($_GET['id']);
	redirect();
}

$jobid = getCurrentSurvey();
if ($jobid != NULL) {
	$job = new Job($jobid);
}


$jobtype = new JobType($job->jobtypeid);

// assume one list for survey job, TODO support multilist
//$listids = QuickQueryList("select listid from joblist where jobid=?", false, false, array($jobid));
//if (isset($listids[0]))
//	$listid = $listids[0];
//$list = new PeopleList($listid);
//$renderedlist = new RenderedList2($list);
//$renderedlist->initWithList($list);
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
		$nextrenderedlist = new RenderedList2();
		$nextrenderedlist->initWithList($nextlist);
		$multilist[] = $nextlist;
		$multirenderedlist[] = $nextrenderedlist;
		$totalpersons += $nextrenderedlist->getTotal();
		$list = $nextlist; // used by single list display
	}
}


$questionnaire = new SurveyQuestionnaire($job->questionnaireid);
$questions = DBFindMany("SurveyQuestion", "from surveyquestion where questionnaireid = $job->questionnaireid order by questionnumber");

$blocksubmit = false;
if ($totalpersons == 0) {
	$blocksubmit = true;
	error("The list you've selected does not have any people in it","Click Cancel to return to the Job configuration page");
}

if (count($questions) == 0) {
	$blocksubmit = true;
	error("The questionnaire you've selected does not contain any questions","Click Cancel to return to the Survey configuration page");
}
$warnearly = $SETTINGS['feature']['warn_earliest'] ? $SETTINGS['feature']['warn_earliest'] : "7:00 am";
$warnlate = $SETTINGS['feature']['warn_latest'] ? $SETTINGS['feature']['warn_latest'] : "9:00 pm";
if( ( (strtotime($job->starttime) > strtotime($warnlate)) || (strtotime($job->endtime) < strtotime($warnearly))
	|| (strtotime($job->starttime) < strtotime($warnearly)) || (strtotime($job->endtime) > strtotime($warnlate)) ) && $questionnaire->hasphone != 0)
	{
		error("WARNING: The call window for this survey is set for: ". date("g:i a", strtotime($job->starttime)) . " - " . date("g:i a", strtotime($job->endtime)));
		error("These times fall outside the range of typical calling hours");
	}



////////////////////////////////////////////////////////////////////////////////
// Display Functions
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
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Review and Confirm Selections";
$DESCRIPTION = "After verifying survey settings click Submit Survey";

include_once("nav.inc.php");

if (!$blocksubmit)
	buttons(button('Save For Later', null, 'surveys.php'),
			button('Modify Survey Settings',null, 'survey.php'),
			button('Submit Survey',null, 'jobsubmit.php?jobid=' . $jobid));
else {
	buttons(button('Modify Survey Settings',null, 'survey.php'));
}	
	
startWindow("Confirmation &amp; Submit");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td class="bottomBorder" width="30%" >Survey Name</td>
					<td class="bottomBorder" ><?= escapehtml($job->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Description</td>
					<td class="bottomBorder" ><?= escapehtml($job->description); ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="bottomBorder" >Job Type</td>
					<td class="bottomBorder" ><?= escapehtml($jobtype->name); ?></td>
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
					<td class="bottomBorder" >Start Date</td>
					<td class="bottomBorder" ><?= escapehtml(date("F jS, Y", strtotime($job->startdate))); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Number of days to run</td>
					<td class="bottomBorder" ><?= 1+ (strtotime($job->enddate) - strtotime($job->startdate))/86400 ?></td>
				</tr>
				<tr>
					<td colspan="2">Survey Time Window:</td>
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

<? if($questionnaire->hasphone) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" class="bottomBorder" >Maximum attempts</td>
					<td class="bottomBorder" ><?= escapehtml($job->getOptionValue("maxcallattempts")); ?></td>
				</tr>
				<? if ($USER->authorize('setcallerid')  && !getSystemSetting('_hascallback', false)) {
					$callerid = $job->getOptionValue('callerid');
					if (!isset($callerid) || $callerid === "")
						$callerid = getSystemSetting('callerid');
				?>
					<tr>
						<td>Caller&nbsp;ID</td>
						<td><?= Phone::format($callerid) ?>&nbsp;</td>
					</tr>
				<? } ?>
			</table>
		</td>
	</tr>
<? } ?>

	<tr valign="top">
		<th align="right" class="windowRowHeader">Survey Template:</th>
		<td >
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" class="bottomBorder" >Template Name</td>
					<td class="bottomBorder" ><?= escapehtml($questionnaire->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Description</td>
					<td class="bottomBorder" ><?= escapehtml($questionnaire->description); ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="bottomBorder" >Randomize Question Order</td>
					<td class="bottomBorder" ><input type="checkbox" disabled <?= $questionnaire->dorandomizeorder ? "checked":"" ?>></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Phone Survey</td>
					<td class="bottomBorder" ><input type="checkbox" disabled <?= $questionnaire->hasphone ? "checked":"" ?>></td>
				</tr>
<?
				if($USER->authorize("leavemessage")){
?>
					<tr>
						<td class="bottomBorder" >Leave Message</td>
						<td class="bottomBorder" ><input type="checkbox" disabled <?= $questionnaire->leavemessage ? "checked":"" ?>></td>
					</tr>
<?
				}
?>
				<tr>
					<td class="bottomBorder" >Web Survey</td>
					<td class="bottomBorder" ><input type="checkbox" disabled <?= $questionnaire->hasweb ? "checked":"" ?>></td>
				</tr>
				<tr>
					<td>Number of Questions</td>
					<td><?= count($questions) ?></td>
				</tr>
			</table>
		</td>
	</tr>

</table>
<?

endWindow();


buttons();
include_once("navbottom.inc.php");
?>
