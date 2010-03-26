<?

class JobDetailReport extends ReportGenerator{

	function generateQuery($hackPDF = false){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];
		$orderquery = getOrderSql($this->params);
		$rulesql = getRuleSql($this->params, "rp");
		$orgsql = getOrgSql($this->params);
		
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
			
			if($this->params['result'] == "undelivered" || (isset($_SESSION['report']['type']) && $_SESSION['report']['type'] == "notcontacted")){

				$undeliveredpersons = QuickQueryList("select rp.personid, sum(rp.iscontacted) as cnt, sum(rp2.iscontacted) as cnt2 from reportperson rp
											left join reportperson rp2 on (rp2.personid = rp.duplicateid and rp2.jobid = rp.jobid and rp2.type = rp.type)
											where rp.jobid in ('" . $joblist . "') group by rp.jobid, rp.personid having cnt = 0 and (cnt2 = 0 or cnt2 is null)", true, $this->_readonlyDB);
				$undeliveredpersons = array_keys($undeliveredpersons);
				$this->params['undeliveredcount'] = count($undeliveredpersons);
				$resultquery = " and rp.personid in ('" . implode("','", $undeliveredpersons) . "') ";
				if(isset($this->params['hideinprogress']) && $this->params['hideinprogress'] == "true"){
					$resultquery .= " and rp.status in ('fail', 'nocontacts', 'blocked', 'declined') ";
				}
			} else if($this->params['result'] == "nocontacts"){
				$resultquery .= " and rp.status = 'nocontacts' ";
			} else {
				$resultarray = array_flip(explode("','", $this->params['result']));
				$resultqueryarray = array();
				
				//check for specific results that aren't just reportcontact results, and make exceptions for them
				//remove them from the array so we can do a big rc.result in (...)
				if(isset($resultarray["confirmed"])){
					$resultqueryarray[] = " rc.response = 1 ";
					unset($resultarray["confirmed"]);
				}
				if(isset($resultarray["notconfirmed"])){
					$resultqueryarray[] = " rc.response = 2 ";
					unset($resultarray["notconfirmed"]);
				}
				if(isset($resultarray["noconfirmation"])){
					$resultqueryarray[] = " rc.response is null ";
					unset($resultarray["noconfirmation"]);
				}
				if(isset($resultarray["declined"])){
					$resultqueryarray[] = " rp.status='declined' ";
					unset($resultarray["declined"]);
				}
				
				//combine the rest, assuming they are rc.result codes
				if(count($resultarray)){
					$resultqueryarray[] = "rc.result in ('" . implode("','",array_flip($resultarray)) . "')";
				}
				
				$resultquery .= " and (" . implode(" OR ",$resultqueryarray) . ") ";
			}
		}

		if(isset($this->params['status']) && $this->params['status']){
			if($this->params['status'] == "completed")
				$resultquery .= " and rp.status in ('success', 'fail', 'duplicate')";
			else if($this->params['status'] == "remaining")
				$resultquery .= " and rp.status not in ('success', 'fail', 'duplicate')";
			else
				$resultquery .= " and rp.status = '" . $this->params['status'] . "'";
		}

