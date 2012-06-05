<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/Job.obj.php");
include_once("obj/Schedule.obj.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemrepeating')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////



////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:repeatingjobs";
$TITLE = _L("Repeating %s", getJobsTitle());

include_once("nav.inc.php");

startWindow(_L('Repeating %s ', getJobsTitle()) . help('System_RepeatingJobs'), 'padding: 3px;');

	if (getSystemSetting("disablerepeat") ) {
?>
		<table width="100%" border="0" cellpadding="0" cellspacing="0"><tr><td align=center><div class='alertmessage noprint'><?= _L("The System Administrator has disabled all Repeating %s. <br>No Repeating %s can be run while this setting remains in effect.", getJobsTitle(), getJobsTitle()) ?></div></td></tr></table>
<?
	}


$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;


$query = "select SQL_CALC_FOUND_ROWS u.login, j.name, schedule.nextrun, j.id, j.status, j.deleted, jobowner.login, jobowner.id, j.type, j.finishdate,
			name+0 as foo from job j
			left join user jobowner
				on (j.userid = jobowner.id)
			left join schedule on (j.scheduleid = schedule.id),
			user u
			where j.userid = u.id and j.status = 'repeating'
			group by j.id order by u.login,foo,name limit $start, $limit
";

$result = Query($query);

$data = array();
while ($row = DBGetRow($result)) {
	$data[] = $row;
}

$titles = array(
				"0" => _L('Submitted by'),
				"1" => _L('%s Name', getJobTitle()),
				"8" => _L("Type"),
				"2" => _L('Next Scheduled Run'),
				"9" => _L("Last Run"),
				"3" => _L('Actions'));
$formatters = array(
				"8" => "fmt_delivery_type_list",
				"2" => 'fmt_next_repeat',
				"3" => 'fmt_jobs_actions_customer',
				"9" => "fmt_date");

$query = "select FOUND_ROWS()";
$total = QuickQuery($query);
showPageMenu($total, $start, $limit);
echo "\n";
echo '<table width="100%" cellpadding="3" cellspacing="1" class="list">';
showTable($data, $titles,$formatters);
echo "\n</table>";
showPageMenu($total, $start, $limit);


endWindow();

include_once("navbottom.inc.php");
?>