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
						coalesce(mp.txt, sq.webmessage) as questiontext,
						sr.jobid as jobid
						from surveyresponse sr
						inner join job j on (sr.jobid = j.id)
						left join surveyquestion sq on (sr.questionnumber = sq.questionnumber and sq.questionnaireid = j.questionnaireid) 
						left join messagepart mp on (mp.messageid = sq.phonemessageid)
						where sr.jobid= '$jobid'
						order by questionnumber, answer";
		}
	}
	
	function runHtml($params=null){
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
		
		$query = "select sum(rp.status='success' and rc.result='A' and rc.participated=1), count(*) as cnt
			from reportperson rp 
			left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			where rp.jobid='$jobid'";
			
		$result = QuickQueryRow($query);
		$phoneparticipants = 0;
		$contacted = 0;
		if($result[0] != 0){
			$phoneparticipants = $result[0];
			$contacted = $result[1];
		}
		$jobstats["survey"]["phoneparticipants"] = $phoneparticipants;
		$jobstats["phone"]["contacted"] = $contacted;
		
		$urloptions = "jobid=$jobid&valid=$validstamp";

		$query = "select count(*) from surveyweb sw
				inner join reportperson rp on (rp.personid = sw.personid and rp.jobid = sw.jobid)
				where sw.status='web' and sw.jobid=$jobid";
	
		$result = QuickQueryRow($query);
		$sentemails=0;
		$emailparticipants = 0;
		if($result[0] != 0){
			$emailparticipants = $result[0];
			$sentemails = $result[1];
		}
		$jobstats["survey"]["emailparticipants"] = $emailparticipants;
		$jobstats["email"]["sentemails"] = $sentemails;
		$query = $this->query;
		
		$res = Query($query);
		echo mysql_error();
	
		$questions = array();
		$questiontext = array();
		while ($row = DBGetRow($res)) {
			$questions[$row[0]]['answers'][$row[1]] = $row[2];
			$questions[$row[0]]['label'] = $row[3] != NULL ? $row[3] : ("Question " . ($row[0] + 1));
			$questiontext[$row[0]] = $row[4] != NULL ? $row[4] : "";
		}
	
		
		$jobstats['survey']['phoneparticipants'] = $phoneparticipants;
		$jobstats['survey']['emailparticipants'] = $emailparticipants;
		$jobstats['survey']['questions'] = $questions;
		$_SESSION['jobstats'][$jobid] = $jobstats;
		

		//////////////////////////////////////
		// DISPLAY
		//////////////////////////////////////
		
		echo "<br>";

		startWindow("Survey Results", NULL, false);
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
				<tr>
					<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Summary</th>
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
									<div style="float; left"><img src="graph_survey_email_participation.png.php?<?= $urloptions ?>"></div>
					<? 
						}
						if($noparticipants){
							?>No one has yet participated in this survey.<?
						}
					?>
					
					
								</td>
								<td>
									<table width="100%" cellpadding="3" cellspacing="1" class="list">
<?
										$titles = array("No.", "Question");
										for ($x = 1; $x <= 9; $x++)
											$titles[$x+2] = " #$x";
										$titles[] = "Total";
										
										$data = array();
										foreach ($jobstats['survey']['questions'] as $index => $question) {
											$line = array_fill(1,11,"");
											foreach ($question['answers'] as $answer => $tally) {
												$line[$answer+2] = $tally;
											}
											$line[12] = array_sum($line);
											foreach($question['answers'] as $answer => $tally) {
												$line[$answer+14] = (round(($tally / $line[12]), 3) * 100) . "%";
											}
											$line[0] = $index+1;
											$line[1] = $question['label'];
											$line[2] = $questiontext[$index];
											$line[14] = $validstamp;
											$data[] = $line;
										}
										
										$formatters = array();
							
										showtable($data,$titles,$formatters);
?>						
									</table>
								</td>
							</tr>
						</table>
					</td>
				</tr>
				<tr>
					<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">Responses</th>
					<td class="bottomBorder">
						<table width="100%" cellpadding="3" cellspacing="1">
			<?
						
						foreach ($jobstats['survey']['questions'] as $index => $question) {
							$line = array_fill(1,11,"");
							foreach ($question['answers'] as $answer => $tally) {
								$line[$answer+2] = $tally;
							}
							$line[12] = array_sum($line);
							foreach($question['answers'] as $answer => $tally) {
								$line[$answer+14] = (round(($tally / $line[12]), 3) * 100) . "%";
							}
							$line[0] = $index+1;
							$line[1] = $question['label'];
							$line[2] = $questiontext[$index];
							$line[14] = $validstamp;
							$data[] = $line;
						}


						$alt=0;
						foreach($data as $line){
						
							
							?>
							<tr>
								
								<td>
									<table>
										<tr><td valign="top"><div style='font-weight:bold; text-decoration: underline'>Question <?=$line[0]?>:</div></td></tr>
										<tr>
											<td><?=fmt_question($line,1)?></td>
										</tr>
										<tr>
												<td>
													<table width="100%" cellpadding="3" cellspacing="1" class="list">
														<tr>
															<th align="left" class="listheader nosort">1</th>
															<th align="left" class="listheader nosort">2</th>
															<th align="left" class="listheader nosort">3</th>
															<th align="left" class="listheader nosort">4</th>
															<th align="left" class="listheader nosort">5</th>
															<th align="left" class="listheader nosort">6</th>
															<th align="left" class="listheader nosort">7</th>
															<th align="left" class="listheader nosort">8</th>
															<th align="left" class="listheader nosort">9</th>
															<th align="left" class="listheader nosort">Total</th>
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
								</td>
								<td><?=fmt_survey_graph($line, 13)?></td>
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

	function setReportFile(){
		$this->reportfile = "Survey.jasper";
	}

	function getReportSpecificParams($params){
		$params['jobid'] = new XML_RPC_VALUE($this->params['jobid'], "string");
		return $params;
	}

}


?>