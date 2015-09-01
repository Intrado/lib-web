<?

class AttachmentDetailReport extends ReportGenerator{

	const DEFAULT_PAGE_SIZE = 100;

	public $queryArgs = false;

	private $titles = array(
			0 => "Job",
			1 => "Unique ID",
			2 => "First Name",
			3 => "Last Name",
			4 => "Status",
			5 => "Activity",
			6 => "Activity Count",
			7 => "Last Attempt"
		);

	private $formatters = array(
			7 => "fmt_ms_timestamp"
		);

	function generateQuery($hackPDF = false){
		global $USER;
		$this->params = $this->reportinstance->getParameters();
		$this->reporttype = $this->params['reporttype'];
		$this->query = null;
		$this->queryArgs = array();
		$orderquery = ""; // remove sorting to improve performance (bug 4461)

		$jobtypes = "";
		if(isset($this->params['jobtypes'])){
			$jobtypes = $this->params['jobtypes'];
		}

		if(isset($this->params['jobid'])){
			$joblist = "";
			$job = new Job($this->params['jobid']);
			$jobtypesarray = explode("','", $jobtypes);
			if($jobtypes == "" || in_array($job->jobtypeid, $jobtypesarray)){
				$joblist = $this->params['jobid'];
			}
		} else {
			$reldate = "today";
			if(isset($this->params['reldate']))
				$reldate = $this->params['reldate'];
			list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
			$joblist = implode("','", getJobList($startdate, $enddate, $jobtypes, "false", isset($this->params['type']) ? $this->params['type'] : ""));
		}

		if ($joblist) {
			$joblistquery = " and rp.jobid in ('" . $joblist . "')";
		} else {
			$joblistquery = " and 0 ";
		}
		$this->params['joblist'] = $joblist;

		if (isset($this->params['attachmentid'])) {
			$query = "select coalesce(nullif(displayName, ''), id) from messageattachment where id = ?";
			$attachmentid = (int) $this->params['attachmentid'];
			$this->attachmentName = QuickQuery($query, $this->_readonlyDB, array($attachmentid));
			$attachmentquery = " and a.id = ?";
			$this->queryArgs[] = $attachmentid;
		} else {
			error_log("No attachmentid set");
		}

		$searchquery = $joblistquery . $attachmentquery;

		$this->query = "select sql_calc_found_rows
			j.name as JobName,
			coalesce(p.pkey, 'n/a') as UniqueID,
			rp." . FieldMap::GetFirstNameField() . " as FirstName,
			rp." . FieldMap::GetLastNameField() . " as LastName,
			rp.status as Status,
			coalesce(dd_download.action, dd_action.action, 'n/a') as Activity,
			coalesce(dd_download.actionCount, dd_action.actionCount, 'n/a') as ActivityCount,
			coalesce(dd_download.timestampMs, dd_action.timestampMs) as LastAttempt
		from reportperson as rp
		inner join person as p on (rp.personid=p.id)
		inner join job as j on (rp.jobid=j.id)
		inner join messagegroup as g on (j.messagegroupid=g.id)
		inner join message as m on (m.messagegroupid=g.id)
		inner join messageattachment as a on (a.messageid=m.id)
		left outer join reportdocumentdelivery as dd_action on (dd_action.messageAttachmentId = a.id and dd_action.personid = rp.personid)
		left outer join reportdocumentdelivery as dd_action2 on (dd_action2.messageAttachmentId = a.id and dd_action2.personid = dd_action.personid and dd_action2.timestampMs > dd_action.timestampMs)
		left outer join reportdocumentdelivery as dd_download on (dd_download.messageAttachmentId = a.id and dd_download.personid = dd_action.personid and dd_download.action = 'download')
		left outer join reportdocumentdelivery as dd_download2 on (dd_download2.messageAttachmentId = a.id and dd_download2.personid = dd_action.personid and dd_download2.action = 'download' and dd_download2.timestampMs > dd_download.timestampMs)
		where 1 $searchquery
		group by rp.jobid, rp.personid
		$orderquery
		";

		// query to test resulting dataset, PDF generation uses this to estimate the number of pages.
		// comment out tables in left outer join that can match only 0 or 1 row, since they can't affect the count.
		// comment out ORDER BY because it can't affect the count either.
		$this->testquery = "select count(*)
		from reportperson as rp
		inner join person as p on (rp.personid=p.id)
		inner join job as j on (rp.jobid=j.id)
		inner join messagegroup as g on (j.messagegroupid=g.id)
		inner join message as m on (m.messagegroupid=g.id)
		inner join messageattachment as a on (a.messageid=m.id)
		where 1 $searchquery
		group by rp.jobid, rp.personid";

	}

	function runHtml() {

		// DISPLAY
		if ((isset($this->params['jobtypes']) && $this->params['jobtypes'] != "")) {
			startWindow(_L("Filter By"));
?>
			<table>
<?
				if(isset($this->params['jobtypes']) && $this->params['jobtypes'] != ""){
					$jobtypes = explode("','", $this->params['jobtypes']);
					$jobtypenames = array();
					foreach($jobtypes as $jobtype){
						$jobtypeobj = new JobType($jobtype);
						$jobtypenames[] = escapehtml($jobtypeobj->name);
					}
					$jobtypenames = implode(", ",$jobtypenames);
?>
					<tr><td>Job Type: <?=$jobtypenames?></td></tr>
				}

				foreach($searchrules as $rule){
					?><tr><td><?=$rule?></td></tr><?
				}
?>
				</table>
			<?
			endWindow();

			?><br><?
		}

		?><br><?

		$data = array();
		$pageSize = self::DEFAULT_PAGE_SIZE;
		$pageStart = isset($this->params['pagestart']) ? (int)$this->params["pagestart"] : 0;
		$limit = " limit $pageStart, $pageSize";
		$sql = $this->query . $limit;

		$result = Query($sql, $this->_readonlyDB, $this->queryArgs);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}

		$query = "select found_rows()";
		$total = QuickQuery($query, $this->_readonlyDB);

		startWindow(_L("Report Details ").help("AttachmentDetailReport_ReportDetails"), 'padding: 3px;', false);

		echo "<h1>Attachment: {$this->attachmentName}</h1>";

		showPageMenu($total, $pageStart, self::DEFAULT_PAGE_SIZE);
		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportdetails">';
		showTable($data, $this->titles, $this->formatters);
		echo "</table>";
		showPageMenu($total, $pageStart, self::DEFAULT_PAGE_SIZE);

		endWindow();
		?>
		<script type="text/javascript">
			var reportdetailstable = new getObj("reportdetails").obj;
		</script>
		<?
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

		session_write_close(); //WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		// we don't need to worry about blowing out PHP memory if we fetch rows unbuffered
		$this->_readonlyDB->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		$result = Query($this->query, $this->_readonlyDB, $this->queryArgs);
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

	function setReportFile(){
		$this->reportfile = "attachmentdetailreport.jasper";
	}

}


?>
