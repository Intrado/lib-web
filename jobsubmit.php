<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");

$jobid = $_GET['jobid'] + 0;

if (!userOwns("job",$jobid))
	redirect('unauthorized.php');

// gather job list rules to store with job.thesql for the trigger to shard db
// job.thesql is used by the jobprocessor
$job = new Job($jobid);

$usersql = $USER->userSQL("p");
//get and compose list rules
$listrules = DBFindMany("Rule","from listentry le, rule r where le.type='R'
		and le.ruleid=r.id and le.listid='" . $job->listid .  "' order by le.sequence", "r");
if (count($listrules) > 0)
	$listsql = "1" . Rule::makeQuery($listrules, "p");
else
	$listsql = "0";//dont assume anyone is in the list if there are no rules

if ($usersql == "")
    $job->thesql = $listsql;
else
    $job->thesql = $usersql ." and ". $listsql;

$job->status = "processing"; // set state, jobprocessor will set it to 'active'
$job->update();

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