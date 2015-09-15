<?

class AttachmentSummaryReport extends ReportGenerator {

	function generateQuery($hackPDF = false) {
		$this->params = $this->reportinstance->getParameters();
		$jobtypes = "";
		$joblistquery = "";
		if (isset($this->params['jobtypes'])) {
			$jobtypes = $this->params['jobtypes'];
		}
		if (isset($this->params['jobid'])) {
			$joblist = "";
			$job = new Job($this->params['jobid']);
			$jobtypesarray = explode("','", $jobtypes);
			if ($jobtypes == "" || in_array($job->jobtypeid, $jobtypesarray)) {
				$joblist = $this->params['jobid'];
			}
		} else {
			$reldate = "today";
			if (isset($this->params['reldate'])) {
				$reldate = $this->params['reldate'];
			}
			list($startdate, $enddate) = getStartEndDate($reldate, $this->params);
			$joblist = implode("','", getJobList($startdate, $enddate, $jobtypes));
		}

		if ($joblist) {
			$joblistquery = " and j.id in ('" . $joblist . "')";
		} else {
			$joblistquery = " and 0 ";
		}
		$this->params['joblist'] = $joblist;

		$this->query = "select
			j.name as BroadcastName,
			a.id as AttachmentId,
			coalesce(nullif(a.displayName, ''), a.id) as AttachmentName,
			coalesce(sum(d.actionCount), 0) as NumberOfDownloads
		from job as j
		inner join messagegroup as g on (j.messagegroupid=g.id)
		inner join message as m on (m.messagegroupid=g.id)
		inner join messageattachment as a on (a.messageid=m.id and a.type='content')
		inner join messagepart as mp on (mp.messageid=m.id and mp.messageattachmentid=a.id and mp.type='MAL')
		left outer join reportdocumentdelivery as d on (d.jobid=j.id and d.messageAttachmentId=a.id and d.action='download')
		where 1 $joblistquery
		group by BroadcastName, AttachmentId, AttachmentName
		order by null
		";
	}

	function runHtml() {
		global $USER;

		if (isset($this->params['jobtypes']) && $this->params['jobtypes'] != "") {

			startWindow(_L("Filter by"));
			?>
			<table>
				<?
				$jobtypes = explode("','", $this->params['jobtypes']);
				$jobtypenames = array();
				foreach ($jobtypes as $jobtype) {
					$jobtypeobj = new JobType($jobtype);
					$jobtypenames[] = escapehtml($jobtypeobj->name);
				}
				$jobtypenames = implode(", ", $jobtypenames);
				?>
				<tr>
					<td><?= _L("%s Type: ", getJobTitle()) . $jobtypenames ?></td>
				</tr>

			</table>
			<?
			endWindow();

			?><br><?
		}

		?><br><?

		function fmt_attachment_name_link($row, $index) {
			return "<a href='reportattachmentdetails.php?attachmentid=".((int)$row[$index-1])."'>".escapeHtml($row[$index])."</a>";
		}

		startWindow(_L("Report Details ") . help("ReportAttachmentSummary_Totals"), 'padding: 3px;', false);

		$titles = array(
			0 => _L("%s Name", getJobTitle()),
			// 1 is attachmentid
			2 => _L("Attachment Name"),
			3 => _L("Number of Downloads")
		);
		$formatters = array(
			2 => "fmt_attachment_name_link"
		);

		$result = Query($this->query, $this->_readonlyDB);
		$data = array();
		while ($row = DBGetRow($result)) {
			$data[] = $row;
		}

		echo '<table width="100%" cellpadding="3" cellspacing="1" class="list" id="reportsummary">';
		showTable($data, $titles, $formatters);
		echo "</table>";

		if (empty($data)) {
			echo _L("No %s with Hosted Attachments", getJobTitle());
		}

		endWindow();
	}

}
