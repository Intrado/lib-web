<?

class JobSummaryReport extends ReportGenerator{

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$jobtypes = "";
		$joblistquery = "";
		if(isset($this->params['jobtypes'])){
			$jobtypes = $this->params['jobtypes'];
		}
		$surveyonly = "false";
		if(isset($this->params['survey']) && $this->params['survey']=="true"){
			$surveyonly = "true";
		}
		if(isset($this->params['jobid'])){
			$url = "jobid=";
			$joblist = "";
			$job = new Job($this->params['jobid']);
			$jobtypesarray = explode("','", $jobtypes);
			if($jobtypes == "" || in_array($job->jobtypeid, $jobtypesarray)){
				$joblist = $this->params['jobid'];
			}
		} else {
			$reldate = "today";
			if(isset($this->params['reldate']))
				$reldate = $this->params['reldate'];
			list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
			$joblist = implode("','", getJobList($startdate, $enddate, $jobtypes, $surveyonly));
		}

		if($joblist){
			$joblistquery = " and rp.jobid in ('" . $joblist . "')";
		} else {
			$joblistquery = " and 0 ";
		}
		$this->params['joblist'] = $joblist;
		// Query for graph in pdf
		$this->query = JobSummaryReport::getDestinationResultQuery($joblistquery, "and rp.type = 'phone'");
	}

	// @param $joblistquery, sql of the form: "and ___", like "and rp.jobid = 123"
	// @param $rptypequery, sql of the form: "and ___", like "and rp.type = 'phone'"
	static function getDestinationResultQuery($joblistquery, $rptypequery) {
		return "select count(*) as cnt,
				coalesce(rc.result, rp.status) as currentstatus,
				sum(rc.result not in ('A','M', 'sent', 'blocked', 'duplicate') and rc.numattempts < js.value) as remaining
				from reportperson rp
				left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
				left join jobsetting js on (js.jobid = rc.jobid and js.name = 'maxcallattempts')
				where 1 $joblistquery $rptypequery
				group by currentstatus";
	}

	// @param $joblist, a comma-separated string of job ids, assumed to be SQL-injection-safe
	static function getPhoneInfo($joblist, $readonlyconn) {
		$phonenumberquery = "select sum(rc.type='phone') as total,
									sum(rp.status in ('success', 'fail', 'duplicate', 'blocked')) as done,
									sum(rp.status not in ('success', 'fail', 'duplicate', 'blocked', 'nocontacts', 'declined') and rc.result not in ('A', 'M', 'duplicate', 'blocked')) as remaining,
									sum(rc.result = 'blocked') as blocked,
									sum(rc.result = 'duplicate') as duplicate,
									sum(rp.status = 'nocontacts' and rc.result is null) as nocontacts,
									sum(rc.numattempts) as totalattempts,
									sum(rp.status = 'declined' and rc.result is null) as declined,
									(select 100 * sum(rp2.status != 'duplicate' and rp2.iscontacted) / (sum(rp2.status != 'duplicate') + 0.00) as success_rate from reportperson rp2 where rp2.type='phone' and rp2.jobid=rp.jobid) as success_rate
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									inner join job j on (j.id = rp.jobid)
									where rp.jobid in ('$joblist')
									and rp.type='phone'";
		return QuickQueryRow($phonenumberquery, false, $readonlyconn);
	}

	// @param $joblist, a comma-separated string of job ids, assumed to be SQL-injection-safe
	static function getEmailInfo($joblist, $readonlyconn) {
		$emailquery = "select sum(rc.type = 'email') as total,
									sum(rp.status in ('success', 'duplicate', 'fail')) as done,
									sum(rp.status not in ('success', 'fail', 'duplicate', 'nocontacts', 'declined') and rc.result not in ('sent', 'duplicate', 'blocked')) as remaining,
									sum(rc.result = 'duplicate') as duplicate,
									sum(rp.status = 'nocontacts' and rc.result is null) as nocontacts,
									sum(rp.status = 'declined' and rc.result is null) as declined,
									(select 100 * sum(rp2.status != 'duplicate' and rp2.iscontacted) / (sum(rp2.status != 'duplicate') + 0.00) as success_rate from reportperson rp2 where rp2.type='email' and rp2.jobid=rp.jobid) as success_rate
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									where rp.jobid in ('$joblist')
									and rp.type='email'";
		return QuickQueryRow($emailquery, false, $readonlyconn);
	}

	static function getSmsInfo($joblist, $readonlyconn) {
		$smsquery = "select sum(rc.type = 'sms') as total,
									sum(rp.status in ('success', 'duplicate', 'fail')) as done,
									sum(rp.status not in ('success', 'fail', 'duplicate', 'blocked', 'nocontacts', 'declined') and rc.result not in ('sent', 'duplicate', 'blocked')) as remaining,
									sum(rc.result = 'blocked') as blocked,
									sum(rc.result = 'duplicate') as duplicate,
									sum(rp.status = 'nocontacts' and rc.result is null) as nocontacts,
									sum(rp.status = 'declined' and rc.result is null) as declined,
									(select 100 * sum(rp2.status != 'duplicate' and rp2.iscontacted) / (sum(rp2.status != 'duplicate') + 0.00) as success_rate from reportperson rp2 where rp2.type='sms' and rp2.jobid=rp.jobid) as success_rate
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									where rp.jobid in ('$joblist')
									and rp.type='sms'";
		return QuickQueryRow($smsquery, false, $readonlyconn);
	}

	function runHtml(){
		global $USER;
		$validstamp = time();
		$jobstats = array ("validstamp" => $validstamp);

		// Gather Job information

		$jobtypes = "";
		if(isset($this->params['jobtypes'])){
			$jobtypes = $this->params['jobtypes'];
		}
		$surveyonly = "false";
		if($this->params['reporttype'] == "surveynotification")
			$surveyonly = "true";
		if(isset($this->params['jobid'])){
			$url = "jobid=";
			$joblist = "";
			$job = new Job($this->params['jobid']);
			$jobtypesarray = explode("','", $jobtypes);
			if($jobtypes == "" || in_array($job->jobtypeid, $jobtypesarray)){
				$url .= $this->params['jobid'];
			}
		} else {
			$reldate = "today";
			if(isset($this->params['reldate']))
				$reldate = $this->params['reldate'];
			list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
			$url = "startdate=" . $startdate . "&enddate=" . $enddate . "&jobtypes=" . $jobtypes . "&surveyonly=" . $surveyonly;
		}

		$hasconfirmation = QuickQuery("select sum(value) from jobsetting where name = 'messageconfirmation' and jobid in ('" . $this->params['joblist'] . "')", $this->_readonlyDB);

		//Gather Detailed Destination Results
		$phonenumberinfo = JobSummaryReport::getPhoneInfo($this->params['joblist'], $this->_readonlyDB);
		$emailinfo = JobSummaryReport::getEmailInfo($this->params['joblist'], $this->_readonlyDB);
		$smsinfo = JobSummaryReport::getSmsInfo($this->params['joblist'], $this->_readonlyDB);


		if($hasconfirmation){
			$confirmedquery = "select sum(rc.response=1),
										sum(rc.response=2),
										sum(rc.response is null)
											from reportperson rp
											left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
											inner join job j on (j.id = rp.jobid)
											where rp.jobid in ('" . $this->params['joblist'] . "')
										and rp.type='phone'";
			$confirmedinfo = QuickQueryRow($confirmedquery, false, $this->_readonlyDB);
		}

		//may need to clean up, null means not called yet
		//do math for the % completed

		$result = Query($this->query, $this->_readonlyDB);
		$cpstats = array (
							"A" => 0,
							"M" => 0,
							"N" => 0,
							"B" => 0,
							"X" => 0,
							"F" => 0,
							"notattempted" => 0,
							"blocked" => 0,
							"duplicate" => 0,
							"nocontacts" => 0,
							"declined" => 0
						);
		$cpcodes = array(
							"A" => "Answered",
							"M" => "Machine",
							"B" => "Busy",
							"N" => "No Answer",
							"X" => "Disconnect",
							"F" => "Unknown",
							"notattempted" => "Not Attempted",
							"blocked" => "Blocked",
							"duplicate" => "Duplicate",
							"nocontacts" => "No Phone #",
							"declined" => "No Phone Selected"
						);
		$jobstats["phone"] = $cpstats;
		$remainingcalls=0;

		while ($row = DBGetRow($result)) {
			$jobstats["phone"][$row[1]] += $row[0];
			if ($row[1] != "A" && $row[1] != "M" && $row[1] != "blocked" && $row[1] != "duplicate") {
				$remainingcalls += $row[2];
			}
		}
		$jobstats["phone"]['totalcalls'] = array_sum($jobstats["phone"]);
		$jobstats["phone"]['remainingcalls'] = $remainingcalls;

		$jobnumberlist = implode("", explode("','", $this->params['joblist']));
		$_SESSION['jobstats'][$jobnumberlist] = $jobstats;

		$urloptions = $url . "&valid=$validstamp";

		// DISPLAY

		if(isset($this->params['jobtypes']) && $this->params['jobtypes'] != ""){

			startWindow("Filter by");
?>
			<table>
<?
				$jobtypes = explode("','", $this->params['jobtypes']);
				$jobtypenames = array();
				foreach($jobtypes as $jobtype){
					$jobtypeobj = new JobType($jobtype);
					$jobtypenames[] = escapehtml($jobtypeobj->name);
				}
				$jobtypenames = implode(", ",$jobtypenames);
?>
				<tr><td>Job Type: <?=$jobtypenames?></td></tr>

			</table>
<?
			endWindow();

			?><br><?
		}

		displayJobSummary($this->params['joblist'], $this->_readonlyDB);
		?><br><?
		startWindow("Totals ".help("JobSummaryReport_Totals"), "padding: 3px;");
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
<?
				if(array_sum($emailinfo) > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=email">Email:</a></th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th># of Emails</th>
											<th>Completed</th>
											<th>Remaining</th>
											<th>Duplicates Removed</th>
											<th>No Email</th>
											<th>No Email Selected</th>
											<th>% Contacted</th>
										</tr>
										<tr>
											<td><?=$emailinfo[0]+0?></td>
											<td><?=$emailinfo[1]+0?></td>
											<td><?=$emailinfo[2]+0?></td>
											<td><?=$emailinfo[3]+0?></td>
											<td><?=$emailinfo[4]+0?></td>
											<td><?=$emailinfo[5]+0?></td>
											<td><?=sprintf("%0.2f", isset($emailinfo[6]) ? $emailinfo[6] : "") . "%" ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				}
				if(array_sum($smsinfo) > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=sms">SMS:</a></th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th># of SMS</th>
											<th>Completed</th>
											<th>Remaining</th>
											<th>Blocked</th>
											<th>Duplicates Removed</th>
											<th>No SMS</th>
											<th>No SMS Selected</th>
											<th>% Contacted</th>
										</tr>
										<tr>
											<td><?=$smsinfo[0]+0?></td>
											<td><?=$smsinfo[1]+0?></td>
											<td><?=$smsinfo[2]+0?></td>
											<td><?=$smsinfo[3]+0?></td>
											<td><?=$smsinfo[4]+0?></td>
											<td><?=$smsinfo[5]+0?></td>
											<td><?=$smsinfo[6]+0?></td>
											<td><?=sprintf("%0.2f", isset($smsinfo[7]) ? $smsinfo[7] : "") . "%" ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				}
				if(array_sum($phonenumberinfo) > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=phone">Phone:</a></th>
					<td class ="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th># of Phones</th>
											<th>Completed</th>
											<th>Remaining</th>
											<th>Blocked</th>
											<th>Duplicates Removed</th>
											<th>No Phone #</th>
											<th>No Phone Selected</th>
											<th>Total Attempts</th>
											<th>% Contacted</th>
										</tr>
										<tr>
											<td><?=$phonenumberinfo[0]+0?></td>
											<td><?=$phonenumberinfo[1]+0?></td>
											<td><?=$phonenumberinfo[2]+0?></td>
											<td><?=$phonenumberinfo[3]+0?></td>
											<td><?=$phonenumberinfo[4]+0?></td>
											<td><?=$phonenumberinfo[5]+0?></td>
											<td><?=$phonenumberinfo[7]+0?></td>
											<td><?=$phonenumberinfo[6]+0?></td>
											<td><?=sprintf("%0.2f", isset($phonenumberinfo[8]) ? $phonenumberinfo[8] : "") . "%" ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">Phone Details:</th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table>
<?
										foreach($cpcodes as $index => $value){
											switch($index){
												case 'nocontacts':
												case 'declined':
													$urltext = "?status=$index";
													break;
												default:
													$urltext = "?result=$index";
													break;
											}
?>
										<tr>
											<td><div class="floatingreportdata"><u><a href="reportjobdetails.php<?=$urltext?>&type=phone"/><?=$value?>:</a><u></div></td>
											<td><?=$jobstats["phone"][$index]?></td>
										</tr>
<?
										}
?>
										<tr>
											<td><div class="floatingreportdata"><u><a href="reportjobdetails.php?type=phone"/a>Total:</a><u></div></td>
											<td><?=$jobstats["phone"]['totalcalls']?></td>
										</tr>
									</table>
								</td>
								<td>
									<img src="graph_detail_callprogress.png.php?<?= $urloptions ?>">
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				}
				if($hasconfirmation){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">Message Confirmation:</th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table>
										<tr><td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=confirmed&type=phone"/a>Yes (Pressed 1):</a.</td><td><?=$confirmedinfo[0]+0?></td></tr>
										<tr><td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=notconfirmed&type=phone"/a>No (Pressed 2):</a></td><td><?=$confirmedinfo[1]+0?></td></tr>
										<tr><td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=noconfirmation&type=phone"/a>No Confirmation:</a></td><td><?=$confirmedinfo[2]+0?></td></tr>
									</table>
								<td>
							</tr>
						</table>
					</td>
				</tr>
<?
				}
				if(array_sum($phonenumberinfo) < 1 && array_sum($smsinfo) < 1 && array_sum($emailinfo) < 1){
?>
				<tr><td>No Job Data</td></tr>
<?
				}
?>
			</table>
		<?
		endWindow();


	}

	function setReportFile(){
		$this->reportfile = "jobsummaryreport.jasper";
	}

	function getReportSpecificParams(){

		$daterange = "";
		if(isset($this->params['reldate'])){
			list($startdate, $enddate) = getStartEndDate($this->params['reldate'], $this->params);
			$daterange = "From: " . date("m/d/Y", $startdate) . " To: " . date("m/d/Y", $enddate);
		}
		$joblist = array();
		if($this->params['joblist'] != "")
			$joblist=explode("','", $this->params['joblist']);

		$sms = QuickQuery("select count(smsmessageid) from job where id in ('" . $this->params['joblist'] . "')", $this->_readonlyDB) ? "1" : "0";
		$messageconfirmation = QuickQuery("select sum(value) from jobsetting where name = 'messageconfirmation' and jobid in ('" . $this->params['joblist'] . "')", $this->_readonlyDB) ? "1" : "0";

		$params = array("jobId" => $this->params['joblist'],
						"jobcount" => count($joblist),
						"daterange" => $daterange,
						"hassms" => $sms,
						"messageconfirmation" => $messageconfirmation);
		return $params;
	}

	static function getOrdering(){
		global $USER;
		$fields = getAuthorizedFieldMaps();

		$ordering = array();
		$ordering["ID#"] = "rp.pkey";
		$ordering[$firstname->name]="rp." . $firstname->fieldnum;
		$ordering[$lastname->name]="rp." . $lastname->fieldnum;
		$ordering["Message"]="m.name";
		$ordering["Destination"]="destination";
		$ordering["Attempts"] = "attempts";
		$ordering["Last Attempt"]="date";
		$ordering["Last Result"]="result";


		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}
}

?>
