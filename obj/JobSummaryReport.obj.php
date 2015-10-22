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
				coalesce(if(rc.result='X' and rc.numattempts<3,'F',rc.result), rp.status) as currentstatus,
				sum(rc.result not in ('A','M', 'sent', 'blocked', 'duplicate') and rc.numattempts < js.value) as remaining
				from reportperson rp
				left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid AND rc.result NOT IN('declined'))
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
									100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate')) as success_rate
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid AND rc.result NOT IN('declined'))
									inner join job j on (j.id = rp.jobid)
									where rp.jobid in ('$joblist')
									and rp.type='phone'";
		return QuickQueryRow($phonenumberquery, true, $readonlyconn);
	}

	// @param $joblist, a comma-separated string of job ids, assumed to be SQL-injection-safe
	static function getEmailInfo($joblist, $readonlyconn) {
		$emailquery = "select sum(rc.type = 'email') as total,
									sum(rp.status in ('success', 'duplicate', 'fail')) as done,
									sum(rp.status not in ('success', 'fail', 'duplicate', 'nocontacts', 'declined') and rc.result not in ('sent', 'duplicate', 'blocked')) as remaining,
									sum(rc.result = 'blocked') as blocked,
									sum(rc.result = 'duplicate') as duplicate,
									sum(rp.status = 'nocontacts' and rc.result is null) as nocontacts,
									sum(rp.status = 'declined' and rc.result is null) as declined,
									100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate')) as success_rate
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid AND rc.result NOT IN('declined'))
									where rp.jobid in ('$joblist')
									and rp.type='email'";
		return QuickQueryRow($emailquery, true, $readonlyconn);
	}

	static function getSmsInfo($joblist, $readonlyconn) {
		$smsquery = "select 
						count(rc.jobid) as total,
						sum(rc.result in ('duplicate', 'blocked', 'declined', 'notattempted')) as filtered, 
						sum(rc.result in ('queued', 'sending')) as pending,
						count(rc.jobid) - sum(rc.result in ('duplicate', 'blocked', 'declined', 'notattempted')) - sum(rc.result in ('delivered', 'sent')) -  sum(rc.result in ('queued', 'sending')) as undelivered,
						sum(rc.result in ('delivered', 'sent')) as delivered
					from 
						reportcontact rc
					where 
						rc.jobid in ('$joblist')
						and rc.type = 'sms'";
		
		return QuickQueryRow($smsquery, true, $readonlyconn);
	}

	static function getDeviceInfo($joblist, $readonlyconn) {
		$devicequery = "select count(rd.jobid) as total,
									sum(rp.status in ('success', 'duplicate', 'fail')) as done,
									sum(rp.status not in ('success', 'fail', 'duplicate', 'blocked', 'nocontacts', 'declined') and rd.result not in ('sent', 'duplicate', 'blocked')) as remaining,
									sum(rd.result = 'blocked') as blocked,
									sum(rd.result = 'duplicate') as duplicate,
									sum(rp.status = 'nocontacts' and rd.result is null) as nocontacts,
									sum(rp.status = 'declined' and rd.result is null) as declined,
									100 * sum(rp.numcontacts and rp.status='success') / (sum(rp.numcontacts and rp.status != 'duplicate')) as success_rate
									from reportperson rp
									left join reportdevice rd on (rp.jobid = rd.jobid and rp.personid = rd.personid)
									where rp.jobid in ('$joblist')
									and rp.type='device'";
		return QuickQueryRow($devicequery, true, $readonlyconn);
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
		$deviceinfo = JobSummaryReport::getDeviceInfo($this->params['joblist'], $this->_readonlyDB);

		if($hasconfirmation){
			$confirmedquery = "select sum(rc.response=1),
										sum(rc.response=2),
										sum(rc.response is null)
											from reportperson rp
											left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid AND rc.result NOT IN('declined'))
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
							"A" => _L("Answered"),
							"M" => _L("Machine"),
							"B" => _L("Busy"),
							"N" => _L("No Answer"),
							"X" => _L("Disconnect"),
							"F" => _L("Unknown"),
							"notattempted" => _L("Not Attempted"),
							"blocked" => _L("Blocked"),
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

		$jobnumberlist = implode("", explode("','", $this->params['joblist']));
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
		startWindow(_L("Totals ") .help("JobSummaryReport_Totals"), "padding: 3px;");
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
<?
				if(array_sum($emailinfo) > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=email"><?= _L("Email:") ?></a></th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th style="min-width: 100px"><?= _L("# of Emails") ?></th>
											<th style="min-width: 100px"><?= _L("Completed") ?></th>
											<th style="min-width: 100px"><?= _L("Remaining") ?></th>
											<th style="min-width: 100px"><?= _L("Blocked") ?></th>
											<th style="min-width: 100px"><?= _L("Duplicates Removed") ?></th>
											<th style="min-width: 100px"><?= _L("No Email") ?></th>
											<th style="min-width: 100px"><?= _L("No Email Selected") ?></th>
											<th style="width: 99%">&nbsp;</td>
											<th style="min-width: 100px"><?= _L("% Contacted") ?></th>
										</tr>
										<tr>
											<td><?=(int)$emailinfo['total']?></td>
											<td><?=(int)$emailinfo['done']?></td>
											<td><?=(int)$emailinfo['remaining']?></td>
											<td><?=(int)$emailinfo['blocked']?></td>
											<td><?=(int)$emailinfo['duplicate']?></td>
											<td><?=(int)$emailinfo['nocontacts']?></td>
											<td><?=(int)$emailinfo['declined']?></td>
											<td>&nbsp;</td>
											<td><?=sprintf("%0.2f", isset($emailinfo['success_rate']) ? $emailinfo['success_rate'] : "") . "%" ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				} // end email summary

				if(array_sum($smsinfo) > 0) {
					
					$total = $smsinfo['total'];
					$formattedResults = array();
					
					foreach($smsinfo as $key => $field) {
						$formattedResults[$key] = percent_value( $field / $total );
					}
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=sms">SMS:</a></th>
					<td class="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th style="min-width: 100px"><?= _L("# of SMS") ?></th>
											<th style="min-width: 100px"><?= _L("Not Attempted") ?></th>
											<th style="min-width: 100px"><?= _L("Pending") ?></th>
											<th style="min-width: 100px"><?= _L("Undelivered") ?></th>
											<th style="min-width: 100px"><?= _L("Delivered") ?></th>
										</tr>
										<tr>
											<?
												foreach($smsinfo as $key => $value) {
													
													echo '<td>';
													
													if($key === 'total') {
														echo $value;
													} else {
														echo $value > 0 ? ($value .' '.'('.$formattedResults[$key].')') : $value;
													};
													
													echo '</td>';
												};
											?>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				} // end sms summary

				if (array_sum($deviceinfo) > 0) {
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=device">Device:</a></th>
					<td class ="bottomBorder">
						<table width="100%">
							<tr>
								<td>

									<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th style="min-width: 100px"><?= _L("# of Devices") ?></th>
											<th style="min-width: 100px"><?= _L("Completed") ?></th>
											<th style="min-width: 100px"><?= _L("Remaining") ?></th>
											<th style="min-width: 100px"><?= _L("Blocked") ?></th>
											<th style="min-width: 100px"><?= _L("Duplicates Removed") ?></th>
											<th style="min-width: 100px"><?= _L("No Device") ?></th>
											<th style="min-width: 100px"><?= _L("No Device Selected") ?></th>
											<th style="width: 99%">&nbsp;</th>
											<th style="min-width: 100px"><?= _L("% Contacted") ?></th>
										</tr>
										<tr>
											<td><?=(int)$deviceinfo['total']?></td>
											<td><?=(int)$deviceinfo['done']?></td>
											<td><?=(int)$deviceinfo['remaining']?></td>
											<td><?=(int)$deviceinfo['blocked']?></td>
											<td><?=(int)$deviceinfo['duplicate']?></td>
											<td><?=(int)$deviceinfo['nocontacts']?></td>
											<td><?=(int)$deviceinfo['declined']?></td>
											<td>&nbsp;</td>
											<td><?=sprintf("%0.2f", isset($deviceinfo['success_rate']) ? $deviceinfo['success_rate'] : "") . "%" ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
<?
				} // end device summary

				if(array_sum($phonenumberinfo) > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=phone">Phone:</a></th>
					<td class ="bottomBorder">
						<table width="100%">
							<tr>
								<td>
									<table border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th style="min-width: 100px"><?= _L("# of Phones") ?></th>
											<th style="min-width: 100px"><?= _L("Completed") ?></th>
											<th style="min-width: 100px"><?= _L("Remaining") ?></th>
											<th style="min-width: 100px"><?= _L("Blocked") ?></th>
											<th style="min-width: 100px"><?= _L("Duplicates Removed") ?></th>
											<th style="min-width: 100px"><?= _L("No Phone #") ?></th>
											<th style="min-width: 100px"><?= _L("No Phone Selected") ?></th>
											<th style="width: 99%">&nbsp;</th>
											<th style="min-width: 100px"><?= _L("% Contacted") ?></th>
										</tr>
										<tr>
											<td><?=(int)$phonenumberinfo['total']?></td>
											<td><?=(int)$phonenumberinfo['done']?></td>
											<td><?=(int)$phonenumberinfo['remaining']?></td>
											<td><?=(int)$phonenumberinfo['blocked']?></td>
											<td><?=(int)$phonenumberinfo['duplicate']?></td>
											<td><?=(int)$phonenumberinfo['nocontacts']?></td>
											<td><?=(int)$phonenumberinfo['declined']?></td>
											<td>&nbsp;</th>
											<td><?=sprintf("%0.2f", isset($phonenumberinfo['success_rate']) ? $phonenumberinfo['success_rate'] : "") . "%" ?></td>
										</tr>
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
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
											<td><?=$jobstats["phone"][$index]?></td>
										</tr>
<?
										}
?>
										<tr>
											<td><div class="floatingreportdata"><u><a href="reportjobdetails.php?type=phone">Total:</a></u></div></td>
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
				} // end phone summary
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
		$joblist = array();
		if($this->params['joblist'] != "")
			$joblist=explode("','", $this->params['joblist']);

		$hassms = QuickQuery("select exists (select * from message m where m.type='sms' and m.messagegroupid = j.messagegroupid) from job j where id in ('" . $this->params['joblist'] . "')", $this->_readonlyDB);
		
		$messageconfirmation = QuickQuery("select sum(value) from jobsetting where name = 'messageconfirmation' and jobid in ('" . $this->params['joblist'] . "')", $this->_readonlyDB) ? "1" : "0";

		$params = array("jobId" => $this->params['joblist'],
						"jobcount" => count($joblist),
						"daterange" => $daterange,
						"hassms" => $hassms,
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
