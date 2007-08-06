<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/Phone.obj.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport') && !$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if(isset($_GET['desc']))
	$_SESSION['reportdesc'] = get_magic_quotes_gpc() ? stripslashes($_GET['desc']) : $_GET['desc'];
if(isset($_GET['name']))
	$_SESSION['reportname'] = get_magic_quotes_gpc() ? stripslashes($_GET['name']) : $_GET['name'];

if (isset($_GET['reporttype']) || isset($_GET['jobid']) || isset($_GET['jobid_archived'])) {

	unset($_SESSION['reportjobid']); //reset the jobid in case this isn't a job report

	//get all the report options and store in session as SQL
	switch ((isset($_GET['reporttype']) ? $_GET['reporttype'] : null)) {
		default:
		case "job":
			if (isset($_GET['check_archived']) && $_GET['check_archived']) {
				$jobid = DBSafe($_GET['jobid_archived']);
			} else {
				$jobid = DBSafe($_GET['jobid']);
			}

			if ($jobid <= 0) {
				$jobid = null;
				break;
			}

			if (userOwns("job",$jobid) || (customerOwnsJob($jobid) && $USER->authorize('viewsystemreports'))) {
				$_SESSION['reportsql'] = "and j.id='$jobid'" ;
				$_SESSION['reportjobid'] = $jobid;
			} else {
				$jobid = QuickQuery("select max(id) from job where userid=$USER->id");
				$_SESSION['reportsql'] = "and j.id='$jobid'" ;
			}

			$job = new Job($jobid);
			if(!isset($_GET['name']) || !$_GET['name'])
				$_SESSION['reportname'] = $job->name;
			if(!isset($_GET['desc']) || !$_GET['desc'])
				$_SESSION['reportdesc'] = $job->description;

			$_SESSION['reportrange'] = "";

			break;
		case "range":
			$range1 = @strtotime($_GET['jobtype_range_range1'] ? $_GET['jobtype_range_range1'] : "today");
			$range2 = @strtotime($_GET['jobtype_range_range2'] ? $_GET['jobtype_range_range2'] : "today");

			$range1 = ($range1 === -1 || $range1 === false) ? time() : $range1;
			$range2 = ($range2 === -1 || $range2 === false) ? time() : $range2;

			//auto arrange in correct order
			if ($range2 < $range1) {
				$temp = $range1;
				$range1 = $range2;
				$range2 = $temp;
			}

			$range1 = date("Y-m-d",$range1);
			$range2 = date("Y-m-d",$range2);

			$_SESSION['reportsql'] = "and ( (rc.starttime >= unix_timestamp('$range1') * 1000 and rc.starttime <= unix_timestamp(date_add('$range2',interval 1 day)) * 1000 )
									or rc.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$range1' and j.startdate <= date_add('$range2',interval 1 day)" ;
			$_SESSION['reportrange'] = "Range $range1 to $range2";
			break;
		case "relative":
			//TODO refactor code with date.inc.php
			switch($_GET['jobtype_relative_data']) {
				default:
				case "today":
					$targetdate = QuickQuery("select curdate()");
					$_SESSION['reportrange'] = "Today ($targetdate)";
					break;
				case "yesterday":
					$targetdate = QuickQuery("select date_sub(curdate(),interval 1 day)");
					$_SESSION['reportrange'] = "Yesterday ($targetdate)";
					break;
				case "lastweekday":
					//1 = Sunday, 2 = Monday, ..., 7 = Saturday
					$dow = QuickQuery("select dayofweek(curdate())");

					//normally go back 1 day
					$daydiff = 1;
					//if it is sunday, go back 2 days
					if ($dow == 1)
						$daydiff = 2;
					//if it is monday, go back 3 days
					if ($dow == 2)
						$daydiff = 3;

					$targetdate = QuickQuery("select date_sub(curdate(),interval $daydiff day)");

					if(!isset($_GET['name']) || !$_GET['name'])
						$_SESSION['reportname'] = "Relative date Report";
					$_SESSION['reportrange'] = "Last Week Day ($targetdate)";
					break;
			}
			$_SESSION['reportsql'] = "and ( (rc.starttime >= unix_timestamp('$targetdate') * 1000 and rc.starttime < unix_timestamp(date_add('$targetdate',interval 1 day)) * 1000 )
												or rc.starttime = null) and ifnull(j.finishdate, j.enddate) >= '$targetdate' and j.startdate <= date_add('$targetdate',interval 1 day) ";
			break;
	}

	if (isset($_GET['option_jobpriority']) && $_GET['option_jobpriority'] &&
		count($_GET['option_jobpriority_data']) > 0) {
		//make the values safe
		$jobprios = $_GET['option_jobpriority_data'];
		foreach ($jobprios as $index => $val) {
			$jobprios[$index] = DBSafe($val);
		}
		//make a big OR group of the possible values
		$_SESSION['reportsql'] .= " and (j.jobtypeid='" . implode($jobprios, "' or j.jobtypeid='") . "')";
	}

	if (isset($_GET['option_jobtype']) && $_GET['option_jobtype'] &&
		count($_GET['option_jobtype_data']) > 0) {
		//make the values safe
		$jobtypes = $_GET['option_jobtype_data'];
		foreach ($jobtypes as $index => $val) {
			$jobtypes[$index] = DBSafe($val);
		}
		//make a big OR group of the possible values
		$_SESSION['reportsql'] .= " and (rp.type='" . implode($jobtypes, "' or rp.type='") . "')";
	}


	if (isset($_GET['option_result']) && $_GET['option_result'] &&
		count($_GET['option_result_data']) > 0) {
		$results = $_GET['option_result_data'];

		$statuses = array();

		if (in_array("success",$results))
			$statuses[] = "rp.status = 'success'";

		if (in_array("fail",$results))
			$statuses[] = "rp.status = 'fail'";

		if (in_array("inprogress",$results))
			$statuses[] = "rp.status not in ('success','fail','duplicate')";

		//make a big OR group of the possible values
		$_SESSION['reportsql'] .= " and (" . implode($statuses, " or ") . ")";
	}

	if (isset($_GET['option_callprogress']) && $_GET['option_callprogress'] &&
		count($_GET['option_callprogress_data']) > 0) {
		//make the values safe
		$callprogresses = $_GET['option_callprogress_data'];
		foreach ($callprogresses as $index => $val) {
			$callprogresses[$index] = DBSafe($val);
		}
		//make a big OR group of the possible values
		$_SESSION['reportsql'] .= " and (rc.result='" . implode($callprogresses, "' or rc.result='") . "')";
	}

	if (isset($_GET['filter_pkey']) && $_GET['filter_pkey'] &&
		isset($_GET['filter_pkey_data'])) {
		$pkey = DBSafe($_GET['filter_pkey_data']);
		$_SESSION['reportsql'] .= " and p.pkey='$pkey'";
	}

	if (isset($_GET['filter_phone']) && $_GET['filter_phone'] &&
		isset($_GET['filter_phone_data'])) {
		$phone = DBSafe(Phone::parse($_GET['filter_phone_data']));

		$_SESSION['reportsql'] .= " and rc.phone like '%$phone%'";
	}

	$orderfields = array(	"fname" => "p.f01,p.f02",
							"lname" => "p.f02,p.f01",
							"type" => "rp.type,p.f02,p.f01",
							"attempt" => "rc.starttime",
							"result" => "rp.status,rc.starttime,p.f02,p.f01"
						);


	$ordername = (isseT($_GET['sort_by']) ? $_GET['sort_by'] : "lname");
	$_SESSION['reportordersql'] = "order by " . $orderfields[$ordername];
}



