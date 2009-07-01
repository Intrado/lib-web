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
include_once("obj/FieldMap.obj.php");
include_once("obj/Job.obj.php");
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
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
$list = new PeopleList($job->listid);
$renderedlist = new RenderedList($list);
$renderedlist->calcStats();
$questionnaire = new SurveyQuestionnaire($job->questionnaireid);
$questions = DBFindMany("SurveyQuestion", "from surveyquestion where questionnaireid = $job->questionnaireid order by questionnumber");

if ($renderedlist->total == 0)
	error("The list you've selected does not have any people in it","Click Cancel to return to the Job configuration page");

if (count($questions) == 0)
	error("The questionnaire you've selected does not contain any questions","Click Cancel to return to the Survey configuration page");

$warnearly = $SETTINGS['feature']['warn_earliest'] ? $SETTINGS['feature']['warn_earliest'] : "7:00 am";
$warnlate = $SETTINGS['feature']['warn_latest'] ? $SETTINGS['feature']['warn_latest'] : "9:00 pm";
if( ( (strtotime($job->starttime) > strtotime($warnlate)) || (strtotime($job->endtime) < strtotime($warnearly))
	|| (strtotime($job->starttime) < strtotime($warnearly)) || (strtotime($job->endtime) > strtotime($warnlate)) ) && $questionnaire->hasphone != 0)
	{
		error("WARNING: The call window for this survey is set for: ". date("g:i a", strtotime($job->starttime)) . " - " . date("g:i a", strtotime($job->endtime)));
		error("These times fall outside the range of typical calling hours");
	}



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "notifications:survey";
$TITLE = "Review and Confirm Selections";
$DESCRIPTION = "After verifying survey settings click Submit Survey";
$f = "survey";

include_once("nav.inc.php");
NewForm($f);

if ($renderedlist->total > 0 && count($questions) > 0)
	buttons(button('Back',null, 'survey.php'), button('Submit Survey',null, 'jobsubmit.php?jobid=' . $jobid));
else
	buttons(button('Cancel',null, 'survey.php'));

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
				<tr>
					<td class="bottomBorder" >List</td>
					<td class="bottomBorder" ><?= escapehtml($list->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Total people in list:</td>
					<td class="bottomBorder" ><span style="font-weight:bold; font-size: 120%;"><?= number_format($renderedlist->total) ?></span></td>
				</tr>
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
<? if ($USER->authorize('setcallerid')) { ?>
					<tr>
						<td>Caller&nbsp;ID</td>
						<td><?= Phone::format($job->getOptionValue("callerid")) ?>&nbsp;</td>
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
EndForm();
include_once("navbottom.inc.php");
?>