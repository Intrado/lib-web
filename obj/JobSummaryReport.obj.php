<?

class JobSummaryReport extends ReportGenerator{

	function generateQuery($hackPDF = false){
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
			$joblist = implode(",", getJobList($startdate, $enddate, $jobtypes, $surveyonly));
		}

		if($joblist){
			$joblistquery = " and rp.jobid in ($joblist)";
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
				coalesce(if(rc.result='X' and rc.numattempts<3,'F',rc.result), rp.status) as currentstatus,
				sum(rc.result not in ('A','M', 'sent', 'blocked', 'duplicate','consentpending','consentdenied','declined') and rc.numattempts < js.value) as remaining
				from reportperson rp
				left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
				left join jobsetting js on (js.jobid = rc.jobid and js.name = 'maxcallattempts')
				where 1 $joblistquery $rptypequery
				group by currentstatus";
	}

	// @param $joblist, a comma-separated string of job ids, assumed to be SQL-injection-safe
	static function getPhoneInfo($joblist, $readonlyconn) {
		// total number of contacts by Phone for job ids in $joblist
		$reportPersonCountQuery = "select count(*) as totalcontacts
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									where rp.jobid in ('" . $joblist . "')
									  and rp.type='phone'";

		$reportPersonCountResults = QuickQueryRow($reportPersonCountQuery, true, $readonlyconn);

		$reportContactCountQuery = "select count(*) as totalwithphone
									  from reportcontact rc
									 where rc.jobid in ('" . $joblist . "')
									   and rc.type='phone'";

		$reportContactCountResults = QuickQueryRow($reportContactCountQuery, true, $readonlyconn);

		$reportContactQuery = "select sum(rc.result in ('N', 'B', 'X', 'F', 'blocked', 'declined', 'consentpending', 'duplicate', 'consentdenied')) as notcontacted,
									sum(rc.result in ('A', 'M')) as contacted,
									sum(rc.numattempts) as totalattempts
								from reportcontact rc
								where rc.jobid in ('" . $joblist . "')
								and rc.type = 'phone'";

		$reportContactResults = QuickQueryRow($reportContactQuery, true, $readonlyconn);

		$reportContactResults['remaining'] = $reportContactCountResults['totalwithphone'] - $reportContactResults['notcontacted'] - $reportContactResults['contacted'];


		$combinedResults = array_merge($reportPersonCountResults, $reportContactCountResults, $reportContactResults);

		return $combinedResults;
	}

	/**
	 * @param string $joblist, a comma-separated string of job ids, assumed to be SQL-injection-safe
	 * @param mixed $readonlyconn
	 * @return array
	 */
	static function getEmailInfo($joblist, $readonlyconn) {
		$reportPersonCountQuery = "select count(*) as totalcontacts
								from reportperson rp
								left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
								where rp.jobid in ('" . $joblist . "')
								  and rp.type='email'";

		$reportPersonCountResults = QuickQueryRow($reportPersonCountQuery, true, $readonlyconn);

		$reportContactCountQuery = "select count(rc.jobid) as totalwithemail
									  from reportcontact rc
									 where rc.jobid in ('" . $joblist . "')
									   and rc.type='email'";

		$reportContactCountResults = QuickQueryRow($reportContactCountQuery, true, $readonlyconn);

		$reportContactQuery = "select sum(rc.result in ('duplicate', 'blocked', 'duplicate', 'declined', 'unsent')) as notcontacted,
								sum(rc.result = 'sent') as contacted
							   from reportcontact rc
							   where rc.jobid in ('" . $joblist . "')
								 and rc.type='email'";

		$reportContactResults = QuickQueryRow($reportContactQuery, true, $readonlyconn);

		$reportContactResults['remaining'] = $reportContactCountResults['totalwithemail'] - $reportContactResults['notcontacted'] - $reportContactResults['contacted'];

		$emailquery = "select sum(rc.type = 'email') as total,
							sum(rp.status in ('success', 'duplicate', 'fail')) as done,
							sum(rc.result = 'blocked') as blocked,
							sum(rc.result = 'duplicate') as duplicate,
							sum(rp.status = 'nocontacts' and rc.result is null) as nocontacts,
							sum(rp.status = 'declined' and rc.result is null) as declined,
							100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate')) as success_rate
						from reportperson rp
							left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid AND rc.result not in ('decliend'))
						where rp.jobid in ('" . $joblist . "')
							and rp.type='email'";

		$emailInfoResults = QuickQueryRow($emailquery, true, $readonlyconn);

		$combinedResults = array_merge($reportPersonCountResults, $reportContactCountResults, $reportContactResults, $emailInfoResults);

