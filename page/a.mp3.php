<?


////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("common.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


if (isset($_GET['full'])) {
	$partnum = 0;
} else if (isset($_GET['p'])) {
	$partnum = $_GET['p'] + 0;
	if ($partnum <= 0 || $partnum > 255) {
		error_log("invalid part number:" . $partnum);
		do404();
	}
} else {
	error_log("Invalid parameters passed to " . __FILE__);
	do404();
}

$filedata = reliablePageLinkCall("postAudioPartGetForCode", array($partnum));

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

header("HTTP/1.0 200 OK");
header("Cache-Control: max-age=3600, public");
header("Content-Type: " . $filedata->contenttype);
//header("Content-disposition: attachment; filename=message.mp3");
//header('Pragma: private');
//header('Cache-control: private, must-revalidate');
header("Content-Length: " . strlen($filedata->data));
header("Connection: close");
echo $filedata->data;
