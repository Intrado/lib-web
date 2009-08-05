<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/JobLanguage.obj.php");
require_once("obj/JobList.obj.php");
require_once("obj/Schedule.obj.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/date.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/ImportJob.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendphone') && !$USER->authorize('sendemail') && !$USER->authorize('sendprint')  && !$USER->authorize('sendsms') && !$USER->authorize('managesystemjobs')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['cancel'])) {
	$cancelid = DBSafe($_GET['cancel']);
	if (userOwns("job",$cancelid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($cancelid);
		$job->cancelleduserid = $USER->id;

		if ($job->status == "active" || $job->status == "procactive" || $job->status == "processing" || $job->status == "scheduled") {
			$job->status = "cancelling";
		} else if ($job->status == "new") {
			$job->status = "cancelled";
			$job->finishdate = QuickQuery("select now()");
			//skip running autoreports for this job since there is nothing to report on
			QuickUpdate("update job set ranautoreport=1 where id='$cancelid'");
		}
		$job->update();
	}
	redirectToReferrer();
}

if (isset($_GET['delete'])) {
	$deleteid = DBSafe($_GET['delete']);
	if (userOwns("job",$deleteid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($deleteid);
		if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
			$job->deleted = 1;
			$job->update();
		} else if ($job->status == "repeating") {
			if ($job->scheduleid) {
				$schedule = new Schedule($job->scheduleid);
				$schedule->destroy();
			}
			$associatedimports = DBFindMany("ImportJob", "from importjob where jobid = '$deleteid'");
			foreach($associatedimports as $importjob){
				$importjob->destroy();
			}
			QuickUpdate("delete from joblanguage where jobid='$deleteid'");
			$job->destroy();
		}
	}
	redirectToReferrer();
}

if (isset($_GET['archive'])) {
	$archiveid = DBSafe($_GET['archive']);
	if (userOwns("job",$archiveid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($archiveid);
		if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
			$job->deleted = 2;
			$job->modifydate = date("Y-m-d H:i:s", time());
			$job->update();
		}
	}
	redirectToReferrer();
}

if (isset($_GET['unarchive'])) {
	$unarchiveid = DBSafe($_GET['unarchive']);
	if (userOwns("job",$unarchiveid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($unarchiveid);
		if ($job->status == "cancelled" || $job->status == "cancelling" || $job->status == "complete") {
			$job->deleted = 0;
			$job->modifydate = date("Y-m-d H:i:s", time());
			$job->update();
		}
	}
	redirectToReferrer();
}

if (isset($_GET['runrepeating'])) {
	$runnow = $_GET['runrepeating'] + 0;
	if (userOwns("job",$runnow) || $USER->authorize('managesystemjobs')) {
		$job = new Job($runnow);
		$job->runNow();
	}
	redirectToReferrer();
}

if (isset($_GET['copy'])) {
	$copyid = DBSafe($_GET['copy']);
	if (userOwns("job",$copyid) || $USER->authorize('managesystemjobs')) {
		$job = new Job($copyid);
		$newjob = $job->copyNew();
		redirect('job.php?id='.$newjob->id);
	}
	redirectToReferrer();
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "notifications:jobs";
$TITLE = "Notification Jobs";

include_once("nav.inc.php");

$data = DBFindMany("Job","from job where userid=$USER->id and (status='new' or status='scheduled' or status='procactive' or status='processing' or status='active' or status='cancelling') and type != 'survey' and deleted=0 order by id desc");
$titles = array(	"name" => "#Job Name",
					"description" => "#Description",
					"type" => "#Deliver by",
					"startdate" => "Start date",
					"Status" => "#Status",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions",
					'Status' => 'fmt_status',
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

button_bar(button('Create New Job', NULL,"job.php?id=new") . help('Jobs_AddStandardJob'), ($USER->authorize("starteasy") ? button('EasyCall',"var namefield = new getObj('easycallname');popup('easycallstart.php?id=new',500,450);") . help('Start_EasyCall') : ''),button('Refresh', 'window.location.reload()'));

showObjects($data, $titles, $formatters, $scroll, true);
endWindow();


print '<br>';
if ($USER->authorize('createrepeat')) {

	$data = DBFindMany("Job",", name + 0 as foo from job where userid=$USER->id and status = 'repeating' and type != 'survey' order by foo,name ");
	$titles = array(	"name" => "#Job Name",
						"description" => "#Description",
						"type" => "#Deliver by",
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
	print '<br>';
}


$data = DBFindMany("Job","from job where userid=$USER->id and (status='complete' or status='cancelled') and type != 'survey' and deleted = 0 order by finishdate desc");
$titles = array(	"name" => "#Job Name",
					"description" => "#Description",
					"type" => "#Deliver by",
					"startdate" => "Start Date",
					"Status" => "#Status",
					"enddate" => "End Date",
					"responses" => "Responses (Unplayed/Total)",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_jobs_actions",
					'Status' => 'fmt_status',
					"startdate" => "fmt_job_startdate",
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
