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
							j.id as jobid,
							j.name,
							jt.name as jobtype,
							u.login,
							j.startdate,
							j.enddate,
							j.starttime,
							j.endtime,
							j.activedate,
							j.status,
							count(distinct rp.personid) as person_count,
							coalesce(sum(rc.type='phone'), 0) as phone_count,
							coalesce(sum(rc.type='email'), 0) as email_count,
							coalesce(sum(rc.type='sms'), 0) as sms_count
							from job j
							left outer join reportperson rp
								on (j.id = rp.jobid)
							left outer join reportcontact rc
								on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid and rc.result not in('declined'))
							inner join user u on (j.userid = u.id)
							inner join jobtype jt on (jt.id = j.jobtypeid)
							where j.id in ('" . $joblist . "')
							group by j.id";
	$jobinforesult = Query($jobinfoquery, $readonlyDB);
	$jobinfo = array();
	while($row = DBGetRow($jobinforesult, true)){
		// the date and time formatters expect $row[$index] to be start and $row[$index+1] to be the end
		$row[4] = $row["startdate"];
		$row[5] = $row["enddate"];
		$row[6] = $row["starttime"];
		$row[7] = $row["endtime"];
		$jobinfo[$row["jobid"]] = $row;
	}

	// get the count of devices separately, and combine the results in PHP space.
	$jobinfoquery = "Select
							rp.jobid,
							coalesce(count(rd.jobid), 0) as device_count
							from reportperson rp
							left outer join reportdevice rd
								on (rp.jobid = rd.jobid and rp.personid = rd.personid and rd.result not in('declined'))
							where rp.jobid in ('" . $joblist . "')
								and rp.type = 'device'
							group by rp.jobid";
	$jobinforesult = Query($jobinfoquery, $readonlyDB);
	while($row = DBGetRow($jobinforesult, true)){
		$jobinfo[$row["jobid"]]["device_count"] = $row["device_count"];
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
		$hasinfocenter = getSystemSetting("_hasinfocenter", false);

		startWindow(_L("Summary "). help("ReportGeneratorUtils_Summary"), 'padding: 3px;');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr valign="top">
					<th align="right" class="windowRowHeader"><?= _L("%s Summary:", getJobTitle()) ?></th>
					<td>
						<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
							<tr class="listHeader" align="left" valign="bottom">
								<th><?= _L("%s Name", getJobTitle()) ?></th>
								<th><?= _L("%s Type", getJobTitle()) ?></th>
								<th><?= _L("Submitted by") ?></th>
								<th><?= _L("Scheduled Date") ?></th>
								<th><?= _L("Scheduled Time") ?></th>
								<th><?= _L("First Pass") ?></th>
								<th><?= _L("Status") ?></th>
								<th><?= _L("Recipients") ?></th>
								<th><?= _L("# of Phones") ?></th>
								<th><?= _L("# of Emails") ?></th>
<? if($hassms) { ?>
								<th><?= _L("# of SMS") ?></th>
<? } ?>
<? if($hasinfocenter) { ?>
								<th><?= _L("# of Devices") ?></th>
<? } ?>
							</tr>
<?
							$alt=0;
							foreach($jobinfo as $job){
								echo ++$alt % 2 ? '<tr>' : '<tr class="listAlt">';
?>
									<td><?=escapehtml($job["name"])?></td>
									<td><?=escapehtml($job["jobtype"])?></td>
									<td><?=escapehtml($job["login"])?></td>
									<td><?=fmt_scheduled_date($job,4)?></td>
									<td><?=fmt_scheduled_time($job,6)?></td>
									<td><?=fmt_job_first_pass($job, "activedate")?></td>
									<td><?=escapehtml(ucfirst($job["status"]))?></td>
									<td><?=(int)$job["person_count"]?></td>
									<td><?=(int)$job["phone_count"]?></td>
									<td><?=(int)$job["email_count"]?></td>
<? if($hassms) { ?>
									<td><?=(int)$job["sms_count"]?></td>
<? } ?>
<? if ($hasinfocenter) { ?>
									<td><?=(int)$job["device_count"]?></td>
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
