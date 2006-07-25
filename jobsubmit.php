<?
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");

$jobid = $_GET['jobid'] + 0;

if (!userOwns("job",$jobid))
	redirect('unauthorized.php');


if (isset($_SERVER['WINDIR'])) {
	$cmd = "start php jobprocess.php $jobid";
	pclose(popen($cmd,"r"));
} else {
	$cmd = "php jobprocess.php $jobid > /dev/null &";
	exec($cmd);
}

sleep(1);


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