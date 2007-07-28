<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("obj/SmsJob.obj.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/formatters.inc.php");


////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('sendsms')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////


if (isset($_GET['delete'])) {
	$deleteid = $_GET['delete'] + 0;
	if (userowns("smsjob",$deleteid)) {
		$smsjob = new SmsJob($deleteid);
		$smsjob->deleted = 1;
		$smsjob->update();
	}

	redirect();
}


////////////////////////////////////////////////////////////////////////////////
// Display Functions
////////////////////////////////////////////////////////////////////////////////

function fmt_actions ($obj,$field) {
	return '<a href="reportsms.php?smsjobid=' . $obj->id . '">Report</a>&nbsp;|&nbsp;'
			.'<a href="smsjobs.php?delete=' . $obj->id . '" onclick="return confirmDelete();">Delete</a>';
}

function fmt_total ($obj,$field) {
	return QuickQuery("select count(*) from smsmsg where smsjobid=$obj->id");
}


////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////


$PAGE = "notifications:sms";
$TITLE = "SMS Jobs";

include_once("nav.inc.php");


startWindow('My SMS Jobs&nbsp;' . help('SMS_jobs'), 'padding: 3px;');

button_bar(button('Create New SMS Job', NULL,"smsjob.php?id=new"));

$data = DBFindMany("SmsJob","from smsjob where userid=$USER->id and deleted = 0 order by sentdate desc");
$titles = array(	"name" => "#Name",
					"description" => "#Description",
					"status" => "Status",
					"Total" => 'Total',
					"sentdate" => "Date Sent",
					"Actions" => "Actions"
					);
$formatters = array("Actions" => "fmt_actions",
					"sentdate" => "fmt_obj_date",
					"status" => "fmt_ucfirst",
					"Total" => 'fmt_total'
					);

showObjects($data, $titles,$formatters, count($data) > 10,  true);
endWindow();


include_once("navbottom.inc.php");
