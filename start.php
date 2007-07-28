<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/JobType.obj.php");
include_once("inc/table.inc.php");
include_once("inc/html.inc.php");
include_once("inc/form.inc.php");
include_once("inc/text.inc.php");
include_once("obj/PeopleList.obj.php");
include_once("obj/Message.obj.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/FieldMap.obj.php");
include_once("obj/SurveyQuestionnaire.obj.php");
require_once("inc/formatters.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if ($USER->authorize("loginweb") === false) {
	redirect('unauthorized');
}

if($USER->authorize("leavemessage")){
	$count = QuickQuery("select count(*) from voicereply where userid = '$USER->id' and listened = '0'");
}


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
$TITLE = 'Welcome ' . $USER->firstname . ' ' . $USER->lastname;

include_once("nav.inc.php");
if($USER->authorize("leavemessage")){
	if($count > 0){
		$unplayed = "<img src=\"img/bug_important.gif\"> You have unplayed responses to your notifications..." .
				"<a href=\"replies.php?jobid=all&showonlyunheard=true\" style=\"font-size: medium;\">click to view</a>";
?>
		<div style="font-size: medium;"><?=$unplayed?><div>
<?
	}
}

if ($USER->authorize("startstats")) {
?>
	<table border=0 cellpadding=0 cellspacing=5>
	<tr>
		<td valign="top">
			<table border=0 cellpadding=0 cellspacing=0>
<?
			if ($USER->authorize("starteasy")) {
			?><tr><td><?
				startWindow('EasyCall ' . help('Start_EasyCall'),NULL);
				?><div align="center" style="margin: 5px;"><img src="img/b1_easycall2.gif" onclick="popup('easycallstart.php?id=new',550,550);");"
					onmouseover="this.src='img/b2_easycall2.gif'"
					onmouseout="this.src='img/b1_easycall2.gif'">
					</div><?
				endWindow();
			?><br></td></tr><?
			}
?>
			<tr><td><?
				startWindow('My Active Calls',NULL);
				button_bar(button('Refresh', 'window.location.reload()'));
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

			startWindow('My Active and Pending Notifications ' . help('Start_MyActiveJobs'),NULL,true);
			button_bar(button('Refresh', 'window.location.reload()'));

			$data = DBFindMany("Job","from job where userid=$USER->id and (status='active' or status = 'new' or status='cancelling' or status='processing') and type != 'survey' and deleted = 0 order by id desc limit $limit");
			$titles = array(	"name" => "Name",
								"type" => "Type",
								"Status" => "Status",
								"responses" => "Responses (Unplayed/Total)",
								"Actions" => "Actions"
								);
			$formatters = array("type" => "fmt_obj_csv_list", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status', 'responses' => 'fmt_response_count');
			if(!$USER->authorize('leavemessage')){
				unset($titles["responses"]);
				unset($formatters["responses"]);
			}
			showObjects($data, $titles, $formatters);
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();



			startWindow('My Completed Notifications ' . help('Start_MyCompletedJobs'),NULL,true);

			$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0 order by finishdate desc limit $limit");
			$titles = array(	"name" => "Name",
								"type" => "Type",
								"Status" => "Status",
								"enddate" => "End Date",
								"responses" => "Responses (Unplayed/Total)",
								"Actions" => "Actions"
								);
			$formatters = array("type" => "fmt_obj_csv_list", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status',"enddate" => "fmt_job_enddate", "responses" => "fmt_response_count");
			if(!$USER->authorize('leavemessage')){
				unset($titles["responses"]);
				unset($formatters["responses"]);
			}
			showObjects($data, $titles, $formatters);
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();


			if ($USER->authorize("survey")) {

				startWindow('My Active and Pending Surveys ' . help('Start_MyActiveJobs'),NULL,true);
				button_bar(button('Refresh', 'window.location.reload()'));

				$data = DBFindMany("Job","from job where userid=$USER->id and (status='active' or status = 'new' or status='cancelling' or status='processing') and type='survey' and deleted = 0 order by id desc limit $limit");
				$titles = array(	"name" => "Name",
									"type" => "Type",
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
				$titles = array(	"name" => "Name",
									"type" => "Type",
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
?>
	<table border=0 width="250"><tr><td><?
	startWindow('EasyCall',NULL);
	?><div align="center" style="margin: 5px;"><img src="img/b1_easycall2.gif" onclick="popup('easycallstart.php?id=new',550,550);");"
					onmouseover="this.src='img/b2_easycall2.gif'"
					onmouseout="this.src='img/b1_easycall2.gif'">
					</div><?;
	endWindow();
	?></td></tr></table><?
}

include_once("navbottom.inc.php");
?>
