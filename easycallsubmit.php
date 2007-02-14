<?
include_once('inc/common.inc.php');
include_once("inc/securityhelper.inc.php");
include_once('inc/html.inc.php');
include_once("inc/form.inc.php");
include_once('inc/table.inc.php');
include_once('obj/SpecialTask.obj.php');
include_once('obj/Message.obj.php');
include_once('obj/MessagePart.obj.php');
include_once('obj/AudioFile.obj.php');
include_once('obj/Phone.obj.php');
include_once("obj/JobType.obj.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Job.obj.php");
include_once('obj/RenderedList.obj.php');
include_once('obj/FieldMap.obj.php');
include_once('obj/JobLanguage.obj.php');

// AUTHORIZATION //////////////////////////////////////////////////
if (!$USER->authorize("starteasy")) {
	redirect("unauthorized.php");
}

$specialtask = new SpecialTask($_REQUEST['taskid']);
$f = "easycall";
$s = "submit";
$reloadform = 0;

	
if(CheckFormSubmit($f,$s)) {
	redirect("jobsubmit.php?jobid=" . $specialtask->getData('jobid') . "&close=1");
} else if (!$specialtask->getData('jobid')) {
	$job = Job::jobWithDefaults();
	//get the job name, type, and messageid

	$name = $specialtask->getData('name');
	
	if (!$name)
		$name = "EasyCall - " . date("F jS, Y g:i a");
	$job->name = $name;
	$job->description = "EasyCall - " . date("F jS, Y g:i a");
	$type = $specialtask->getData('jobtypeid');
	$job->listid = $specialtask->getData('listid');
	$job->jobtypeid = $type;
	$job->sendphone = true;
	$job->type = "phone";
	$messagelangs = $specialtask->getData('messagelangs');
	if($messagelangs) {
		$messagelangs = unserialize($messagelangs);
		foreach($messagelangs as $lang => $message){
			if($lang == "Default"){
				$job->phonemessageid = $message;
				$job->create();
			} else {
				$joblang = new JobLanguage();
				$joblang->type = "phone";
				$joblang->language = $lang;
				$joblang->messageid = $message;
				$joblang->jobid = $job->id;
				if ($joblang->language && $joblang->messageid) {
					$joblang->create();
				}
			}
		}
	}
	if($job->id){
		$specialtask->setData('jobid', $job->id);
	}
	$specialtask->update();
} else {
	$job = new Job($specialtask->getData('jobid'));
}


$jobtype = new JobType($specialtask->getData("jobtypeid"));
$list = new PeopleList($specialtask->getData("listid"));

////////////////////////////////////////////////////////////////////////////////
// FUNCTIONS
function alternatelangs($messagelangs) {
	?>
	<table border="0" cellpadding="2" cellspacing="1" class="list">
		<tr class="listHeader" align="left" valign="bottom">
			<th>Language Preference</th>
			<th>Message to Send</th>
			<th>&nbsp;</th>
		</tr>
		<?
		
		if (count($messagelangs) == 1)
			echo "<tr><td colspan='2'>No additional language and message combinations defined</td></tr>";
		else
			if($messagelangs){
				foreach($messagelangs as $lang => $messageid) {
					if($lang == "Default")
						continue;
					$message = new Message($messageid);
					?>
						<tr valign="middle">
							<td><?= $lang ?> </td>
							<td><?= htmlentities($message->name) ?></td>
							<td>&nbsp;<?= button('play', "popup('previewmessage.php?id=" . $messageid . "', 400, 400);"); ?></td>
						</tr>
					<?
				}
			}
		?>
	</table>
	<?
}

function getSetting($name) {
	global $USER;
	$name = DBSafe($name);
	return QuickQuery("select value from setting where customerid = $USER->customerid and name = '$name'");
}


////////////////////////////////////////////////////////////////////////////////
// Display
$TITLE = 'EasyCall';

include_once('popup.inc.php');

NewForm(f);
buttons(submit($f,$s, 'submit','submit_job'), button('modifyjobsetting', "window.opener.document.location='job.php?id=$job->id'; window.close();"), button('saveforlater', 'window.opener.document.location.reload(); window.close(); '));

startWindow("Confirmation &amp; Submit");

?>
<table border="0" cellpadding="3" cellspacing="0" width="400">
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Job Priority:</td>
		<td class="bottomBorder"><?= htmlentities($jobtype->name) ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">List to Call:</td>
		<td class="bottomBorder"><?= htmlentities($list->name) ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Total people in list:</td>
		<td class="bottomBorder">
		<?
			$renderedlist = new RenderedList($list);
			$renderedlist->calcStats();
			print $renderedlist->total;
		?>
	</td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Start Date:</td>
		<td class="bottomBorder"><?= htmlentities(date("F jS, Y", strtotime($job->startdate))) ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">End Date:</td>
		<td class="bottomBorder"><?= htmlentities(date("F jS, Y", strtotime($job->enddate))) ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Earliest time to Call:</td>
		<td class="bottomBorder"><?= htmlentities(date("g:i a", strtotime($job->starttime))) ?></td>
	</tr>
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Latest time to Call:</td>
		<td class="bottomBorder"><?= htmlentities(date("g:i a", strtotime($job->endtime))) ?></td>
	</tr>
	
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Default Message:</td>
		<td class="bottomBorder" >
			<?
			$messagelangs = unserialize($specialtask->getData('messagelangs'));
			$phonemessage = new Message($messagelangs["Default"]);
			echo htmlentities($phonemessage->name);
			if($messagelangs)
				echo "&nbsp;" . button('play', "popup('previewmessage.php?id=" . $job->phonemessageid . "', 400, 400);");
			else
				echo "&nbsp;";
			?>
		</td>
	</tr>
	<? if($USER->authorize('sendmulti')) { ?>
		<tr>
			<th align="right" class="windowRowHeader bottomBorder">Additional Languages:</td>
			<td class="bottomBorder" ><? alternatelangs($messagelangs); ?></td>
		</tr>
	<? } ?>
		
	<tr>
		<th align="right" class="windowRowHeader bottomBorder">Maximum Attempts:</td>
		<td class="bottomBorder"><?= htmlentities($job->maxcallattempts) ?></td>
	</tr>
	<tr>
		<td colspan="2" style="padding: 10px;"><img src="img/bug_lightbulb.gif" > To send this message using settings that are different than the defaults, click the Modify Job Settings button. You may also save this job, and it will appear in the My Active and Pending Jobs section on the Start tab. From there you can edit and submit the job at any time.
		</td>
	</tr>

</table>

<?

endWindow();
buttons();
EndForm();

include_once('popupbottom.inc.php');
?>