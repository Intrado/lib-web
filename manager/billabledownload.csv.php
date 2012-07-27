<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
include("../inc/table.inc.php");

if(!$MANAGERUSER->authorized("aspreportgraphs"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////

if(!$MANAGERUSER->authorized("aspreportgraphs"))
	exit("Not Authorized");

////////////////////////////////////////////////////////////////////////////////
// Request Processing
////////////////////////////////////////////////////////////////////////////////

$customerid = $_GET['customerid']+0;

////////////////////////////////////////////////////////////////////////////////
// Data and File Handling
////////////////////////////////////////////////////////////////////////////////
// output headers so that the file is downloaded rather than displayed
header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=billable_' . $customerid . '.csv');

// create a file pointer connected to the output stream
$output = fopen('php://output', 'w');

// output the column headings
fputcsv($output, array('date', 'attempted'));

// fetch the data
global $SETTINGS;
$conn = mysql_connect($SETTINGS['aspreports']['host'], $SETTINGS['aspreports']['user'], $SETTINGS['aspreports']['pass']);
mysql_select_db($SETTINGS['aspreports']['db'], $conn);

$query = "SELECT date, attempted FROM billable where customerid=$customerid";
$rows = mysql_query($query, $conn) or die(mysql_error());

// loop over the rows, outputting them
while ($row = mysql_fetch_assoc($rows)) fputcsv($output, $row);
?>