///////////// Report formatters /////////////

function fmt_result ($row,$index) {
	if ($row[3] == "phone") {
		if ($row[9] == "duplicate")
			return "Duplicate";
		switch($row[$index]) {
			case "A":
				return "Answered";
			case "M":
				return "Machine";
			case "B":
				return "Busy";
			case "N":
				return "No Answer";
			case "X":
				return "Disconnect";
			case "F":
				return "Failed";
			case "C":
				return "In Progress";
			default:
				return "";
		}
	} else {
		if ($row[9] == "success")
			return "Success";
		else if ($row[9] == "fail")
			return "Failed";
		else if ($row[9] == "duplicate")
			return "Duplicate";
		else
			return "In Progress";
	}
}

function fmt_attempts ($row,$index) {

	if ($row[$index] !== NULL && $row[$index] !== "") {
		if ($row[3] == "phone") {
			return $row[$index] . "/" . $row[10];
		} else {
			return $row[$index] . "/1";
		}
	} else {
		return "";
	}
}

function fmt_message ($row,$index) {
	return '<img src="img/icon_' . $row[$index] . '_12.gif" align="bottom" />&nbsp;' . htmlentities($row[$index+1]);
}

$starttime = microtime_float();

////////// paging ///////////

$limit=500;
$start=0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$pagesql = isset($_GET['csv']) ? "" : "limit $start,$limit";

$reportsql = $_SESSION['reportsql'];
$ordersql = $_SESSION['reportordersql'];

////////////////////////////////////////////////////////////////////////////////
// Report Data
////////////////////////////////////////////////////////////////////////////////

//if this user can see systemwide reports, then lock them to the customerid
//otherwise lock them to jobs that they own
if (!$USER->authorize('viewsystemreports')) {
	$userJoin = " and j.userid = $USER->id ";
} else {
	$userJoin = "";
}



