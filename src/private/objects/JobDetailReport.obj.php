<?

class JobDetailReport extends ReportGenerator{

	function getData() {
		$query = $this->query;
		$data = array();

		$result = Query($query, $this->_readonlyDB);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}
		$query = "select found_rows()";
		$total = QuickQuery($query, $this->_readonlyDB);

		return $data;
	}

	function generateQuery($hackPDF = false){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];
		$orderquery = ""; // remove sorting to improve performance (bug 4461)
		$rulesql = getRuleSql($this->params, "rp");
		$orgsql = getOrgSql($this->params);
		
		$jobtypes = "";
		if(isset($this->params['jobtypes'])){
			$jobtypes = $this->params['jobtypes'];
		}

		$typequery = "";
		switch ($this->params['reporttype']) {
		case 'phonedetail':
			$typequery = " and rp.type = 'phone'";
			$this->params['type'] = 'phone';
			break;
		case 'emaildetail':
			$typequery = " and rp.type = 'email'";
			$this->params['type'] = 'email';
			break;
		case 'smsdetail':
			$typequery = " and rp.type = 'sms'";
			$this->params['type'] = 'sms';
			break;
		case 'devicedetail':
			$typequery = " and rp.type = 'device'";
			$this->params['type'] = 'device';
			break;
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

				// special case for 'device' because `reportdevice`.`response` doesn't exist
				if ($this->params['type'] != 'device') {
					if (isset($resultarray["confirmed"])) {
						$resultqueryarray[] = " rc.response = 1 ";
						unset($resultarray["confirmed"]);
					}
					if (isset($resultarray["notconfirmed"])) {
						$resultqueryarray[] = " rc.response = 2 ";
						unset($resultarray["notconfirmed"]);
					}
					if (isset($resultarray["noconfirmation"])) {
						$resultqueryarray[] = " rc.response is null ";
						unset($resultarray["noconfirmation"]);
					}
				}
				if(isset($resultarray["declined"])){
					$resultqueryarray[] = " rp.status='declined' ";
					unset($resultarray["declined"]);
				}
				if (isset($resultarray["X"])) {
					$resultqueryarray[] = " (rc.result = 'X' and if (rc.result = 'X' and rc.numattempts < 3, 'F', rc.result)) ";
					unset($resultarray["X"]);
				}
				if (isset($resultarray["F"])) {
					$resultqueryarray[] = " (rc.result = 'X' and rc.numattempts < 3) ";
				}
				
				// combine the rest, assume they are either rc.result codes or rd.result codes
				if(count($resultarray)){
					$r = ($this->params['reporttype'] == 'devicedetail' ? "rd" : "rc");
					$resultqueryarray[] = " $r.result in ('" . implode("','", array_flip($resultarray)) . "')";
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
		$orgfieldquery = generateOrganizationFieldQuery("rp.personid", true);
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
				coalesce(mg.name, sq.name) as messagename,
				case rp.type when 'device' then concat(left(rd.deviceUuid,8), '...') when 'phone' then rc.phone when 'email' then rc.email when 'sms' then rc.sms else concat_ws(' ', rc.addr1, rc.addr2, rc.city, rc.state, rc.zip) end as destination,
				case rp.type when 'device' then from_unixtime(rd.startTimeMs/1000) else from_unixtime(rc.starttime/1000) end as lastattempt,
				coalesce(if(rc.result='X' and rc.numattempts<3,'F',rc.result), rd.result, rp.status) as result,
				'unused' as emailstatuscode,
				1000 as emailreadduration,
				rp.status,
				case rp.type when 'device' then rd.numAttempts else rc.numattempts end as numattempts,
				rc.resultdata,
				sw.resultdata,
				rc.response as confirmed,
				case rp.type when 'device' then rd.sequence else rc.sequence end as sequence,
				rc.voicereplyid as voicereplyid,
				vr.id as vrid
				$orgfieldquery
				$fieldquery
				$gfieldquery
				, (select dl.label from destlabel dl
					where dl.type = rp.type and dl.sequence = (
						rc.sequence % (select js.value from jobsetting js
							where js.jobid = rp.jobid and name = concat('max', rp.type, if((rp.type = 'email' || rp.type = 'phone'), 's', '') )
						)
					)
				) as label,
				case rp.type when 'device' then rd.recipientpersonid else rc.recipientpersonid end as recipientpersonid,
				case rp.type when 'device' then concat(' ', rdp.f01, ' ', rdp.f02) else concat(' ', rcp.f01, ' ', rcp.f02) end as recipientpersonname
			from
				reportperson rp
				inner join job j on (rp.jobid = j.id)
				inner join user u on (u.id = j.userid)
				left outer join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid )
				left outer join reportdevice rd on (rd.jobid = rp.jobid and rd.personid = rp.personid and rp.type = 'device' )
				left outer join messagegroup mg on (mg.id = j.messagegroupid)
				left outer join surveyquestionnaire sq on (sq.id = j.questionnaireid)
				left outer join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
				left outer join voicereply vr on (vr.jobid = rp.jobid and vr.personid = rp.personid and vr.sequence = rc.sequence and vr.userid = {$USER->id} and rc.type='phone')
				left outer join language l on (l.code = rp." . FieldMap::GetLanguageField() . ")
				left outer join person rcp on (rc.recipientpersonid = rcp.id)
				left outer join person rdp on (rd.recipientpersonid = rdp.id)
			where
				1
			$searchquery
			$rulesql
			$orgsql
			$orderquery
			";

		// query to test resulting dataset, PDF generation uses this to estimate the number of pages.
		// comment out tables in left outer join that can match only 0 or 1 row, since they can't affect the count.
		// comment out ORDER BY because it can't affect the count either.
		$this->testquery =
			"select count(*)
				from reportperson rp
				inner join job j on (rp.jobid = j.id)
				inner join user u on (u.id = j.userid)
				left outer join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid and rc.result not in ('nocontacts'))
				left outer join reportdevice rd on (rd.jobid = rp.jobid and rd.personid = rp.personid and rp.type = 'device')
				-- left outer join	messagegroup mg on (mg.id = j.messagegroupid)
				-- left outer join surveyquestionnaire sq on (sq.id = j.questionnaireid)
				-- left outer join surveyweb sw on (sw.personid = rp.personid and sw.jobid = rp.jobid)
				left outer join voicereply vr on (vr.jobid = rp.jobid and vr.personid = rp.personid and vr.sequence = rc.sequence and u.id = vr.userid)
				-- left outer join person rcp on (rc.recipientpersonid = rcp.id)
				-- left outer join person rdp on (rd.recipientpersonid = rdp.id)
				where 1
				$searchquery
				$rulesql
				$orgsql
				-- $orderquery
				";

	}

	function runHtml() {
		//index 18 is voicereply id
		function fmt_detailedresponse($row, $index){
			global $USER;
			$text = "";

			if ($row[5] != 'email') {
				if ($row[18] != null) {
					if ($row[19] != null) {
					$text .= ' <span class="voicereplyclickableicon"><img src="img/speaker.gif" onclick="popup(\'repliespreview.php?id=' . $row[18] . '&close=1\', 450, 600)"></span>';
				} else {
					$text .= ' <span class="voicereplyicon"><img src="img/speaker2.gif"></span>';
				}
				}

				if ($row[$index] == "1") {
					$text .= "Yes";
				} else if ($row[$index] == "2") {
					$text .= "No";
				}
			}
			return $text;
		}

		function fmt_person_status($status){
			switch ($status) {
			case 'nocontacts':
				return _L("No Contacts");
			case 'declined':
				return _L("Declined");
			default;
				return ucfirst($status);
			}
		}

		function fmt_organization($row, $index) {
			return $row[20];
		}

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
			startWindow(_L("Filter By"));
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

		//displayJobSummary($this->params['joblist'], $this->_readonlyDB);

		?><br><?
		startWindow(_L("Report Details ").help("JobDetailReport_ReportDetails"), 'padding: 3px;', false);

		showPageMenu($total,$pagestart,500);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list sortable" id="reportdetails">';
		$titles = array(0 => _L("#%s Name",getJobTitle()),
						1 => _L("#Submitted by"),
						2 => _L("#ID#"),
						3 => _L("#First Name"),
						4 => _L("#Last Name"),
						17 => _L("#Sequence"),
						51 => _L("#Recipient"),
						7 => _L("#Destination"),
						13 => _L("#Attempts"),
						8 => _L("#Last Attempt"),
						9 => _L("#Delivery Results"),
						16 => _L("#Response"),
						19 => getSystemSetting("organizationfieldname","Organization"));
		$titles = appendFieldTitles($titles, 19, $fieldlist, $activefields);

		$formatters = array(7 => "fmt_destination",
							8 => "fmt_date",
							9 => "fmt_jobdetail_result",
							16 => "fmt_detailedresponse",
							17 => "fmt_dst_src",
							19 => "fmt_organization"
							);
		showTable($data,$titles,$formatters);
		echo "</table>";
		showPageMenu($total,$pagestart,500);

		endWindow();
		?>
		<script type="text/javascript">
			var reportdetailstable = new getObj("reportdetails").obj;
		</script>
		<?
	}

	function runCSV(){


		function fmt_confirmation($row, $index){
			if ($row[5] != 'email') {
				if($row[$index] == "1"){
					$text = "Yes";
				} else if($row[$index] == "2"){
					$text = "No";
				} else {
					$text = "";
				}
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
		$header = array(_L("%s Name",getJobTitle()),_L("Submitted by"),_L("ID"),_L("First Name"),_L("Last Name"),_L("Dst. Src."),_L("Recipient"),_L("Destination"),_L("Attempts"),_L("Last Attempt"),_L("Delivery Results"),_L("Response"),_L(getSystemSetting("organizationfieldname","Organization")));
		foreach($fieldlist as $fieldnum => $fieldname){
			if(isset($activefields[$fieldnum])){
				$header[] = $fieldname;
			}
		}

		if (isset($issurvey) && $issurvey) {
			for ($x = 1; $x <= $maxquestions; $x++) {
				$header[] = "Question $x";
			}
		}

		echo '"' . implode('","',$header) . '"';
		echo "\r\n";

		$result = Query($this->query, $this->_readonlyDB);

		while ($row = DBGetRow($result)) {
			if($row[5] == "phone")
				$row[7] = Phone::format($row[7]);
			$row[13] = (isset($row[13]) ? $row[13] : "");


			if (isset($row[8])) {
				$time = strtotime($row[8]);
				if ($time !== -1 && $time !== false)
					$row[8] = date("m/d/Y h:i A",$time);
			} else {
				$row[8] = "";
			}
			$row[9] = html_entity_decode(fmt_jobdetail_result($row,9));

			$reportarray = array($row[0], $row[1], $row[2],$row[3],$row[4],fmt_dst_src($row, 17), $row[51], $row[7],$row[13],$row[8],$row[9],fmt_confirmation($row, 16), $row[20]);
			//index 18 is the last position of a non-ffield
			foreach($fieldlist as $fieldnum => $fieldname){
				if(isset($activefields[$fieldnum])){
					if (strpos($fieldnum, "g") === 0) {
						$num = 18 + substr($fieldnum, 1); // gfields come after the 20 ffields (firstname/lastname excluded, 18 more ffields)
					} else {
						$num = $fieldindex[$fieldnum]; // ffield
					}
					$reportarray[] = $row[19+$num]; // 18 is last index, $num starts at 1 for f03
				}
			}
			if ($issurvey) {
				//fill in survey result data, be sure to fill in an array element for all questions, even if blank
				$startindex = count($reportarray);

				$questiondata = array();
				if ($row[5] == "phone")
					parse_str($row[14],$questiondata);
				else if ($row[5] == "email")
					parse_str($row[15],$questiondata);

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
			$daterange = _L("From: % To: %s", date("m/d/Y", $startdate),date("m/d/Y", $enddate));
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
		$ordering[getJobTitle()] = "j.name";
		$ordering[_L("Submitted By")] = "u.login";
		$ordering[_L("ID#")] = "rp.pkey";
		$ordering[_L("Message")]="m.name";
		$ordering[_L("Destination")]="destination";
		$ordering[_L("Attempts")] = "numattempts";
		$ordering[_L("Last Attempt")]="lastattempt";
		$ordering[_L("Last Result")]="result";
		$ordering[_L("Response")]="confirmed DESC, voicereplyid DESC";
		$ordering[getSystemSetting("organizationfieldname","Organization")]="org";

		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}
}


?>
