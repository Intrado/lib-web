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
if ($_GET['id'] == 'new') {
	$_SESSION['listid'] = NULL;
	redirect("list.php");
}

//get the message to edit from the request params or session
if (isset($_GET['id'])) {
	setCurrentList($_GET['id']);
	redirect();
}


if ($USER->authorize("loginweb") === false) {
	redirect('unauthorized');
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


if ($USER->authorize("startstats")) {
?>
	<table border=0 cellpadding=0 cellspacing=5>
	<tr>
		<td valign="top">
			<table border=0 cellpadding=0 cellspacing=0>
<?
			if ($USER->authorize("starteasy")) {
			?><tr><td><?
				startWindow('EasyCall ' . help('Start_EasyCall', NULL, 'blue'), 'padding: 3px;');
				?><div align="center"><?= button('easycall2',"var namefield = new getObj('easycallname');popup('easycallstart.php',500,450);"); ?></div><?
				endWindow();
			?><br></td></tr><?
			}
?>
			<tr><td><?
				startWindow('My Active Calls', 'padding: 3px;');
				button_bar(button('refresh', 'window.location.reload()'));
				?><div align="center"><img src="graph_start_actrive_breakdown.png.php" /></div><?
				endWindow();
?>
				</td>
			</tr>
			</table>
		</td>
		<td width="100%" valign="top">
<?



			$limit = 5; // Limit on max # of each type of job to show on the start page.

			startWindow('My Active and Pending Notification Jobs ' . help('Start_MyActiveJobs', NULL, 'blue'), 'padding: 3px;');
			button_bar(button('refresh', 'window.location.reload()'));

			$data = DBFindMany("Job","from job where userid=$USER->id and (status='active' or status = 'new' or status='cancelling') and type != 'survey' and deleted = 0 order by id desc limit $limit");
			$titles = array(	"name" => "Name",
								"type" => "Type",
								"Status" => "Status",
								"Actions" => "Actions"
								);
			$formatters = array("type" => "fmt_obj_csv_list", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status');
			showObjects($data, $titles, $formatters);
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();



			startWindow('My Completed Notification Jobs ' . help('Start_MyCompletedJobs', NULL, 'blue'), 'padding: 3px;');
			button_bar(button('refresh', 'window.location.reload()'));

			$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0 order by finishdate desc limit $limit");
			$titles = array(	"name" => "Name",
								"type" => "Type",
								"Status" => "Status",
								"enddate" => "End Date",
								"Actions" => "Actions"
								);
			$formatters = array("type" => "fmt_obj_csv_list", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status',"enddate" => "fmt_job_enddate");
			showObjects($data, $titles, $formatters);
			?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
			endWindow();


			if ($USER->authorize("survey")) {

				startWindow('My Active and Pending Survey Jobs ' . help('Start_MyActiveJobs', NULL, 'blue'), 'padding: 3px;');
				button_bar(button('refresh', 'window.location.reload()'));

				$data = DBFindMany("Job","from job where userid=$USER->id and (status='active' or status = 'new' or status='cancelling') and type='survey' and deleted = 0 order by id desc limit $limit");
				$titles = array(	"name" => "Name",
									"type" => "Type",
									"Status" => "Status",
									"Actions" => "Actions"
									);
				$formatters = array("type" => "fmt_surveytype", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status');
				showObjects($data, $titles, $formatters);
				?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
				endWindow();



				startWindow('My Completed Survey Jobs ' . help('Start_MyCompletedJobs', NULL, 'blue'), 'padding: 3px;');
				button_bar(button('refresh', 'window.location.reload()'));

				$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type='survey' and deleted = 0 order by finishdate desc limit $limit");
				$titles = array(	"name" => "Name",
									"type" => "Type",
									"Status" => "Status",
									"enddate" => "End Date",
									"Actions" => "Actions"
									);
				$formatters = array("type" => "fmt_surveytype", "Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status',"enddate" => "fmt_job_enddate");
				showObjects($data, $titles, $formatters);
				?><div style="text-align:right; white-space:nowrap"><a href="jobs.php" style="font-size: xx-small;">More...</a></div><?
				endWindow();
			}



	?></td></tr></table><?

} else if ($USER->authorize("starteasy")) {
	?><table border=0 width="250"><tr><td><?
	startWindow('EasyCall', 'padding: 3px;');
	echo button('easycall2',"var namefield = new getObj('easycallname');popup('easycallstart.php',500,450);");
	endWindow();
	?></td></tr></table><?
}

include_once("navbottom.inc.php");
?>
