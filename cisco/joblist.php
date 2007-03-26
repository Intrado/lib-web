<?
include("common.inc.php");
include("../obj/Job.obj.php");
if(isset($_GET['max'])){
	$min = $_GET['max']+1;
	$max = $_GET['max']+30;
} else {
	$min = 0;
	$max = 30;
}
if($min - 31 <= 0){
	$back = -1;
} else {
	$back = $min - 31;
}

$activejoblist=QuickQueryList("select id from job where userid = '$USER->id' and status = 'active' and deleted = '0' 
	order by id desc
	limit 30 offset $min");

$completedjoblist=QuickQueryList("select id from job where userid = '$USER->id' and status = 'complete'  and deleted = '0' 
	order by finishdate desc
	limit 30 offset $min");


header("Content-type: text/xml");
?>
<CiscoIPPhoneMenu>
<Title>SchoolMessenger - Jobs</Title>
<Prompt>Please select a job</Prompt>

<?

foreach($activejoblist as $job){ 
	$newjob = new Job($job);
?>
	<MenuItem>
		<Name><?=htmlentities($newjob->name) ?></Name>
		<URL><?= htmlentities($URL . "/status_jobgraph.php?jobid=" . $job) ?></URL>
	</MenuItem>
<? 
}

foreach($completedjoblist as $job){ 
	$newjob = new Job($job);
?>
	<MenuItem>
		<Name><?= htmlentities($newjob->name) ?></Name>
		<URL><?= htmlentities($URL . "/status_jobgraph.php?jobid=" . $job) ?></URL>
	</MenuItem>
<? } ?>


<SoftKeyItem>
<Name>Select</Name>
<URL>SoftKey:Select</URL>
<Position>1</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Back</Name>
<URL><?= htmlentities($URL . "/main.php") ?></URL>
<Position>2</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Mor Job</Name>
<URL><?= htmlentities($URL . "/joblist.php?max=". $max)  ?></URL>
<Position>3</Position>
</SoftKeyItem>

<SoftKeyItem>
<Name>Bck Job</Name>
<URL><?= htmlentities($URL . "/joblist.php?max=". $back)  ?></URL>
<Position>4</Position>
</SoftKeyItem>

</CiscoIPPhoneMenu>