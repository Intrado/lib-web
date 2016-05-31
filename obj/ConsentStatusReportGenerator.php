<?

require_once('ConsentReportData.obj.php');
require_once("FieldMap.obj.php");


class ConsentStatusReportGenerator extends ReportGenerator {

	public $reportType = "html";
	public $queryArgs = false;
	
	public $consentReportData;

	private $basicReportTitles = array(
		"pkey" => "Unique ID",
		"f01" => "f01",
		"f02" => "f02",
		"phone" => "Phone",
		"consent" => "Consent",
		"timestamp" => "Last Updated"
	);

	private $summaryReportTitles = array(
		"status" => "Status",
		"count" => "Count"
	);

	function __construct() {
		parent::__construct();

		$this->consentReportData = new ConsentReportData();

		$fields = FieldMap::getAuthorizedFieldMaps();
		
		$this->basicReportTitles["f01"] = $fields["f01"]->name;
		$this->basicReportTitles["f02"] = $fields["f02"]->name;
	}

	function generateQuery( $hackPDF = false ) {

		$this->params = $this->reportinstance->getParameters();
		$this->reportType = $this->params["reporttype"];

		$jobId = null;
		$phone = null;

		if( isset($this->params['phone'] ) ) {
			if( trim($this->params['phone']) !== '' ) {
				$phone = $this->params['phone'];
				$this->queryArgs[] = $phone;
			} 
		}	

		if( $this->params["broadcast"] !== "-1" ) {
			$jobId = $this->params["broadcast"];
			$this->queryArgs[] = $jobId;
		}
		
		$this->query = $this->consentReportData->generateGetContactsQuery( $phone, $jobId );

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

			// if a specific job is not set, show the total amount of contacts
			if($this->params["broadcast"] === '-1') {

				$totalQuery = $this->consentReportData->getContactsCountQuery();
				$total = QuickQuery( $totalQuery, $this->_readonlyDB );
			}
			// otherwise the total is the count of the returned rows for that job
			else {
				$total = count( $queryResults );
			}
		}

		$consentData = $this->consentReportData->fetchConsentFromContacts( $queryResults );
		$data = $this->consentReportData->mergeContactsWithConsent( $queryResults, $consentData );

		switch ($this->reportType) {
		case "summary":

			$labeledRowsArray = array(
				"pending" => array(),
				"yes" => array(),
				"no" => array()
			);

			// divide results by status
			foreach ($data as $row) {
				if( ! isset( $labeledRowsArray[ $row['consent'] ] ) ) {
					$labeledRowsArray[ $row['consent'] ] = array();
				}

				$labeledRowsArray[ $row['consent'] ][] = $row;
			}

			$data = array(
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
			$formatters = array();
			
			break;

		case "view":
			$formatters = array(
			    "phone" => "fmt_phone",
			    "timestamp" => "fmt_lastupdate_date"
			);
			break;
		}

		switch ($this->reportType) {
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
		if ($data) {
			if($this->reportType === "summary") {
				showTable($data, $this->summaryReportTitles, $formatters);
			}
			else {
				showTable($data, $this->basicReportTitles, $formatters);
			}
		}
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

		$queryResults = QuickQueryMultiRow( $this->query, true, $this->_readonlyDB, $this->queryArgs );

		$consentData = $this->consentReportData->fetchConsentFromContacts( $queryResults );

		$resultRows = $this->consentReportData->mergeContactsWithConsent( $queryResults, $consentData );

		$numFetched = 0;
		foreach( $resultRows as $row ) {

			if( trim($row["timestamp"]) !== '' ) {
				$row["timestamp"] = $row["timestamp"] / 1000;
				$row["timestamp"] = date('F j, Y g:i a', $row["timestamp"]);
			}

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
		$this->reportfile = "PhoneConsentStatus.jasper"; // TODO
	}
}

?>
