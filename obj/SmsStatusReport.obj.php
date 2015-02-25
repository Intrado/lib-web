<?

class SmsStatusReport extends ReportGenerator {

	public $reportType = "html";
	public $queryArgs = false;

	function generateQuery($hackPDF = false){
		$hassms = getSystemSetting("_hassms", false);

		$this->params = $this->reportinstance->getParameters();
		$this->reportType = $this->params["reporttype"];

		$selectList0 = "*";
		$selectList1 = "asb.sms, p.pkey, asb.status, 'global' as modifiedby, unix_timestamp(asb.lastupdate)*1000 as modifieddate, asb.notes";
		$selectList2 = "s.sms, p.pkey, 'block', bu.login, unix_timestamp(b.createdate)*1000, b.description";
		$whereSms = "";
		$groupBy = "";
		$orderBy = "";

		switch ($this->reportType) {
		case "csv":
			$orderBy = "order by sms";
			break;
		case "summary":
			$selectList0 = "status, sum(`Count`) as `Count`";
			$selectList1 = "status, count(*) as `Count`";
			$selectList2 = "'block' as status, count(*) as `Count`";
			$groupBy = "group by status";
			$orderBy = "";
			break;
		case "view":
			$whereSms = "and sms = ?";
			// need two parameters to fill two placeholders
			$this->queryArgs[] = $this->params["sms"];
			$this->queryArgs[] = $this->params["sms"];
			break;
		case "paged":
			$selectList0 = "sql_calc_found_rows *";
			$orderBy = "order by sms";
			break;
		default:
			break;
		}

		$this->query =
"select $selectList0 from (
(
    select $selectList1
    from aspsmsblock as asb
    join person as p on (p.id = asb.personid)
    where (p.type in ('system', 'guardianauto') and asb.editlock = 0 and not p.deleted) $whereSms
    $groupBy
)
union
(
    select $selectList2
    from sms as s
    inner join blockeddestination as b on (b.type = 'sms' and b.destination = s.sms)
    inner join user as bu on (b.userid = bu.id)
    inner join person as p on (s.personid = p.id)
    where not p.deleted $whereSms
    $groupBy
)) t
$groupBy $orderBy ";
    }

	function runHtml() {

		if ($this->reportType === "html") {
			return;
		}

		$data = array();
		$total = 0;
		$pageSize = null;
		$pageStart = null;

		function fmt_lastupdate_date($row, $index) {
			return date("M j, Y g:i a", $row[$index]/1000);
		}
		
		function fmt_modifiedby($row, $index) {
			if ($row[$index] === "global")
				return "System";
			else
				return $row[$index];
		}
		
		function fmt_smsstatus($row, $index) {
			switch ($row[$index]) {
				case "new":
				case "pendingoptin":
					return "Pending Opt-In";
				case "block":
					return "Blocked";
				case "optin":
					return "Opted In";
			}
		}

		if ($this->reportType == "paged") {
			$pageSize = 100;
			$pageStart = isset($this->params["pagestart"]) ? (int) $this->params["pagestart"] : 0;
			$limit = "limit $pageStart, $pageSize";
		} else {
			$limit = "";
		}

		$result = Query($this->query . $limit, $this->_readonlyDB, $this->queryArgs);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}

		if ($this->reportType == "paged") {
			$total = QuickQuery("select found_rows()", $this->_readonlyDB);
		}

		switch ($this->reportType) {
		case "summary":
			$titles = array("0" => "Status",
					"1" => "Count"
			);
			$formatters = array("0" => "fmt_smsstatus");
			break;
		case "view":
		case "paged":
			$titles = array("0" => "Phone Number",
					"1" => "Person Key",
					"2" => "Status",
					"3" => "Modified By",
					"4" => "Modified Date",
					"5" => "Notes"
			);
			$formatters = array("0" => "fmt_phone",
					    "2" => "fmt_smsstatus",
					    "3" => "fmt_modifiedby",
					    "4" => "fmt_lastupdate_date"
			);
			break;
		}

		switch ($this->reportType) {
		case "summary":
			startWindow(_L("Summary SMS Status Results"), "padding: 3px;");
			break;
		case "view":
			startWindow(_L("SMS Search Results"), "padding: 3px;");
			break;
		case "paged":
			startWindow(_L("Full SMS Status Results"), "padding: 3px;");
			showPageMenu($total, $pageStart, $pageSize);
			break;
		}
		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($data, $titles, $formatters);
		?>
			</table>
			<script type="text/javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?
		if ($this->reportType == "paged") {
			showPageMenu($total, $pageStart, $pageSize);
		}
		endWindow();
	}

	function runCSV($options = false){
		function fmt_lastupdate_date($row, $index) {
			return date("M j, Y g:i a", $row[$index]/1000);
		}
		
		function fmt_modifiedby($row, $index) {
			if ($row[$index] === "global")
				return "System";
			else
				return $row[$index];
		}
		
		function fmt_smsstatus($row, $index) {
			switch ($row[$index]) {
				case "new":
				case "pendingoptin":
					return "Pending Opt-In";
				case "block":
					return "Blocked";
				case "optin":
					return "Opted In";
			}
		}

		if ($options) {
			if (!$options["filename"])
				return false;
			$outputfile = $options["filename"];
		} else {
			$outputfile = "php://output";
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.csv");
			header("Content-type: application/vnd.ms-excel");
		}
		$fp = fopen($outputfile, "w");
		if (!$fp)
			return false;

		//generate the CSV header
		$headerfields = array("Phone Number","Person Pkey","Status","Modified By","Modified Date","Notes");
		$headerfields = array_map(function ($value) { return '"'.$value.'"'; }, $headerfields);
		$header = implode(",", $headerfields);

		$ok = fwrite($fp, $header . "\r\n");
		if (!$ok)
			return false;

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		// batch api request by 10000 smsnumber, cannot load all 100k into memory
		
		$pageSize = 10000;
		$pageStart = 0;
		$n = 0;
		do {

			$limit = "limit $pageStart, $pageSize";
			$result = Query($this->query . $limit, $this->_readonlyDB, $this->queryArgs);
			$n = 0;
			while ($row = DBGetRow($result)) {
				$n++;
				$row[0] = fmt_phone($row, 0);
				// $row[1] is Person Pkey
				$row[2] = fmt_smsstatus($row, 2);
				$row[3] = fmt_modifiedby($row, 3);
				$row[4] = fmt_lastupdate_date($row, 4);
				$row = array_map(function ($value) { return '"'.$value.'"'; }, $row);
				$ok = fwrite($fp, implode(",", $row) . "\r\n");
				if (!$ok)
					return false;
			}
			$pageStart += $pageSize;
		} while ($n == $pageSize);

		if ($options) {
			return fclose($fp);
		}
	}

	function getReportSpecificParams(){
		return $params;
	}

	function setReportFile(){
		$this->reportfile = "SmsStatus.jasper"; // TODO
	}

}

?>