//TODO
//total people, notified, unnotified, remaining
//total calls, delivered, undelivered, remaining
//total attempts, answered, machine, busy, no answer,

$summaryquery = "
select
	rp.type,
	count(rp.personid) as people,
	sum(rp.status='success' or rp.status='fail') / (count(rp.personid) + 0.00 - sum(rp.status = 'duplicate')) as completed_percent,
	sum(rp.status='success') as success,
	sum(rp.status='fail') as fail,
	sum(rp.status not in ('success','fail','duplicate')) as in_progress,
	sum(rp.status = 'duplicate') as duplicate,
	0 as success_rate,

	0 as total_attempts,

	j.userid, j.name, u.firstname, u.lastname, u.login

	from 		job j
	inner join reportperson rp on (rp.jobid=j.id)
	inner join user u on (u.id = j.userid)
	left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
	left join	person p on
					(p.id=rp.personid)
	where  1
	$userJoin
	$reportsql

	group by rp.type
";

/*
$callprogressquery = "
select
	count(*), cl.callprogress

	from 		job j, jobworkitem wi
	left join	jobtask jt on
						(jt.jobworkitemid=wi.id)
	left join	calllog cl on
						(cl.jobtaskid=jt.id and (cl.callattempt=jt.numattempts-1))

	where 	wi.jobid=j.id
	$userJoin
	and cl.id is not null

	$reportsql
	group by cl.callprogress
";
*/

$detailquery="
select SQL_CALC_FOUND_ROWS
	p.pkey,
	p.f01,
	p.f02,
	rp.type,
	m.name,
	coalesce(rc.phone,
				rc.email,
				concat(
					coalesce(rc.addr1,''), ' ',
					coalesce(rc.addr2,''), ' ',
					coalesce(rc.city,''), ' ',
					coalesce(rc.state,''), ' ',
					coalesce(rc.zip,''))
			) as destination,
	rc.numattempts,
	from_unixtime(rc.starttime/1000),
	coalesce(rc.result,
			rp.status) as result,
	rp.status,
	j.maxcallattempts,
	u.login,
	j.name,
	rc.resultdata

	from 		person p
	inner join reportperson rp on (p.id = rp.personid)
	inner join job j on (rp.jobid = j.id)
	inner join user u on (u.id = j.userid)
	left join	reportcontact rc on (rc.jobid = rp.jobid and rc.type = rp.type and rc.personid = rp.personid)
	left join	message m on
					(m.id = rp.messageid)

	where 1
		$userJoin

	$reportsql

	$ordersql
	$pagesql
";


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


