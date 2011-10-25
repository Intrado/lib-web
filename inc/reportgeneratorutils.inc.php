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

/*
 * TODO this is used only by reports that pull off of person table for showing contacts.
 * Can be rewritten to do something similar to renderedlist (or perhaps use renderedlist)
 */
function getUserOrganizationSql () {
	global $USER;
	$query = "select ua.organizationid from userassociation ua where ua.userid=? 
		union
		select s.organizationid from userassociation ua left join section s on (ua.sectionid=s.id) where ua.userid=?";
	$restrictedorgs = QuickQueryList($query, false, false, array($USER->id, $USER->id));
	$count = count($restrictedorgs);
	if ($count == 0)
		return "";
	else if ($count == 1 && $restrictedorgs[0] == null) {
		return " and 0 /* no org access */ "; //if only thing in user association is section id=0, then it will return a single null
	} else {
		//check for and remove null elements from section id=0
		foreach ($restrictedorgs as $k => $v)
			if ($v == null)
				unset($restrictedorgs[$k]);
		$orgidcsv = implode(",",$restrictedorgs);
		return "and exists (select * from personassociation pa 
				where pa.personid=p.id and pa.organizationid in ($orgidcsv))";
	}
}

//deprecated
function getRuleSql($params, $alias, $isjobreport=true){
	$rulesql = "";
	if(isset($params['rules'])){
		$rulesql = Rule::makeQuery($params['rules'], $alias,false,$isjobreport);
	}
	return $rulesql;
}

function getOrgSql($params) {
	if (isset($params['organizationids']) && count($params['organizationids']) > 0) {
		$orgidcsv = implode(",",$params['organizationids']);
		$query = " and exists (select * from reportorganization ro 
				where ro.jobid=rp.jobid and ro.personid=rp.personid and ro.organizationid in ($orgidcsv))";
		return $query;
	}
	return "";
}

function getJobSummary($joblist, $readonlyDB = false){
	global $USER;

	$jobinfoquery = "Select
							j.id,
							j.name,
							jt.name,
							u.login,
							j.startdate,
							j.enddate,
							j.starttime,
							j.endtime,
							j.activedate,
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
	$jobinforesult = Query($jobinfoquery, $readonlyDB);
	$jobinfo = array();
	while($row = DBGetRow($jobinforesult)){
		//combine start date and start time for formatter to output correctly
		$row[5] = $row[5] . " " . $row[6];
		$jobinfo[] = $row;
	}
	
	global $JOB_STATS;
	$JOB_STATS = array();
	$query = "select jobid, name, value from jobstats where jobid in ('" . $joblist . "') and name = 'complete-seconds-phone-attempt-0-sequence-0'";
	$jobstats_objects = QuickQueryMultiRow($query);
	foreach ($jobstats_objects as $obj) {
		$JOB_STATS[$obj[0]][$obj[1]] = $obj[2];
	}
	
	return $jobinfo;
}

function displayJobSummary($joblist, $readonlyDB = false){

		$jobinfo = getJobSummary($joblist, $readonlyDB);

		//Check for any sms messages
		$hassms = QuickQuery("select exists (select * from message m where m.type='sms' and m.messagegroupid = j.messagegroupid) from job j where id in ('" . $joblist . "')", $readonlyDB);

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
								<th>First Pass Completed</th>
								<th>Status</th>
								<th>Recipients</th>
								<th># of Phones</th>
								<th># of Emails</th>
<? if($hassms) { ?>
								<th># of SMS</th>
<? } ?>
							</tr>
<?
							$alt=0;
							foreach($jobinfo as $job){
								echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>

									<td><?=escapehtml($job[1])?></td>
									<td><?=escapehtml($job[2])?></td>
									<td><?=escapehtml($job[3])?></td>
									<td><?=fmt_scheduled_date($job,4)?></td>
									<td><?=fmt_scheduled_time($job,6)?></td>
									<td><?=fmt_job_first_pass($job, 8)?></td>
									<td><?=ucfirst($job[9])?></td>
									<td><?=$job[10]?></td>
									<td><?=$job[11]?></td>
									<td><?=$job[12]?></td>
<? if($hassms) { ?>
									<td><?=$job[13]?></td>
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
