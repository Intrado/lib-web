<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/form.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('createreport')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "reports:builder";
$TITLE = "Report Builder";

include_once("nav.inc.php");
?>
<script langauge="javascript">
function ensureJobChosen() {
	var radio = new getObj('jobtype_job');

	if (radio.obj.checked &&
	    (!isSelected('jobid_select', 'jobid') && !isSelected('jobid_archived_select', 'jobid_archived'))) {
			alert('Please select a job for this report');
			return false;
	} else {
		return true;
	}
}
</script>
<form method="GET" action="report.php" onsubmit="return ensureJobChosen()">
<? buttons(submit('report', 'main', 'create_report', 'create_report')); ?>

<? startWindow("Report Information", "padding: 3px;"); ?>
Name: <input type="text" name="name" size="30"> &nbsp;&nbsp; Description: <input type="text" name="desc" size="30">
<? endWindow(); ?>

<br>

<? startWindow("Report Settings"); ?>

<table border="0" cellpadding="3" cellspacing="0" width="100%">
<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Report Type:<br><? print help('ReportOptions_ReportType', NULL, 'grey'); ?></th>
<td class="bottomBorder">
	<table>
		<tr>
		<td onclick="new getObj('jobtype_job').obj.checked=true;">
			<table>
			<tr>
			<td><input id="jobtype_job" type="radio" name="reporttype" value="job"> Job Report: </td>
			<td><div id="jobid"><select name="jobid" id='jobid_select'>
					<option value='0'>Not selected</option>
