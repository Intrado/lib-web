<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/Job.obj.php");

$jobid = $_GET['jobid'] + 0;

if (!userOwns("job",$jobid))
	redirect('unauthorized.php');



Job::runNow($jobid);
sleep(3);


if ($_REQUEST['close']) {
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