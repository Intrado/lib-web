<?


////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////

require_once("common.inc.php");
require_once("../inc/table.inc.php");

////////////////////////////////////////////////////////////////////////////////
// Action/Request Processing
////////////////////////////////////////////////////////////////////////////////


if (isset($_GET['id'])) {
	$id = $_GET['id'] + 0;
} else {
	do404();
}

$filedata = reliablePageLinkCall("postContentGetForCode", array($id));

////////////////////////////////////////////////////////////////////////////////
// Display
////////////////////////////////////////////////////////////////////////////////

header("HTTP/1.0 200 OK");
header("Cache-Control: max-age=3600, public");
header("Content-Type: " . $filedata->contenttype);

if (isset($_GET['fn'])) {
	header("Content-disposition: attachment; filename=" . urlencode($_GET['fn']));
}

//header("Content-disposition: attachment; filename=message.mp3");
//header('Pragma: private');
//header('Cache-control: private, must-revalidate');
header("Content-Length: " . strlen($filedata->data));
header("Connection: close");
echo $filedata->data;
