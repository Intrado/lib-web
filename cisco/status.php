<?
require_once("common.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../obj/Job.obj.php");


if (!$USER->authorize('createreport')) {
	header("Location: $URL/index.php");
	exit();
}

//get the top 5 jobs

$jobs = DBFindMany("Job", "from job where userid=$USER->id and deleted=0 and status in ('scheduled','processing','procactive','active','complete','cancelled','cancelling') order by id desc limit 5");
foreach ($jobs as $job) {
	$query = "
	select
		count(rp.personid) as people,
		sum(rp.status='success' or rp.status='fail') / (count(rp.personid) + 0.00 - sum(rp.status in ('duplicate','blocked','nocontacts','declined'))) as completed_percent,
		sum(rp.status='success') as success,
		sum(rp.status='fail') as fail,
		sum(rp.status not in ('success','fail','duplicate','blocked','nocontacts','declined')) as in_progress,
		sum(rp.status = 'duplicate') as duplicate,
		sum(rp.status='success') / (sum(rp.status='success' or rp.status='fail' or (rc.numattempts>0 and rp.status = 'queued' or rp.status='scheduled')) +0.00) as success_rate,
		sum(rp.status = 'blocked') as blocked,
		sum(rp.status = 'nocontacts') as nocontacts,
		sum(rp.status = 'declined') as declined

		from reportperson rp
		left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
		where rp.jobid = $job->id
		and rp.type='phone'
	";

	$row = QuickQueryRow($query);
	$job->stats = $row;
}

header("Content-type: text/xml");
?>
<CiscoIPPhoneText>
<Title><?=$_SESSION['productname']?> - Status</Title>
<Prompt>Showing the last <?=  count($jobs) ?> job(s)</Prompt>
<Text><?

$output = "";
if (count($jobs) > 0) {
	foreach ($jobs as $job) {

		// is this a high res display? tweak the tabs
		if (isModel("7961") || isModel("7941")) {
//error_log("7961");
			$output .= "--$job->name (" . fmt_status($job,"status") . ")--\r\n";
			$output .= "    Complete:\t\t\t\t\t" . sprintf("%0.2f%%",$job->stats[1]*100) . "\r\n";
			$output .= "    Success Rate:\t" . sprintf("%0.2f%%",$job->stats[6]*100) . "\r\n";
			$output .= "    People:\t\t\t\t\t\t\t" . $job->stats[0] . "\r\n";
			$output .= "    Successful:\t\t\t\t" . $job->stats[2] . "\r\n";
			$output .= "    Unknown:\t\t\t\t\t\t\t" . $job->stats[3] . "\r\n";
			$output .= "    Duplicates:\t\t\t\t" . $job->stats[5] . "\r\n";
			$output .= "    Blocked:\t\t\t\t" . $job->stats[7] . "\r\n";
			$output .= "    No Phone#:\t\t" . $job->stats[8] . "\r\n";
			$output .= "    No Phone Sel:\t" . $job->stats[9] . "\r\n";
			$output .= "    In Progress:\t\t\t" . $job->stats[4] . "\r\n";
		} else if (doesSupport("CiscoIPPhoneImageFile")) {
//error_log("image");
			$output .= "--$job->name (" . fmt_status($job,"status") . ")--\r\n";
			$output .= "    Complete:\t\t\t" . sprintf("%0.2f%%",$job->stats[1]*100) . "\r\n";
			$output .= "    Success Rate:\t" . sprintf("%0.2f%%",$job->stats[6]*100) . "\r\n";
			$output .= "    People:\t\t\t\t\t" . $job->stats[0] . "\r\n";
			$output .= "    Successful:\t\t" . $job->stats[2] . "\r\n";
			$output .= "    Unknown:\t\t\t" . $job->stats[3] . "\r\n";
			$output .= "    Duplicates:\t\t\t" . $job->stats[5] . "\r\n";
			$output .= "    Blocked:\t\t\t\t" . $job->stats[7] . "\r\n";
			$output .= "    No Phone#:\t\t" . $job->stats[8] . "\r\n";
			$output .= "    No Phone Sel:\t" . $job->stats[9] . "\r\n";
			$output .= "    In Progress:\t\t" . $job->stats[4] . "\r\n";
		} else {
//error_log("else");
			$output .= "--$job->name (" . fmt_status($job,"status") . ")--\r\n";
			$output .= "    Complete:\t\t\t" . sprintf("%0.2f%%",$job->stats[1]*100) . "\r\n";
			$output .= "    Success Rate:\t" . sprintf("%0.2f%%",$job->stats[6]*100) . "\r\n";
			$output .= "    People:\t\t\t\t" . $job->stats[0] . "\r\n";
			$output .= "    Successful:\t\t" . $job->stats[2] . "\r\n";
			$output .= "    Unknown:\t\t\t" . $job->stats[3] . "\r\n";
			$output .= "    Duplicates:\t\t" . $job->stats[5] . "\r\n";
			$output .= "    Blocked:\t\t\t\t" . $job->stats[7] . "\r\n";
			$output .= "    No Phone#:\t\t" . $job->stats[8] . "\r\n";
			$output .= "    No Phone Sel:\t" . $job->stats[9] . "\r\n";
			$output .= "    In Progress:\t" . $job->stats[4] . "\r\n";
		}
	}
} else {
	$output .= "No active jobs";
}

echo htmlentities($output);

?>
</Text>

	<SoftKeyItem>
			<Name>Update</Name>
			<URL><?= $URL . "/status.php" ?></URL>
			<Position>1</Position>
	</SoftKeyItem>

<? if (doesSupport("CiscoIPPhoneImageFile") && !(isModel("7961") || isModel("7941"))) { ?>
	<SoftKeyItem>
	<Name>ActvChrt</Name>
	<URL><?= $URL . "/status_graph.php" ?></URL>
	<Position>2</Position>
	</SoftKeyItem>
<? } ?>

<!--
<SoftKeyItem>
<Name>Jobs</Name>
<URL><?= $URL . "/status_jobs.php" ?></URL>
<Position>2</Position>
</SoftKeyItem>
-->

<? if (doesSupport("CiscoIPPhoneImageFile") && !(isModel("7961") || isModel("7941"))) { ?>
	<SoftKeyItem>
	<Name>Joblist</Name>
	<URL><?= $URL . "/joblist.php" ?></URL>
	<Position>3</Position>
	</SoftKeyItem>
<? } ?>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= $URL . "/main.php" ?></URL>
<Position>4</Position>
</SoftKeyItem>


</CiscoIPPhoneText>