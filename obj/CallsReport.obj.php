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
			$emailquery = $this->params['email'] ? " and rc.email = '" . DBSafe($this->params['email']) . "'" : "";
		}

		if(isset($this->params['jobtypes'])){
			$jobtypesquery = $this->params['jobtypes'] ? " and j.jobtypeid in ('" . $this->params['jobtypes'] . "')" : "";
		}

		$joblist = false;
		if(isset($this->params['reldate']) && $this->params['reldate'] != ""){
			$reldate = $this->params['reldate'];
			list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
			$this->params['joblist'] = implode("','", getJobList($startdate, $enddate));
			$jobquery = " and rp.jobid in ('" . $this->params['joblist'] . "')";
			$joblist = $this->params['joblist'];
		}

		if(isset($this->params['results']) && $this->params['results'] != ""){
			$resultquery = " and rc.result in ('" . $this->params['results'] . "') ";
		}

		$search = $personquery . $phonequery . $emailquery . $jobtypesquery . $resultquery  . $jobquery;

		$orgfieldquery = generateOrganizationFieldQuery("rp.personid", true);
		$fieldquery = generateFields("rp");
		$gfieldquery = generateGFieldQuery("rp.personid", true);

		$this->query =
				"Select
					j.name as jobname,
					u.login as username,
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
					coalesce(if(rc.result='X' and rc.numattempts<3,'F',rc.result), rp.status) as status,
					rc.sequence as sequence
					$orgfieldquery
					$fieldquery
					$gfieldquery
					from reportperson rp
					left join reportcontact rc on (rp.jobid = rc.jobid and rp.personid = rc.personid and rp.type = rc.type)
					inner join job j on (rp.jobid= j.id)
					inner join jobtype jt on (j.jobtypeid = jt.id)
					left join message m on (m.id = rp.messageid)
					left join surveyquestionnaire sq on (sq.id = j.questionnaireid)
					inner join user u on (u.id = j.userid)
					where 1
					$search
					$rulesql
					$userjoin
					";
	}

	function runHtml(){

		$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
		$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fields = $ffields + $gfields;
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", $this->params['activefields']);

		//fetch f-fields just like the query and explode the string into an array for an easier count
		$joblist = false;
		if(isset($this->params['reldate']) && $this->params['reldate'] != ""){
			$joblist = $this->params['joblist'];
		}

		$result = Query($this->query, $this->_readonlyDB);
		$data = array();
		// parse through data and seperate attempts.
		// if no attempt made, look at rp.status for reason(index 5)
		while($row = DBGetRow($result)){
			$tmp = explode(",",$row[6]);

			foreach($tmp as $attempt){
				$line = array();
				if($attempt == ""){
					$time = "";
					$res = $row[7];
				} else {
					list($time, $res) = explode(":", $attempt);
				}
				$line[] = $row[0];
				$line[] = $row[1];
				$line[] = $row[2];
				$line[] = $row[3];
				$line[] = $row[4];
				$line[] = $row[5];
				$line[] = $time;
				$line[] = $res;
				$line[] = $row[8];
				$line[] = $row[9];
				//generatefields returns a string beginning with a comma so the count of generatefields is 1 plus the count of f-fields
				// TODO hardcode 27 ffields+gfields ugh...
				for($i=0; $i<=27; $i++){
					$line[] = $row[10+$i];
				}
				$data[] = $line;
			}
		}
		// Ordering done in php due to attempt data
		// index 5 is date/time column
		$temparray = $data;
		foreach($data as $index => $row){
			$temparray[$index] = $row[6];
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
						"1" => "Submitted by",
						"2" => "Job Type",
						"8" => "Sequence",
						"5" => "Destination",
						"6" => "Date/Time",
						"7" => "Result",
						"9" => getSystemSetting("organizationfieldname","Organization"));
		$titles = appendFieldTitles($titles, 8, $fieldlist, $activefields);

		$formatters = array("3" => "fmt_delivery_type_list",
							"5" => "fmt_destination",
							"6" => "fmt_ms_timestamp",
							"7" => "fmt_contacthistory_result",
							"8" => "fmt_dst_src",
							"9" => "fmt_organization"
							);

		$searchrules = array();
		if(isset($this->params['rules']) && $this->params['rules']){
			$searchrules = displayRules($this->params['rules']);
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
				<tr><td>Phone: <?=Phone::format($this->params['phone'])?></td></tr>
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
					$jobtypenames[] = escapehtml($jobtypeobj->name);
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
			list($pkey,$firstname, $lastname) = QuickQueryRow($query, false, $this->_readonlyDB);
?>
			<table  width="100%" cellpadding="3" cellspacing="1">
				<tr><td>ID#: <?=escapehtml($pkey)?></td></tr>
				<tr><td>First Name: <?=escapehtml($firstname)?></td></tr>
				<tr><td>Last Name: <?=escapehtml($lastname)?></td></tr>
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
				setColVisability(searchresultstable, 7+<?=$count?>, new getObj("hiddenfield".concat('<?=$index?>')).obj.checked);
				<?
				$count++;
			}
		?>
		</script>
		<?

	}

	// NOTE seems this is not an option in the GUI (Contact History)
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
		$result = Query($query, $this->_readonlyDB);
		$persondata = array();
		while($row = DBGetRow($result)){
			$persondata[$row[3]] = $row;
		}
		$total = QuickQuery("select found_rows()", $this->_readonlyDB);

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
			$result = Query($query, $this->_readonlyDB);
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
		$ordering[getSystemSetting("organizationfieldname","Organization")] = "org";
		foreach($fields as $field){
			$ordering[$field->name]= "rp." . $field->fieldnum;
		}
		return $ordering;
	}

}

?>