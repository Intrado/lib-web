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

header('Cache-Control: no-cache'); //keep ie from breaking itself. files wouldn't play 2nd time
header("Content-Type: " . $filedata->contenttype);
header("Content-Length: " . strlen($filedata->data));

if (isset($_GET['dl'])) {
	header("Content-disposition: attachment; filename=message.mp3");
}

echo $filedata->data;
