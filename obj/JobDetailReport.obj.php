<?

class JobDetailReport extends ReportGenerator{
	
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
			$joblist = array(DBSafe($params['jobid']));
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
		$this->params['joblist'] = $joblist;
		$resultquery = "";
		if(isset($params['result']) && $params['result']){
			$resultquery = " and rc.result in ('" . $params['result'] . "')";
		}
		
		$typequery = "";
		if(isset($params['type']) && $params['type']){
			$typequery = " and rp.type = '" . $params['type'] . "'";
		}
		
		$searchquery = " and rp.jobid in ('" . implode("','", $joblist) ."')";
		$searchquery .= $resultquery . $typequery;
		$usersql = $USER->userSQL("rp");
		$fields = $instance->getFields();
		$fieldquery = generateFields("rp");
		$this->query = 
			"select SQL_CALC_FOUND_ROWS
			j.name as jobname,
			u.login,
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type,
			coalesce(m.name, sq.name) as messagename,
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
			from_unixtime(rc.starttime/1000) as lastattempt,
			coalesce(rc.result,
					rp.status) as result,
			rp.status,
			rp.type as jobtype,
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
	
	function runHtml(){
		
		$typequery = "";
		if(isset($this->params['type'])){
			$typequery = " and rp.type = '" . $this->params['type'] . "'";
		}
		
		$joblist = implode("','", $this->params['joblist']);
		
		$jobinfoquery = "Select u.login, 
								j.name, 
								j.description,
								coalesce(m.name, sq.name),
								count(distinct rp.personid),
								count(*)
								from reportperson rp
								left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
								inner join job j on (rp.jobid = j.id)
								left join message m on (rp.messageid = m.id)
								left join surveyquestionnaire sq on (j.questionnaireid = sq.id)
								inner join user u on (rp.userid = u.id)
								where rp.jobid in ('$joblist')
								$typequery
								group by m.id, sq.id";
		$jobinforesult = Query($jobinfoquery);
		$jobinfo = array();
		while($row = DBGetRow($jobinforesult)){
			$jobinfo[] = $row;
		}
		
		$fields = $this->reportinstance->getFields();
		$pagestart = $this->params['pagestart'];
		print '<br>';
		$query = $this->query;
		$query .= " limit $pagestart, 500";
		$data = array();
		
		$result = Query($query);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}
		$query = "select found_rows()";
		$total = QuickQuery($query);
		
		startWindow("Report Information", 'padding: 3px;');
		?>
			<table border="0" cellpadding="3" cellspacing="0" width="100%">
					<tr valign="top">
						<th align="right" class="windowRowHeader">Job Info:</th>
						<td >
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
											foreach($job as $jdata){
												if($jdata == null)
													$jdata = "&nbsp";
												?><td><?=$jdata?></td><?
											}
										?></tr><?
									}
								}
								?>
							</table>
						</td>
					</tr>
			</table>
		<?
		endWindow();
		?><br><?
		startWindow("Report Details", 'padding: 3px;', false);
	
		showPageMenu($total,$pagestart,500);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportdetails">';
		$titles = array(0 => "Job Name",
						1 => "User Login",
						2 => "ID#",
						3 => "First Name",
						4 => "Last Name",
						5 => "Message",
						7 => "Destination",
						8 => "Attempts",
						9 => "Last Attempt",
						10 => "Last Result");
		$count=16;
		foreach($fields as $index => $field){
			$titles[$count] = $field;
			$count++;
		}
			
		$formatters = array(5 => "fmt_message",
							6 => "fmt_limit_25",
							7 => "fmt_destination",
							8 => "fmt_attempts",
							9 => "fmt_date",
							10 => "fmt_result");
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
				setColVisability(reportdetailstable, 9+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
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
		if (isset($options['jobid']) && $options['jobid']) {
			$job = new Job($options['jobid']);
			if ($job->questionnaireid) {
				$issurvey = true;
				$numquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid");
			}
		}
	
		//generate the CSV header
		$header = '"Job Name","User","Type","Message","ID","First Name","Last Name","Destination","Attempts","Last Attempt","Last Result"';
		
		
		if (isset($issurvey) && $issurvey) {
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
					parse_str($row[14],$questiondata);
				else if ($row[3] == "email")
					parse_str($row[15],$questiondata);
	
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
		$this->reportfile = "jobdetailreport.jasper";
	}
	
	function getReportSpecificParams($params){
		return $params;
	}

	static function getOrdering(){
		global $USER;
		$fields = getFieldMaps();
		$firstname = DBFind("FieldMap", "from fieldmap where options like '%firstname%'");
		$lastname = DBFind("FieldMap", "from fieldmap where options like '%lastname%'");
	
		$ordering = array();
		$ordering["Job Name"] = "j.name";
		$ordering["User Login"] = "u.login";
		$ordering["ID#"] = "rp.pkey";
		$ordering[$firstname->name]="rp." . $firstname->fieldnum;
		$ordering[$lastname->name]="rp." . $lastname->fieldnum;
		$ordering["Message"]="m.name";
		$ordering["Destination"]="destination";
		$ordering["Attempts"] = "attempts";
		$ordering["Last Attempt"]="lastattempt";
		$ordering["Last Result"]="result";
		
		
		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}
}

?>