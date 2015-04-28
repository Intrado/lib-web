<?

require_once('obj/ReportGenerator.obj.php');

function fmt_sdd_action($row, $index) {
	$map = array(
		"NONE" => "N/A",
		"SEND" => "Sent",
		"CLICK" => "Clicked",
		"DOWNLOAD" => "Downloaded",
		"BAD_PASSWORD" => "Entered Bad Password"
	);
	return isset($map[$row[$index]]) ? $map[$row[$index]] : "Unknown";
}

class SddReport extends ReportGenerator {

	const DEFAULT_PAGE_SIZE = 100;

	public $queryArgs = false;

	private $titles = array(
		'0' => "Last Name",
		'1' => "First Name",
		'2' => "Sequence",
		'3' => "Destination",
		'5' => "Attachment Filename",
		'6' => "Result",
		'7' => "Activity",
		'8' => "Activity Count",
		'9' => "Last Timestamp",
	);
	private $formatters = array(
		'2' => "fmt_renderedlist_destination_sequence",
		'3' => "fmt_email",
		'6' => "fmt_result",
		'7' => "fmt_sdd_action",
		'9' => "fmt_ms_timestamp"
	);

	function generateQuery($hackPDF = false) {
		$this->params = $this->reportinstance->getParameters();

		// the query returns all recipients even if they have 0 SDD actions and 0 rows in reportsdddelivery.
		// - sdd_action is a reference to the most recent action of any type for a given attachment and given person.
		// - sdd_download is a reference to the most recent DOWNLOAD action for a given attachment and given person.
		$this->query =
"select sql_calc_found_rows
p.f02 as lastname,
p.f01 as firstname,
rc.sequence,
rc.email as destination,
rc.type,
ba.filename,
rc.result,
coalesce(sdd_download.action, sdd_action.action, 'NONE') as action,
coalesce(sdd_download.actionCount, sdd_action.actionCount) as actionCount,
coalesce(sdd_download.timestampMs, sdd_action.timestampMs) as actionTimestampMs
from burst as b
inner join burstattachment as ba on (ba.burstid = b.id)
inner join messageattachment as ma on (ma.burstattachmentid = ba.id)
inner join job as j on (j.id = b.jobid)
inner join reportperson as rp on (rp.jobid = b.jobid and rp.type = 'email')
inner join reportcontact as rc on (rc.jobid = b.jobid and rc.type = 'email' and rc.personid = rp.personid)
left outer join reportsdddelivery as sdd_action on (sdd_action.messageAttachmentId = ma.id and sdd_action.personid = rp.personid)
left outer join reportsdddelivery as sdd_action2 on (sdd_action2.messageAttachmentId = ma.id and sdd_action2.personid = sdd_action.personid and sdd_action2.timestampMs > sdd_action.timestampMs)
left outer join reportsdddelivery as sdd_download on (sdd_download.messageAttachmentId = ma.id and sdd_download.personid = sdd_action.personid and sdd_download.action = 'DOWNLOAD')
left outer join reportsdddelivery as sdd_download2 on (sdd_download2.messageAttachmentId = ma.id and sdd_download2.personid = sdd_action.personid and sdd_download2.action = 'DOWNLOAD' and sdd_download2.timestampMs > sdd_download.timestampMs)
inner join person as p on (p.id = rc.recipientpersonid)
where b.id = ? and sdd_action2.messageattachmentid is null and sdd_download2.messageattachmentid is null
order by lastname, firstname
";

		$this->queryArgs[] = $this->params['id'];
	}

	function runHtml() {

		$data = array();
		$pageSize = self::DEFAULT_PAGE_SIZE;
		$pageStart = isset($this->params['pagestart']) ? (int) $this->params["pagestart"] : 0;
		$limit = "limit $pageStart, $pageSize";

		$sql = $this->query . $limit;
		$result = Query($sql, $this->_readonlyDB, $this->queryArgs);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}

		$total = QuickQuery("select found_rows()", $this->_readonlyDB);

		if ($data) {
			if ($total > $pageSize) {
				showPageMenu($total, $pageStart, $pageSize);
			}

		?>
			<table width="100%" cellpadding="3" cellspacing="1" class="list" id="searchresults">
		<?
			showTable($data, $this->titles, $this->formatters);
		?>
			</table>
			<script type="text/javascript">
			var searchresultstable = new getObj("searchresults").obj;
			</script>
		<?
			if ($total > $pageSize) {
				showPageMenu($total, $pageStart, $pageSize);
			}
		} else {
		?>
			<em>No results found.</em>
		<?
		}
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

		$headerfields = array_map(function ($value) { return '"'.$value.'"'; }, $this->titles);
		$header = implode(",", $headerfields);
		$ok = fwrite($fp, $header . "\r\n");
		if (!$ok) {
			return false;
		}

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

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

}
