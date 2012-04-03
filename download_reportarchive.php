<?
require_once('inc/common.inc.php');
require_once('inc/securityhelper.inc.php');
require_once('inc/content.inc.php');
require_once('obj/Content.obj.php');
require_once('inc/appserver.inc.php');

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

// get all the content ids for this date
$contentid = QuickQuery("select contentid from reportarchive where reportdate = ?", false, array($_GET['date']));

if ($contentid) {
	
	$name = "reportarchive";
	if (isset($_GET['name']))
		$name .= '_'. $_GET['name'];
	
	header("HTTP/1.0 200 OK");
	header("Content-Type: application/zip");
	header("Content-disposition: attachment; filename=$name.zip");
	header("Pragma: private");
	header("Cache-control: private, must-revalidate");
	header("Connection: close");

	list($contenttype, $data) = contentGet($contentid, false);
	echo $data;
	
} else {
	echo _L("An error occurred trying to generate the report file. Please try again.");
}


