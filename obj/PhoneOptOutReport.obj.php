<?

class PhoneOptOutReport extends ReportGenerator {

	const DEFAULT_PAGE_SIZE = 100;

	private $titles = array(
		"0" => "ID#",
		"1" => "First Name",
		"2" => "Last Name",
		"3" => "Phone Number",
		"4" => "Reason",
		"5" => "Count", 
		"6" => "Organization"
	);

	private $formatters = array(
		"3" => "fmt_phone"
	);

	function generateQuery($hackPDF = false) {
		global $USER;
		
		$this->params = $this->reportinstance->getParameters();

		$this->titles["6"] = getSystemSetting("organizationfieldname"); 

		$autoBlockEnabled = getSystemSetting("_enableautoblock") ? true: false;

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
		

		if ( $autoBlockEnabled ) { 

			// remove titles entry 5 (Count) and shift 6 to entry 5
			array_splice($this->titles, 5, 1);

			$this->query = 
						"select SQL_CALC_FOUND_ROWS DISTINCT
						p.pkey as pkey,
						p." . FieldMap::GetFirstNameField() . " as firstname,
						p." . FieldMap::GetLastNameField() . " as lastname,
						rpo.phone, 

						case
							when rpo.optOutCode = '1' then CONCAT('Recipient Unsubscribed: ', jt.name)
							when rpo.optOutCode = '2' then 'Recipient Unsubscribed: All but Emergency Priority'
							when rpo.optOutCode = '3' then 'Recipient Blocked'
						end as optOutCodeReason,

						org.orgkey
						from person p

						join (
							select personId,
							phone,
							jobTypeId,
							optOutCode
							from reportphoneoptout
							where lastUpdateMs >= " . ($startdate  * 1000) . "
							and lastUpdateMs < " . (($enddate+86400) * 1000) . "
						) as rpo on p.id = rpo.personId

						left join jobtype as jt on jt.id = rpo.jobTypeId
						left join personassociation passoc on (passoc.type = 'organization' and passoc.personid = p.id)
						left join organization org on org.id = passoc.organizationid
						$orgJoin
						$orderquery
						";

		} else {
			$this->query = 
						"select SQL_CALC_FOUND_ROWS DISTINCT
						p.pkey as pkey,
						p." . FieldMap::GetFirstNameField() . " as firstname,
						p." . FieldMap::GetLastNameField() . " as lastname,
						rpo.phone, 

						case
							when rpo.optOutCode = '1' then CONCAT('Unsubscribe Requested: ', jt.name)
							when rpo.optOutCode = '2' then 'Unsubscribe requested: All but Emergency Priority'
							when rpo.optOutCode = '3' then 'Block requested by recipient'
						end as optOutCodeReason,

						rpo.numRequests,
						org.orgkey
						from person p

						join (
							select personId,
							phone,
							count(*) as numRequests,
							max(lastUpdateMs) as lastUpdateMs,
							jobTypeId,
							optOutCode
							from reportphoneoptout
							where lastUpdateMs >= " . ($startdate  * 1000) . "
							and lastUpdateMs < " . (($enddate+86400) * 1000) . "
							group by personId, phone, optOutCode, jobTypeId
						) as rpo on p.id = rpo.personId

						left join jobtype as jt on jt.id = rpo.jobTypeId
						left join personassociation passoc on (passoc.type = 'organization' and passoc.personid = p.id)
						left join organization org on org.id = passoc.organizationid
						$orgJoin
						$orderquery
						";
		}
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
		$ordering["Organization"] = "org.orgkey";
		
		$requiredFields= array('f01','f02');
		
		foreach ($fields as $field) {
			if (in_array($field->fieldnum, $requiredFields)) {
				$ordering[$field->name]= "p." . $field->fieldnum;
			}
		}
		
		return $ordering;
	}
}
