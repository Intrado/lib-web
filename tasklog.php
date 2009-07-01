<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/form.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Import.obj.php");
require_once("obj/ImportLogEntry.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

if (isset($_GET['id'])) {
	$_SESSION['tasklogid'] = $_GET['id'] + 0;
	$_SESSION['tasklogpage'] = 0;
	redirect();
}

$id = $_SESSION['tasklogid'];
$IMPORT = new Import($id);


$entriesperpage = 500;
$entrycount = QuickQuery("select count(*) from importlogentry where importid=" . $_SESSION['tasklogid']);
$pages = ceil($entrycount/$entriesperpage);
$limitoffset = isset($_GET['pagestart']) ? $_GET['pagestart'] + 0 : 0;


$entries = DBFindMany("ImportLogEntry","from importlogentry where importid=" . $_SESSION['tasklogid'] . " order by severity, linenum asc limit $limitoffset,$entriesperpage");

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$PAGE = "admin:taskmanager";
$TITLE = "Import Log Viewer: " . escapehtml($IMPORT->name);
$DESCRIPTION = "Last run on: " . fmt_obj_date($IMPORT,"lastrun");

include_once("nav.inc.php");

buttons(button('Done', null, "tasks.php"));


startWindow('Import Log');

showPageMenu($entrycount, $limitoffset, $entriesperpage);

$titles = array("severity" => "Severity",
				"linenum" => "Line",
				"txt" => "Log Entry"
				);
$formatters = array("severity" => "fmt_ucfirst");

showObjects($entries,$titles,$formatters);

showPageMenu($entrycount, $limitoffset, $entriesperpage);


endWindow();
buttons();
include_once("navbottom.inc.php");
?>