		return $combinedResults;
	}

	static function getSmsInfo($joblist, $readonlyconn) {

		// total number of contacts by SMS for job ids in $joblist
		$reportPersonCountQuery = "select count(*) as totalcontacts
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									where rp.jobid in ('" . $joblist . "')
									  and rp.type='sms'";

		$reportPersonCountResults = QuickQueryRow($reportPersonCountQuery, true, $readonlyconn);

		$reportContactCountQuery = "select count(*) as totalwithsms
								from reportcontact rc
								where rc.jobid in ('" . $joblist . "')
								  and rc.type = 'sms'";

		$reportContactCountResults = QuickQueryRow($reportContactCountQuery, true, $readonlyconn);

		$reportContactQuery = "select sum(rc.result in ('queued', 'sending')) as pending,
									sum(rc.result in ('duplicate', 'blocked', 'duplicate', 'declined', 'unsent')) as notcontacted,
									sum(rc.result in ('delivered', 'sent')) as contacted
								from reportcontact rc
								where rc.jobid in ('" . $joblist . "')
								  and rc.type = 'sms'";

		$reportContactResults = QuickQueryRow($reportContactQuery, true, $readonlyconn);

		$reportContactResults['remaining'] = $reportContactCountResults['totalwithsms'] - $reportContactResults['notcontacted'] - $reportContactResults['contacted'];

		$combinedResults = array_merge($reportPersonCountResults, $reportContactCountResults, $reportContactResults);

		return $combinedResults;
	}

	static function getDeviceInfo($joblist, $readonlyconn) {
		$reportPersonCountQuery = "select count(*) as totalcontacts
									from reportperson rp
									left join reportdevice rd on (rp.jobid = rd.jobid and rp.personid = rd.personid)
									where rp.jobid in ('" . $joblist . "')
									  and rp.type='device'";

		$reportPersonCountResults = QuickQueryRow($reportPersonCountQuery, true, $readonlyconn);

		$reportContactCountQuery = "select count(*) as totalwithdevice
									  from reportdevice rd
									 where rd.jobid in ('" . $joblist . "')";

		$reportContactCountResults = QuickQueryRow($reportContactCountQuery, true, $readonlyconn);

		$devicequery = "select sum(rd.result in ('blocked','declined','duplicate','unsent')) as notcontacted,
								sum(rd.result = 'sent') as contacted
							from reportdevice rd
							where rd.jobid in ('" . $joblist . "')";

		$reportContactResults = QuickQueryRow($devicequery, true, $readonlyconn);

		$reportContactResults['remaining'] = $reportContactCountResults['totalwithdevice'] - $reportContactResults['notcontacted'] - $reportContactResults['contacted'];

		$combinedResults = array_merge($reportPersonCountResults, $reportContactCountResults, $reportContactResults);

		return $combinedResults;
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

		$joblist =  $this->params['joblist'];
		$hasconfirmation = QuickQuery("select sum(value) from jobsetting where name = 'messageconfirmation' and jobid in ('" . $joblist . "')", $this->_readonlyDB);

		//Gather Detailed Destination Results
		$phonenumberinfo = JobSummaryReport::getPhoneInfo($joblist, $this->_readonlyDB);
		$emailinfo = JobSummaryReport::getEmailInfo($joblist, $this->_readonlyDB);
		$smsinfo = JobSummaryReport::getSmsInfo($joblist, $this->_readonlyDB);
		$deviceinfo = JobSummaryReport::getDeviceInfo($joblist, $this->_readonlyDB);

		if($hasconfirmation){
			$confirmedquery = "select sum(rc.response=1),
										sum(rc.response=2),
										sum(rc.response is null)
											from reportperson rp
											left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid AND rc.result NOT IN('declined'))
											inner join job j on (j.id = rp.jobid)
											where rp.jobid in ('" . $joblist . "')
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
							"consentdenied" => 0,
							"consentpending" => 0,
							"duplicate" => 0,
							"nocontacts" => 0,
							"declined" => 0
						);
		$cpcodes = array(
							"A" => _L("Answered"),
							"M" => _L("Machine"),
							"B" => _L("Busy"),
							"N" => _L("No Answer"),
							"X" => _L("Disconnect"),
							"F" => _L("Unknown"),
							"notattempted" => _L("Not Attempted"),
							"blocked" => _L("Blocked"),
							"consentdenied" => _L("Consent Denied"),
							"consentpending" => _L("Consent Pending"),
							"duplicate" => _L("Duplicate"),
							"nocontacts" => _L("No Phone #"),
							"declined" => _L("No Phone Selected")
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

		// Note this seems wrong because if I have 1,2,3,4 it becomes 1234 as does 12,34 so no clue what jobstats is used for later on.
		$jobnumberlist = implode("", explode(",", $this->params['joblist']));
		$_SESSION['jobstats'][$jobnumberlist] = $jobstats;

		$urloptions = $url . "&valid=$validstamp";

		// DISPLAY

		if(isset($this->params['jobtypes']) && $this->params['jobtypes'] != ""){

			startWindow(_L("Filter by"));
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
				<tr><td><?= _L("%s Type: ", getJobTitle()) . $jobtypenames?></td></tr>

			</table>
<?
			endWindow();

			?><br><?
		}

		displayJobSummary($this->params['joblist'], $this->_readonlyDB);
		?><br><?
		startWindow(_L("Results ") .help("JobSummaryReport_Totals"), "padding: 3px;");
?>
		<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<tr>
				<th align="right" class="windowRowHeader bottomBorder">Summary:</th>
				<td class="bottomBorder">
<?
		if($phonenumberinfo['totalcontacts'] > 0){
?>
			<div class="col bloc" style="margin: 10px; float: left;">
				<h4><a href="reportjobdetails.php?type=phone"> <?= _L("Phone (" . number_format($phonenumberinfo['totalcontacts']) . ( $phonenumberinfo['totalcontacts'] == 1 ? " contact" : " contacts") . ")")?></a></h4>
				<div>
					<a href="reportjobdetails.php?type=phone">
						<img class="dashboard_graph" src="graph_job_summary.png.php?type=phone&jobId=<?= $this->params['joblist'] ?>"/>
					</a>
				</div>
			</div>
<?
		}
		if($emailinfo['totalcontacts'] > 0){
?>
			<div class="col bloc" style="margin: 10px; float: left;">
				<h4><a href="reportjobdetails.php?type=email"> <?= _L("Email (" . number_format($emailinfo['totalcontacts']) . ( $emailinfo['totalcontacts'] == 1 ? " contact" : " contacts") . ")")?></a></h4>
				<div>
					<a href="reportjobdetails.php?type=email">
						<img class="dashboard_graph" src="graph_job_summary.png.php?type=email&jobId=<?= $this->params['joblist'] ?>"/>
					</a>
				</div>
			</div>
<?
		}
		if($smsinfo['totalcontacts'] > 0){
?>
			<div class="col bloc" style="margin: 10px; float: left;">
				<h4><a href="reportjobdetails.php?type=sms"> <?= _L("SMS (" . number_format($smsinfo['totalcontacts']) . ( $smsinfo['totalcontacts'] == 1 ? " contact" : " contacts") . ")")?></a></h4>
				<div>
					<a href="reportjobdetails.php?type=sms">
						<img class="dashboard_graph" src="graph_job_summary.png.php?type=sms&jobId=<?= $this->params['joblist'] ?>"/>
					</a>
				</div>
			</div>
<?
		}
		if($deviceinfo['totalcontacts'] > 0 && $deviceinfo['totalwithdevice'] > 0) {
?>
			<div class="col bloc" style="margin: 10px; float: left;">
				<h4><a href="reportjobdetails.php?type=device"> <?= _L("Push (" . number_format($deviceinfo['totalcontacts']) . ( $deviceinfo['totalcontacts'] == 1 ? " contact" : " contacts") . ")")?></a></h4>
				<div>
					<a href="reportjobdetails.php?type=device">
						<img class="dashboard_graph" src="graph_job_summary.png.php?type=device&jobId=<?= $this->params['joblist'] ?>"/>
					</a>
				</div>
			</div>
<?
		}
?>
				</td>
				</tr>
			</table>

			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><?= _L("Phone Details:") ?></th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table>
<?
										foreach($cpcodes as $index => $value) {
											switch($index) {
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
											<td><div class="floatingreportdata"><u><a href="reportjobdetails.php<?=$urltext?>&type=phone"><?=$value?>:</a></u></div></td>
											<td style="vertical-align: middle"><?=$jobstats["phone"][$index]?></td>
										</tr>
<?
										}
?>
										<tr>
											<td><div class="floatingreportdata"><u><a href="reportjobdetails.php?type=phone">Total:</a></u></div></td>
											<td style="vertical-align: middle"><?=$jobstats["phone"]['totalcalls']?></td>
										</tr>
									</table>
								</td>
								<td>
									<img src="graph_detail_callprogress.png.php?scalex=0&<?= $urloptions ?>">
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				if($hasconfirmation){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><?= _L("Message Confirmation:") ?></th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table>
										<tr><td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=confirmed&type=phone">Yes (Pressed 1):</a></u></div></td><td><?=$confirmedinfo[0]+0?></td></tr>
										<tr><td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=notconfirmed&type=phone">No (Pressed 2):</a></u></div></td><td><?=$confirmedinfo[1]+0?></td></tr>
										<tr><td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=noconfirmation&type=phone">No Confirmation:</a></u></div></td><td><?=$confirmedinfo[2]+0?></td></tr>
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
				<tr><td><?= _L("No %s Data",getJobTitle()) ?></td></tr>
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
			$daterange = _L("From: %s To: %s",date("m/d/Y", $startdate),date("m/d/Y", $enddate));
		}
		$joblist =  $this->params['joblist'];
		$jobIds = array();
		if($joblist != "")
			$jobIds=explode(",", $joblist);

		$hassms = QuickQuery("select exists (select * from message m where m.type='sms' and m.messagegroupid = j.messagegroupid) from job j where id in ('" . $joblist . "')", $this->_readonlyDB);
		
		$messageconfirmation = QuickQuery("select sum(value) from jobsetting where name = 'messageconfirmation' and jobid in ('" . $joblist . "')", $this->_readonlyDB) ? "1" : "0";

		$params = array("jobId" => $joblist,
						"jobcount" => count($jobIds),
						"daterange" => $daterange,
						"hassms" => $hassms,
						"hasJobSummaryGraphs" => "1",
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
