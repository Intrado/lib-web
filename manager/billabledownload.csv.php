<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
include("../inc/table.inc.php");

if (!$MANAGERUSER->authorized("aspreportgraphs")) {
	exit("Not Authorized");
}
$aspdb = SetupASPReportsDB();
if (is_null($aspdb)) {
	exit('aspreports not configured');
}

$customerid = $_GET['customerid']+0;

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename=billable_' . $customerid . '.csv');

$output = fopen('php://output', 'w');

fputcsv($output, array('date', 'attempted'));

$query = "SELECT date, attempted FROM billable WHERE customerid=?";
$rows = QuickQueryMultiRow($query, false, $aspdb, array($customerid));

foreach ($rows as $row) {
	fputcsv($output, $row);
}
?>