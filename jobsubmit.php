<?
include_once("inc/common.inc.php");
require_once("inc/date.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");

$jobid = $_GET['jobid'] + 0;

if (!userOwns("job",$jobid))
	redirect('unauthorized.php');

// gather job list rules to store with job.thesql for the trigger to shard db
// job.thesql is used by the jobprocessor
$job = new Job($jobid);

$job->runNow();
sleep(3);

if (isset($_REQUEST['close']) && $_REQUEST['close']) {
?>

<script language="javascript">
	window.opener.document.location.reload();
	window.close();
	</script>
	Your job has been submitted, you may now close this window.
<?
} else {
	redirect("start.php");
}
?>