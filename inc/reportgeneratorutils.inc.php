<?

function getOrderSql($params){

	$orderquery = "";
	$orderfields = array();
	//only 3 order by's allowed. if more is specified, must change on web also
	for($i=1; $i<=3; $i++){
		if(isset($params["order$i"]) && $params["order$i"]!=""){
			$orderfields[] = $params["order$i"];
		}
	}
	if(count($orderfields) > 0){
		$orderquery = "order by " . implode(", ", $orderfields);
	}
	return $orderquery;

}

function getRuleSql($params, $alias){
	$rulesql = "";
	if(isset($params['rules'])){
		foreach($params['rules'] as $rule){
			$rulesql .= " " . $rule->toSql($alias);
		}
	}
	return $rulesql;
}

function getJobSummary($joblist){
	global $USER;

	$jobinfoquery = "Select
							j.name,
							jt.name,
							u.login,
							j.startdate,
							j.enddate,
							j.starttime,
							j.endtime,
							j.status,
							count(distinct rp.personid) as pcount,
							coalesce(sum(rc.type='phone'), 0),
							coalesce(sum(rc.type='email'), 0),
							coalesce(sum(rc.type='sms'), 0)
							from job j
							left join reportperson rp on (j.id = rp.jobid)
							left join reportcontact rc on (rp.personid = rc.personid and rp.jobid = rc.jobid and rp.type = rc.type)
							inner join user u on (j.userid = u.id)
							inner join jobtype jt on (jt.id = j.jobtypeid)
							where j.id in ('" . $joblist . "')
							group by j.id";
	$jobinforesult = Query($jobinfoquery);
	$jobinfo = array();
	while($row = DBGetRow($jobinforesult)){
		//combine start date and start time for formatter to output correctly
		$row[4] = $row[4] . " " . $row[5];
		$jobinfo[] = $row;
	}
	return $jobinfo;
}

function displayJobSummary($joblist){

		$jobinfo = getJobSummary($joblist);

		//Check for any sms messages
		$smscheck = QuickQuery("select count(smsmessageid) from job where id in ('" . $joblist . "')");


		startWindow("Summary ". help("ReportGeneratorUtils_Summary"), 'padding: 3px;');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr valign="top">
					<th align="right" class="windowRowHeader">Job Summary:</th>
					<td>
						<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
							<tr class="listHeader" align="left" valign="bottom">
								<th>Job Name</th>
								<th>Job Type</th>
								<th>Submitted by</th>
								<th>Scheduled Date</th>
								<th>Scheduled Time</th>
								<th>Status</th>
								<th>Recipients</th>
								<th># of Phones</th>
								<th># of Emails</th>
<? if($smscheck) { ?>
								<th># of SMS</th>
<? } ?>
							</tr>
<?
							$alt=0;
							foreach($jobinfo as $job){
								echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>

									<td><?=$job[0]?></td>
									<td><?=$job[1]?></td>
									<td><?=$job[2]?></td>
									<td><?=fmt_scheduled_date($job,3)?></td>
									<td><?=fmt_scheduled_time($job,5)?></td>
									<td><?=ucfirst($job[7])?></td>
									<td><?=$job[8]?></td>
									<td><?=$job[9]?></td>
									<td><?=$job[10]?></td>
<? if($smscheck) { ?>
									<td><?=$job[11]?></td>
<? } ?>
								</tr>
<?
							}
?>
						</table>
					</td>
				</tr>
			</table>
<?
		endWindow();

}
?>