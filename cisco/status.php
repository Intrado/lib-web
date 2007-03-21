<?
require_once("common.inc.php");
require_once("../inc/formatters.inc.php");
require_once("../obj/Job.obj.php");


if (!$USER->authorize('createreport')) {
	header("Location: $URL/index.php");
	exit();
}

//get the top 5 jobs

$jobs = DBFindMany("Job", "from job where userid=$USER->id and deleted=0 and status in ('active','complete','cancelled','cancelling') order by id desc limit 5");
foreach ($jobs as $job) {
	$query = "
	select
		count(wi.personid) as people,
		sum(wi.status='success' or wi.status='fail') / (count(wi.personid) + 0.00 - sum(wi.status = 'duplicate')) as completed_percent,
		sum(wi.status='success') as success,
		sum(wi.status='fail') as fail,
		sum(wi.status not in ('success','fail','duplicate')) as in_progress,
		sum(wi.status = 'duplicate') as duplicate,
		sum(wi.status='success') / (sum(wi.status='success' or wi.status='fail' or (jt.numattempts>0 and wi.status = 'queued' or wi.status='scheduled')) +0.00) as success_rate

		from 		job j, jobworkitem wi
		left join	jobtask jt on
						(jt.jobworkitemid=wi.id)
		left join	person p on
						(p.id=wi.personid)
		left join	calllog cl on
						(cl.jobtaskid=jt.id and (cl.callattempt=jt.numattempts-1))
		where wi.jobid=j.id
		and j.id = $job->id
		and wi.type='phone'
	";



	$row = QuickQueryRow($query);
	$job->stats = $row;
}

header("Content-type: text/xml");
?>
<CiscoIPPhoneText>
<Title>SchoolMessenger - Status</Title>
<Prompt>Showing the last <?=  count($jobs) ?> job(s)</Prompt>
<Text><?

$output = "";
if (count($jobs) > 0) {
	foreach ($jobs as $job) {

		// is this a high res display? tweak the tabs
		if (isModel("7961") || isModel("7941")) {
			$output .= "--$job->name (" . fmt_status($job,"status") . ")--\r\n";
			$output .= "    Complete:\t\t\t\t\t" . sprintf("%0.2f%%",$job->stats[1]*100) . "\r\n";
			$output .= "    Success Rate:\t" . sprintf("%0.2f%%",$job->stats[6]*100) . "\r\n";
			$output .= "    People:\t\t\t\t\t\t\t" . $job->stats[0] . "\r\n";
			$output .= "    Duplicates:\t\t\t\t" . $job->stats[5] . "\r\n";
			$output .= "    Successful:\t\t\t\t" . $job->stats[2] . "\r\n";
			$output .= "    Failed:\t\t\t\t\t\t\t" . $job->stats[3] . "\r\n";
			$output .= "    In Progress:\t\t\t" . $job->stats[4] . "\r\n";
		} else if (doesSupport("CiscoIPPhoneImageFile")) {
			$output .= "--$job->name (" . fmt_status($job,"status") . ")--\r\n";
			$output .= "    Complete:\t\t\t" . sprintf("%0.2f%%",$job->stats[1]*100) . "\r\n";
			$output .= "    Success Rate:\t" . sprintf("%0.2f%%",$job->stats[6]*100) . "\r\n";
			$output .= "    People:\t\t\t\t\t" . $job->stats[0] . "\r\n";
			$output .= "    Duplicates:\t\t\t" . $job->stats[5] . "\r\n";
			$output .= "    Successful:\t\t" . $job->stats[2] . "\r\n";
			$output .= "    Failed:\t\t\t\t\t" . $job->stats[3] . "\r\n";
			$output .= "    In Progress:\t\t" . $job->stats[4] . "\r\n";
		} else {
			$output .= "--$job->name (" . fmt_status($job,"status") . ")--\r\n";
			$output .= "    Complete:\t\t\t" . sprintf("%0.2f%%",$job->stats[1]*100) . "\r\n";
			$output .= "    Success Rate:\t" . sprintf("%0.2f%%",$job->stats[6]*100) . "\r\n";
			$output .= "    People:\t\t\t\t" . $job->stats[0] . "\r\n";
			$output .= "    Duplicates:\t\t" . $job->stats[5] . "\r\n";
			$output .= "    Successful:\t\t" . $job->stats[2] . "\r\n";
			$output .= "    Failed:\t\t\t\t\t" . $job->stats[3] . "\r\n";
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