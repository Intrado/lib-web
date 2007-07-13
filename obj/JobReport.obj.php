<?

class JobReport extends ReportGenerator{
	
	function generateQuery(){
		$USER = new User($this->userid);
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
		if($orderquery == ""){
			$orderquery = " order by rp.pkey ";
		}
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
					$rulesql .= " " . $newrule->toSql("rp");
				}
			}
		}
		if(isset($params['jobid'])){
			$jobid = DBSafe($params['jobid']);
		} else {
			if(isset($params['datestart']))
				$datestart = date("Y-m-d", strtotime($params['datestart']));
			else
				$datestart = date("Y-m-d", strtotime("today"));
			if(isset($params['dateend']))
				$dateend = date("Y-m-d", strtotime($params['dateend']));
			else
				$dateend = date("Y-m-d", strtotime("now"));
			$joblist = QuickQueryList("select j.id from job j where j.startdate < '$dateend' and (j.finishdate > '$datestart' or j.enddate > '$datestart')");
		}
		$resultquery = "";
		if(isset($params['result']) && $params['result']){
			$resultquery = " and rc.result = '" . $params['result'] . "' ";
		}
		
		$searchquery = isset($jobid) ? " and rp.jobid='$jobid'" : " and rp.jobid in ('" . implode("','", $joblist) ."')";
		$searchquery .= $resultquery;
		$usersql = $USER->userSQL("rp");
		$fields = $instance->getFields();
		$fieldquery = generateFields("rp");
		$this->query = 
			"select SQL_CALC_FOUND_ROWS
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type,
			coalesce(m.name, sq.name),
			coalesce(rc.phone,
						rc.email,
						concat(
							coalesce(rc.addr1,''), ' ',
							coalesce(rc.addr2,''), ' ',
							coalesce(rc.city,''), ' ',
							coalesce(rc.state,''), ' ',
							coalesce(rc.zip,''))
					) as destination,
			rc.numattempts,
			from_unixtime(rc.starttime/1000) as date,
			coalesce(rc.result,
					rp.status) as result,
			rp.status,
			u.login,
			rp.type as jobtype,
			j.name as jobname,
			rc.numattempts as attempts,
			rc.resultdata,
			sw.resultdata
			$fieldquery
			from reportperson rp
			inner join job j on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			left join	message m on
							(m.id = rp.messageid)
			left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
			left join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
		
			where 1 
			$searchquery
			
			$usersql
			$rulesql
			$orderquery
			";
	}
	
	function runHtml($params = null){
		
		if($params == "detailed"){
			$this->runDetailedHtml();
		} else {
			$options = $this->params;
			//////////////////////////////////////
			// Processing
			//////////////////////////////////////
			$datestart = "";
			$dateend = "";
			if(isset($options['jobid'])){
				$joblist = array($options['jobid']);
			} else {
				if(isset($options['datestart']))
					$datestart = date("Y-m-d", strtotime($options['datestart']));
				else
					$datestart = date("Y-m-d", strtotime("today"));
				if(isset($options['dateend']))
					$dateend = date("Y-m-d", strtotime($options['dateend']));
				else
					$dateend = date("Y-m-d", strtotime("now"));
				$joblist = QuickQueryList("select j.id from job j where j.startdate < '$dateend' and (j.finishdate > '$datestart' or j.enddate > '$datestart')");
			}
			$validstamp = time();
			$jobstats = array ("validstamp" => $validstamp);
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
			
			
			foreach($joblist as $jobid) {
				if ($jobid) {
					$job = new Job($jobid);
					
					//TODO check if there are new workitems, then display a pie chart with % queued
					if (QuickQuery("select personid from reportperson where status='new' and jobid='$jobid' limit 1")) {
						$isprocessing = true;
					} else {
						$isprocessing = false;
				
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
						
							if(!isset($jobstats["phone"]))
								$jobstats["phone"] = $cpstats; //start with the cp codes
							//people, dupes, contacted, notcontacted, %complete (actually from phone)
				
							$query = "select count(*) as cnt, rp.status
										from reportperson rp
										where rp.jobid='$jobid' and rp.type='phone' group by rp.status";
							//then need to stitch the results back together by summing them.
				
							$totalpeople = 0;
							$duplicates = 0;
							$contacted = 0;
							$notcontacted = 0;
							$nocontacts = 0;
							$result = Query($query);
							while ($row = DBGetRow($result)) {
								$totalpeople += $row[0];
				
								if ($row[1] == "success")
									$contacted += $row[0];
								else if ($row[1] == "duplicate")
									$duplicates += $row[0];
								else if ($row[1] == "nocontacts")
									$nocontacts +=$row[0];
								else
									$notcontacted += $row[0];
							}
				
							//phones by cp
							$maxcallattempts = QuickQuery("select value from jobsetting where name='maxcallattempts' and jobid = '$jobid'");
							$query = "select count(*) as cnt, rc.result, sum(rc.result not in ('A','M') and rc.numattempts < $maxcallattempts) as remaining
										from reportcontact rc
							where rc.jobid = '$jobid'
							and rc.type='phone'
							group by rc.result";
							//may need to clean up, null means not called yet
							//do math for the % completed
							
							$remainingcalls = 0;
							$totalcalls = 0;
							$result = Query($query);
							while ($row = DBGetRow($result)) {
								$totalcalls += $row[0];
								$index = ( ($row[1] !== NULL) && ($row[1] !== 'notattempted') ) ? $row[1] : "nullcp";
								$jobstats["phone"][$index] += $row[0];
								if ($row[1] != "A" && $row[1] != "M") {
									$remainingcalls += $row[2];
								}
							}
				

							//add people stats
							$jobstats["phone"]["totalpeople"] = (isset($jobstats["phone"]["totalpeople"]) ? $jobstats["phone"]["totalpeople"] : 0) + $totalpeople;
							$jobstats["phone"]["duplicates"] = (isset($jobstats["phone"]["duplicates"]) ? $jobstats["phone"]["duplicates"] : 0) + $duplicates;
							$jobstats["phone"]["contacted"] = (isset($jobstats["phone"]["contacted"]) ? $jobstats["phone"]["contacted"] : 0) + $contacted;
							$jobstats["phone"]["notcontacted"] = (isset($jobstats["phone"]["notcontacted"]) ? $jobstats["phone"]["notcontacted"] : 0) + $notcontacted;
							$jobstats["phone"]["nocontacts"] = (isset($jobstats["phone"]["nocontacts"]) ? $jobstats["phone"]["nocontacts"] : 0) + $nocontacts;
							
							$jobstats["phone"]["remainingcalls"] = (isset($jobstats["phone"]["remainingcalls"]) ? $jobstats["phone"]["remainingcalls"] : 0) + $remainingcalls;
							$jobstats["phone"]["totalcalls"] = (isset($jobstats["phone"]["totalcalls"]) ? $jobstats["phone"]["totalcalls"] : 0) + $totalcalls;
							$jobstats["phone"]["percentcomplete"] = $jobstats["phone"]["totalcalls"] ? ($jobstats["phone"]["totalcalls"] - $jobstats["phone"]["remainingcalls"])/$jobstats["phone"]["totalcalls"] : 0;
				
						}
						//-------------------------------------
				
						//--------------- EMAIL ---------------
						if(in_array("email",$jobtypes) !== false) {
							if(!isset($jobstats["email"]))
								$jobstats["email"] = array();
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
				
							$jobstats["email"]["emailpeople"] = (isset($jobstats["email"]["emailpeople"]) ? $jobstats["email"]["emailpeople"] : 0) + $emailpeople;
							$jobstats["email"]["totalemails"] = (isset($jobstats["email"]["totalemails"]) ? $jobstats["email"]["totalemails"] : 0) + $totalemails;
							$jobstats["email"]["sentemails"] = (isset($jobstats["email"]["sentemails"]) ? $jobstats["email"]["sentemails"] : 0) + $sentemails;
							$jobstats["email"]["percentsent"] = $jobstats["email"]["sentemails"]/$jobstats["email"]["totalemails"];
				
				
						}
						//-------------------------------------
				
						//--------------- PRINT ---------------
						if(in_array("print",$jobtypes) !== false) {
							//print people %sent
							if(!isset($jobstats["print"]))
								$jobstats["print"] = array();
							$query = "select count(*) as totoal, sum(rp.status='success') as printed
										from reportperson rp
										where rp.jobid='$jobid' and rp.type='print'";
							list($totalprint, $printed) = QuickQueryRow($query);
				
							
							$jobstats["print"]["totalprint"] = (isset($jobstats["print"]["totalprint"]) ? $jobstats["print"]["totalprint"] : 0) +$totalprint;
							$jobstats["print"]["printed"] = (isset($jobstats["print"]["printed"]) ? $jobstats["print"]["printed"] : 0) +$printed;
						}
						//-------------------------------------
				
						//save all these stats to the session with a jobid and timestamp so we can use them in the pie charts
						$_SESSION['jobstats'][$jobid] = $jobstats;
						$urloptions = "jobid=$jobid&valid=$validstamp";
					}
				}
			}
			
			//////////////////////////////////////
			// DISPLAY
			//////////////////////////////////////
			if (count($joblist) && $isprocessing) {
				startWindow("Report Summary - Processing Job", NULL, false);
			?>
				<div style="padding: 10px;">Please wait while your job is processed...</div>
				<img src="graph_processing.png.php?jobid=<?= $jobid ?>" >
				<meta http-equiv="refresh" content="10;url=reportsummary.php?jobid=<?= $jobid ?>&t=<?= rand() ?>">
			
			<?
				endWindow();
			} else if (count($joblist)) {
				//--------------- Summary ---------------
				startWindow("Report Summary", NULL, false);
			?>
			
				<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<? if (isset($jobstats["phone"])) { ?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
						<td class="bottomBorder"><img src="graph_summary_contacted.png.php?<?= $urloptions ?>"><img src="graph_summary_completed.png.php?<?= $urloptions ?>"></td>
					</tr>
			<? } ?>
			
			<? if (isset($jobstats["email"])) { ?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
						<td class="bottomBorder"><img src="graph_summary_email.png.php?<?= $urloptions ?>"></td>
					</tr>
			<? } ?>
			
			
				</table>
			
			<?
				endWindow();
				
			echo "<br>";	
				//--------------- Detail ---------------
				startWindow("Report Detail", NULL, false);
			?>
				<table border="0" cellpadding="3" cellspacing="0" width="100%">
			<? if (isset($jobstats["phone"])) { ?>
			
				<!--
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone<br>(by People)</th>
						<td class="bottomBorder">
							<div class="floatingreportdata"><u>People</u><br><?= number_format($jobstats["phone"]["totalpeople"]) ?></div>
							<div class="floatingreportdata"><u>Duplicates</u><br><?= number_format($jobstats["phone"]["duplicates"]) ?></div>
							<div class="floatingreportdata"><u>Contacted</u><br><?= number_format($jobstats["phone"]["contacted"]) ?></div>
							<div class="floatingreportdata"><u>Not Contacted</u><br><?= number_format($jobstats["phone"]["notcontacted"]) ?></div>
							<div class="floatingreportdata"><u>Complete</u><br><?= sprintf("%0.2f%%",100 * $jobstats["phone"]["percentcomplete"]) ?></div>
						</td>
						<td class="bottomBorder" align="left"><img src="graph_summary_contacted.png.php?<?= $urloptions ?>"></td>
					</tr>
				-->
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Phone:</th>
						<td class="bottomBorder">
							<table>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=A">Answered</a></u></td><td><?= number_format($jobstats["phone"]["A"]) ?></div>
							</td></tr>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=M">Machine</a></u></td><td><?= number_format($jobstats["phone"]["M"]) ?></div>
							</td></tr>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=N">No Answer</a></u></td><td><?= number_format($jobstats["phone"]["N"]) ?></div>
							</td></tr>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=B">Busy</a></u></td><td><?= number_format($jobstats["phone"]["B"]) ?></div>
							</td></tr>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=X">Disconnect</a></u></td><td><?= number_format($jobstats["phone"]["X"]) ?></div>
							</td></tr>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=F">Fail</a></u></td><td><?= number_format($jobstats["phone"]["F"]) ?></div>
							</td></tr>
							<tr><td>
								<div class="floatingreportdata"><u><a href="reportjobdetails.php?result=notattempted">Not Attempted</a></u></td><td><?= number_format($jobstats["phone"]["nullcp"]) ?></div>
							</td></tr>
							</table>
						</td>
						<td class="bottomBorder" align="left"><img src="graph_detail_callprogress.png.php?<?= $urloptions ?>"></td>
<?
						if(count($joblist) == 1){
?>
							<td class="bottomBorder" align="left"><img src="report_graph_hourly.png.php?jobid=<?=$joblist[0]?>"></td>
<?
						} else {
?>						
							<td class="bottomBorder" align="left"><img src="report_graph_hourly.png.php?datestart=<?=$datestart?>&dateend=<?=$dateend?>"></td>
<?
						}
?>
					</tr>
			
			<? } ?>
			
			<? if (isset($jobstats["email"])) { ?>
					<tr>
						<th align="right" class="windowRowHeader bottomBorder" valign="top" style="padding-top: 6px;">Email:</th>
						<td class="bottomBorder" >
							<div class="floatingreportdata"><u>People</u><br><?= number_format($jobstats["email"]["emailpeople"]) ?></div>
			
							<div class="floatingreportdata"><u>Email Addresses</u><br><?= number_format($jobstats["email"]["totalemails"]) ?></div>
							<div class="floatingreportdata"><u>% Sent</u><br><?= sprintf("%0.2f%%",100 * $jobstats["email"]["percentsent"]) ?></div>
						</td>
						<td class="bottomBorder" align="left"><img src="graph_summary_email.png.php?<?= $urloptions ?>"></td>
					</tr>
			<? } ?>
					<tr>
						<th align="right" class="windowRowHeader" valign="top" style="padding-top: 6px;">View Options</th>
						<td >
							<? if(!isset($this->params['detailed']) || !$this->params['detailed']){ ?>
								<a href="reportjobdetails.php">Call Details</a>&nbsp;|&nbsp;
							<? } ?>
							<a href="reportjobdetails.php?csv=true">Download CSV File</a>
							<?
								if(isset($options['jobid'])){
									$job = new Job($options['jobid']);
									if($job->questionnaireid){
										?>&nbsp;|&nbsp;<a href="reportjobsurvey.php?surveyid=<?=$options['jobid']?>">Survey Results</a><?
									}
								}
							?>
						</td>
					</tr>
			
				</table>
			
			<?
				endWindow();
			}
		}
	}
	
	function runDetailedHtml(){
		
		$fields = $this->reportinstance->getFields();
		$pagestart = $this->params['pagestart'];
		print '<br>';
		$query = $this->query;
		$query .= " limit $pagestart, 500";
		//load page to memory
		$data = array();
		$result = Query($query);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}
		
		$query = "select found_rows()";
		$total = QuickQuery($query);
	
		startWindow("Report Details", 'padding: 3px;', false);
	
		showPageMenu($total,$pagestart,500);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportdetails">';
		$titles = array(0 => "ID#",
						1 => "First Name",
						2 => "Last Name",
						3 => "Message",
						5 => "Destination",
						6 => "Attempts",
						7 => "Last Attempt",
						8 => "Last Result");
		$count=16;
		foreach($fields as $index => $field){
			$titles[$count] = $field;
			$count++;
		}
			
		$formatters = array(3 => "fmt_message",
							4 => "fmt_limit_25",
							5 => "fmt_destination",
							6 => "fmt_attempts",
							7 => "fmt_date",
							8 => "fmt_result");
		showTable($data,$titles,$formatters);
		echo "</table>";
		showPageMenu($total,$pagestart,500);
	
		endWindow();
		?>
		<script langauge="javascript">
			var reportdetailstable = new getObj("reportdetails").obj;
		<?
			$count=1;
			foreach($fields as $index => $field){
				?>
				setColVisability(reportdetailstable, 7+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
			</script>
		<?
	
	}

	function runCSV(){
		
		$query = $this->query;
		$fieldlist = $this->reportinstance->getFields();
		$activefields = $this->reportinstance->getActiveFields();
		$options = $this->params;
		
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
	
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
	
		$issurvey = false;
		if (isset($_SESSION['reportjobid']) && $_SESSION['reportjobid']) {
			$job = new Job($_SESSION['reportjobid']);
			if ($job->questionnaireid) {
				$issurvey = true;
				$numquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid");
			}
		}
	
		//generate the CSV header
		$header = '"Job Name","User","Type","Message","ID","First Name","Last Name","Destination","Attempts","Last Attempt","Last Result"';
		
		
		if (isset($options['issurvey']) && $options['issurvey']) {
			for ($x = 1; $x <= $numquestions; $x++) {
				$header .= ",Question $x";
			}
		}
		foreach($activefields as $active){
			$header .= ',"' . $fieldlist[$active] . '"';
		}
		echo $header;
		echo "\r\n";
	
		$result = Query($query);
	
		while ($row = DBGetRow($result)) {
			$row[5] = html_entity_decode(fmt_destination($row,5));
			$row[6] = (isset($row[6]) ? $row[6] : "");
			
	
			if (isset($row[7])) {
				$time = strtotime($row[7]);
				if ($time !== -1 && $time !== false)
					$row[7] = date("m/d/Y H:i",$time);
			} else {
				$row[7] = "";
			}
			$row[8] = fmt_result($row,8);
	
	
			$reportarray = array($row[12],$row[10],ucfirst($row[3]),$row[4],$row[0],$row[1],$row[2],$row[5],$row[6],$row[7],$row[8]);
	
			if ($issurvey) {
				//fill in survey result data, be sure to fill in an array element for all questions, even if blank
				$startindex = count($reportarray);
	
				$questiondata = array();
				if ($row[3] == "phone")
					parse_str($row[12],$questiondata);
				else if ($row[3] == "email")
					parse_str($row[13],$questiondata);
	
				//add data to the report for each question
				for ($x = 0; $x < $numquestions; $x++) {
					$reportarray[$startindex + $x] = isset($questiondata["q$x"]) ? $questiondata["q$x"] : "";
				}
			}
			$count=0;
			foreach($fieldlist as $index => $field){
				if(in_array($index, $activefields)){
					$reportarray[] = $row[16+$count];
				}
				$count++;
			}
			echo '"' . implode('","', $reportarray) . '"' . "\r\n";
			
		}
	}
	
	function setReportFile(){
		$this->reportfile = "jobreport.jasper";
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