<?

class CallsReport extends ReportGenerator{

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];
		if (!$USER->authorize('viewsystemreports')) {
			$userjoin = " and rp.userid = $USER->id ";
		} else {
			$userjoin = "";
		}
		
		$rulesql = getRuleSql($this->params, "rp");
		
		$usersql = $USER->userSQL("rp");
		$personquery="";
		$phonequery="";
		$emailquery="";
		$jobtypesquery="";
		$reldatequery = "";
		$resultquery="";
		$jobquery = "";
		
		if(isset($this->params['pid'])){
			$personquery = " and rp.personid like '" . DBSafe($this->params['pid']) . "'";
		}
		if(isset($this->params['phone'])){
			$phonequery = $this->params['phone'] ? " and rc.phone like '%" . DBSafe($this->params['phone']) . "%'" : "";
		}
		if(isset($this->params['email'])){
			$emailquery = $this->params['email'] ? " and rc.email like '%" . DBSafe($this->params['email']) . "%'" : "";
		}

		if(isset($this->params['jobtypes'])){
			$jobtypesquery = $this->params['jobtypes'] ? " and j.jobtypeid in ('" . $this->params['jobtypes'] . "')" : "";
		}
	
		if(isset($this->params['reldate']) && $this->params['reldate'] != ""){
			$reldate = $this->params['reldate'];
			list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
			$this->params['joblist'] = implode("','", getJobList($startdate, $enddate));
			$jobquery = " and rp.jobid in ('" . $this->params['joblist'] . "')";
		}
		
		if(isset($this->params['result']) && $this->params['result'] != ""){
			$resultquery = " and rc.result in ('" . $this->params['result'] . "') ";
		}
			
		$search = $personquery . $phonequery . $emailquery . $jobtypesquery . $resultquery  . $jobquery;
		
		$fieldquery = generateFields("rp");
		
		$this->query = 
				"Select
					j.name as jobname,
					jt.name as jobtype,
					rp.type as type,
					coalesce(m.name, sq.name) as message,
					coalesce(rc.phone,
						rc.email,
						concat(
							coalesce(rc.addr1,''), ' ',
							coalesce(rc.addr2,''), ' ',
							coalesce(rc.city,''), ' ',
							coalesce(rc.state,''), ' ',
							coalesce(rc.zip,''))
							) as destination,
					rc.attemptdata as attemptdata,
					coalesce(rc.result, rp.status) as status
					$fieldquery
					from reportperson rp
					left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
					inner join job j on (rp.jobid= j.id)
					inner join jobtype jt on (j.jobtypeid = jt.id)
					left join message m on (m.id = rp.messageid)
					left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
					where 1
					$search
					$usersql
					$rulesql
					$userjoin
					";
	}

	function runHtml(){
		
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}

		$result = Query($this->query);
		$data = array();
		// parse through data and seperate attempts.
		// if no attempt made, look at rp.status for reason(index 5)
		while($row = DBGetRow($result)){
			$tmp = explode(",",$row[5]);
			
			foreach($tmp as $attempt){
				$line = array();
				if($attempt == ""){
					$time = "";
					$res = $row[6];
				} else {
					list($time, $res) = explode(":", $attempt);
				}
				$line[] = $row[0];
				$line[] = $row[1];
				$line[] = $row[2];
				$line[] = $row[3];
				$line[] = $row[4];
				$line[] = $time;
				$line[] = $res;
				for($i=0; $i<count($fields); $i++){
					$line[] = $row[7+$i];
				}
				$data[] = $line;
			}
		}
		// Ordering done in php due to attempt data
		// index 4 is date/time column
		$temparray = $data;
		foreach($data as $index => $row){
			$temparray[$index] = $row[4];
		}
		$tempdata=array();
		if(asort($temparray, SORT_NUMERIC)){
			$count=0;
			foreach($temparray as $taindex => $value){					
				$tempdata[$count] = $data[$taindex];
				$count++;
			}
			$data = $tempdata;
		}

		
		
		$titles = array("0" => "Job Name",
						"1" => "Job Type",
						"2" => "Message",
						"4" => "Destination",
						"5" => "Date/Time",
						"6" => "Result");
		foreach($fieldlist as $index => $field){
			$titles[] = $field;
		}
		$formatters = array("2" => "fmt_message",
							"4" => "fmt_destination",
							"5" => "fmt_ms_timestamp",
							"6" => "fmt_contacthistory_result");
		
		$searchrules = array();
		if(isset($this->params['rules']) && $this->params['rules']){
			$rules = explode("||", $this->params['rules']);
			foreach($rules as $rule){
				if($rule) {
					$rule = explode(";", $rule);
					$newrule = new Rule();
					$newrule->logical = $rule[0];
					$newrule->op = $rule[1];
					$newrule->fieldnum = $rule[2];
					$newrule->val = $rule[3];
					$fieldname = QuickQuery("select name from fieldmap where fieldnum = '$newrule->fieldnum'");
					$searchrules[] = $fieldname . ": " . preg_replace("{\|}", ", ", $newrule->val);
				}
			}
		}
	
		startWindow("Search Parameters");
?>
		<table>
