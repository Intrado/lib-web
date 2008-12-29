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
require_once("inc/text.inc.php");
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
$TITLE = 'Welcome ' . escapehtml($USER->firstname) . ' ' . escapehtml($USER->lastname);

if($USER->authorize("leavemessage")){
	if($count > 0){
		$DESCRIPTION = "<img src=\"img/bug_important.gif\"> You have unplayed responses to your notifications..." .
				"<a href=\"replies.php?jobid=all&showonlyunheard=true\">click to view</a>";
	}
}
include_once("nav.inc.php");



if ($USER->authorize("startstats")) {
?>
	<table border=0 cellpadding=0 cellspacing=5>
	<tr>
		<td valign="top">
			<table border=0 cellpadding=0 cellspacing=0>
<?
		  	if ($USER->getSetting("whatsnewversion") != $CURRENTVERSION) {
			?><tr><td><?
			  	$startCustomTitle = "What's New";
				startWindow($startCustomTitle,NULL);
				button_bar(button('Close', null, 'start.php?closewhatsnew'));

?>				<div align="center" style="margin: 5px;">
					<div style="text-align: left; padding: 5px;">
					<p>Update 6.2</p>
					<A style="padding: 5 px;" CLASS=hoverlinks HREF="help/schoolmessenger_help.htm#getting_started/new_features.htm" target=_blank>
					<img src="img/bug_lightbulb.gif" >Click here to see what's new.
					</A>
					</div>
					<BR>
				</div>

<?				endWindow();
			?><br></td></tr><?
			}
			if (($USER->authorize("starteasy") && $USER->authorize('sendphone') && $listsdata)
				|| ($USER->authorize('sendphone') && $listsdata)
				|| ($USER->authorize('sendemail') && $listsdata)
				|| ($USER->authorize('sendprint') && $listsdata)
				|| ($USER->authorize('sendsms') && $listsdata)
				|| $USER->authorize('createlist')) {
				$theme = getBrandTheme();
			?><tr><td><?
				startWindow('Quick Start ' . help('Start_EasyCall'),NULL);
				?>
				<table border="0" cellpadding="3" cellspacing="0" width=100%>
				<?			
				if ($listsdata && $USER->authorize("starteasy") && $USER->authorize('sendphone')) {
					?>
					<tr>
						<th align="left" class="bottomBorder">EasyCall:<?=help('Start_EasyCall', '', 'small')?></th>
					</tr>
					<tr>
						<td align="center" style="display: block;">
							<img src="img/themes/<?=$theme?>/b1_easycall2.gif" onclick="popup('easycallstart.php?id=new',550,550);"
							onmouseover="this.src='img/themes/<?=$theme?>/b2_easycall2.gif'"
							onmouseout="this.src='img/themes/<?=$theme?>/b1_easycall2.gif'">
						</td>
					</tr>
				<?
				}
				if ($listsdata && ($USER->authorize('sendphone') || $USER->authorize('sendemail') || $USER->authorize('sendprint') || $USER->authorize('sendsms'))) {
				?>
					<tr>
						<th align="left" class="bottomBorder">Advanced Jobs:<?=help('Jobs_AddStandardJob', '', 'small')?></th>
					</tr>
					<tr align="center" style="display: block;">
						<td>
							<?=button('Create New Job', NULL,"job.php?origin=start&id=new")?>
						</td>
					</tr>
				<?
				}
				if ($USER->authorize('createlist')) {
					?>
						<tr>
							<th align="left" class="bottomBorder">New List:<?=help('Lists_AddList', '', 'small')?></th>
						</tr>
					<?
					if (!$listsdata) {
					?>
						<tr>
							<td>
								<span style="text-decoration: underline; color: blue; cursor: help;" onclick="window.open('help/schoolmessenger_help.htm#creating_a_list/listsoverview.htm', '_blank', 'width=750,height=500,location=no,menub ar=yes,resizable=yes,scrollbars=yes,status=no,titlebar=no,toolbar=yes');">Make a List</span>
								 - For every notification job, you must have a list of people whom you wish to receive your message. Your list can be static or dynamic (automatically updated every time it is used). Your lists can always be saved and easily reused.
							</td>
						</tr>
					<?
					}
					?>
						<tr align="center" style="display: block;">
							<td>
								<?=button('Create New List', NULL,"list.php?origin=start&id=new")?>
							</td>
						</tr>
					<?
					if ($listsdata) {
					?>
					
						<tr>
							<td>
								<div style="font-size: x-small">Tip: Lists are reusable.</div>
							</td>
						</tr>
					<?
					}
				}
				?>
				</table>
				<?		
				endWindow();
			?><br></td></tr><?
			}
?>
			<tr><td><?
				startWindow('My Active Calls',NULL);
				button_bar(button('Refresh', 'window.location.reload()'));
				?><div align="center"><img width="300" height="200" src="graph_start_actrive_breakdown.png.php?junk=<?= rand() ?>" /></div><?
				endWindow();
?>
				</td>
			</tr>
			</table>
		</td>
		<td width="100%" valign="top">
<?

			$limit = 5; // Limit on max # of each type of job to show on the start page.

			startWindow('My Active and Pending Notifications ' . help('Start_MyActiveJobs'),NULL,true);
			button_bar(button('Refresh', 'window.location.reload()'));

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
			showObjects($data, $titles, $formatters);
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();



			startWindow('My Completed Notifications ' . help('Start_MyCompletedJobs'),NULL,true);

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
			showObjects($data, $titles, $formatters);
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();


			if (getSystemSetting('_hassurvey', true) && $USER->authorize("survey")) {

				startWindow('My Active and Pending Surveys ' . help('Start_MyActiveJobs'),NULL,true);
				button_bar(button('Refresh', 'window.location.reload()'));

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
				showObjects($data, $titles, $formatters);
				?><div style="text-align:right; white-space:nowrap"><a href="surveys.php" style="font-size: xx-small;">More...</a></div><?
				endWindow();



				startWindow('My Completed Surveys ' . help('Start_MyCompletedJobs'),NULL,true);

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
				showObjects($data, $titles, $formatters);
				?><div style="text-align:right; white-space:nowrap"><a href="surveys.php" style="font-size: xx-small;">More...</a></div><?
				endWindow();
			}



	?></td></tr></table><?

} else if ($USER->authorize("starteasy")) {
	$theme = getBrandTheme();
?>
	<table border=0 width="250"><tr><td><?
	startWindow('EasyCall',NULL);
	?><div align="center" style="margin: 5px;"><img src="img/themes/<?=$theme?>/b1_easycall2.gif" onclick="popup('easycallstart.php?id=new',550,550);");"
					onmouseover="this.src='img/themes/<?=$theme?>/b2_easycall2.gif'"
					onmouseout="this.src='img/themes/<?=$theme?>/b1_easycall2.gif'">
					</div><?;
	endWindow();
	?></td></tr></table><?
}

include_once("navbottom.inc.php");
?>
