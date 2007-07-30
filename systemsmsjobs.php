<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("obj/SmsJob.obj.php");
include_once("obj/SurveyQuestionnaire.obj.php");
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
if (!$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$start = 0 + (isset($_GET['pagestart']) ? $_GET['pagestart'] : 0);
$limit = 100;

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

$PAGE = "system:smsjobs";
$TITLE = "Completed SMS Jobs";

include_once("nav.inc.php");

startWindow('Completed SMS Jobs', 'padding: 3px;');



$total = QuickQuery("select count(*) from smsjob s inner join user u on (u.id = s.userid) where s.deleted = 0");


$smsjobs = DBFindMany("SmsJob","from smsjob s inner join user u on (u.id = s.userid) where s.deleted = 0 order by sentdate desc","s");


function fmt_actions ($obj,$field) {
	global $USER;

	$actions = "";
	if ($USER->id != $obj->userid) {
		$usr = new User($obj->userid);
		$actions .= '<a href="./?login=' . $usr->login . '">Login&nbsp;as&nbsp;this&nbsp;user</a>&nbsp;|&nbsp;';
	}
	$actions .= '<a href="reportsms.php?smsjobid=' . $obj->id . '">Report</a>';

	return $actions;
}

function fmt_smsjob_owner ($obj,$field) {
	$usr = new User($obj->userid);
	return $usr->login;
}

function fmt_total ($obj,$field) {
	return QuickQuery("select count(*) from smsmsg where smsjobid=$obj->id");
}


$titles = array ("Owner" => "Submitted by",
				"name" => 'Name',
				"status" => 'Status',
				"Total" => 'Total',
				"sentdate" => 'Date Sent',
				"Actions" => 'Actions');
$formatters = array(
				"Owner" => 'fmt_smsjob_owner',
				"sentdate" => 'fmt_obj_date',
				"status" => "fmt_ucfirst",
				"Total" => 'fmt_total',
				"Rate" => 'fmt_rate',
				"enddate" => 'fmt_job_enddate',
				"responses" => 'fmt_response_count',
				"Actions" => 'fmt_actions');

if(!$USER->authorize('leavemessage')){
	unset($titles["responses"]);
	unset($formatters["responses"]);
}

showPageMenu($total, $start, $limit);
showObjects($smsjobs, $titles, $formatters, false, false);
showPageMenu($total, $start, $limit);
endWindow();

include_once("navbottom.inc.php");

?>