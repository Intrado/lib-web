<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
include_once("obj/ImportJob.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint')  && !$USER->authorize('managesystemjobs')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['cancel'])) {
	$cancelid = DBSafe($_GET['cancel']);
	if (userOwns("job",$cancelid) || (customerOwnsJob($cancelid) && $USER->authorize('managesystemjobs'))) {
		$job = new Job($cancelid);
		$job->cancelleduserid = $USER->id;

		if ($job->status = "active") {
			$job->status = "cancelling";
		} else if ($job->status = "new") {
			$job->status = "cancelled";
			$job->finishdate = QuickQuery("select now()");
			//skip running autoreports for this job since there is nothing to report on
			QuickUpdate("update job set ranautoreport=1 where jobid='$cancelid'");
		}
		$job->update();
	}
	redirectToReferrer();
}

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (userOwns("job",$deleteid) || (customerOwnsJob($deleteid) && $USER->authorize('managesystemjobs'))) {
		$job = new Job($deleteid);
		if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
			$job->deleted = 1;
			$job->update();
		} else if ($job->status == "repeating") {
			if ($job->scheduleid) {
				$schedule = new Schedule($job->scheduleid);
				QuickUpdate("delete from scheduleday where scheduleid=$schedule->id");
				$schedule->destroy();
			}
			$associatedimports = DBFindMany("ImportJob", "from importjob where jobid = '$deleteid'");
			foreach($associatedimports as $importjob){
				$importjob->destroy();
			}
			QuickUpdate("delete from joblanguage where jobid='$deletedid'");
			$job->destroy();
		}
	}
	redirectToReferrer();
}

if (isset($_GET['archive'])) {
	$archiveid = DBSafe($_GET['archive']);
	if (userOwns("job",$archiveid) || (customerOwnsJob($archiveid) && $USER->authorize('managesystemjobs'))) {
		$job = new Job($archiveid);
		if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
			$job->deleted = 2;
			$job->update();
		}
	}
	redirectToReferrer();
}

if (isset($_GET['runrepeating'])) {
	$runnow = $_GET['runrepeating'] + 0;
	if (userOwns("job",$runnow) || (customerOwnsJob($runnow) && $USER->authorize('managesystemjobs'))) {
		$job = new Job($runnow);
		if ($job->status=="repeating") {
			$job->runNow();
			sleep(3);
		}
	}
	redirectToReferrer();
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs";
$TITLE = "Notification Jobs";

include_once("nav.inc.php");

$data = DBFindMany("Job","from job where userid=$USER->id and (status='new' or status='active' or status='cancelling') and type != 'survey' and deleted=0 order by id desc");
$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"type" => "#Type",
					"startdate" => "Start date",
					"Status" => "#Status",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions",
					'Status' => 'fmt_status',
					"type" => "fmt_obj_csv_list",
					"startdate" => "fmt_job_startdate");

$scrollThreshold = 8;
$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}
startWindow('My Active and Pending Notification Jobs ' . help('Jobs_MyActiveJobs', NULL, 'blue'), 'padding: 3px;', true, true);

button_bar(button('createjob', NULL,"job.php?id=new") . help('Jobs_AddStandardJob'), ($USER->authorize("starteasy") ? button('easycall',"var namefield = new getObj('easycallname');popup('easycallstart.php?id=new',500,450);") . help('Start_EasyCall') : ''));

showObjects($data, $titles, $formatters, $scroll, true);
endWindow();


print '<br>';
if ($USER->authorize('createrepeat')) {

	$data = DBFindMany("Job",", name + 0 as foo from job where userid=$USER->id and status = 'repeating' and type != 'survey' order by foo,name desc");
	$titles = array(	"name" => "#Name",
						"description" => "#Description",
						"type" => "#Type",
						"startdate" => "Next Scheduled Run",
						"finishdate" => "Last Run",
						"Actions" => "Actions"
						);

	$scroll = false;
	if (count($data) > $scrollThreshold) {
		$scroll = true;
	}
	startWindow('My Repeating Notification Jobs ' . help('Jobs_MyRepeatingJobs', NULL, 'blue'), 'padding: 3px;', true, true);


	button_bar(button('createrepeatjob', NULL,"jobrepeating.php?id=new") . help('Jobs_AddRepeatingJob'));


	showObjects($data, $titles, array("startdate" => "fmt_nextrun", "type" => "fmt_obj_csv_list","finishdate" => "fmt_obj_date", "Actions" => "fmt_jobs_actions"), $scroll, true);
	endWindow();
	print '<br>';
}


$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0 order by finishdate desc");
$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"type" => "#Type",
					"startdate" => "Start Date",
					"Status" => "#Status",
					"enddate" => "End Date",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions", 'Status' => 'fmt_status', "startdate" => "fmt_job_startdate", "enddate" => "fmt_job_enddate","type" => "fmt_obj_csv_list");

$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}
startWindow('My Completed Notification Jobs ' . help('Jobs_MyCompletedJobs', NULL, 'blue'),'padding: 3px;', true, true);
showObjects($data, $titles, $formatters, $scroll, true);
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
		<tr>
		<td width="100%">
			&nbsp;
		</td>
			<td>
				<div style="text-align:right; white-space:nowrap"><a href="jobsarchived.php">Archived Jobs...</a></div>
			</td>
		</tr>
	</table>
<?
endWindow();

include_once("navbottom.inc.php");
