<?

//TODO remove SQL_CALC_FOUND_ROWS, and use count(*) instead. with all the g field crap and whatnot, it's slowing it down

class PhoneOptOutReport extends ReportGenerator {
	
	function generateQuery($hackPDF = false) {
		global $USER;
		
		$orgIds = null;
		if(isset($this->params['organizationids'])) {
			$orgIds = $this->params['organizationids'];
		} 
		
		$this->params = $this->reportinstance->getParameters();
		
		$this->reporttype = $this->params['reporttype'];

		if(! isset($this->params['order1'])) {
			$this->params['order1'] = 'pkey';
		}
		$orderquery = getOrderSql($this->params);
		
		if(is_array($orgIds)) {
			$flippedOrgs = array_flip($orgIds);

			$filteredOrgs = $USER->filterOrgs($flippedOrgs);

			$orgs = array_flip($filteredOrgs);
		}
		
		$orgsql = '';
		// if user has no organizations then default to empty result set from query.
		if(count($orgIds) > 0 && count($orgs) === 0) {
			$orgsql = "where 0";
		} else if (isset($orgIds) && count($orgs)) {
			$orgsql = "where pa.organizationid in ('". implode("','", $orgs) ."')";
		} 
		
		$reldate = "today";
		if(isset($this->params['reldate']))
			$reldate = $this->params['reldate'];
		list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
		
		$phonesQuery = "select personId,
					phone,
					count(*) as numRequests,
					max(lastUpdateMs) as lastUpdateMs
					from reportphoneoptout
					where lastUpdateMs >= " . ($startdate  * 1000) . "
					and lastUpdateMs < " . (($enddate+86400) * 1000) . "
					group by personId, phone
					";

		$this->query = "select SQL_CALC_FOUND_ROWS distinct
					p.pkey as pkey,
					p." . FieldMap::GetFirstNameField() . " as firstname,
					p." . FieldMap::GetLastNameField() . " as lastname,
					rpo.phone, 
					rpo.numRequests
					from person p
					join ($phonesQuery) as rpo on p.id = rpo.personId
					left join personassociation pa on (pa.personid = p.id)
					$orgsql
					$orderquery
					";

	}

	function runHtml(){
		$max = 100;
		
		$query = $this->query;

		$pagestart = isset($this->params['pagestart']) ? $this->params['pagestart'] : 0;
		$query .= "limit $pagestart, $max";

		$result = Query($query, $this->_readonlyDB);
		
		$total = QuickQuery("select found_rows()", $this->_readonlyDB);
		//fetch data with main query and populate arrays using personid as the key
		$personlist = array();
		$personidlist = array();
		
		
		while($row = DBGetRow($result)){
			// format the phone number for display AS a phone number
			$row[3] = Phone::format($row[3]);
			
			$personlist[] = $row;
			$personidlist[] = $row[1];
		}
		
		
		// personrow index 0 is pKey
		// personrow index 1 is First Name
		// personrow index 2 is Last Name
		// personrow index 3 is Phone
		// personrow index 4 is Count
		
		$titles = array("0" => "ID#",
						"1" => "First Name",
						"2" => "Last Name",
						"3" => "Phone Number",
						"4" => "Count");
		
		startWindow("Search Results", "padding: 3px;");
		showPageMenu($total,$pagestart,$max);

		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($personlist, $titles);
		?>
			</table>
			<script type="text/javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?

		showPageMenu($total,$pagestart,$max);
		endWindow();
	}

	function runCSV($options = false){
		
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
		
		//generate the CSV header
		$header = '"ID#","First Name","Last Name","Phone","Count"';
		
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
			$personlist[$row[0]] = $row;
			$personidlist[] = $row[0];
		}
		$pagestart += count($personidlist);
		
		// store results indexed by person id $row[0]
		$destinationdata = array();
		while($row = DBGetRow($result)){
			$destinationdata[$row[0]] = $row;
		}

		// for each person, write a row to the file
		foreach ($personlist as $row) {
			
			// [0] pKey (ID#)
			// [1] First Name
			// [2] Last Name
			// [3] Phone Number
			// [4] Count
			
			$reportarray = array($row[0], $row[1], $row[2], $row[3], $row[4]);
			
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

	function getReportSpecificParams() {
		return $params;
	}

	function setReportFile(){
		$this->reportfile = "Phoneoptoutreport.jasper"; // TODO
	}

	static function getOrdering() {
		global $USER;
		$fields = FieldMap::getAuthorizedFieldMaps();

		$ordering = array();
		$ordering["ID#"] = "p.pkey";
		
		$requiredFields= array('f01','f02');
		
		foreach($fields as $field){
			if(in_array($field->fieldnum, $requiredFields)) {
				$ordering[$field->name]= "p." . $field->fieldnum;
			}
		}
		
		return $ordering;
	}
}

?>