if (isset($_GET['csv'])) {


	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-disposition: attachment; filename=report.csv");
	header("Content-type: application/vnd.ms-excel");

	session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point


	$issurvey = false;
	if ($_SESSION['reportjobid']) {
		$job = new Job($_SESSION['reportjobid']);
		if ($job->questionnaireid) {
			$issurvey = true;
			$numquestions = QuickQuery("select count(*) from surveyquestion where questionnaireid=$job->questionnaireid");
		}
	}


	//generate the CSV header
	echo '"Job Name","User","Type","Message","ID","First Name","Last Name","Destination","Attempts","Max Attempts","Last Attempt","Last Result"';
	if ($issurvey) {
		for ($x = 1; $x <= $numquestions; $x++) {
			echo ",Question $x";
		}
	}
	echo "\r\n";

	$result = Query($detailquery);

	while ($row = DBGetRow($result)) {
		$row[5] = html_entity_decode(fmt_destination($row,5));
		$row[6] = (isset($row[6]) ? $row[6] : "");
		$row[10] = $row[3] == "phone" ? $row[10] : 1;

		if (isset($row[7])) {
			$time = strtotime($row[7]);
			if ($time !== -1 && $time !== false)
				$row[7] = date("m/d/Y H:i",$time);
		} else {
			$row[7] = "";
		}
		$row[8] = fmt_result($row,8);


		$reportarray = array($row[12],$row[11],ucfirst($row[3]),$row[4],$row[0],$row[1],$row[2],$row[5],$row[6],$row[10],$row[7],$row[8]);

		if ($issurvey) {
			//fill in survey result data, be sure to fill in an array element for all questions, even if blank
			$startindex = count($reportarray);

			$questiondata = array();
			if ($row[3] == "phone")
				parse_str($row[13],$questiondata);
			else if ($row[3] == "email")
				parse_str($row[14],$questiondata);

			//add data to the report for each question
			for ($x = 0; $x < $numquestions; $x++) {
				$reportarray[$startindex + $x] = isset($questiondata["q$x"]) ? $questiondata["q$x"] : "";
			}
		}


		echo '"' . implode('","', $reportarray) . '"' . "\r\n";
	}


} else {
	//load page to memory
	$data = array();
	$result = Query($summaryquery);

	while ($row = DBGetRow($result)) {
		$data[] = $row;
	}

	$PAGE = "reports:view";
	$TITLE = (isset($_SESSION['reportname']) && $_SESSION['reportname'] != "" ? $_SESSION['reportname'] : "Custom Report");

	$DESCRIPTION = (isset($_SESSION['reportdesc']) ? $_SESSION['reportdesc'] . " - " : "") . $_SESSION['reportrange'];
	/*
	TODO FIXME this breaks if the user clears the GET request data, ex by paging or clicking on "view report"
	Show this info if:
	1. The user running the report is different than the user that owns the job.
	2. The user has permission to view systemwide reports.
	3. The report is in job mode. ($_GET['reporttype'] == "job")
	*/
	if ($USER->authorize('viewsystemreports') && (isset($_GET['reporttype']) ? $_GET['reporttype'] : "") == "job" && $USER->id != $data[0][9] && $data[0][10] != null) { // Check for non-null report name
		$DESCRIPTION .= "<br>Created by {$data[0][11]} {$data[0][12]} ({$data[0][13]})";
	}
	include_once("nav.inc.php");
	print buttons(button('Refresh', 'window.location.reload()'), button('Done', 'window.history.go(-1)'));

	startWindow("Report Summary", NULL, false);

	echo '<div align="center"><table width="100%" cellpadding="3" cellspacing="1" class="list">';
	//$titles = array("Type", "People", "Percent Complete", "Succeeded", "Failed", "In Progress", "Success Rate", "Total Attempts");
	//$formatters = array(2=>"fmt_percent", 6=>"fmt_percent");

	echo "</table></div>";
	?>
	<table border="0" cellpadding="5" cellspacing="0" width="100%">
	<?

		foreach($data as $item) {
			switch ($item[0]) {
				case 'phone':
	?>
					<tr>
						<th class="windowRowHeader bottomBorder" align="right">Phone:<br><? print help('ReportResults_Phone'); ?></th>
						<td class="bottomBorder"><table border="0" cellpadding="3" cellspacing="1" class="border"><? showTable(array($item),array(1 => "People", 6 => "Duplicates Removed", 2 => "% Complete", 3 => "Delivered", 4 => "Undelivered", 5 => "Remaining"),array(2=>"fmt_percent", 7=>"fmt_percent")); ?></table></td>
					</tr>
	<?
					break;
				case 'email':
	?>
					<tr>
						<th class="windowRowHeader bottomBorder" align="right">Email:<br><? print help('ReportResults_Email'); ?></th>
						<td class="bottomBorder"><table border="0" cellpadding="3" cellspacing="1" class="border"><? showTable(array($item),array(1 => "Total", 2 => "% Completed", 5 => "Remaining"),array(2=>"fmt_percent")); ?></table></td>
					</tr>
	<?
					break;
				case 'print':
	?>
					<tr>
						<th class="windowRowHeader" align="right">Print:<br><? print help('ReportResults_Print'); ?></th>
						<td ><table border="0" cellpadding="3" cellspacing="1" class="border"><? showTable(array($item),array(1 => "Total", 2 => "% Completed", 5 => "Remaining"),array(2=>"fmt_percent")); ?></table></td>
					</tr>
	<?
					break;
			}
		}
	?>
	</table>
	<?
	endWindow();

	print '<br>';



	//load page to memory
	$data = array();
	$result = Query($detailquery);
	while ($row = DBGetRow($result)) {
		$data[] = $row;
	}

	$query = "select found_rows()";
	$total = QuickQuery($query);

	startWindow("Report Details", 'padding: 3px;', false);

	showPageMenu($total,$start,$limit);
	echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
	$titles = array(0 => "ID",
					1 => "First Name",
					2 => "Last Name",
					3 => "Message",
					5 => "Destination",
					6 => "Attempts",
					7 => "Last Attempt",
					8 => "Last Result");
	$formatters = array(3 => "fmt_message",
						4 => "fmt_limit_25",
						5 => "fmt_destination",
						6 => "fmt_attempts",
						7 => "fmt_date",
						8 => "fmt_result");
	showTable($data,$titles,$formatters);
	echo "</table>";
	showPageMenu($total,$start,$limit);

	endWindow();
?>
	<br><a href="report.php/report.csv?csv=true&t=<?= time() ?>" class="noprint">Click here to download report data in CSV format</a>
<?


	print buttons(button('Refresh', 'window.location.reload()'), button('Done', NULL, 'reportoptions.php'));

	include_once("navbottom.inc.php");
} //else if csv
?>
