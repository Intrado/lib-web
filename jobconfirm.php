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


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint')) {
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
$list = new PeopleList($job->listid);
$renderedlist = new RenderedList($list);
$renderedlist->calcStats();

if ($renderedlist->total == 0)
	error("The list you selected does not have any people in it","Click Cancel to return to the Job configuration page");

////////////////////////////////////////////////////////////////////////////////
// Display

$joblangs = array("asdf");
$joblangs['phone'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'phone' and jobid = " . $job->id);
$joblangs['email'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'email' and jobid = " . $job->id);
$joblangs['print'] = DBFindMany('JobLanguage', "from joblanguage where joblanguage.type = 'print' and jobid = " . $job->id);


function alternate($type) {
	global $USER, $f, $job, $messages, $joblangs, $submittedmode;
	if($USER->authorize('sendmulti')) {
?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
			<th>Language Preference</th>
			<th>Message to Send</th>
		</tr>
<?
$id = $type . 'messageid';
//just show the selected options? allowing to edit could cause the page to become slow
//with many languages/messages
if (count($joblangs[$type]) == 0)
	echo "<tr><td colspan='2'>No alternate language and message combinations defined</td></tr>";
else
foreach($joblangs[$type] as $joblang) {
		$message = new Message($joblang->messageid);
?>
			<tr valign="middle">
				<td><?= $joblang->language ?>
				</td>
				<td><?= $message->name ?></td>
			</tr>
<?
}
?>
	</table>
<?
	} else {
		echo "&nbsp;";
	}
}

////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs";
$TITLE = "Review and Confirm Selections";
$DESCRIPTION = "After verifying job settings click Submit Job";

$f = "notification";
$s = "send";

include_once("nav.inc.php");

NewForm($f, "jobsubmit.php?jobid=$jobid");
if ($renderedlist->total > 0)
	buttons(button('submit_job',null, 'jobsubmit.php?jobid=' . $_SESSION['jobid']),button('cancel',null, 'job.php'));
else
	buttons(button('cancel',null, 'job.php'));


startWindow("Confirmation &amp; Submit");
?>
<table border="0" cellpadding="3" cellspacing="0" width="100%">
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Settings:<br></th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width="100%">
				<tr>
					<td width="30%" >Name</td>
					<td><?= htmlentities($job->name); ?></td>
				</tr>
				<tr>
					<td>Description</td>
					<td><?= htmlentities($job->description); ?></td>
				</tr>
				<tr>
					<td>Priority</td>
					<td><?= htmlentities($jobtype->name); ?></td>
				</tr>
				<tr>
					<td>List</td>
					<td><?= htmlentities($list->name); ?></td>
				</tr>
				<tr>
					<td>Total people in list:</td>
					<td><?= $renderedlist->total ?></td>
				</tr>
				<tr>
					<td>Start Date</td>
					<td><?= htmlentities(date("F jS, Y", strtotime($job->startdate))); ?></td>
				</tr>
				<tr>
					<td>Number of days to run</td>
					<td><?= 1+ (strtotime($job->enddate) - strtotime($job->startdate))/86400 ?></td>
				</tr>
				<tr>
					<td colspan="2">Delivery Window:</td>
				<tr>
					<td>&nbsp;&nbsp;Earliest</td>
					<td><?= htmlentities(date("g:i a", strtotime($job->starttime))); ?></td>
				</tr>
				<tr>
					<td>&nbsp;&nbsp;Latest</td>
					<td><?= htmlentities(date("g:i a", strtotime($job->endtime))); ?></td>
				</tr>
				<tr>
					<td>Email a report when the job completes</td>
					<td><input type="checkbox" disabled <?= $job->isOption("sendreport") ? "checked":"" ?>>Report</td>
				</tr>
			</table>
		</td>
	</tr>
<? if(strpos($job->type,"phone") !== false) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Phone:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >Default Message</td>
					<td>
<?
$phonemessage = new Message($job->phonemessageid);
echo htmlentities($phonemessage->name);
?>
					</td>
				</tr>
<? if($USER->authorize('sendmulti')) { ?>

				<tr>
					<td>Multilingual message options</td>
					<td><? alternate('phone'); ?></td>
				</tr>
<? } ?>
				<tr>
					<td>Maximum attempts</td>
					<td><?= htmlentities($job->maxcallattempts); ?></td>
				</tr>
				<? if ($USER->authorize('setcallerid')) { ?>
					<tr>
							<td>Caller&nbsp;ID</td>
							<td><?= Phone::format($job->getOptionValue("callerid")) ?></td>
					</tr>
				<? } ?>

				<tr>
					<td>Skip Duplicate Phone Numbers</td>
					<td><input type="checkbox" disabled <?= $job->isOption("skipduplicates") ? "checked":"" ?>>Skip Duplicates</td>
				</tr>
				<tr>
					<td>Call every available phone number for each person</td>
					<td><input type="checkbox" disabled <?= $job->isOption("callall") ? "checked":"" ?>>Call all phone numbers</td>
				</tr>
			</table>
		</td>
	</tr>
<? } ?>
<? if(strpos($job->type,"email") !== false) { ?>
	<tr valign="top">
		<th align="right" class="windowRowHeader bottomBorder">Email:</th>
		<td class="bottomBorder">
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >Default Message</td>
					<td>
<?
$emailmessage = new Message($job->emailmessageid);
echo htmlentities($emailmessage->name);
?>
					</td>
				</tr>
<? if($USER->authorize('sendmulti')) { ?>

				<tr>
					<td>Multilingual message options</td>
					<td><? alternate('email'); ?></td>
				</tr>
<? } ?>
			</table>
		</td>
	</tr>
<? } ?>
<? if(strpos($job->type,"print") !== false) { ?>
	<tr valign="top">
		<th align="right" valign="top" class="windowRowHeader">Print</th>
		<td>
			<table border="0" cellpadding="2" cellspacing="0" width=100%>
				<tr>
					<td width="30%" >Default Message </td>
					<td>
<?
$printmessage = new Message($job->printmessageid);
echo htmlentities($printmessage->name);
?>
					</td>
				</tr>
<? if($USER->authorize('sendmulti')) { ?>

				<tr>
					<td>Multilingual message options </td>
					<td><? alternate('print'); ?></td>
				</tr>
<? } ?>
			</table>
		</td>
	</tr>
<? } ?>
</table>

<?
endWindow();
buttons();
EndForm();

include_once("navbottom.inc.php");
?>