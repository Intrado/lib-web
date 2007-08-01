<?

class JobSummaryReport extends ReportGenerator{
	
	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$usersql = $USER->userSQL("rp");
		$jobtypes = "";
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
		$this->params['joblist'] = $joblist;
		// Query for graph in pdf
		$this->query = "select count(*) as cnt, rc.result, sum(rc.result not in ('A','M') and rc.numattempts < js.value) as remaining
				from reportperson rp
				left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
				left join jobsetting js on (js.jobid = rc.jobid and js.name = 'maxcallattempts')
				where rc.jobid in ('" . $joblist . "')
				and rc.type='phone'
				$usersql
				group by rc.result";

	}
	
	function runHtml(){
		global $USER;
		$usersql = $USER->userSQL("rp");
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
		
		//Gather Phone Information		
		$phonenumberquery = "select sum(rc.result not in ('duplicate', 'blocked')) as total,
									sum(rc.result in ('A','M', 'duplicate', 'blocked')) as completed,
									sum(rc.result not in ('A','M', 'duplicate', 'blocked') and rc.numattempts < js.value and j.status in ('processing', 'active')) as remaining,
									sum(rc.numattempts) as totalattempts,
									sum(rc.result = 'duplicate') as duplicate,
									sum(rc.result = 'blocked') as blocked,
									sum(rp.status = 'nocontacts') as nocontacts
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									left join jobsetting js on (js.jobid = rc.jobid and js.name = 'maxcallattempts')
									inner join job j on (j.id = rp.jobid)
									where rp.jobid in ('" . $this->params['joblist'] . "')
									$usersql
									and rp.type='phone'";
		$phonenumberinfo = QuickQueryRow($phonenumberquery);
						
		$emailquery = "select sum(rc.result != 'duplicate') as total,
									sum(rc.result = 'sent') as Sent,
									sum(rc.result = 'unsent') as Unsent,
									sum(rc.result = 'duplicate') as duplicate,
									sum(rp.status = 'nocontacts') as nocontacts
									from reportperson rp
									left join reportcontact rc on (rp.jobid = rc.jobid and rp.type = rc.type and rp.personid = rc.personid)
									where rp.jobid in ('" . $this->params['joblist'] . "')
									$usersql
									and rc.type='email'";
		$emailinfo = QuickQueryRow($emailquery);
			
		//may need to clean up, null means not called yet
		//do math for the % completed
		
		$result = Query($this->query);
		$cpstats = array (
							"A" => 0,
							"M" => 0,
							"N" => 0,
							"B" => 0,
							"X" => 0,
							"F" => 0,
							"duplicate" => 0,
							"blocked" => 0,
							"nocontacts" => 0,
							"notattempted" => 0
						);
		$cpcodes = array(
							"A" => "Answered",
							"M" => "Machine",
							"B" => "Busy",
							"N" => "No Answer",
							"X" => "Disconnect",
							"F" => "Failed",
							"duplicate" => "Duplicate",
							"blocked" => "Blocked",
							"nocontacts" => "No Contact Info",
							"notattempted" => "Not Attempted"
						);
		$jobstats["phone"] = $cpstats;
		$remainingcalls=0;
		while ($row = DBGetRow($result)) {
			$jobstats["phone"][$row[1]] += $row[0];
			if ($row[1] != "A" && $row[1] != "M" && $row[1] != "blocked" && $row[1] != "duplicate") {
				$remainingcalls += $row[2];
			}
		}
		$jobstats["phone"]['remainingcalls'] = $remainingcalls;
		$jobstats["phone"]['totalcalls'] = $phonenumberinfo[0];
		$jobnumberlist = implode("", explode("','", $this->params['joblist']));
		$_SESSION['jobstats'][$jobnumberlist] = $jobstats;
		$urloptions = $url . "&valid=$validstamp";	

		// DISPLAY
		startWindow("Filter by");
?>
		<table>
<?
			if(isset($this->params['jobtypes']) && $this->params['jobtypes'] != ""){
				$jobtypes = explode("','", $this->params['jobtypes']);
				$jobtypenames = array();
				foreach($jobtypes as $jobtype){
					$jobtypeobj = new JobType($jobtype);
					$jobtypenames[] = $jobtypeobj->name;
				}
				$jobtypenames = implode(", ",$jobtypenames);
?>
				<tr><td>Job Type: <?=$jobtypenames?></td></tr>
<?
			}
?>
			</table>
		<? 
		endWindow();
		
		?><br><?
		
		displayJobSummary($this->params['joblist']);	
		?><br><?
		startWindow("Totals", "padding: 3px;");
?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
<?
				if($emailinfo[0] > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=email"/a>Email:</a></th>
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
											<th>No Contact Info</th>
										</tr>
										<tr>
											<td><?=$emailinfo[0]?></td>
											<td><a href="reportjobdetails.php?result=sent"/><?=$emailinfo[1]?></a></td>
											<td><a href="reportjobdetails.php?result=unsent"/><?=$emailinfo[2]?><a></td>
											<td><?=$emailinfo[3]?></td>
											<td><?=$emailinfo[4]?></td>
										</tr>
									</table>
								</td>
							</tr>						
						</table>
					</td>
				</tr>
<?
				}
				if($phonenumberinfo[0] > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=phone"/a>Phone:</a></th>
					<td class ="bottomBorder">	
						<table width="100%">
							<tr>
								<td>
									<table  border="0" cellpadding="2" cellspacing="1" class="list" width="100%">
										<tr class="listHeader" align="left" valign="bottom">
											<th># of Phones</th>
											<th>Completed</th>
											<th>Remaining</th>
											<th>Total Attempts</th>
											<th>Duplicates Removed</th>
											<th>Blocked</th>
											<th>No Contact Info</th>
										</tr>
										<tr>
											<td><?=$phonenumberinfo[0]?></td>
											<td><?=$phonenumberinfo[1]?></td>
											<td><?=$phonenumberinfo[2]?></td>
											<td><?=$phonenumberinfo[3]?></td>
											<td><?=$phonenumberinfo[4]?></td>
											<td><?=$phonenumberinfo[5]?></td>
											<td><?=$phonenumberinfo[6]?></td>
											
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
			?>
										<tr>
											<td><div class="floatingreportdata"><u><a href="reportjobdetails.php?result=<?=$index?>"/><?=$value?></a><u></div></td>
											<td><?=$jobstats["phone"][$index]?></td>
										</tr>
			<?
										}
			?>
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
?>
			</table>
		<?
		endWindow();				
		
	
	}
	
	function setReportFile(){
		$this->reportfile = "jobsummaryreport.jasper";
	}
	
	function getReportSpecificParams(){
		
		$params = array("jobId" => $this->params['joblist']);
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