		$searchquery = " and rp.jobid in ('" . $joblist. "')";
		$searchquery .= $resultquery . $typequery;
		$orgfieldquery = generateOrganizationFieldQuery("rp.personid");
		$fieldquery = generateFields("rp");
		$gfieldquery = generateGFieldQuery("rp.personid", true, $hackPDF);
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
			sw.resultdata,
			rc.response as confirmed,
			rc.sequence as sequence,
			rc.voicereplyid as voicereplyid,
			vr.id as vrid
			$orgfieldquery
			$fieldquery
			$gfieldquery
			, dl.label as label
			from reportperson rp
			inner join job j on (rp.jobid = j.id)
			inner join user u on (u.id = j.userid)
			left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
			left join	message m on
							(m.id = rp.messageid)
			left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
			left join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
			left join destlabel dl on (rc.type = dl.type and rc.sequence = dl.sequence)
			left join voicereply vr on (vr.jobid = rp.jobid and vr.personid = rp.personid and vr.sequence = rc.sequence and vr.userid = " . $USER->id . " and rc.type='phone')
			left join language l on (l.code = rp." . FieldMap::GetLanguageField() . ")
			where 1
			$searchquery
			$rulesql
			$orgsql
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
				left join destlabel dl on (rc.sequence = dl.sequence)
				left join voicereply vr on (vr.jobid = rp.jobid and vr.personid = rp.personid and vr.sequence = rc.sequence and u.id = vr.userid)
				where 1
				$searchquery
				$rulesql
				$orgsql
				$orderquery
				";

	}

	function runHtml(){

		//index 16 is voicereply id
		function fmt_detailedresponse($row, $index){
			global $USER;
			$text = "";

			if($row[16] != null){
				if($row[17] != null){
					$text .= ' <span class="voicereplyclickableicon"><img src="img/speaker.gif" onclick="popup(\'repliespreview.php?id=' . $row[16] . '&close=1\', 450, 600)"></span>';
				} else {
					$text .= ' <span class="voicereplyicon"><img src="img/speaker2.gif"></span>';
				}
			}
			if($row[$index] == "1"){
				$text .= "Yes";
			} else if($row[$index] == "2"){
				$text .= "No";
			}
			return $text;
		}

		function fmt_person_status($status){
			switch ($status){
				case 'nocontacts':
					return "No Contacts";
				case 'declined':
					return "Declined";
				default;
					return ucfirst($status);
			}
		}

		function fmt_organization($row, $index) {
			return $row[18];
		}

		$typequery = "";
		if(isset($this->params['type']))
			$typequery = " and rp.type = '" . $this->params['type'] . "'";

		$activefields = explode(",", $this->params['activefields']);
		$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
		$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fields = $ffields + $gfields;
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$pagestart = $this->params['pagestart'];
		print '<br>';
		$query = $this->query;
		$query .= " limit $pagestart, 500";
		$data = array();

		$result = Query($query, $this->_readonlyDB);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}
		$query = "select found_rows()";
		$total = QuickQuery($query, $this->_readonlyDB);

		$searchrules = array();
		if(isset($this->params['rules']) && $this->params['rules']){
			$searchrules = displayRules($this->params['rules']);
		}

		// DISPLAY
		if( (isset($this->params['jobtypes']) && $this->params['jobtypes'] != "") || (isset($this->params['result']) && $this->params['result'] != "") || count($searchrules) || (isset($this->params['status']) && $this->params['status'] != "") ){
			startWindow("Filter By");
?>
			<table>
<?
				if(isset($this->params['jobtypes']) && $this->params['jobtypes'] != ""){
					$jobtypes = explode("','", $this->params['jobtypes']);
					$jobtypenames = array();
					foreach($jobtypes as $jobtype){
						$jobtypeobj = new JobType($jobtype);
						$jobtypenames[] = escapehtml($jobtypeobj->name);
					}
					$jobtypenames = implode(", ",$jobtypenames);
?>
					<tr><td>Job Type: <?=$jobtypenames?></td></tr>
<?
				}
				if(isset($this->params['status']) && $this->params['status'] != ""){
					$statuses = explode("','",$this->params['status']);
					$statusnames = array();
					foreach($statuses as $status)
						$statusnames[] = fmt_person_status($status);
					$statusnames = implode(", ", $statusnames);
?>
					<tr><td>Status: <?=$statusnames?></td></tr>
<?
				}
				if(isset($this->params['result']) && $this->params['result'] != ""){
					$results = explode("','",$this->params['result']);
					$resultnames = array();
					foreach($results as $result)
						$resultnames[] = fmt_result(array($result), 0);
					$resultnames = implode(", ", $resultnames);
?>
					<tr><td>Result: <?=$resultnames?></td></tr>
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
		}

		displayJobSummary($this->params['joblist'], $this->_readonlyDB);

		?><br><?
		startWindow("Report Details ".help("JobDetailReport_ReportDetails"), 'padding: 3px;', false);

		showPageMenu($total,$pagestart,500);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportdetails">';
		$titles = array(0 => "Job Name",
						1 => "Submitted by",
						2 => "ID#",
						3 => "First Name",
						4 => "Last Name",
						15 => "Sequence",
						7 => "Destination",
						11 => "Attempts",
						8 => "Last Attempt",
						9 => "Last Result",
						14 => "Response",
						17 => "Current Org");
		$titles = appendFieldTitles($titles, 18, $fieldlist, $activefields);

		$formatters = array(7 => "fmt_destination",
							8 => "fmt_date",
							9 => "fmt_jobdetail_result",
							14 => "fmt_detailedresponse",
							15 => "fmt_dst_src",
							17 => "fmt_organization"
							);
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


		function fmt_confirmation($row, $index){
			if($row[$index] == "1"){
				$text = "Yes";
			} else if($row[$index] == "2"){
				$text = "No";
			} else {
				$text = "";
			}
			return $text;
		}

		$options = $this->params;
		$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
		$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fields = $ffields + $gfields;
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
					$numquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid", $this->_readonlyDB);
					if($numquestions > $maxquestions)
						$maxquestions = $numquestions;
				}
			}
		}

		$fieldindex = getFieldIndexList("p");
		$activefields = array_flip($activefields);
		//generate the CSV header
		$header = '"Job Name","Submitted by","ID","First Name","Last Name","Dst. Src.","Destination","Attempts","Last Attempt","Last Result","Response","Current Org"';
		foreach($fieldlist as $fieldnum => $fieldname){
			if(isset($activefields[$fieldnum])){
				$header .= ',"' . $fieldname . '"';
			}
		}

		if (isset($issurvey) && $issurvey) {
			for ($x = 1; $x <= $maxquestions; $x++) {
				$header .= ',"Question '. $x . '"';
			}
		}

		echo $header;
		echo "\r\n";

		$result = Query($this->query, $this->_readonlyDB);

		while ($row = DBGetRow($result)) {
			if($row[5] == "phone")
				$row[7] = Phone::format($row[7]);
			$row[11] = (isset($row[11]) ? $row[11] : "");


			if (isset($row[8])) {
				$time = strtotime($row[8]);
				if ($time !== -1 && $time !== false)
					$row[8] = date("m/d/Y h:i A",$time);
			} else {
				$row[8] = "";
			}
			$row[9] = html_entity_decode(fmt_jobdetail_result($row,9));

			$reportarray = array($row[0], $row[1], $row[2],$row[3],$row[4],fmt_dst_src($row, 15),$row[7],$row[11],$row[8],$row[9],fmt_confirmation($row, 14), $row[18]);
			//index 18 is the last position of a non-ffield
			foreach($fieldlist as $fieldnum => $fieldname){
				if(isset($activefields[$fieldnum])){
					if (strpos($fieldnum, "g") === 0) {
						$num = 18 + substr($fieldnum, 1); // gfields come after the 20 ffields (firstname/lastname excluded, 18 more ffields)
					} else {
						$num = $fieldindex[$fieldnum]; // ffield
					}
					$reportarray[] = $row[18+$num]; // 18 is last index, $num starts at 1 for f03
				}
			}
			if ($issurvey) {
				//fill in survey result data, be sure to fill in an array element for all questions, even if blank
				$startindex = count($reportarray);

				$questiondata = array();
				if ($row[5] == "phone")
					parse_str($row[12],$questiondata);
				else if ($row[5] == "email")
					parse_str($row[13],$questiondata);

				//add data to the report for each question
				for ($x = 0; $x < $maxquestions; $x++) {
					$reportarray[$startindex + $x] = isset($questiondata["q$x"]) ? $questiondata["q$x"] : "";
				}
			}

			echo '"' . implode('","', $reportarray) . '"' . "\r\n";
		}
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

		$sms = QuickQuery("select * from job j inner join message m on (m.messagegroupid=j.messagegroupid) where m.type='sms' and j.id in ('" . $this->params['joblist'] . "') limit 1", $this->_readonlyDB) ? "1" : "0";

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
		$ordering["Response"]="confirmed DESC, voicereplyid DESC";
		$ordering["Organization"]="org";

		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}
}


?>