<?
$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 0 and status!='repeating' order by id desc");
foreach ($jobs as $job) {
	echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
?>
			</select><br><img src="img/spacer.gif" width="100" height="1"><img src="img/spacer.gif" width="100" height="1"></div></td>
			<td><div id="jobid_archived" style="display: none"><select name="jobid_archived" id='jobid_archived_select'>
					<option value='0'>Not selected</option>
<?
$jobs = DBFindMany("Job","from job where userid=$USER->id and deleted = 2 and status!='repeating' order by id desc");
foreach ($jobs as $job) {
	echo '<option value="' . $job->id . '">' . htmlentities($job->name) . '</option>';
}
	?>
			</select><br><img src="img/spacer.gif" width="100" height="1"><img src="img/spacer.gif" width="100" height="1"></div></td>
			<td><input id="check_archived" type="checkbox" name="check_archived" value="true" onclick = "setVisibleIfChecked(this, 'jobid_archived'); setHiddenIfChecked(this, 'jobid');">
			Show archived jobs</td>
			</tr>
			</table>


		</td>
	</tr>
	<tr>
		<td onclick="new getObj('jobtype_range').obj.checked=true;"><input id="jobtype_range" type="radio" name="reporttype" value="range"> Date range between:
			<input name="jobtype_range_range1" id="jobtype_range_range1" type="text"> and <input name="jobtype_range_range2" id="jobtype_range_range2" type="text"> (inclusive)
		</td>
	</tr>
	<tr>
		<td onclick="new getObj('jobtype_relative').obj.checked = true;"><input id="jobtype_relative" type="radio" name="reporttype" value="relative" checked> Relative date:
			<select name="jobtype_relative_data" id="jobtype_relative_data">
				<option value="today" selected>Today</option>
				<option value="yesterday">Yesterday</option>
				<option value="lastweekday">Last Week Day</option>
			</select>
		</td>
	</tr>

	</table>
</td>
</tr>

<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Sort Options:</th>
<td class="bottomBorder">

	<table>
<!-- SORT -->
	<tr>
		<td>Sort results by:</td>
	</tr>
	<tr>
		<td>
		<select name="sort_by">
			<option value="lname" selected>Last,First</option>
			<option value="fname" >First, Last</option>
			<option value="attempt" >Attempt date</option>
			<option value="result" >Result</option>
			<option value="type" >Delivery Type</option>
		</select>
		</td>
	</tr>
	</table>

</td>
</tr>

<tr valign="top"><th align="right" class="windowRowHeader bottomBorder">Report Options:<br><? print help('ReportOptions_ReportOptions', NULL, 'grey'); ?></th>
<td class="bottomBorder">
	<table>

	<tr>
		<td><input id="option_jobpriority" type="checkbox" name="option_jobpriority" value="true" onclick="clearAllIfNotChecked(this,'option_jobpriority_select');"></td>
		<td>Include only these types of jobs:</td>
		<td style="width: 60">&nbsp;</td>
		<td><input id="option_result" type="checkbox" name="option_result" value="true" onclick="clearAllIfNotChecked(this,'option_result_select');"></td>
		<td>Include only results that are:</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td style="vertical-align: top">
		<select id="option_jobpriority_select" name="option_jobpriority_data[]" multiple onmousedown="setChecked('option_jobpriority');">
		<?
		$jobpriorities = DBFindMany("JobType","from jobtype where customerid=$USER->customerid");
		foreach ($jobpriorities as $jobpriority) {
			echo '<option value="' . $jobpriority->id . '">' . htmlentities($jobpriority->name . ($jobpriority->deleted ? " (deleted)" : "")) . '</option>';
		}
		?>
		</select>
		</td>
		<td style="width: 60">&nbsp;</td>
		<td>&nbsp;</td>
		<td style="vertical-align: top">
		<select id="option_result_select" name="option_result_data[]" multiple onmousedown="setChecked('option_result');">
			<option value="success">Successful</option>
			<option value="fail">Failed</option>
			<option value="inprogress">In Progress</option>
		</select>
		</td>
	</tr>

	<tr>
		<td><input id="option_jobtype" type="checkbox" name="option_jobtype" value="true" onclick="clearAllIfNotChecked(this,'option_jobtype_select');"></td>
		<td>Include only:</td>
		<td style="width:60">&nbsp;</td>
		<td><input id="option_callprogress" type="checkbox" name="option_callprogress" value="true" onclick="clearAllIfNotChecked(this,'option_callprogress_select');"></td>
		<td>Include only phone calls that are:</td>
	</tr>

	<tr>
		<td>&nbsp;</td>
		<td style="vertical-align: top">
		<select id="option_jobtype_select" name="option_jobtype_data[]" multiple onmousedown="setChecked('option_jobtype');">
			<option value="phone">Phone</option>
			<option value="email">Email</option>
			<option value="print">Print</option>
		</select>
		</td>
		<td style="width: 60">&nbsp;</td>
		<td>&nbsp;</td>
		<td style="vertical-align: top">
		<select id="option_callprogress_select" name="option_callprogress_data[]" multiple onmousedown="setChecked('option_callprogress');">
			<option value="A">Answered</option>
			<option value="M">Machine</option>
			<option value="N">No Answer</option>
			<option value="B">Busy</option>
			<option value="X">Bad Number</option>
			<option value="F">Failed</option>
		</select>
		</td>
	</tr>

	</table>
</td>
</tr>

<tr valign="top"><th align="right" class="windowRowHeader">Filter Options:<br><? print help('ReportOptions_FilterOptions', NULL, 'grey'); ?></th>
<td>


	<table>
<!-- PERSON KEY -->
	<tr>
		<td><input id="filter_pkey" type="checkbox" name="filter_pkey" value="true"></td>
		<td>Include only delivery attempts to the following person ID#:</td>
	</tr>
	<tr>
		<td>&nbsp;</td>
		<td>
		<input type="text" name="filter_pkey_data" onclick="new getObj('filter_pkey').obj.checked=true;">
		</td>
	</tr>
	<tr>
			<td><input id="filter_phone" type="checkbox" name="filter_phone" value="true"></td>
			<td>Include only delivery attempts to the following phone number:</td>
	</tr>
	<tr>
			<td>&nbsp;</td>
			<td>
			<input type="text" name="filter_phone_data" onclick="new getObj('filter_phone').obj.checked=true;">
			</td>
	</tr>
	</table>
</td>
</tr>



</table>

<?
endWindow();
buttons(submit('report', 'main', 'create_report', 'create_report'));
?>

</form>

<?
include_once("navbottom.inc.php");
?>
