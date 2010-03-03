<?

//TODO remove SQL_CALC_FOUND_ROWS, and use count(*) instead. with all the g field crap and whatnot, it's slowing it down

class ContactChangeReport extends ReportGenerator {

	function generateQuery(){
		global $USER;
		$hassms = getSystemSetting("_hassms", false);

		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];

		$orderquery = getOrderSql($this->params);
		$rulesql = getRuleSql($this->params, "p", false);

		$userJoin = " and p.userid = '$USER->id' ";

		$usersql = $USER->userSQL("p");

		$reldate = "today";
		if(isset($this->params['reldate']))
			$reldate = $this->params['reldate'];
		list($startdate, $enddate) = getStartEndDate($reldate, $this->params);

		$peoplephonelist = QuickQueryList("select personid from phone where editlockdate >= ? and editlock=1", false, false, array(makeDateTime($startdate)));
		$peopleemaillist = QuickQueryList("select personid from email where editlockdate >= ? and editlock=1", false, false, array(makeDateTime($startdate)));
		if ($hassms) {
			$peoplesmslist = QuickQueryList("select personid from sms where editlockdate >= ? and editlock=1", false, false, array(makeDateTime($startdate)));
		} else {
			$peoplesmslist = array();
		}
		$peoplelist = array_merge($peoplephonelist, $peopleemaillist, $peoplesmslist);
		$peoplelist = array_unique($peoplelist);

		if (count($peoplelist) > 0) {
			$peoplelist = implode("','", $peoplelist);
			$peoplequery = " and p.id in ('" . $peoplelist . "') ";
		} else {
			$peoplequery = " and false ";
		}

		$personquery = "";
		$orgfieldquery = generateOrganizationFieldQuery("p.id");
		$fieldquery = generateFields("p");
		$gfieldquery = generateGFieldQuery("p.id");
		
