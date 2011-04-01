<?

class SurveyReport extends ReportGenerator{

	function generateQuery(){
		$instance = $this->reportinstance;
		$params = $this->params = $instance->getParameters();
		$this->reporttype = $params['reporttype'];
		$orders = array("order1", "order2", "order3");
		$orderquery = "";
		foreach($orders as $order){
			if(!isset($params[$order]))
				continue;
			$orderby = $params[$order];
			if($orderby == "") continue;
			if($orderquery == ""){
				$orderquery = " order by ";
			} else {
				$orderquery .= ", ";
			}
			$orderquery .= $orderby;
		}
		$rulesql = "";
		
		$jobid = $params['jobid'];
		if($jobid != ""){
			$this->query = "select 
						sr.questionnumber as questionnumber, 
						sr.answer as answer, 
						sr.tally as tally, 
						sq.reportlabel as questionname,
						sr.jobid as jobid
						from surveyresponse sr
						inner join job j on (sr.jobid = j.id)
						left join surveyquestion sq on (sr.questionnumber = sq.questionnumber and sq.questionnaireid = j.questionnaireid) 
						left join messagepart mp on (mp.messageid = sq.phonemessageid)
						where sr.jobid= '$jobid'
						order by questionnumber, answer";
		}
	}
	
	function runHtml(){
		//////////////////////////////////////
		// Processing
		//////////////////////////////////////
		
		$jobid = $this->params['jobid'];
		if(!$jobid){
			error_log("Ran survey report without a jobid");
			exit();
		}
		$job = new Job($jobid);
		$questionnaire = new SurveyQuestionnaire($job->questionnaireid);
		$validstamp = time();
		$jobstats = array ("validstamp" => $validstamp);
		$jobstats['survey'] = array();
		$jobstats['phone'] = array();
		$jobstats['email'] = array();
		
		$query = "select sum(rp.status='success' and rc.result='A' and rc.participated=1), 
			sum(rp.status not in ('duplicate', 'blocked', 'nocontacts'))
			from reportperson rp 
			left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			where rp.jobid='$jobid' and rp.type ='phone'";
			
		$result = QuickQueryRow($query, false, $this->_readonlyDB);
		$phoneparticipants = 0;
		$contacted = 0;
		if($result[0] != 0){
			$phoneparticipants = $result[0];
			$contacted = $result[1];
		}
		$jobstats["survey"]["phoneparticipants"] = $phoneparticipants;
		$jobstats["phone"]["contacted"] = $contacted;
		
		$urloptions = "jobid=$jobid&valid=$validstamp";

		$query = "select sum(sw.status = 'web' and rp.status != 'nocontacts'),
				sum(rp.status not in ('duplicate', 'blocked', 'nocontacts'))
				from surveyweb sw
				inner join reportperson rp on (rp.personid = sw.personid and rp.jobid = sw.jobid)
				where sw.jobid=$jobid";
	
		$result = QuickQueryRow($query, false, $this->_readonlyDB);
		$sentemails=0;
		$emailparticipants = 0;
		if($result[0] != 0){
			$emailparticipants = $result[0];
			$sentemails = $result[1];
		}
		$jobstats["survey"]["emailparticipants"] = $emailparticipants;
		$jobstats["email"]["sentemails"] = $sentemails;
		$query = $this->query;
		
		$res = Query($query, $this->_readonlyDB);
	
		$questions = array();
		$questiontext = array();
		while ($row = DBGetRow($res)) {
			$questions[$row[0]]['answers'][$row[1]] = $row[2];
			$questions[$row[0]]['label'] = $row[3] != NULL ? $row[3] : ("Question " . ($row[0] + 1));
		}
	
		
		$jobstats['survey']['phoneparticipants'] = $phoneparticipants;
		$jobstats['survey']['emailparticipants'] = $emailparticipants;
		$jobstats['survey']['questions'] = $questions;
		$_SESSION['jobstats'][$jobid] = $jobstats;
		
		$titles = array(0 => "");
		for ($x = 1; $x <= 9; $x++)
			$titles[$x+2] = " #$x";
		$titles[] = "Total";
		
		$data = array();
		foreach ($jobstats['survey']['questions'] as $index => $question) {
			$line = array_fill(0,12,"");
			foreach ($question['answers'] as $answer => $tally) {
				$line[$answer+2] = $tally;
			}
			$line[12] = array_sum($line);
			foreach($question['answers'] as $answer => $tally) {
				$line[$answer+14] = (round(($tally / $line[12]), 3) * 100) . "%";
			}
			$line[0] = $index+1;
			$line[1] = nl2br($question['label']);
			$line[14] = $validstamp;
			$data[] = $line;
		}
		

		//////////////////////////////////////
		// DISPLAY
		//////////////////////////////////////
		
		echo "<br>";

		startWindow("Survey Results", NULL, false);
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Participation</th>
					<td class="bottomBorder">
						<table border="0" cellpadding="3" cellspacing="0" width="100%">
							<tr>
								<td>
					<? 
						$noparticipants=true;
						if ($jobstats['survey']['phoneparticipants'] ){ 
							$noparticipants=false;
					?>
									<div><img src="graph_survey_phone_participation.png.php?<?= $urloptions ?>"></div>
					<? 
						}
						
						if ($jobstats['survey']['emailparticipants'] ){ 
							$noparticipants=false;
					?>
									<div style="float: left"><img src="graph_survey_email_participation.png.php?<?= $urloptions ?>"></div>
					<? 
						}
						if($noparticipants){
							?>No one has yet participated in this survey.<?
						}
					?>
					
					
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Response Summary</th>
					<td class="bottomBorder">
						<div style="float: left">
						<table width="100%" cellpadding="3" cellspacing="1" class="list">
<?										
							showtable($data,$titles,array(0 => "fmt_questionnumber"));
?>						
						</table>
						</div>
					</td>
				
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Response Details</th>
					<td class="bottomBorder">
						<table width="100%" cellpadding="3" cellspacing="1">
			<?
						$alt=0;
						foreach($data as $line){
						
							
							?>
							<tr>
								
								<td>
								<div style="float:left">
									<table>
										<tr><td valign="top"><div style='font-weight:bold;'><?=fmt_questionnumber($line,0)?>:</div></td></tr>
										<tr>
											<td width="400px"><?=fmt_question($line,1)?></td>
										</tr>
										<tr>
												<td>
													<table width="400px" cellpadding="3" cellspacing="1" class="list">
														<tr>
															<th align="left" class="listheader nosort" width='10%'>1</th>
															<th align="left" class="listheader nosort" width='10%'>2</th>
															<th align="left" class="listheader nosort" width='10%'>3</th>
															<th align="left" class="listheader nosort" width='10%'>4</th>
															<th align="left" class="listheader nosort" width='10%'>5</th>
															<th align="left" class="listheader nosort" width='10%'>6</th>
															<th align="left" class="listheader nosort" width='10%'>7</th>
															<th align="left" class="listheader nosort" width='10%'>8</th>
															<th align="left" class="listheader nosort" width='10%'>9</th>
															<th align="left" class="listheader nosort" width='10%'>Total</th>
														</tr>
														<tr>
							<?
														
															for($i = 3; $i<12; $i++){
																?><td><?=fmt_answer($line, $i)?></td><?
															}
							?>
															<td><?=$line[12]?></td>
														</tr>
													</table>
												</td>
											</tr>
									</table>
								</div>
								<div style="float: left"><?=fmt_survey_graph($line, 13)?></div>
								</td>
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
	
	function writeSurveyLine($reportarray) {
		echo '"' . implode('","', $reportarray) . '"' . "\r\n";
	}

	function runCSV(){
	//For csv data, give them call details
		$options = $this->params;
		$jobid = $options['jobid'];
		$query = "select SQL_CALC_FOUND_ROWS
			j.name as jobname,
			u.login,
			rp.personid,
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type as jobtype,
			rc.resultdata,
			sw.resultdata
			from reportperson rp
			inner join job j on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
			left join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
			where rp.jobid = '$jobid' order by rp.pkey, rp." . FieldMap::GetLastNameField() . ", rp.personid";
			
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		$job = new Job($jobid);
		$maxquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid", $this->_readonlyDB);

		//generate the CSV header
		$header = '"Job Name","Submitted by","ID","First Name","Last Name"';

		for ($x = 1; $x <= $maxquestions; $x++) {
			$header .= ',"Question '. $x .'"';
		}

		echo $header;
		echo "\r\n";

		$result = Query($query, $this->_readonlyDB);

		$currentPersonid = 0;
		$isPersonWritten = false;
		while ($row = DBGetRow($result)) {
			// if next person
			if ($currentPersonid != $row[2]) {
				// if person not written
				if (!$isPersonWritten && $currentPersonid != 0) {
					$this->writeSurveyLine($reportarray);
				}
				// reset variables for new person
				$currentPersonid = $row[2];
				$isPersonWritten = false;
			}

			// fill first columns of row with basic person info
			$reportarray = array($row[0], $row[1], $row[3], $row[4], $row[5]);

			//fill in survey result data, be sure to fill in an array element for all questions, even if blank
			$startindex = count($reportarray);

			$questiondata = array();
			if ($row[6] == "phone")
				parse_str($row[7],$questiondata);
			else if ($row[6] == "email")
				parse_str($row[8],$questiondata);

			//add data to the report for each question
			$found = false;
			for ($x = 0; $x < $maxquestions; $x++) {
				if (isset($questiondata["q$x"])) {
					$found = true;
					$reportarray[$startindex + $x] = $questiondata["q$x"];
				} else {
					$reportarray[$startindex + $x] = "";
				}
			}
			// if one or more answers found, then write results for this person
			// NOTE only the first result found for each person will be reported - if any other results for other phone/email they are discarded (rare case)
			if ($found && !$isPersonWritten) {
				$this->writeSurveyLine($reportarray);
				$isPersonWritten = true;
			}
		}
		if (!$isPersonWritten && $currentPersonid != 0) {
			$this->writeSurveyLine($reportarray);
		}
	}
	
	function setReportFile(){
		$this->reportfile = "survey.jasper";
	}
	
	

	function getReportSpecificParams(){
		$params = array("jobId" => $this->params['jobid'],
						"jobcount" => 1);
		return $params;
	}

}


?>