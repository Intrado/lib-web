<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
include("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!$MANAGERUSER->authorized("aspreportgraphs"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Request Processing
////////////////////////////////////////////////////////////////////////////////

$customerid = $_GET['customerid'] + 0;

$startdate = isset($_GET['startdate']) ? $_GET['startdate'] : date("Ymd", time() - 60*60*24*365); //default 30 days
$enddate = isset($_GET['enddate']) ? $_GET['enddate'] : date("Ymd");

global $SETTINGS;
$conn = mysql_connect($SETTINGS['aspreports']['host'], $SETTINGS['aspreports']['user'], $SETTINGS['aspreports']['pass']);
mysql_select_db($SETTINGS['aspreports']['db'], $conn);

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////
$TITLE = "Customer Details";
$PAGE = "commsuite";
include_once("nav.inc.php");

startWindow(_L('Billable Calls'));
$startdate = date("Ymd", time() - 60*60*24*14);
echo('<img src="graph_billablelastseven.php?customerid='.$customerid.'"/><br/><br/>');
echo('<img src="graph_billablelast365.php?customerid='.$customerid.'"/><br/><br/>');
echo('<img src="graph_billablemonth.php?customerid='.$customerid.'"/><br/><br/>');
echo('<a href="billabledownload.csv.php?customerid='.$customerid.'">Download CSV</a>');
endWindow();

startWindow(_L('Contacts'));
echo('<img src="graph_contactsbymonth.php?customerid='.$customerid.'"/><br/><br/>');
echo('<img src="graph_systemcontactslast365.php?customerid='.$customerid.'"/>');
endWindow();

startWindow(_L('Disk Usage'));
echo('<img src="graph_diskusagelast365.php?customerid='.$customerid.'"/><br/><br/>');
echo('<img src="graph_diskusagepiechart.php?customerid='.$customerid.'&enddate='.$enddate.'"/>');
endWindow();

include_once("navbottom.inc.php"); 
?>