<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/SurveyQuestionnaire.obj.php");
require_once("inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$CURRENTVERSION = "6.2";

if ($USER->authorize("loginweb") === false) {
	redirect('unauthorized');
}

if($USER->authorize("leavemessage")){
	$count = QuickQuery("select count(*) from voicereply where userid = '$USER->id' and listened = '0'");
}

if (isset($_GET['closewhatsnew'])) {
	QuickUpdate("delete from usersetting where userid=$USER->id and name='whatsnewversion'");
	QuickUpdate("insert into usersetting (userid, name, value) values ($USER->id, 'whatsnewversion', $CURRENTVERSION)");
}

$listsdata = DBFindMany("PeopleList"," from list where userid=$USER->id and deleted=0");

////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_surveyactions ($obj,$name) {

	return '<a href="surveytemplate.php?id=' . $obj->id . '">Edit</a>&nbsp;|&nbsp;'
			. '<a href="survey.php?scheduletemplate=' . $obj->id . '">Schedule</a>&nbsp;|&nbsp;'
			. '<a href="surveys.php?deletetemplate=' . $obj->id . '">Delete</a>';
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = 'start:start';
$TITLE = _L('Welcome %1$s %2$s',
	escapehtml($USER->firstname),
	escapehtml($USER->lastname));

if($USER->authorize("leavemessage")){
	if($count > 0){
		$DESCRIPTION = "<img src=\"img/bug_important.gif\"> You have unplayed responses to your notifications..." .
				"<a href=\"replies.php?jobid=all&showonlyunheard=true\">click to view</a>";
	}
}
include_once("nav.inc.php");

if ($listsdata) {
?>
	<table border=0 cellpadding=0 cellspacing=5>
	<tr>
		<td valign="top">
			<table width="300px" border=0 cellpadding=0 cellspacing=0>
<?
		  	if ($USER->getSetting("whatsnewversion") != $CURRENTVERSION) {
			?><tr><td><?
			  	$startCustomTitle = _L("What's New");
				startWindow($startCustomTitle,NULL);

?>				<div align="center" style="margin: 5px;">
					<div style="text-align: left; padding: 5px;">
					<p>Update 6.2</p>
					<a href="#" onclick="window.open('help/schoolmessenger_help.htm#getting_started/new_features.htm', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');">
					<img src="img/bug_lightbulb.gif" >Click here to see what's new.</a>
					</div>
					<BR>
				</div>

				<table align="center"><tr><td>
<?				echo button(_L('Close'), null, 'start.php?closewhatsnew'); ?>
				</td></tr></table>

<?
				endWindow();
			?><br></td></tr><?
			}
			if ( (( ($USER->authorize("starteasy") && $USER->authorize('sendphone'))
				|| $USER->authorize('sendphone')
				|| $USER->authorize('sendemail')
				|| $USER->authorize('sendprint')
				|| $USER->authorize('sendsms')) && $listsdata)
				|| $USER->authorize('createlist')) {
				$theme = getBrandTheme();
			?><tr><td><?
				startWindow(_L('Quick Start ') . help('Start_EasyCall'),NULL);
				?>
				<table border="0" cellpadding="3" cellspacing="0" width=100%>
				<?
				if ($USER->authorize("starteasy") && $USER->authorize('sendphone')) {
					?>
					<tr>
						<td align="right" valign="center" class="bottomBorder"><div NOWRAP class="destlabel">Basic<?=help('Start_EasyCall', '', 'small')?></div></td>
						<td class="bottomBorder" style="padding: 5px;" valign="center">
							<img src="img/themes/<?=$theme?>/b1_easycall2.gif" onclick="window.location = 'jobwizard.php'"
							onmouseover="this.src='img/themes/<?=$theme?>/b2_easycall2.gif'"
							onmouseout="this.src='img/themes/<?=$theme?>/b1_easycall2.gif'">
						</td>
					</tr>
					<?
				}
				if ($USER->authorize('sendphone') || $USER->authorize('sendemail') || $USER->authorize('sendprint') || $USER->authorize('sendsms')) {
					?>
					<tr>
						<td align="right" valign="center" class="bottomBorder"><div NOWRAP class="destlabel">Advanced<?=help('Jobs_AddStandardJob', '', 'small')?></div></td>
						<td class="bottomBorder" style="padding: 5px;" valign="center"><?=button_bar(button(_L('Create New Job'), NULL,"job.php?origin=start&id=new"))?></td>
					</tr>
					<?
				}
				if ($USER->authorize('createlist')) {
					?>
					<tr>
						<td align="right" valign="center"><div NOWRAP class="destlabel">List<?=help('Lists_AddList', '', 'small')?></div></td>
						<td style="padding: 5px;" valign="center"><?=button_bar(button(_L('Create New List'), NULL,"list.php?origin=start&id=new"))?></td>
					</tr>
				<?
				}
				?>
				</table>
				<?
				endWindow();
			?><br></td></tr><?
			}
			if ($USER->authorize("startstats")) {
?>
			<tr><td><?
				startWindow(_L('My Active Calls'),NULL);
				button_bar(button(_L('Refresh'), 'window.location.reload()'));
				?><div align="center"><img src="graph_start_actrive_breakdown.png.php?junk=<?= rand() ?>" /></div><?
				endWindow();
?>
				</td>
			</tr>
			</table>
		</td>
		<td width="100%" valign="top">
<?

			$limit = 5; // Limit on max # of each type of job to show on the start page.

			startWindow(_L('My Jobs ') . help('Start_MyActiveJobs'),NULL,true);

			$data = DBFindMany("Job","from job where userid=$USER->id and (status='active' or status = 'new' or status='cancelling' or status='scheduled' or status='procactive' or status='processing') and type != 'survey' and deleted = 0 order by id desc limit $limit");
			$titles = array(	"name" => "Job Name",
								"type" => "Deliver by",
								"Status" => "Status",
								"responses" => "Responses (Unplayed/Total)",
								"Actions" => "Actions"
								);
			$formatters = array("type" => "fmt_obj_delivery_type_list", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status', 'responses' => 'fmt_response_count');
			if(!$USER->authorize('leavemessage')){
				unset($titles["responses"]);
				unset($formatters["responses"]);
			}
			if (count($data)) {
				button_bar(button(_L('Refresh'), 'window.location.reload()'));
				showObjects($data, $titles, $formatters);
			} else {
				?><div style="font-size: xx-small; float:left; color: grey;" >You have no active jobs...</div><?
			}
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();



			startWindow(_L('My Completed Jobs ') . help('Start_MyCompletedJobs'),NULL,true);

			$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0 order by finishdate desc limit $limit");
			$titles = array(	"name" => "Job Name",
								"type" => "Deliver by",
								"Status" => "Status",
								"enddate" => "End Date",
								"responses" => "Responses (Unplayed/Total)",
								"Actions" => "Actions"
								);
			$formatters = array("type" => "fmt_obj_delivery_type_list", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status',"enddate" => "fmt_job_enddate", "responses" => "fmt_response_count");
			if(!$USER->authorize('leavemessage')){
				unset($titles["responses"]);
				unset($formatters["responses"]);
			}
			if (count($data)) {
				showObjects($data, $titles, $formatters);
			} else {
				?><div style="font-size: xx-small; float:left; color: grey;" >You have no completed jobs...</div><?
			}
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();


			if (getSystemSetting('_hassurvey', true) && $USER->authorize("survey")) {

				startWindow(_L('My Surveys ') . help('Start_MyActiveJobs'),NULL,true);

				$data = DBFindMany("Job","from job where userid=$USER->id and (status='active' or status = 'new' or status='cancelling' or status='procactive' or status='processing' or status='scheduled') and type='survey' and deleted = 0 order by id desc limit $limit");
				$titles = array(	"name" => "Job Name",
									"type" => "Deliver by",
									"Status" => "Status",
									"responses" => "Responses (Unplayed/Total)",
									"Actions" => "Actions"
									);
				$formatters = array("type" => "fmt_surveytype", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status', "responses" => "fmt_response_count");
				if(!$USER->authorize('leavemessage')){
					unset($titles["responses"]);
					unset($formatters["responses"]);
				}

				if (count($data)) {
					button_bar(button(_L('Refresh'), 'window.location.reload()'));
					showObjects($data, $titles, $formatters);
				} else {
					?><div style="font-size: xx-small; float:left; color: grey;" >You have no active surveys...</div><?
				}
				?><div style="text-align:right; white-space:nowrap"><a href="surveys.php" style="font-size: xx-small;">More...</a></div><?
				endWindow();



				startWindow(_L('My Completed Surveys ') . help('Start_MyCompletedJobs'),NULL,true);

				$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type='survey' and deleted = 0 order by finishdate desc limit $limit");
				$titles = array(	"name" => "Job Name",
									"type" => "Deliver by",
									"Status" => "Status",
									"enddate" => "End Date",
									"responses" => "Responses (Unplayed/Total)",
									"Actions" => "Actions"
									);
				$formatters = array("type" => "fmt_surveytype", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status',"enddate" => "fmt_job_enddate", "responses" => "fmt_response_count");
				if(!$USER->authorize('leavemessage')){
					unset($titles["responses"]);
					unset($formatters["responses"]);
				}
				if (count($data)) {
					showObjects($data, $titles, $formatters);
				} else {
					?><div style="font-size: xx-small; float:left; color: grey;" >You have no completed surveys...</div><?
				}
				?><div style="text-align:right; white-space:nowrap"><a href="surveys.php" style="font-size: xx-small;">More...</a></div><?
				endWindow();
			}
			}


	?></td></tr></table><?

} else {
	if ($USER->getSetting("whatsnewversion") != $CURRENTVERSION) {
		QuickUpdate("delete from usersetting where userid=$USER->id and name='whatsnewversion'");
		QuickUpdate("insert into usersetting (userid, name, value) values ($USER->id, 'whatsnewversion', $CURRENTVERSION)");
	}
?>
	<table border=0 width="500px"><tr><td><?
	startWindow(_L('Getting Started '),NULL);
	?>
	<table border="0" cellpadding="3" cellspacing="0" width=100%>
		<tr>
			<td NOWRAP align="right" valign="center" class="bottomBorder"><div class="destlabel">Help:&nbsp;&nbsp; </div></td>
			<td class="bottomBorder">Need Assistance? Try the comprehensive online help system by clicking the button to the right or by using the Help link in the top right of the page.</td>
			<td class="bottomBorder"><?=button_bar(button(_L('Go To Help'), "window.open('help/index.php', '_blank', 'width=750,height=500,location=no,menubar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');"))?>
		</tr>
		<tr>
			<td NOWRAP align="right" valign="center" class="bottomBorder"><div class="destlabel">New User:&nbsp;&nbsp; </div></td>
			<td class="bottomBorder">This printable PDF training guide teaches product basics in an simple step-by-step format.</td>
			<td class="bottomBorder"><?=button_bar(button(_L('Training Guide'), NULL, "help/getting_started_online.pdf"))?>
		</tr>
	<?
	if ($USER->authorize('createlist')) {
	?>
		<tr>
			<td NOWRAP align="right" valign="center" class="bottomBorder"><div class="destlabel">List:&nbsp;&nbsp; </div></td>
			<td class="bottomBorder">Ready to start? Before sending a job you'll need to create a list.</td>
			<td class="bottomBorder"><?=button_bar(button(_L('Create New List'), NULL,"list.php?origin=start&id=new"))?>
		</tr>
	<?
	}
	?>
	</table>
	<?
	endWindow();
	?></td></tr></table><?
}

include_once("navbottom.inc.php");
?>
