<?

require_once('ConsentReportData.obj.php');

class ConsentStatusReportGenerator extends ReportGenerator {

	public $reportType = "html";
	public $queryArgs = false;
	
	public $consentReportData;

	private $basicReportTitles = array(
		"pkey" => "Unique ID",
		"first_name" => "First Name",
		"last_name" => "Last Name",
		"phone" => "Phone",
		"consent" => "Consent",
		"timestamp" => "Last Updated"
	);
	function __construct() {
		parent::__construct();

		$this->consentReportData = new ConsentReportData();
	}

	function generateQuery( $hackPDF = false ) {
		
		$this->params = $this->reportinstance->getParameters();
		$this->reportType = $this->params["reporttype"];

		// make sure we don't query for an empty phone string but still allow the user to send such a request
		if( $this->params['reporttype'] === 'singlePhone' ) {
			if( trim($this->params['phone']) !== '' ) {
				$this->query = $this->consentReportData->generateGetContactsQuery( $this->params['phone'] );
			} 
		} 

		// otherwise pull all results
		else {
			$this->query = $this->consentReportData->generateGetContactsQuery();
		}
	}

	function runHtml() {
		if ($this->reportType === "html") {
			return;
		}

		$data = array();
		$total = 0;
		$pageSize = 100;
		$pageStart = null;

		if ($this->reportType == "view") {
			$pageStart = isset($this->params["pagestart"]) ? (int) $this->params["pagestart"] : 0;
			$limit = "limit $pageStart, $pageSize";
		} else {
			$limit = "";
		}

		// query to fetch contacts for this customer.
		$queryResults = QuickQueryMultiRow($this->query . $limit, true, $this->_readonlyDB, $this->queryArgs);

		if ($this->reportType == "view") {
			$totalQuery = $this->consentReportData->getContactsCountQuery();
			$total = QuickQuery($totalQuery, $this->_readonlyDB);
		}

		$consentData = $this->consentReportData->fetchConsentFromContacts( $queryResults );
		$data = $this->consentReportData->mergeContactsWithConsent( $queryResults, $consentData );

		switch ($this->reportType) {
		case "summary":

			$labeledRowsArray = array();

			// divide results by status
			foreach ($data as $row) {
				if( ! isset( $labeledRowsArray[ $row['consent'] ] ) ) {
					$labeledRowsArray[ $row['consent'] ] = array();
				}

				$labeledRowsArray[ $row['consent'] ][] = $row;
			}

			$data = array(
				array(
					"status" => "Unknown",
					"count" => count( $labeledRowsArray["unknown"] )
				),
				array(
					"status" => "Pending",
					"count" => count( $labeledRowsArray["pending"] )
				),
				array(
					"status" => "Yes",
					"count" => count( $labeledRowsArray["yes"] )
				),
				array(
					"status" => "No",
					"count" => count( $labeledRowsArray["no"] )
				)
			);

			$titles = array(
				"status" => "Status",
				"count" => "Count"
			);
			$formatters = array("Status" => "fmt_smsstatus");
			
			break;

		case "singlePhone":
		case "view":
			$formatters = array(
			    "phone" => "fmt_phone",
			    "timestamp" => "fmt_lastupdate_date"
			);
			break;
		}

		switch ($this->reportType) {
		case "singlePhone":
			startWindow(_L("Phone Search for '" . fmt_phone(array($this->params["phone"]), 0) . "'"), "padding: 3px;");
			break;
		case "summary":
			startWindow(_L("Summary of Count per Status"), "padding: 3px;");
			break;
		case "view":
			startWindow(_L("View Phone Consent Status Results"), "padding: 3px;");
			break;
		}
		if ($data) {
		    if ($this->reportType == "view") {
			    showPageMenu($total, $pageStart, $pageSize);
		    }
		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($data, $this->basicReportTitles, $formatters);
		?>
			</table>
			<script type="text/javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?
		    if ($this->reportType == "view") {
			    showPageMenu($total, $pageStart, $pageSize);
		    }
		} else {
		?>
			<em>No results found.</em>
		<?
		}
		endWindow();
	}

	function runCSV($options = false){

		if ($options) {
			if (!$options["filename"]) return false;

			$outputfile = $options["filename"];
		} else {
			$outputfile = "php://output";
			header("Pragma: private");
			header("Cache-Control: private");
			header("Content-disposition: attachment; filename=report.csv");
			header("Content-type: application/vnd.ms-excel");
		}

		$fp = fopen($outputfile, "w");
		
		if ( ! $fp ) return false;

		//generate the CSV header
		$headerfields = array_values( $this->basicReportTitles );

		$headerfields = array_map( function ($value) { return '"'.$value.'"'; }, $headerfields );

		$header = implode(",", $headerfields);

		$ok = fwrite($fp, $header . "\r\n");
		
		if (!$ok) return false;

		session_write_close(); //WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point
		
		// we don't need to worry about blowing out PHP memory if we fetch rows unbuffered
		$this->_readonlyDB->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		
		$queryResults = QuickQueryMultiRow( $this->query, $this->_readonlyDB, $this->queryArgs );
		$consentData = $this->consentReportData->fetchConsentFromContacts( $queryResults );
		$resultRows = $this->consentReportData->mergeContactsWithConsent( $queryResults, $consentData );

		$numFetched = 0;
		foreach( $resultRows as $row ) {

			$row = array_map(function ($value) { return '"' . $value . '"'; }, $row);
			$ok = fwrite($fp, implode(",", $row) . "\r\n");
			
			if ( ! $ok) return false;
		}

		if ( $options ) {
			return fclose($fp);
		}
	}

	function getReportSpecificParams(){
		return $this->params;
	}

	function setReportFile(){
		$this->reportfile = "SmsStatus.jasper"; // TODO
	}

}

?>
