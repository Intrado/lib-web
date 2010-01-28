<?

//TODO remove SQL_CALC_FOUND_ROWS, and use count(*) instead. with all the g field crap and whatnot, it's slowing it down

class ContactsReport extends ReportGenerator {

	function generateQuery(){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];

		$orderquery = getOrderSql($this->params);
		$rulesql = getRuleSql($this->params, "p", false);

		$userJoin = " and p.userid = '$USER->id' ";

		$usersql = $USER->userSQL("p");
		$phonequery="";
		$emailquery="";
		$personquery="";
		$peoplequery = "";
		if(isset($this->params['phone']) && $this->params['phone'] != ""){
			$peoplephonelist = QuickQueryList("select personid from phone ph where 1 and ph.phone like '%" . DBSafe($this->params['phone']) . "%' group by personid");
		}
		if(isset($this->params['email']) && $this->params['email'] != ""){
			$peopleemaillist = QuickQueryList("select personid from email e where 1 and e.email = '" . DBSafe($this->params['email']) . "'  group by personid");
		}
		if(isset($this->params['personid'])){
			$personquery = $this->params['personid'] ? " and p.pkey = '" . DBSafe($this->params['personid']) . "'" : "";
		}
		$fieldquery = generateFields("p");
		$gfieldquery = generateGFieldQuery("p.id");

		if(isset($peoplephonelist) && isset($peopleemaillist))
			$peoplelist = implode("','", array_intersect($peoplephonelist, $peopleemaillist));
		else if(isset($peoplephonelist))
			$peoplelist = implode("','", $peoplephonelist);
		else if(isset($peopleemaillist))
			$peoplelist = implode("','", $peopleemaillist);

		if(isset($peoplelist))
			$peoplequery = " and p.id in ('" . $peoplelist . "') ";

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
					$fieldquery
					$gfieldquery
					from " . getPersonSubquerySql($this->params) . " p
					left join address a on (a.personid = p.id)
					where not p.deleted
					and p.type='system'
					$peoplequery
					$personquery
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
				editlock
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
				editlock
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
				editlock
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
		$data = array();
		foreach($personlist as $personrow){
			if(!isset($destinationdata[$personrow[1]])){
				array_splice($personrow, 5, 0, array("","-None-","",""));
				$data[] = $personrow;
			} else {
				$displayed = false;
				foreach($destinationdata[$personrow[1]] as $destination){
					if($destination[1]!=""){
						array_splice($personrow, 5, 0, array($destination[2],$destination[1], $destination[4], $destination[5]));
						$data[] = $personrow;
						$displayed = true;
					}
				}

				if (!$displayed) {
					array_splice($personrow, 5, 0, array("","-None-","", ""));
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

		$titles = array("0" => "ID#",
						"2" => "First Name",
						"3" => "Last Name",
						"4" => "Address",
						"5" => "Sequence",
						"6" => "Destination");
		// index 7 is a flag to tell what type of destination
		// so set the title of starting f-field at appropriate place
		// append begins after index specified
		$titles = appendFieldTitles($titles, 8, $fieldlist, $activefields);

		$formatters = array("0" => "fmt_idmagnify",
							"5" => "fmt_destination_sequence",
							"6" => "fmt_editlocked_destination");
		
		//I think this is safe since it starts appending f03 right after other fields
		if (in_array(FieldMap::getLanguageField(),$activefields))
			$formatters[9] = "fmt_languagecode";

		startWindow("Search Results", "padding: 3px;");
		showPageMenu($total,$pagestart,$max);

		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($data, $titles, $formatters, array("5", "6"), 0);
		?>
			</table>
			<script langauge="javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?

		showPageMenu($total,$pagestart,$max);
		endWindow();
	}

	function runCSV(){

		$fields = FieldMap::getOptionalAuthorizedFieldMaps();
		$fieldlist = array();
		foreach($fields as $field){
			$fieldlist[$field->fieldnum] = $field->name;
		}
		$activefields = explode(",", isset($this->params['activefields']) ? $this->params['activefields'] : "");

		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-disposition: attachment; filename=report.csv");
		header("Content-type: application/vnd.ms-excel");

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		//generate the CSV header
		$header = '"ID#", "First Name", "Last Name", "Address"';

		foreach($activefields as $active){
			if(!$active) continue;
			$header .= ',"' . $fieldlist[$active] . '"';
		}
		echo $header;
		echo "\r\n";

		$result = Query($this->query, $this->_readonlyDB);

		while ($row = DBGetRow($result)) {

			$reportarray = array($row[0], $row[2], $row[3], $row[4]);

			$count=0;
			foreach($fieldlist as $index => $field){
				if(in_array($index, $activefields)){
					$reportarray[] = $row[5+$count];
				}
				$count++;
			}
			$phonelist = DBFindMany("Phone", "from phone where personid = '$row[1]'", false, false, $this->_readonlyDB);
			foreach($phonelist as $phone){
				$reportarray[] = $phone->phone;
			}
			$emaillist = DBFindMany("Email", "from email where personid = '$row[1]'", false, false, $this->_readonlyDB);
			foreach($emaillist as $email){
				$reportarray[] = $email->email;
			}
			echo '"' . implode('","', $reportarray) . '"' . "\r\n";

		}
	}

	function getReportSpecificParams(){
		return $params;
	}

	function setReportFile(){
		$this->reportfile = "Contactsreport.jasper";
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
