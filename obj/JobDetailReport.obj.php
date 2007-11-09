<?

class JobDetailReport extends ReportGenerator{

	function generateQuery(){
		global $USER;
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
		} else if($this->params['reporttype'] == "smsdetail"){
			$typequery = " and rp.type = 'sms'";
			$this->params['type'] = "sms";
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
			$joblist = implode("','", getJobList($startdate, $enddate, $jobtypes, "false", isset($this->params['type']) ? $this->params['type'] : ""));
		}
		$this->params['joblist'] = $joblist;
		$resultquery = "";
		if(isset($this->params['result']) && $this->params['result']){
			if($this->params['result'] == "undelivered"){
				//TODO: rename newflag
				$resultquery = " and rp.iscontacted = '0' and rp.status != 'duplicate'";
				if(isset($this->params['hideinprogress']) && $this->params['hideinprogress'] == "true"){
					$resultquery .= " and rp.status in ('fail', 'nocontacts', 'blocked') ";
				}
			} else if($this->params['result'] == "nocontacts"){
				$resultquery = " and rp.status = 'nocontacts' ";
			} else {
				$resultquery = " and rc.result in ('" . DBSafe($this->params['result']) . "')";
			}
		}
		
		if(isset($this->params['status']) && $this->params['status']){
			if($this->params['status'] == "completed")
				$resultquery .= " and rp.status in ('success', 'fail', 'duplicate')";
			else if($this->params['status'] == "remaining")
				$resultquery .= " and rp.status not in ('success', 'fail', 'duplicate')";
			else
				$resultquery .= " and rp.status = '" . DBSafe($this->params['status']) . "'";
		}

		$searchquery = " and rp.jobid in ('" . $joblist. "')";
		$searchquery .= $resultquery . $typequery;
		$fieldquery = generateFields("rp");
		$this->query =
			"select SQL_CALC_FOUND_ROWS
			j.name as jobname,
			u.login,
			rp.pkey,
			rp." . FieldMap::GetFirstNameField() . " as firstname,
			rp." . FieldMap::GetLastNameField() . " as lastname,
			rp.type as type,
			coalesce(m.name, sq.name) as messagename,
			coalesce(rc.phone,
						rc.email,
						rc.sms,
						concat(
							coalesce(rc.addr1,''), ' ',
							coalesce(rc.addr2,''), ' ',
							coalesce(rc.city,''), ' ',
							coalesce(rc.state,''), ' ',
							coalesce(rc.zip,''))
					) as destination,
			from_unixtime(rc.starttime/1000) as lastattempt,
			coalesce(rc.result,
					rp.status) as result,
			rp.status,
			rc.numattempts as numattempts,
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
			$rulesql
			$orderquery
			";
		//query to test resulting dataset
		$this->testquery =
			"select count(*)
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
				$rulesql
				$orderquery
				";

	}

	function runHtml(){

		$typequery = "";
		if(isset($this->params['type']))
			$typequery = " and rp.type = '" . $this->params['type'] . "'";

		$activefields = explode(",", $this->params['activefields']);
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

		$searchrules = array();
		if(isset($this->params['rules']) && $this->params['rules']){
			$searchrules = displayRules($this->params['rules']);
		}

		// DISPLAY

			startWindow("Filter By");
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
			if(isset($this->params['result']) && $this->params['result'] != ""){
				$results = explode("','",$this->params['result']);
				$resultnames = array();
				foreach($results as $result)
					$resultnames[] = fmt_result(array($result), 0);
				$resultnames = implode(", ", $resultnames);
?>
				<tr><td>Results: <?=$resultnames?></td></tr>
<?
			}

			foreach($searchrules as $rule){
				?><tr><td><?=$rule?></td></tr><?
			}
?>
			</table>
		<?
		endWindow();

		?><br><?

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
						11 => "Attempts",
						8 => "Last Attempt",
						9 => "Last Result");
		$titles = appendFieldTitles($titles, 13, $fieldlist, $activefields);

		$formatters = array(5 => "fmt_message",
							7 => "fmt_destination",
							8 => "fmt_date",
							9 => "fmt_jobdetail_result");
		showTable($data,$titles,$formatters);
		echo "</table>";
		showPageMenu($total,$pagestart,500);

		endWindow();
		?>
		<script langauge="javascript">
			var reportdetailstable = new getObj("reportdetails").obj;
		</script>
		<?

	}

	function runCSV(){

		$options = $this->params;
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", isset($options['activefields']) ? $options['activefields'] : "");


		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point


		$issurvey = false;
		$maxquestions = 0;
		if(isset($this->params['joblist']) && $this->params['joblist']!= ""){
			$joblist = explode("','", $this->params['joblist']);
			foreach($joblist as $jobid){
				$job = new Job($jobid);
				if($job->questionnaireid != null){
					$issurvey = true;
					$numquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid");
					if($numquestions > $maxquestions)
						$maxquestions = $numquestions;
				}
			}
		}

		//generate the CSV header
		$titles = array("0" => "Job Name",
						"1" => "User",
						"5" => "Type",
						"6" => "Message",
						"2" => "ID",
						"3" => "First Name",
						"4" => "Last Name",
						"7" => "Destination",
						"11" => "Attempts",
						"8" => "Last Attempt",
						"9" => "Last Result");


		if (isset($issurvey) && $issurvey) {
			for ($x = 1; $x <= $maxquestions; $x++) {
				$titles["question$x"] = "Question $x";
			}
		}

		$titles = appendFieldTitles($titles, 13, $fieldlist, $activefields);

		$formatters = array("5" => "fmt_delivery_type",
							"7" => "csv_destination",
							"8" => "csv_date",
							"9" => "fmt_jobdetail_result");
		if ($issurvey) {
			for ($x = 1; $x <= $maxquestions; $x++) {
				$formatters["question$x"] = "parse_survey_data";
			}
		}
		$result = Query($this->query);
		$data = array();
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}
		createCSV($data, $titles, $formatters, null);
	}

	function setReportFile(){
		$this->reportfile = "jobdetailreport.jasper";
	}

	function getReportSpecificParams(){
		$daterange = "";
		if(isset($this->params['reldate'])){
			list($startdate, $enddate) = getStartEndDate($this->params['reldate'], $this->params);
			$daterange = "From: " . date("m/d/Y", $startdate) . " To: " . date("m/d/Y", $enddate);
		}
		$joblist = array();
		if($this->params['joblist'] != "")
			$joblist=explode("','", $this->params['joblist']);
				
		$sms = QuickQuery("select count(smsmessageid) from job where id in ('" . $this->params['joblist'] . "')") ? "1" : "0";
		
		$params = array("jobId" => $this->params['joblist'],
						"jobcount" => count($joblist),
						"daterange" => $daterange,
						"hassms" => $sms);
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
		$ordering["Attempts"] = "numattempts";
		$ordering["Last Attempt"]="lastattempt";
		$ordering["Last Result"]="result";


		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}
}

?>