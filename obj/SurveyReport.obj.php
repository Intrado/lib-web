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
		if ($jobid) {
			$job = new Job($jobid);
			
			//TODO check if there are new workitems, then display a pie chart with % queued
			if (QuickQuery("select personid from reportperson where status='new' and jobid='$jobid' limit 1")) {
				$isprocessing = true;
			} else {
				$isprocessing = false;
		
				$validstamp = time();
				$jobstats = array ("validstamp" => $validstamp);
				$jobtypes = explode(",",$job->type);
		
				//--------------- SURVEY ---------------
				if(in_array("survey",$jobtypes) !== false) {
		
					$questionnaire = new SurveyQuestionnaire($job->questionnaireid);
					if ($questionnaire->hasphone)
						$jobtypes[] = "phone";
					if ($questionnaire->hasweb)
						$jobtypes[] = "email";
		
				}
				
				//--------------- PHONE ---------------
				if(in_array("phone",$jobtypes) !== false) {
					//people, dupes, contacted, notcontacted, %complete (actually from phone)
		
					$query = "select count(*) as cnt, rp.status
								from reportperson rp
								where rp.jobid='$jobid' and rp.type='phone' group by rp.status";
					//then need to stitch the results back together by summing them.
		
					$totalpeople = 0;
					$duplicates = 0;
					$contacted = 0;
					$notcontacted = 0;
					$result = Query($query);
					while ($row = DBGetRow($result)) {
						$totalpeople += $row[0];
		
						if ($row[1] == "success")
							$contacted += $row[0];
						else if ($row[1] == "duplicate")
							$duplicates += $row[0];
						else
							$notcontacted += $row[0];
					}
		
					//phones by cp
					$maxcallattempts = QuickQuery("select value from jobsetting where name='maxcallattempts' and jobid = '$jobid'");
					$query = "select count(*) as cnt, rc.result, sum(rp.status not in ('success','fail') and rc.numattempts < $maxcallattempts) as remaining
								from reportperson rp 
								left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					where rp.jobid = '$jobid'
					and rp.status != 'duplicate' and rp.type='phone'
					group by rc.result";
					//may need to clean up, null means not called yet
					//do math for the % completed
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
					$remainingcalls = 0;
					$totalcalls = 0;
					$result = Query($query);
					while ($row = DBGetRow($result)) {
						$totalcalls += $row[0];
						$index = $row[1] !== NULL ? $row[1] : "nullcp";
						$cpstats[$index] += $row[0];
						if ($row[1] != "A" && $row[1] != "M") {
							$remainingcalls += $row[2];
						}
					}
		
					$jobstats["phone"] = $cpstats; //start with the cp codes
					//add people stats
					$jobstats["phone"]["totalpeople"] = $totalpeople;
					$jobstats["phone"]["duplicates"] = $duplicates;
					$jobstats["phone"]["contacted"] = $contacted;
					$jobstats["phone"]["notcontacted"] = $notcontacted;
		
					$jobstats["phone"]["remainingcalls"] = $remainingcalls;
					$jobstats["phone"]["totalcalls"] = $totalcalls;
					$jobstats["phone"]["percentcomplete"] = $totalcalls ? ($totalcalls - $remainingcalls)/$totalcalls : 0;
		
				}
				//-------------------------------------
		
				//--------------- EMAIL ---------------
				if(in_array("email",$jobtypes) !== false) {
					//email people, emails, % sent
					$query = "select count(*)
								from reportperson rp
								where rp.jobid='$jobid' and rp.type='email'";
		
					$emailpeople = QuickQuery($query);
		
					$query = "select count(*) totalemails, sum(rc.numattempts>0) as sent
								from reportperson rp 
								left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
								where rp.jobid='$jobid' and rp.type='email'";
					list($totalemails, $sentemails) = QuickQueryRow($query);
		
					$jobstats["email"] = array();
					$jobstats["email"]["emailpeople"] = $emailpeople;
					$jobstats["email"]["totalemails"] = $totalemails;
					$jobstats["email"]["sentemails"] = $sentemails;
					$jobstats["email"]["percentsent"] = $sentemails/$totalemails;
		
		
				}
				//-------------------------------------
		
				//--------------- PRINT ---------------
				if(in_array("print",$jobtypes) !== false) {
					//print people %sent
					$query = "select count(*) as totoal, sum(rp.status='success') as printed
								from reportperson rp
								where rp.jobid='$jobid' and rp.type='print'";
					list($totalprint, $printed) = QuickQueryRow($query);
		
					$jobstats["print"] = array();
					$jobstats["print"]["totalprint"] = $totalprint;
					$jobstats["print"]["printed"] = $printed;
				}
				//-------------------------------------
		
				//save all these stats to the session with a jobid and timestamp so we can use them in the pie charts
				$_SESSION['jobstats'][$jobid] = $jobstats;
				$urloptions = "jobid=$jobid&valid=$validstamp";
			}
		}

		$jobstats = $_SESSION['jobstats'][$jobid];
		$validstamp = $jobstats['validstamp']; 
		$urloptions = "jobid=$jobid&valid=$validstamp";
		//% people participated (subset of contacted)
		$query = "select count(*) as cnt
					from reportperson rp 
					left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					where rp.jobid='$jobid'
					and rp.status='success' and rc.result='A' and rc.participated=1";
		$phoneparticipants = QuickQuery($query);
	
		//TODO: fix jobworkitem related code
		$query = "select count(*) from surveyweb sw
				inner join reportperson rp on (rp.personid = sw.personid and rp.jobid = sw.jobid)
				where sw.status='web' and sw.jobid=$jobid";
	
		$emailparticipants = QuickQuery($query);
	
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
	
		$jobstats['survey'] = array();
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
					<? if ($jobstats['survey']['phoneparticipants'] || $jobstats['survey']['emailparticipants']) { ?>
									<img src="graph_survey_participation.png.php?<?= $urloptions ?>">
					<? } else { ?>
									No one has yet participated in this survey.
					<? } ?>
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
		$this->reportfile = "surveyreport.jasper";
	}

}


?>