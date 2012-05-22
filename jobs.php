<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/ImportJob.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/RenderedList.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint')  && !$USER->authorize('sendsms') && !$USER->authorize('managesystemjobs')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
// NOTE: Job::update() makes many database updates for jobsettings, which makes it appropriate to use transactions around Job::update()
////////////////////////////////////////////////////////////////////////////////
if (isset($_GET['cancel'])) {
	$cancelid = DBSafe($_GET['cancel']);
	if (userOwns("job",$cancelid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($cancelid);
		if ($job->cancel())
			notice(_L("The job, %s, is now cancelled.", escapehtml($job->name)));
	}

	redirectToReferrer();
}

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (userOwns("job",$deleteid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($deleteid);
		if ($job->softDelete())
			notice(_L("The job, %s, is now deleted.", escapehtml($job->name)));
	}
	redirectToReferrer();
}

if (isset($_GET['archive'])) {
	$archiveid = DBSafe($_GET['archive']);
	if (userOwns("job",$archiveid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($archiveid);
		if ($job->archive())
			notice(_L("The job, %s, is now archived.", escapehtml($job->name)));
	}
	redirectToReferrer();
}

if (isset($_GET['unarchive'])) {
	$unarchiveid = DBSafe($_GET['unarchive']);
	if (userOwns("job",$unarchiveid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($unarchiveid);
		if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
			Query('BEGIN');
				$job->deleted = 0;
				$job->modifydate = date("Y-m-d H:i:s", time());
				$job->update();
			Query('COMMIT');

			notice(_L("The job, %s, is now unarchived.", escapehtml($job->name)));
		}
	} else {
		notice(_L("You do not have permission to unarchive this job."));
	}

	redirectToReferrer();
}

if (isset($_GET['runrepeating']) && isset($_GET['uuid'])) {
	$runnow = $_GET['runrepeating'] + 0;
	// don't re-run a repeating job from the same link
	if (isset($_SESSION['lastrunrepeatingjob'][$runnow]) && $_GET['uuid'] == $_SESSION['lastrunrepeatingjob'][$runnow]) {
		// Do nothing, this is a repeat request
		error_log("Ignoring duplicate runrepeating request: runrepeating=$runnow");
	} else {
		if (userOwns("job",$runnow) || $USER->authorize('managesystemjobs')) {
			$job = new Job($runnow);
			if ($job->status != 'repeating') {
				notice(_L("The job, %s, is not a repeating job.", escapehtml($job->name)));
			} else {
				Query('BEGIN');
					$job->runNow();
				Query('COMMIT');
			
				notice(_L("The repeating job, %s, will now run.", escapehtml($job->name)));
				
				// this repeating job has been run
				$_SESSION['lastrunrepeatingjob'][$runnow] = $_GET['uuid'];
			}
		} else {
			notice(_L("You do not have permission to run this repeating job."));
		}
	}
	redirectToReferrer();
}

if (isset($_GET['copy'])) {
	$copyid = DBSafe($_GET['copy']);
	if (userOwns("job",$copyid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($copyid);
		if ($job->type != 'notification') {
			notice(_L("Unable to copy this job"));
		} else if ($job->userid !== null) {
			 Query('BEGIN');
				$newjob = $job->copyNew();
			Query('COMMIT');

			notice(_L("%s has been copied.", escapehtml($job->name)));
			redirect('job.php?id='.$newjob->id);
		} else {
			notice(_L("You do not have permission to copy this job."));
		}
	} else {
		notice(_L("You do not have permission to copy this job."));
	}

	redirectToReferrer();
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs";
$TITLE = "Notification Jobs";

include_once("nav.inc.php");

// Active Jobs
$data = DBFindMany("Job","from job where userid=$USER->id and (status='new' or status='scheduled' or status='procactive' or status='processing' or status='active' or status='cancelling') and type != 'survey' and deleted=0 order by id desc");
// find jobids to use in later query
$jobids = array();
foreach ($data as $job) {
	$jobids[] = $job->id;
}
// find jobstats for all jobs
global $JOB_STATS;
$JOB_STATS = array();
if (count($jobids) > 0) {
	$query = "select jobid, name, value from jobstats where jobid in (" . implode(",", $jobids)  .") and name = 'complete-seconds-phone-attempt-0-sequence-0'";
	$jobstats_objects = QuickQueryMultiRow($query);
	foreach ($jobstats_objects as $obj) {
		$JOB_STATS[$obj[0]][$obj[1]] = $obj[2];
	}
}

$titles = array(	"name" => "#Job Name",
					"description" => "#Description",
					"type" => "#Type",
					"startdate" => "Start Date",
					"firstpass" => "First Pass",
					"Status" => "#Status",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions",
					'Status' => 'fmt_status',
					"firstpass" => "fmt_obj_job_first_pass",
					"type" => "fmt_obj_delivery_type_list",
					"responses" => "fmt_response_count",
					"startdate" => "fmt_job_startdate");

if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($formatters["responses"]);
}

$scrollThreshold = 8;
$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}
startWindow('My Jobs ' . help('Jobs_MyActiveJobs'), 'padding: 3px;', true, true);

button_bar(button('Create New Job', NULL,"job.php?id=new") . help('Jobs_AddStandardJob'),button('Refresh', 'window.location.reload()'));

showObjects($data, $titles, $formatters, $scroll, true);
endWindow();

// Repeating Jobs
if ($USER->authorize('createrepeat')) {

	$data = DBFindMany("Job",", name + 0 as foo from job where userid=$USER->id and status = 'repeating' and type not in ('survey', 'alert') order by foo,name ");
	$titles = array(	"name" => "#Job Name",
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
	startWindow('My Repeating Jobs ' . help('Jobs_MyRepeatingJobs'), 'padding: 3px;', true, true);
	if (count($data) > 0 && getSystemSetting("disablerepeat") ) {
?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td align=center><div class='alertmessage noprint'>The System Administrator has disabled all Repeating Jobs. <br>No Repeating Jobs can be run while this setting remains in effect.</div></td></tr></table>
<?
	}

	button_bar(button('Create Repeating Job', NULL,"jobrepeating.php?id=new") . help('Jobs_AddRepeatingJob'));


	showObjects($data, $titles, array("startdate" => "fmt_nextrun", "type" => "fmt_obj_delivery_type_list","finishdate" => "fmt_obj_date", "Actions" => "fmt_jobs_actions"), $scroll, true);
	endWindow();
}

// Completed Jobs
$query = "from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0";
$totalcompletedjobs = QuickQuery("select count(*) " . $query);
$data = DBFindMany("Job", $query . " order by finishdate desc limit 100");
// find jobids to use in later query
$jobids = array();
foreach ($data as $job) {
	$jobids[] = $job->id;
}
// find jobstats for all jobs
global $JOB_STATS;
$JOB_STATS = array();
if (count($jobids) > 0) {
	$query = "select jobid, name, value from jobstats where jobid in (" . implode(",", $jobids)  .") and name = 'complete-seconds-phone-attempt-0-sequence-0'";
	$jobstats_objects = QuickQueryMultiRow($query);
	foreach ($jobstats_objects as $obj) {
		$JOB_STATS[$obj[0]][$obj[1]] = $obj[2];
	}
}

$titles = array(	"name" => "#Job Name",
					"description" => "#Description",
					"type" => "#Type",
					"startdate" => "Start Date",
					"firstpass" => "First Pass",
					"Status" => "#Status",
					"enddate" => "End Date",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions",
					'Status' => 'fmt_status',
					"startdate" => "fmt_job_startdate",
					"firstpass" => "fmt_obj_job_first_pass",
					"enddate" => "fmt_job_enddate",
					"type" => "fmt_obj_delivery_type_list",
					"responses" => "fmt_response_count");

if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($formatters["responses"]);
}

$scroll = false;
if (count($data) > $scrollThreshold) {
	$scroll = true;
}
startWindow('My Completed Jobs ' . help('Jobs_MyCompletedJobs'),'padding: 3px;', true, true);
showObjects($data, $titles, $formatters, $scroll, true);
?>
	<table style="margin-top: 5px;" border="0" cellpadding="0" cellspacing="0">
<?
	if ($totalcompletedjobs > 100) {
?>
		<tr>
		<td width="100%">
			&nbsp;
		</td>
			<td>
				<div style="text-align:right; white-space:nowrap"><a href="jobscompleted.php">More Completed Jobs...</a></div>
			</td>
		</tr>
<?
	}
?>
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
