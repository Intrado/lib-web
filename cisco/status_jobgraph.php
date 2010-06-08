<?
require_once("common.inc.php");

if(isset($_GET['jobid'])) {
	$jobid = $_GET['jobid'] + 0;
}

//TODO refresh header
header("Refresh: 10; url=" . $URL . "/status_jobgraph.php?jobid=" . $jobid);
header("Content-type: text/xml");

?>
<CiscoIPPhoneImageFile>
<Title><?=$_SESSION['productname']?> - Status</Title>
<Prompt>Active Jobs</Prompt>
<LocationX>-1</LocationX>
<LocationY>-1</LocationY>
<URL><?= $URL . "/graph_job_breakdown.png.php?jobid=" . $jobid ?></URL>

<SoftKeyItem>
<Name>Update</Name>
<URL><?= $URL . "/status_jobgraph.php?jobid=" . $jobid ?></URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= $URL . "/joblist.php" ?></URL>
<Position>3</Position>
</SoftKeyItem>

</CiscoIPPhoneImageFile>
