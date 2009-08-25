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
if (isset($_GET['id']))
	list($contenttype, $data) = contentGet(($_GET['id'] + 0), false);

$name = "reportarchive";
if (isset($_GET['name']))
	$name .= '_'. $_GET['name'];


if ($data) {
			header("HTTP/1.0 200 OK");
			header("Content-Type: application/zip");
			header("Content-disposition: attachment; filename=$name.zip");
			header("Pragma: private");
			header("Cache-control: private, must-revalidate");
			header("Content-Length: " . strlen($data));
			header("Connection: close");
			echo $data;
} else {
	echo _L("An error occurred trying to generate the report file. Please try again.");
}


