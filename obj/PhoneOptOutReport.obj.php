<?

class PhoneOptOutReport extends ReportGenerator {

	const DEFAULT_PAGE_SIZE = 100;

	private $titles = array(
		"0" => "ID#",
		"1" => "First Name",
		"2" => "Last Name",
		"3" => "Phone Number",
		"4" => "Count"
	);

	private $formatters = array(
		"3" => "fmt_phone"
	);

	function generateQuery($hackPDF = false) {
		global $USER;

		$this->params = $this->reportinstance->getParameters();

		$orgIds = null;
		if (isset($this->params['organizationids'])) {
			$orgIds = $this->params['organizationids'];
		} 

		$this->reporttype = $this->params['reporttype'];

		if (! isset($this->params['order1'])) {
			$this->params['order1'] = 'pkey';
		}
		$orderquery = getOrderSql($this->params);

		$orgJoin = $USER->getPersonAssociationJoinSql($orgIds, array(), "p");

		$reldate = "today";
		if (isset($this->params['reldate'])) {
			$reldate = $this->params['reldate'];
		}
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

		$this->query = "select SQL_CALC_FOUND_ROWS DISTINCT
					p.pkey as pkey,
					p." . FieldMap::GetFirstNameField() . " as firstname,
					p." . FieldMap::GetLastNameField() . " as lastname,
					rpo.phone, 
					rpo.numRequests
					from person p
					join ($phonesQuery) as rpo on p.id = rpo.personId
					$orgJoin
					$orderquery
					";
	}

	function runHtml() {
		$pageSize = self::DEFAULT_PAGE_SIZE;

		$query = $this->query;

		$pageStart = isset($this->params['pagestart']) ? $this->params['pagestart'] : 0;
		$query .= "limit $pageStart, $pageSize";

		$result = Query($query, $this->_readonlyDB);

		$personlist = array();
		while ($row = DBGetRow($result)) {
			$personlist[] = $row;
		}

		$query = "select found_rows()";
		$total = QuickQuery($query, $this->_readonlyDB);

		startWindow("Search Results", "padding: 3px;");
		showPageMenu($total,$pageStart,$pageSize);

		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($personlist, $this->titles, $this->formatters);
		?>
			</table>
			<script type="text/javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?

		if (empty($personlist)) {
			echo _L("No Phone Opt-Out records match search criteria", getJobTitle());
		}

		showPageMenu($total,$pageStart,$pageSize);
		endWindow();
	}

	function runCSV($options = false) {
		if ($options) {
			if (!$options["filename"]) {
				return false;
			}
			$outputfile = $options["filename"];
		} else {
			$outputfile = "php://output";
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.csv");
			header("Content-type: application/vnd.ms-excel");
		}
		$fp = fopen($outputfile, "w");
		if (!$fp) {
			return false;
		}

		$headerfields = array_map(function ($value) { return '"' . $value . '"'; }, $this->titles);
		$header = implode(",", $headerfields);
		$ok = fwrite($fp, $header . "\r\n");
		if (!$ok) {
			return false;
		}

		$this->_readonlyDB->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		$result = Query($this->query, $this->_readonlyDB);

		while ($row = DBGetRow($result)) {
			foreach ($this->formatters as $index => $formatter) {
				$row[$index] = $formatter($row, $index);
			}
			$row = array_map(function ($value) {
				return '"' . $value . '"';
			}, $row);
			$ok = fwrite($fp, implode(",", $row) . "\r\n");
			if (!$ok) {
				return false;
			}
		}

		if ($options) {
			return fclose($fp);
		}
	}

	function setReportFile() {
		$this->reportfile = "Phoneoptoutreport.jasper"; // TODO
	}

	static function getOrdering() {
		$fields = FieldMap::getAuthorizedFieldMaps();

		$ordering = array();
		$ordering["ID#"] = "p.pkey";
		
		$requiredFields= array('f01','f02');
		
		foreach ($fields as $field) {
			if (in_array($field->fieldnum, $requiredFields)) {
				$ordering[$field->name]= "p." . $field->fieldnum;
			}
		}
		
		return $ordering;
	}
}
