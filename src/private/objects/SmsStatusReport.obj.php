<?

class SmsStatusReport extends ReportGenerator {

	public $reportType = "html";
	public $queryArgs = false;

	function generateQuery($hackPDF = false){

		$firstNameField= FieldMap::getFirstNameField();
		$lastNameField = FieldMap::getLastNameField();

		$hassms = getSystemSetting("_hassms", false);

		$this->params = $this->reportinstance->getParameters();
		$this->reportType = $this->params["reporttype"];

		$selectList0 = "*";
		$selectList1 = "p.pkey as pkey, p.". $firstNameField ." as fname, p.". $lastNameField ." as lname, s.sms, max(s.status) as status, max('global') as modifiedby, max(unix_timestamp(s.lastupdate)*1000) as modifieddate, max(s.notes) as notes, orgnames.orgkey ";
		$selectList2 = "p.pkey as pkey, p.". $firstNameField ." as fname, p.". $lastNameField ." as lname, s.sms, max('block') as status, max(bu.login) as modifiedby, max(unix_timestamp(b.createdate)*1000) as modifieddate, max(b.description) as notes, orgnames.orgkey ";
		$whereSms = "";
		$groupBy0 = "";
		$groupBy = "group by orgkey";
		$orderBy = "order by lname";

		switch ($this->reportType) {
		case "csv":
			$selectList0 = "*";
			break;
		case "summary":
			$selectList0 = "status, sum(`Count`) as `Count`";
			$selectList1 = "status, count(distinct s.sms) as `Count`";
			$selectList2 = "'block' as status, count(distinct s.sms) as `Count`";
			$groupBy0 = "group by status";
			$groupBy = "group by status";
			$orderBy = "order by null";
			break;
		case "smsview":
			$whereSms = "and s.sms = ?";
			// need two parameters to fill two placeholders
			$this->queryArgs[] = $this->params["sms"];
			$this->queryArgs[] = $this->params["sms"];
			break;
		case "view":
			$selectList0 = "sql_calc_found_rows *";
			break;
		default:
			break;
		}

		$this->query =
"select $selectList0 from (
(
    select $selectList1
    from aspsmsblock as s
    join person as p on (p.id = s.personid)
    left join (
      select p.id, group_concat( o.orgkey ) as orgkey
      from person p
      inner join personassociation pa on ( pa.personid = p.id )
      inner join organization o on ( o.id = pa.organizationid )
      group by p.id
    ) as orgnames on ( orgnames.id = p.id )
    where (p.type in ('system', 'guardianauto') and s.editlock = 0 and not p.deleted) $whereSms
    $groupBy
)
union all
(
    select $selectList2
    from sms as s
    inner join blockeddestination as b on (b.type = 'sms' and b.destination = s.sms)
    inner join user as bu on (b.userid = bu.id)
    inner join person as p on (s.personid = p.id)
    left join (
      select p.id, group_concat( o.orgkey ) as orgkey
      from person p
      inner join personassociation pa on ( pa.personid = p.id )
      inner join organization o on ( o.id = pa.organizationid )
      group by p.id
    ) as orgnames on ( orgnames.id = p.id )
    where not p.deleted $whereSms
)) t
where status is not null
$groupBy0
$orderBy ";
	}

	function runHtml() {

		if ($this->reportType === "html") {
			return;
		}

		$data = array();
		$total = 0;
		$pageSize = null;
		$pageStart = null;

		if ($this->reportType == "view") {
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

		if ($this->reportType == "view") {
			$total = QuickQuery("select found_rows()", $this->_readonlyDB);
		}

		switch ($this->reportType) {
		case "summary":
			$titles = array("0" => "Status",
					"1" => "Count"
			);
			$formatters = array("0" => "fmt_smsstatus");
			$newdata = array(
				"Pending Opt-In" => array(
					"0" => "pendingoptin", // also used for 'new'
					"1" => "0"
				),
				"Opted In" => array(
					"0" => "optin",
					"1" => "0"
				),
				"Blocked" => array(
					"0" => "block",
					"1" => "0"
				)
			);
			foreach ($data as $row) {
				$newdata[fmt_smsstatus($row, 0)][1] += $row[1];
			}
			$data = array_values($newdata);
			break;
		case "smsview":
		case "view":
			$titles = array("0" => "Unique ID",
					"1" => "First Name",
					"2" => "Last Name",
					"3" => "Phone Number",
					"4" => "Status",
					"5" => "Modified By",
					"6" => "Modified Date",
					"7" => "Notes",
					"8" => "School"
			);
			$formatters = array(// 0 is the Unique ID
					    "3" => "fmt_phone",
					    "4" => "fmt_smsstatus",
					    "5" => "fmt_modifiedby",
					    "6" => "fmt_lastupdate_date"
			);
			break;
		}

		switch ($this->reportType) {
		case "summary":
			startWindow(_L("Summary of Count per Status"), "padding: 3px;");
			break;
		case "smsview":
			startWindow(_L("SMS Search for '" . fmt_phone(array($this->params["sms"]), 0) . "'"), "padding: 3px;");
			break;
		case "view":
			startWindow(_L("View SMS Status Results"), "padding: 3px;");
			break;
		}
		if ($data) {
		    if ($this->reportType == "view") {
			    showPageMenu($total, $pageStart, $pageSize);
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
		$headerfields = array("Unique ID", "First Name", "LastName", "Phone Number","Status","Modified By","Modified Date","Notes","School");
		$headerfields = array_map(function ($value) { return '"'.$value.'"'; }, $headerfields);
		$header = implode(",", $headerfields);

		$ok = fwrite($fp, $header . "\r\n");
		if (!$ok)
			return false;

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		
		// we don't need to worry about blowing out PHP memory if we fetch rows unbuffered
		$this->_readonlyDB->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		$result = Query($this->query, $this->_readonlyDB, $this->queryArgs);

		$numFetched = 0;
		while ($row = DBGetRow($result)) {
			// $row[0] is Unique ID
			$row[3] = fmt_phone($row, 3);
			$row[4] = fmt_smsstatus($row, 4);
			$row[5] = fmt_modifiedby($row, 5);
			$row[6] = fmt_lastupdate_date($row, 6);
			$row = array_map(function ($value) { return '"'.$value.'"'; }, $row);
			$ok = fwrite($fp, implode(",", $row) . "\r\n");
			if (!$ok)
				return false;
		}

		if ($options) {
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