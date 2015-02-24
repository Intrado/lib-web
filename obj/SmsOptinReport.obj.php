<?

class SmsOptinReport extends ReportGenerator {

	function generateQuery($hackPDF = false){
		$hassms = getSystemSetting("_hassms", false);

		$this->params = $this->reportinstance->getParameters();
		//$this->reporttype = $this->params['reporttype'];
		
		// TODO: this query is known to be expensive, because the UNION causes a big temp table.
		$this->query = "select sql_calc_found_rows * from (
    (
	select asb.sms, asb.status, 'global' as modifiedby, unix_timestamp(asb.lastupdate)*1000 as modifieddate, asb.notes
	from aspsmsblock as asb 
	join person as p on (p.id = asb.personid)
	where (p.type in ('system', 'guardianauto') and asb.editlock = 0 and not p.deleted)
    )
    union
    (
	select s.sms, 'block', bu.login, unix_timestamp(b.createdate)*1000, b.description
	from sms as s
	inner join blockeddestination as b
	  on (b.type = 'sms' and b.destination = s.sms)
	inner join user as bu
	  on (b.userid = bu.id)
	inner join person as p
	  on (s.personid = p.id)
	where not p.deleted
    )) t
    order by sms "; // expect a limit clause when displaying by paging
	}

	protected function fetchData($pageStart, $pageSize) {
		QuickQuery("set session max_heap_table_size=1024*1024*1024");
		QuickQuery("set session tmp_table_size=1024*1024*1024");

		$pageStart = (int) $pageStart;
		$pageSize = (int) $pageSize;
		$limit = " limit $pageStart, $pageSize";
		$result = Query($this->query . $limit, $this->_readonlyDB);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}

		$query = "select found_rows()";
		$total = QuickQuery($query, $this->_readonlyDB);
		return(array($data, $total));
	}
	
	function runHtml() {
		$pageSize = 100;
		$pageStart = isset($this->params["pagestart"]) ? (int) $this->params["pagestart"] : 0;

		list($data, $total) = $this->fetchData($pageStart, $pageSize);

		$titles = array("0" => "Phone Number",
				"1" => "Status",
				"2" => "Modified By",
				"3" => "Modified Date",
				"4" => "Notes"
		);

		//Display Formatters
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
		
		$formatters = array("0" => "fmt_phone",
				    "1" => "fmt_smsstatus",
				    "2" => "fmt_modifiedby",
				    "3" => "fmt_lastupdate_date"
		);
		
		///////////////
		startWindow(_L("Search Results"), "padding: 3px;");
		showPageMenu($total, $pageStart, $pageSize);

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

		showPageMenu($total, $pageStart, $pageSize);
		endWindow();
	}

	function runCSV($options = false){
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
		$headerfields = array("Phone Number","Status","Modified By","Modified Date","Notes");
		$header = '"' . implode('","', $headerfields) . '"';
		
		$ok = fwrite($fp, $header . "\r\n");
		if (!$ok)
			return false;
		
		//Display Formatter
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

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		// batch api request by 10000 smsnumber, cannot load all 100k into memory
		
		$pageSize = 10000;
		$pageStart = array_key_exists('pagestart', $this->params) ? $this->params['pagestart'] : 0;
		do {
			list($data, $total) = $this->fetchData($pageStart, $pageSize);
			foreach ($data as $row) {
				$row[0] = fmt_phone($row, 0);
				$row[1] = fmt_smsstatus($row, 1);
				$row[2] = fmt_modifiedby($row, 2);
 				$row[3] = fmt_lastupdate_date($row, 3);
				$ok = fwrite($fp, '"' . implode('","', $row) . '"' . "\r\n");
				if (!$ok)
					return false;
			}
			$pageStart += $pageSize;
		} while ($pageStart < $total);

		if ($options) {
			return fclose($fp);
		}
	}

	function getReportSpecificParams(){
		return $params;
	}

	function setReportFile(){
		$this->reportfile = "SmsOptin.jasper"; // TODO
	}

}

?>