		$this->query = "select SQL_CALC_FOUND_ROWS
					p.pkey as pkey,
					p.id as pid,
					p." . FieldMap::GetFirstNameField() . " as firstname,
					p." . FieldMap::GetLastNameField() . " as lastname,
					concat(
							coalesce(a.addr1,''), ' ',
							coalesce(a.addr2,''), ' ',
							coalesce(a.city,''), ' ',
							coalesce(a.state,''), ' ',
							coalesce(a.zip,'')
						) as address
					$orgfieldquery
					$fieldquery
					$gfieldquery
					from person p
					left join address a on (a.personid = p.id)
					left join language l on (l.code = p." . FieldMap::GetLanguageField() . ")
					where not p.deleted
					and p.type='system'
					$peoplequery
					$usersql
					$rulesql
					$orderquery
					";
	}

	function runHtml(){
		$max = 100;
		$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
		$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fields = $ffields + $gfields;
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}

		$activefields = explode(",", $this->params['activefields']);
		$query = $this->query;

		$pagestart = isset($this->params['pagestart']) ? $this->params['pagestart'] : 0;
		$query .= "limit $pagestart, $max";

		$result = Query($query, $this->_readonlyDB);
		$total = QuickQuery("select found_rows()", $this->_readonlyDB);

		//fetch data with main query and populate arrays using personid as the key
		$personlist = array();
		$personidlist = array();
		while($row = DBGetRow($result)){
			$personlist[$row[1]] = $row;
			$personidlist[] = $row[1];
		}

		// select static value "ordering" in order to order results as phone, email, sms
		$phoneemailquery =
			"(select personid as pid,
				phone as destination,
				sequence as sequence,
				'1' as ordering,
				'phone' as type,
				editlock,
				editlockdate
				from phone ph
				where
				personid in ('" . implode("','",$personidlist) . "')
				)
			union
			(select personid as pid2,
				email as destination,
				sequence as sequence,
				'2' as ordering,
				'email' as type,
				editlock,
				editlockdate
				from email
				where
				personid in ('" . implode("','",$personidlist) . "')
				)";
		$smsquery = " union
			(select personid as pid3,
				sms as destination,
				sequence as sequence,
				'3' as ordering,
				'sms' as type,
				editlock,
				editlockdate
				from sms
				where
				personid in ('" . implode("','",$personidlist) . "')
				) ";
		$extraquery ="order by pid, ordering, sequence";

		//Don't display SMS if no sms in system
		if(getSystemSetting("_hassms", false)){
			$phoneemailsmsquery = $phoneemailquery . $smsquery . $extraquery;
		} else {
			$phoneemailsmsquery = $phoneemailquery . $extraquery;
		}
		$result = Query($phoneemailsmsquery, $this->_readonlyDB);
		$destinationdata = array();
		while($row = DBGetRow($result)){
			if(!isset($destinationdata[$row[0]])){
				$destinationdata[$row[0]] = array();
			}
			$destinationdata[$row[0]][] = $row;
		}


		// personrow index 4 is address
		// personrow index 5 is the start of f-fields
		// personrow insert all destinations before f-fields
		// array_splice inserts data after 2nd argument's array index
		// destination index 1 is phone/email/sms
		// destination index 2 is sequence
		// destination index 4 is type
		// destination index 5 is editlock
		// destination index 6 is editlockdate
		$data = array();
		foreach($personlist as $personrow){
			if(!isset($destinationdata[$personrow[1]])){
				array_splice($personrow, 5, 0, array("","-None-","","",""));
				$data[] = $personrow;
			} else {
				$displayed = false;
				foreach($destinationdata[$personrow[1]] as $destination){
					if($destination[1]!=""){
						array_splice($personrow, 5, 0, array($destination[2],$destination[1], $destination[4], $destination[5], $destination[6]));
						$data[] = $personrow;
						$displayed = true;
					}
				}

				if (!$displayed) {
					array_splice($personrow, 5, 0, array("","-None-","","",""));
					$data[] = $personrow;
				}
			}
		}

		//Display Formatter
		//type at index 7
		function fmt_destination_sequence($row, $index){
			if($row[$index] != "" || $row[$index] != false){
				return destination_label($row[7], $row[$index]);
			} else {
				return "";
			}
		}

		//index 8 should be the editlock flag;
		function fmt_editlocked_destination($row, $index){
			$output = fmt_destination($row, $index);
			if($row[8] == 1){
				$output = "<img src='img/padlock.gif'>&nbsp;" . $output;
			}
			return $output;
		}
		
		// index 9 should be the editlockdate
		function fmt_editlock_date($row, $index) {
			return $row[9];
		}
		
		// index 10 is organization
		function fmt_organization($row, $index) {
			return $row[10];
		}

		$titles = array("0" => "ID#",
						"2" => "First Name",
						"3" => "Last Name",
						"4" => "Address",
						"5" => "Sequence",
						"6" => "Destination",
						"9" => "Modified Date",
						"10" => "Current Org");
		// index 7 is a flag to tell what type of destination
		// index 8 editlock
		// so set the title of starting f-field at appropriate place
		// append begins after index specified
		$titles = appendFieldTitles($titles, 10, $fieldlist, $activefields);

		$formatters = array("0" => "fmt_idmagnify",
							"5" => "fmt_destination_sequence",
							"6" => "fmt_editlocked_destination",
							"9" => "fmt_editlock_date",
							"10" => "fmt_organization");
		
		if (in_array(FieldMap::getLanguageField(),$activefields))
			$formatters[11] = "fmt_languagecode";

		startWindow("Search Results", "padding: 3px;");
		showPageMenu($total,$pagestart,$max);

		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($data, $titles, $formatters, array("5", "6", "9"), 0);
		?>
			</table>
			<script langauge="javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?

		showPageMenu($total,$pagestart,$max);
		endWindow();
	}

	function runCSV($options = false){

		$ffields = FieldMap::getOptionalAuthorizedFieldMapsLike('f');
		$gfields = FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fields = $ffields + $gfields;
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", isset($this->params['activefields']) ? $this->params['activefields'] : "");
		$languageField = FieldMap::getLanguageField();
		
		$maxphones = getSystemSetting('maxphones', '1');
		$maxemails = getSystemSetting('maxemails', '1');
		$maxsms = getSystemSetting('maxsms', '1');
		$hassms = getSystemSetting('_hassms', false);
		
		if ($options) {
			$fp = fopen($options['filename'], "w");
			if (!$fp)
				return false;
		} else {
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.csv");
			header("Content-type: application/vnd.ms-excel");
		}

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
		
		//generate the CSV header
		$header = '"ID#","First Name","Last Name","Address","Current Org"';

		foreach($activefields as $active){
			if(!$active) continue;
			$header .= ',"' . $fieldlist[$active] . '"';
		}
		
		for ($i=0; $i<$maxphones; $i++) {
			$header .= ',"Phone '.($i +1).'","Modified Ph'.($i+1).'"';
		}
		for ($i=0; $i<$maxemails; $i++) {
			$header .= ',"Email '.($i +1).'","Modified Email'.($i+1).'"';
		}
		
		if ($hassms) {
			for ($i=0; $i<$maxsms; $i++) {
				$header .= ',"SMS '.($i +1).'","Modified SMS'.($i+1).'"';
			}
		}
		
		if ($options) {
			$ok = fwrite($fp, $header . "\r\n");
			if (!$ok)
				return false;
				
		} else {
			echo $header;
			echo "\r\n";
		}


		// batch query by 100 persons, cannot load all 100k into memory
		$batchsize = 100;
		$pagestart = 0;
		$total = 1;
		do {
		$query = $this->query;
		$query .= " limit $pagestart, $batchsize";
		$result = Query($query, $this->_readonlyDB);
		$total = QuickQuery("select found_rows()", $this->_readonlyDB);
		
		//fetch data with main query and populate arrays using personid as the key
		$personlist = array();
		$personidlist = array();
		while ($row = DBGetRow($result)) {
			$personlist[$row[1]] = $row;
			$personidlist[] = $row[1];
		}
		$pagestart += count($personidlist);

		// for each person, get maxphones, maxemails, maxsms to handle any missing records
		$query = "select p.id,";
		for ($seq=0; $seq<$maxphones; $seq++) {
			$query .= "p$seq.phone, p$seq.editlockdate,";
		}
		for ($seq=0; $seq<$maxemails; $seq++) {
			$query .= "e$seq.email, e$seq.editlockdate,";
		}
		if ($hassms) {
			for ($seq=0; $seq<$maxsms; $seq++) {
				$query .= "s$seq.sms, s$seq.editlockdate,";
			}
		}
		$query = substr($query, 0, strlen($query)-1); // chop trailing comma
		$query .= " from person p ";
		for ($seq=0; $seq<$maxphones; $seq++) {
			$query .= "left join phone p$seq on (p$seq.personid=p.id and p$seq.sequence=$seq) ";
		}
		for ($seq=0; $seq<$maxemails; $seq++) {
			$query .= "left join email e$seq on (e$seq.personid=p.id and e$seq.sequence=$seq) ";
		}
		if ($hassms) {
			for ($seq=0; $seq<$maxsms; $seq++) {
				$query .= "left join sms s$seq on (s$seq.personid=p.id and s$seq.sequence=$seq) ";
			}
		}
		$query .= "where p.id in ('" . implode("','",$personidlist) . "')";
		
		$result = Query($query, $this->_readonlyDB);
		// store results indexed by person id $row[0]
		$destinationdata = array();
		while($row = DBGetRow($result)){
			$destinationdata[$row[0]] = $row;
		}

		// for each person, write a row to the file
		foreach ($personlist as $row) {
			// id, name, address, org
			$reportarray = array($row[0], $row[2], $row[3], $row[4], $row[5]);

			// optional F/G fields
			$count=0;
			foreach($fieldlist as $index => $field){
				if(in_array($index, $activefields)){
					if ($languageField == $index) {
						$reportarray[] = Language::getName($row[6+$count]);
					} else {
						$reportarray[] = $row[6+$count];
					}
				}
				$count++;
			}

			// if there is any phone/email/sms for this person, write it out, else move to next person
			if (isset($destinationdata[$row[1]])) {
				$persondestinationdata = $destinationdata[$row[1]];
				array_shift($persondestinationdata); // strip personid [0]
				foreach ($persondestinationdata as $val) {
					$reportarray[] = $val;
				}
			}
			
			if ($options) {
				$ok = fwrite($fp, '"' . implode('","', $reportarray) . '"' . "\r\n");
				if (!$ok)
					return false;
			} else {
				echo '"' . implode('","', $reportarray) . '"' . "\r\n";
			}

		}
		} while ($pagestart < $total);
		
		if ($options) {
			return fclose($fp);
		}
	}

	function getReportSpecificParams(){
		return $params;
	}

	function setReportFile(){
		$this->reportfile = "Contactchangereport.jasper"; // TODO
	}

	static function getOrdering(){
		global $USER;
		$fields = FieldMap::getAuthorizedFieldMaps();

		$ordering = array();
		$ordering["ID#"] = "p.pkey";

		foreach($fields as $field){
			$ordering[$field->name]= "p." . $field->fieldnum;
		}
		$ordering["Address"] = "address";
		return $ordering;
	}
}

?>
