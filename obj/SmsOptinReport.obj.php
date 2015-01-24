<?

class SmsOptinReport extends ReportGenerator {

	var $total = 0;
	var $data = array();
	
	function generateQuery($hackPDF = false){
		$hassms = getSystemSetting("_hassms", false);

		$this->params = $this->reportinstance->getParameters();
		//$this->reporttype = $this->params['reporttype'];
		
		$this->query = "select 1"; // TODO call API
	}
	
	function fetchPage($pagestart, $max) {
		global $csApi;
		global $total;
		global $data;
		
		$apiResponse = $csApi->getSmsStatusReport($pagestart, $max);
		$total = $apiResponse->paging->total;
		
		// fill data array from query
		$data = array(); // array of rows with these columns
		// 0 = sms
		// 1 = status
		// 2 = scope local vs global
		// 3 = date
		// 4 = notes
		foreach ($apiResponse->smsStatus as $row) {
			$data[] = array($row->sms, $row->status, $row->scope, $row->lastUpdateMs, $row->notes);
		}
	}
	
	function runHtml() {
		global $total;
		global $data;
		
		$max = 100;
		$pagestart = $this->params['pagestart'];
		$this->fetchPage($pagestart, $max);

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
		showPageMenu($total, $pagestart, $max);

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

		showPageMenu($total, $pagestart, $max);
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
		$header = '"Phone Number","Status","Modified By","Modified Date","Notes"';
		
		if ($options) {
			$ok = fwrite($fp, $header . "\r\n");
			if (!$ok)
				return false;
				
		} else {
			echo $header;
			echo "\r\n";
		}
		
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

		// batch api request by 100 smsnumber, cannot load all 100k into memory
		global $total;
		global $data;
		
		$max = 10000;
		$pagestart = $this->params['pagestart'];
		do {
			$this->fetchPage($pagestart, $max);
			foreach ($data as $row) {
				$row[0] = fmt_phone($row, 0);
				$row[1] = fmt_smsstatus($row, 1);
				$row[2] = fmt_modifiedby($row, 2);
 				$row[3] = fmt_lastupdate_date($row, 3);
				if ($options) {
					$ok = fwrite($fp, '"' . implode('","', $row) . '"' . "\r\n");
					if (!$ok)
						return false;
				} else {
					echo '"' . implode('","', $row) . '"' . "\r\n";
				}
			}
			$pagestart += $max;
		} while ($pagestart < $total);

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
