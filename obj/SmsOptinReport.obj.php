<?

class SmsOptinReport extends ReportGenerator {

	function generateQuery($hackPDF = false){
		$hassms = getSystemSetting("_hassms", false);

		$this->params = $this->reportinstance->getParameters();
		//$this->reporttype = $this->params['reporttype'];
		
		$this->query = "select 1"; // TODO call API
	}

	function runHtml(){
		global $csApi;
		$max = 100;
		$pagestart = $this->params['pagestart'];
		
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

		//Display Formatter
		// index 3 should be the lastupdate date
		function fmt_lastupdate_date($row, $index) {
			return date("M j, Y g:i a",$row[$index]/1000);
		}
		
		//TODO formatters
		
		$titles = array("0" => "SMS",
						"1" => "Status",
						"2" => "Scope",
						"3" => "Last Update",
						"4" => "Notes"
					);

		$formatters = array("0" => "fmt_phone",
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
		// TODO call API, will it return file or data?
		
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
		$header = '"SMS","Status","Last Update","Notes"';

		
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