<?
			if(isset($this->params['personid']) && $this->params['personid'] != ""){
?>
				<tr><td>ID#: <?=$this->params['personid']?></td></tr>
<?	
			}
			if(isset($this->params['phone']) && $this->params['phone'] != ""){
?>
				<tr><td>Phone: <?=$this->params['phone']?></td></tr>
<?
			}
			if(isset($this->params['email']) && $this->params['email'] != ""){
?>
				<tr><td>Email: <?=$this->params['email']?></td></tr>
<?
			}
			if(isset($this->params['reldate']) && $this->params['reldate'] != ""){
				list($startdate, $enddate) = getStartEndDate($this->params['reldate'], $this->params);
?>
				<tr><td>From: <?=date("m/d/Y", $startdate)?> To: <?=date("m/d/Y", $enddate)?></td></tr>
<?
			}
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
			if(isset($this->params['results']) && $this->params['results'] != ""){
				$results = explode("','",$this->params['results']);
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
		?>
		<br>
		<?
		startWindow("Search Results", "padding: 3px;");
			$query = "select rp.pkey, " .
						" rp." . FieldMap::getFirstNameField() . ", " .
						" rp." . FieldMap::getLastNameField() .
						" from reportperson rp
						where rp.personid = '" . DBSafe($this->params['pid']) . "'
						group by rp.personid";
			list($pkey,$firstname, $lastname) = QuickQueryRow($query);
?>
			<table  width="100%" cellpadding="3" cellspacing="1" class="list" >
				<tr><td>ID#: <?=$pkey?></td></tr>
				<tr><td>First Name: <?=$firstname?></td></tr>
				<tr><td>Last Name: <?=$lastname?></td></tr>
			</table>
<?
		endWindow();
?>
<br>
<?		

		startWindow("Contact History", "padding: 3px;");
?>
		<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
<?
		showTable($data, $titles, $formatters);
?>
		</table>
<?
		endWindow();
		
		?>
		<script langauge="javascript">
			var searchresultstable = new getObj("searchresults").obj;	
		<?
			$count=1;
			foreach($fieldlist as $index => $field){
				?>
				setColVisability(searchresultstable, 5+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
		</script>
		<?
		
	}

	function runCSV(){
		$results = isset($this->params['result']) ? $this->params['result'] : "";
		$resultquery = "";
		$resulttypes = "";
		if($results){
			$resulttypes = array();
			$resultquery = "and rc.result in ( $results )";
			$restypes = explode(",", $results);
			foreach($restypes as $res){
				$resulttypes[] = job_status(preg_replace("{'}", "",$res));
			}
			$resulttypes = implode(", ", $resulttypes);
		}
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = $this->params['activefields'];
		$query = $this->query;
		$result = Query($query);
		$persondata = array();
		while($row = DBGetRow($result)){
			$persondata[$row[3]] = $row;
		}
		$total = QuickQuery("select found_rows()");
		
		$fieldquery = generateFields("rp");
		$personcalls = array();
		foreach($persondata as $info){
			$query = "select j.name as jobname,
					from_unixtime(rc.starttime/1000) as date, 
					rp.status as status
					$fieldquery
					,j.id
					from reportperson rp
					left join reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
					inner join job j on (rp.jobid= j.id)
					where rp.personid = '$info[3]'
					$resultquery
					group by j.id";
			$result = Query($query);
			$temparray = array();
			while($row = DBGetRow($result)){
				$temparray[] = $row;
			}
			$personcalls[$info[3]] = $temparray;
		}
		$priority = "";
		if(isset($this->params['priority'])){
			$jobtype = new JobType($this->params['priority']+0);
			$priority = $jobtype->name;
		}
		// Begin output to csv
		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");
	
		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
	
		$titles = "Person ID, First Name,Last Name,Job Name,Date";
		foreach($fieldlist as $index => $field){
			if(in_array($index, $activefields)){
				$titles .= "," . $field;
			}
		}
		echo $titles;
		echo "\r\n";
		foreach($persondata as $row){
			$line = array();
			$line[] = $row[0];
			$line[] = $row[1];
			$line[] = $row[2];
			foreach($personcalls[$row[3]] as $callinfo){
				$line2 = array();
				$line2[] = $callinfo[0];
				$line2[] = date("m/d/Y H:i", strtotime($callinfo[1]));
				$line2[] = $callinfo[2];
				$i = 4;
				foreach($fieldlist as $index => $field){
					if(in_array($index, $activefields)){
						$line2[] = $callinfo[$i];
					}
					$i++;
				}
				echo '"' . implode('","', $line) . '","' . implode('","', $line2) . '"' . "\r\n";
			}
		}
	}
	
	function setReportFile(){
		$this->reportfile = "CallsReport.jasper";
	}
	
	function getReportSpecificParams(){
		return array();
	}
	
	/**static functions**/

	static function getOrdering(){
		global $USER;
		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
	
		$ordering = array();
		$ordering["Job Name"]="jobname";
		$ordering["Message"] = "message";
		$ordering["Destination"] = "destination";
		$ordering["Date/Time"] = "date";
		$ordering["result"] = "result";
		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}

}
?>