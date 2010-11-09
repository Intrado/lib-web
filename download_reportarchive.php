<?
include_once('inc/common.inc.php');
include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once('obj/Content.obj.php');

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('viewsystemreports')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
if (!isset($_GET['date']))
	redirect('unauthorized.php');

error_log("Request for reportarchive date: ". $_GET['date']);

// get all the content ids for this date
$contentids = QuickQueryList("select contentid from reportarchive where date = ?", false, false, array($_GET['date']));

if ($contentids) {
	
	$name = "reportarchive";
	if (isset($_GET['name']))
		$name .= '_'. $_GET['name'];
	
	header("HTTP/1.0 200 OK");
	header("Content-Type: application/zip");
	header("Content-disposition: attachment; filename=$name.zip");
	header("Pragma: private");
	header("Cache-control: private, must-revalidate");
	header("Connection: close");

	foreach ($contentids as $contentid) {
		list($contenttype, $data) = contentGet($contentid, false);
		echo $data;
	}
	
} else {
	echo _L("An error occurred trying to generate the report file. Please try again.");
}


