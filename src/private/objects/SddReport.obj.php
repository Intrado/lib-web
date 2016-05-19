<?

$thisDir = dirname(__FILE__);
require_once("{$thisDir}/ReportGenerator.obj.php");

// TODO: this function is similar to the one of the same name in reportjobdetails.php
// and there are also functions of the same name with different functionality in
// reportcallsperson.php and reportcontactchangesummary.php. We should unify them
// and move them to inc/formatters.inc.php.
function fmt_dst_src($row, $index) {
	if ($row[$index] != null) {
		$type = $row[$index + 1];
		$maxtypes = fetch_max_types();
		$actualsequence = isset($maxtypes[$type]) ? ($row[$index] % $maxtypes[$type]) : $row[$index];
		return escapehtml(destination_label($type, $actualsequence));
	} else {
		return "";
	}
}

function fmt_sdd_action($row, $index) {
	$map = array(
		"none" => "n/a",
		"click" => "Clicked",
		"download" => "Downloaded",
		"bad_password" => "Entered Bad Password"
	);
	return isset($map[$row[$index]]) ? $map[$row[$index]] : "Unknown";
}

class SddReport extends ReportGenerator {

	const DEFAULT_PAGE_SIZE = 100;

	public $queryArgs = false;

	private $titles = array(
		'0' => "Unique ID",
		'1' => "Last Name",
		'2' => "First Name",
		'3' => "Attachment Filename",
		'4' => "Status",
		'5' => "Activity",
		'6' => "Activity Count",
		'7' => "Last Timestamp",
	);
	private $formatters = array(
		'4' => "fmt_result",
		'5' => "fmt_sdd_action",
		'7' => "fmt_ms_timestamp"
	);

	function generateQuery($hackPDF = false) {
		$this->params = $this->reportinstance->getParameters();

		// TODO: expand this to show activity per reportcontact (i.e. include a row for each recipient)
		// This requires a data model change and an API change, to make the mal reference recipientPersonId
		// instead of personId. Then the SQL below should join to reportcontact, and sdd_action should join
		// using recipientPersonId, and include reportcontact.result instead of reportperson.status,
		// and also sequence and destination.

		// the query returns all recipients even if they have 0 SDD actions and 0 rows in reportdocumentdelivery
		// - sdd_action is a reference to the most recent action of any type for a given attachment and given person.
		// - sdd_download is a reference to the most recent DOWNLOAD action for a given attachment and given person.
		$this->query =
"select sql_calc_found_rows
p.pkey,
p.f02 as lastname,
p.f01 as firstname,
ba.filename,
rp.status,
coalesce(sdd_download.action, sdd_action.action, 'none') as action,
coalesce(sdd_download.actionCount, sdd_action.actionCount) as actionCount,
coalesce(sdd_download.timestampMs, sdd_action.timestampMs) as actionTimestampMs
from burst as b
inner join burstattachment as ba on (ba.burstid = b.id)
inner join messageattachment as ma on (ma.burstattachmentid = ba.id)
inner join job as j on (j.id = b.jobid)
inner join reportperson as rp on (rp.jobid = b.jobid and rp.type = 'email')
left outer join reportdocumentdelivery as sdd_action on (sdd_action.messageAttachmentId = ma.id and sdd_action.personid = rp.personid)
left outer join reportdocumentdelivery as sdd_action2 on (sdd_action2.messageAttachmentId = ma.id and sdd_action2.personid = sdd_action.personid and sdd_action2.timestampMs > sdd_action.timestampMs)
left outer join reportdocumentdelivery as sdd_download on (sdd_download.messageAttachmentId = ma.id and sdd_download.personid = sdd_action.personid and sdd_download.action = 'download')
left outer join reportdocumentdelivery as sdd_download2 on (sdd_download2.messageAttachmentId = ma.id and sdd_download2.personid = sdd_action.personid and sdd_download2.action = 'download' and sdd_download2.timestampMs > sdd_download.timestampMs)
inner join person as p on (p.id = rp.personid)
where b.id = ? and b.deleted = 0 and sdd_action2.messageattachmentid is null and sdd_download2.messageattachmentid is null
order by lastname, firstname
";

		$this->queryArgs[] = $this->params['id'];
	}

	function runHtml() {

		$data = array();
		$pageSize = self::DEFAULT_PAGE_SIZE;
		$pageStart = isset($this->params['pagestart']) ? (int)$this->params["pagestart"] : 0;
		$limit = " limit $pageStart, $pageSize";

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

		$headerfields = array_map(function ($value) { return '"' . $value . '"'; }, $this->titles);
		$header = implode(",", $headerfields);
		$ok = fwrite($fp, $header . "\r\n");
		if (!$ok) {
			return false;
		}

		session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point

		// we don't need to worry about blowing out PHP memory if we fetch rows unbuffered
		$this->_readonlyDB->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, false);
		$result = Query($this->query, $this->_readonlyDB, $this->queryArgs);
		unset($this->formatters[4]); // no email formatting in CSV mode
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
