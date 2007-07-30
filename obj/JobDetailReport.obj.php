<?

class JobDetailReport extends ReportGenerator{
	
	function generateQuery(){
		$USER = new User($this->userid);
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];
		$orderquery = getOrderSql($this->params);
		$rulesql = getRuleSql($this->params, "rp");
		
		$jobtypes = "";
		if(isset($this->params['jobtypes'])){
			$jobtypes = $this->params['jobtypes'];
		}
		
		$typequery = "";
		if($this->params['reporttype'] == "phonedetail"){
			$typequery = " and rp.type = 'phone'";
			$this->params['type'] = "phone";
		} else if($this->params['reporttype'] == "emaildetail"){
			$typequery = " and rp.type = 'email'";
			$this->params['type'] = "email";
		}
		
		if(isset($this->params['jobid'])){
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
			$joblist = implode("','", getJobList($startdate, $enddate, $jobtypes, null, isset($this->params['type']) ? $this->params['type'] : ""));
		}
		$this->params['joblist'] = $joblist;
		
		$resultquery = "";
		if(isset($this->params['result']) && $this->params['result']){
			if($this->params['result'] == "undelivered"){
				$resultquery = " and rp.status = 'fail' ";
			} else {
				$resultquery = " and rc.result in ('" . $this->params['result'] . "')";
			}
		}		
		
		$searchquery = " and rp.jobid in ('" . $joblist. "')";
		$searchquery .= $resultquery . $typequery;
		$usersql = $USER->userSQL("rp");
		$this->params['usersql'] = $usersql;
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
		if(isset($this->params['type']))
			$typequery = " and rp.type = '" . $this->params['type'] . "'";
	
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
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
		
		
		// DISPLAY
		
		displayJobSummary($this->params['joblist']);	

		?><br><?
		startWindow("Report Details", 'padding: 3px;', false);
	
		showPageMenu($total,$pagestart,500);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportdetails">';
		$titles = array(0 => "Job",
						1 => "Submitted By",
						2 => "ID#",
						3 => "First Name",
						4 => "Last Name",
						5 => "Message",
						7 => "Destination",
						8 => "Attempts",
						9 => "Last Attempt",
						10 => "Last Result");
		$count=16;
		foreach($fieldlist as $index => $field){
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
			foreach($fieldlist as $index => $field){
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
		$options = $this->params;
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = $options['activefields'];
		
		
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
	
	function getReportSpecificParams(){
		$params = array("jobId" => $this->params['joblist'],
						"usersql" => $this->params['usersql'] );
		return $params;
	}

	static function getOrdering(){
		global $USER;
		$fields = FieldMap::getAuthorizedFieldMaps();
	
		$ordering = array();
		$ordering["Job"] = "j.name";
		$ordering["Submitted By"] = "u.login";
		$ordering["ID#"] = "rp.pkey";
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