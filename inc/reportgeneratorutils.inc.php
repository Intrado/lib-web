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
	if(isset($params['rules']) && $params['rules']){
		$rules = explode("||", $params['rules']);
		foreach($rules as $rule){
			if($rule) {
				$rule = explode(";", $rule);
				$newrule = new Rule();
				$newrule->logical = $rule[0];
				$newrule->op = $rule[1];
				$newrule->fieldnum = $rule[2];
				$newrule->val = $rule[3];
				$rulesql .= " " . $newrule->toSql($alias);
			}
		}
	}
	return $rulesql;
}

function getJobSummary($joblist){
	global $USER;
	$usersql = $USER->userSQL("rp");
	$jobinfoquery = "Select 
							j.name, 
							j.description,
							jt.name,
							u.login,
							j.startdate,
							j.starttime,
							coalesce(j.finishdate, j.enddate),
							j.status,
							sum(rc.type='phone'),
							sum(rc.type='email')
							from reportperson rp
							left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
							inner join job j on (rp.jobid = j.id)
							inner join user u on (rp.userid = u.id)
							inner join jobtype jt on (jt.id = j.jobtypeid)
							where rp.jobid in ('" . $joblist . "')
							$usersql
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
	
		startWindow("Summary", 'padding: 3px;');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr valign="top">
					<th align="right" class="windowRowHeader">Job Summary:</th>
					<td>
						<table border="1" cellpadding="2" cellspacing="1" class="list" width="100%">
							<tr class="listHeader" align="left" valign="bottom">
								<th>Job Name</th>
								<th>Description</th>
								<th>Type</th>
								<th>Submitted By</th>
								<th>Start Date</th>
								<th>End Date</th>
								<th>Status</th>
								<th># of Phones</th>
								<th># of Emails</th>
							</tr>
<?
							foreach($jobinfo as $job){
?>
								<tr>
									<td><?=$job[0]?></td>
									<td><?= ($job[1] == "" ) ? "&nbsp;" : $job[1]?></td>
									<td><?=$job[2]?></td>
									<td><?=$job[3]?></td>
									<td><?=fmt_date($job,4)?></td>
									<td><?=fmt_date($job,6)?></td>
									<td><?=ucfirst($job[7])?></td>
									<td><?=$job[8]?></td>
									<td><?=$job[9]?></td>
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