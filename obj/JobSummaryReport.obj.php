<?

class JobSummaryReport extends ReportGenerator{
	
	function generateQuery(){
		$this->params = $this->reportinstance->getParameters();
	}
	
	function runHtml($params = null){
	
		$validstamp = time();
		$jobstats = array ("validstamp" => $validstamp);
		$params = $this->params;
		
		// Gather Job information
		$datestart = "";
		$dateend = "";
		$reldatequery = "";
		if(isset($params['jobid'])){
			$joblist = array($params['jobid']);
		} else {
			$reldate = $params['reldate'];
			if($reldate != ""){
				switch($reldate){
					case 'today':
						$targetdate = QuickQuery("select curdate()");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
						
						break;
					
					case 'weekday':
						//1 = Sunday, 2 = Monday, ..., 7 = Saturday
						$dow = QuickQuery("select dayofweek(curdate())");
	
						//normally go back 1 day
						$daydiff = 1;
						//if it is sunday, go back 2 days
						if ($dow == 1)
							$daydiff = 2;
						//if it is monday, go back 3 days
						if ($dow == 2)
							$daydiff = 3;
	
						$targetdate = QuickQuery("select date_sub(curdate(),interval $daydiff day)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
						
						break;
					case 'yesterday':
						$targetdate = QuickQuery("select date_sub(curdate(),interval 1 day)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$targetdate',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
						
						break;
					case 'xdays':
						$lastxdays = $params['lastxdays'];
						if($lastxdays == "")
							$lastxdays = 1;
						$today = QuickQuery("select curdate()");
						$targetdate = QuickQuery("select date_sub(curdate(),interval $lastxdays day)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
						
						break;
					case 'daterange':
						
						$datestart = strtotime($params['startdate']);
						$dateend = strtotime($params['enddate']);
						$reldatequery = "and ( (j.startdate >= from_unixtime('$datestart') and j.startdate < date_add(from_unixtime('$dateend'),interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= from_unixtime('$datestart') and j.startdate <= date_add(from_unixtime('$dateend'),interval 1 day) ";
						break;
					case 'weektodate':
						$today = QuickQuery("select curdate()");
						$targetdate = QuickQuery("select date_sub(curdate(), interval 1 week)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
						break;
					case 'monthtodate':
						$today = QuickQuery("select curdate()");
						$targetdate = QuickQuery("select date_sub(curdate(), interval 1 month)");
						$reldatequery = "and ( (j.startdate >= '$targetdate' and j.startdate < date_add('$today',interval 1 day) )
											or j.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$today',interval 1 day) ";
						break;
				}
			}
		
			$joblist = QuickQueryList("select j.id from job j where 1 $reldatequery");
		}
		$joblist = implode("','", $joblist);
		
		$jobinfoquery = "Select u.login, 
								j.name, 
								j.description,
								coalesce(m.name, sq.name),
								count(distinct rp.personid),
								count(rc.personid)
								from reportperson rp
								left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
								inner join job j on (rp.jobid = j.id)
								left join message m on (rp.messageid = m.id)
								left join surveyquestionnaire sq on (j.questionnaireid = sq.id)
								inner join user u on (rp.userid = u.id)
								where rp.jobid in ('$joblist')
								group by m.id, sq.id";
		$jobinforesult = Query($jobinfoquery);
		$jobinfo = array();
		while($row = DBGetRow($jobinforesult)){
			$jobinfo[] = $row;
		}
		
		//Gather Phone Information
		$phonecontactquery = "select count(rp.personid),
								sum(rp.status = 'success'),
								sum(rp.status not in ('success', 'duplicate', 'nocontacts', 'blocked')),
								sum(rp.status = 'duplicate'),
								sum(rp.status = 'nocontacts'),
								sum(rp.status = 'blocked')
								from reportperson rp
								where rp.jobid in ('$joblist')
								and rp.type='phone'";
		$phonecontactinfo = QuickQueryRow($phonecontactquery);
								
		$phonenumberquery = "select sum(rc.jobid in ('$joblist') and rc.result not in ('duplicate', 'blocked')) as total,
									sum(rc.result in ('A','M', 'duplicate', 'blocked')) as completed,
									sum(rc.result not in ('A','M', 'duplicate', 'blocked') and rc.numattempts < js.value) as remaining
									from reportcontact rc
									left join jobsetting js on (js.jobid = rc.jobid and js.name = 'maxcallattempts')
									where rc.jobid in ('$joblist')
									and rc.type='phone'";
		$phonenumberinfo = QuickQueryRow($phonenumberquery);
		
		$emailcontactquery = "select count(rp.personid),
								sum(rp.status = 'success'),
								sum(rp.status not in ('success', 'duplicate', 'nocontacts')),
								sum(rp.status = 'duplicate'),
								sum(rp.status = 'nocontacts')
								from reportperson rp
								where rp.jobid in ('$joblist')
								and rp.type='email'";
		$emailcontactinfo = QuickQueryRow($emailcontactquery);
								
		$emailquery = "select sum(rc.jobid in ('$joblist') and rc.result not in ('duplicate')) as total,
									sum(rc.result = 'sent') as completed,
									sum(rc.result  = 'unsent') as remaining
									from reportcontact rc
									where rc.jobid in ('$joblist')
									and rc.type='email'";
		$emailinfo = QuickQueryRow($emailquery);

		$query = "select count(*) as cnt, rc.result, sum(rc.result not in ('A','M') and rc.numattempts < js.value) as remaining
					from reportcontact rc
					left join jobsetting js on (js.jobid = rc.jobid and js.name = 'maxcallattempts')
					where rc.jobid in ('$joblist')
					and rc.type='phone'
					group by rc.result";
			
					
		//may need to clean up, null means not called yet
		//do math for the % completed
		
		$result = Query($query);
		$cpstats = array (
								"C" => 0,
								"A" => 0,
								"M" => 0,
								"N" => 0,
								"B" => 0,
								"X" => 0,
								"F" => 0,
								"nullcp" => 0
							);
		$jobstats["phone"] = $cpstats;
		$remainingcalls=0;
		while ($row = DBGetRow($result)) {
			$index = ( ($row[1] !== NULL) && ($row[1] !== 'notattempted') && ($row[1] !== "blocked") && ($row[1] !== "duplicate") ) ? $row[1] : "nullcp";
			$jobstats["phone"][$index] += $row[0];
			if ($row[1] != "A" && $row[1] != "M") {
				$remainingcalls += $row[2];
			}
		}
		$jobstats["phone"]['remainingcalls'] = $remainingcalls;
		$jobstats["phone"]['totalcalls'] = $phonenumberinfo[0];
		$jobnumberlist = implode("", explode("','", $joblist));
		$_SESSION['jobstats'][$jobnumberlist] = $jobstats;

		$urloptions = "jobid=$jobnumberlist&valid=$validstamp";

		startWindow("Summary");
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr valign="top">
					<th align="right" class="windowRowHeader bottomBorder">Job Info:</th>
					<td class ="bottomBorder">
						<table border="1" cellpadding="2" cellspacing="1" class="list">
							<tr class="listHeader" align="left" valign="bottom">
								<th>User</th>
								<th>Job</th>
								<th>Description</th>
								<th>Message</th>
								<th>People to Contact</th>
								<th>Total Destinations</th>
							</tr>
							<?
							foreach($jobinfo as $job){
								//if there is no message, then it is a no contact
								if($job[3]){
									?><tr><?
										foreach($job as $data){
											if($data == null)
												$data = "&nbsp";
											?><td><?=$data?></td><?
										}
									?></tr><?
								}
							}
							?>
						</table>
					</td>
				</tr>
<?
				if($phonecontactinfo[0] > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=phone"/a>Phone Details:</a></th>
					<td class ="bottomBorder">	
						<table>
							<tr>
								<td>
									<table border="1" cellpadding="2" cellspacing="1" class="list">
										<tr>
											<td colspan="5">People to Contact: <?=$phonecontactinfo[0]?></td>
										</tr>
										<tr class="listHeader" align="left" valign="bottom">
											<th>Contacted</th>
											<th>Not Contacted</th>
											<th>Duplicates</th>
											<th>Blocked</th>
											<th>No Contact Info</th>
										</tr>
										<tr>
											<td><?=$phonecontactinfo[1]?></td>
											<td><?=$phonecontactinfo[2]?></td>
											<td><?=$phonecontactinfo[3]?></td>
											<td><?=$phonecontactinfo[4]?></td>
											<td><?=$phonecontactinfo[5]?></td>
										</tr>
									</table>
								</td>
								<td>
									<table  border="1" cellpadding="2" cellspacing="1" class="list">
										<tr>
											<td colspan="2">Numbers to Call: <?=$phonenumberinfo[0]?></td>
										</tr>
										<tr class="listHeader" align="left" valign="bottom">
											<th>Completed</th>
											<th>Remaining</th>
										</tr>
										<tr>
											<td><?=$phonenumberinfo[1]?></td>
											<td><?=$phonenumberinfo[2]?></td>
										</tr>
									</table>
								</td>
							</tr>						
						</table>
					</td>
				</tr>
<?
				}
				if($emailcontactinfo[0] > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder"><a href="reportjobdetails.php?type=email"/a>Email</a></th>
					<td class="bottomBorder">	
						<table>
							<tr>
								<td>
									<table border="1" cellpadding="2" cellspacing="1" class="list">
										<tr>
											<td colspan="5">People to Contact: <?=$emailcontactinfo[0]?></td>
										</tr>
										<tr class="listHeader" align="left" valign="bottom">
											<th>Contacted</th>
											<th>Not Contacted</th>
											<th>Duplicates</th>
											<th>No Contact Info</th>
										</tr>
										<tr>
											<td><?=$emailcontactinfo[1]?></td>
											<td><?=$emailcontactinfo[2]?></td>
											<td><?=$emailcontactinfo[3]?></td>
											<td><?=$emailcontactinfo[4]?></td>
										</tr>
									</table>
								</td>
								<td>
									<table  border="1" cellpadding="2" cellspacing="1" class="list">
										<tr>
											<td colspan="2">Email Destinations: <?=$emailinfo[0]?></td>
										</tr>
										<tr class="listHeader" align="left" valign="bottom">
											<th>Completed</th>
											<th>Remaining</th>
										</tr>
										<tr>
											<td><?=$emailinfo[1]?></td>
											<td><?=$emailinfo[2]?></td>
										</tr>
									</table>
								</td>
							</tr>						
						</table>
					</td>
				</tr>
<?
				}
				if($phonecontactinfo[0] > 0){
?>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder">Phone Details:</th>
					<td class="bottomBorder"><img src="graph_detail_callprogress.png.php?<?= $urloptions ?>"></td>
				</tr>			
<?
				}
?>
			</table>
		<?
		endWindow();				
		
	
	}
	
	function setReportFile(){
		$this->reportfile = "jobreport.jasper";
	}
	
	function getReportSpecificParams($params){
		$params['jobId'] = new XML_RPC_VALUE($this->params['jobid'], "string");
		return $params;
	}

	static function getOrdering(){
		global $USER;
		$fields = getFieldMaps();
		$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
		$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");
	
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