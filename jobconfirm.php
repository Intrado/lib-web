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
	error("The list you've selected does not have any people in it","Click Cancel to return to the Job configuration page");

////////////////////////////////////////////////////////////////////////////////
// Display

$joblangs = array();
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
<? if ($type == "phone") { ?>
			<th>&nbsp;</th>
<? } ?>
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
				<td><?= htmlentities($message->name) ?></td>
<? if ($type == "phone") { ?>
				<td>&nbsp;<?= button('play', "popup('previewmessage.php?id=" . $job->phonemessageid . "', 400, 400);"); ?></td>
<? } ?>
				</td>
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
	buttons(button('submit_job',null, 'jobsubmit.php?jobid=' . $_SESSION['jobid']),button('back',null, 'job.php'));
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
					<td class="bottomBorder" width="30%" >Name</td>
					<td class="bottomBorder" ><?= htmlentities($job->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Description</td>
					<td class="bottomBorder" ><?= htmlentities($job->description); ?>&nbsp;</td>
				</tr>
				<tr>
					<td class="bottomBorder" >Priority</td>
					<td class="bottomBorder" ><?= htmlentities($jobtype->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >List</td>
					<td class="bottomBorder" ><?= htmlentities($list->name); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Total people in list:</td>
					<td class="bottomBorder" ><?= $renderedlist->total ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Start Date</td>
					<td class="bottomBorder" ><?= htmlentities(date("F jS, Y", strtotime($job->startdate))); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >Number of days to run</td>
					<td class="bottomBorder" ><?= 1+ (strtotime($job->enddate) - strtotime($job->startdate))/86400 ?></td>
				</tr>
				<tr>
					<td colspan="2">Delivery Window:</td>
				<tr>
					<td class="bottomBorder" >&nbsp;&nbsp;Earliest</td>
					<td class="bottomBorder" ><?= htmlentities(date("g:i a", strtotime($job->starttime))); ?></td>
				</tr>
				<tr>
					<td class="bottomBorder" >&nbsp;&nbsp;Latest</td>
					<td class="bottomBorder" ><?= htmlentities(date("g:i a", strtotime($job->endtime))); ?></td>
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
					<td class="bottomBorder"  width="30%" >Default Message</td>
					<td class="bottomBorder" >
<?
$phonemessage = new Message($job->phonemessageid);
echo htmlentities($phonemessage->name);
echo "&nbsp;" . button('play', "popup('previewmessage.php?id=" . $job->phonemessageid . "', 400, 400);");
?>
					</td>
				</tr>
<? if($USER->authorize('sendmulti')) { ?>

				<tr>
					<td class="bottomBorder" >Multilingual message options</td>
					<td class="bottomBorder" ><? alternate('phone'); ?></td>
				</tr>
<? } ?>
				<tr>
					<td class="bottomBorder" >Maximum attempts</td>
					<td class="bottomBorder" ><?= htmlentities($job->maxcallattempts); ?></td>
				</tr>
				<? if ($USER->authorize('setcallerid')) { ?>
					<tr>
						<td class="bottomBorder" >Caller&nbsp;ID</td>
						<td class="bottomBorder" ><?= Phone::format($job->getOptionValue("callerid")) ?>&nbsp;</td>
					</tr>
				<? } ?>

				<tr>
					<td class="bottomBorder" >Skip Duplicate Phone Numbers</td>
					<td class="bottomBorder" ><input type="checkbox" disabled <?= $job->isOption("skipduplicates") ? "checked":"" ?>>Skip Duplicates</td>
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
					<td class="bottomBorder"  width="30%" >Default Message</td>
					<td class="bottomBorder" >
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
					<td class="bottomBorder"  width="30%" >Default Message </td>
					<td class="bottomBorder" >
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

$callearly = $USER->getSetting("callearly", "8:00 am");
$calllate = $USER->getSetting("calllate", "9:00 pm");
if((strtotime($job->starttime) >= strtotime($calllate)) || (strtotime($job->endtime) <= strtotime($callearly))
	|| (strtotime($job->starttime) <= strtotime($callearly)) || (strtotime($job->endtime) >= strtotime($calllate)) )
	print '<script language="javascript">window.alert(\'Your message will be delivered between '. date("g:i a", strtotime($job->starttime)) .' and '. date("g:i a", strtotime($job->endtime)) .'\');</script>';


include_once("navbottom.inc.php");
?>