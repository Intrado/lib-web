<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("obj/Message.obj.php");
require_once("inc/content.inc.php");
include_once("inc/appserver.inc.php");
require_once("obj/Content.obj.php");
require_once("obj/MessageAttachment.obj.php");

////////////////////////////////////////////////////////////////////////////////
// Authorization
////////////////////////////////////////////////////////////////////////////////
if (!$USER->authorize('managetasks')) {
	redirect('unauthorized.php');
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////
$id = $_GET['id'] + 0;
if (!$id)
	exit();

$messageattachment = new MessageAttachment($id);
if (!userOwns("message",$messageattachment->messageid))
	redirect('unauthorized.php');

if ($c = contentGet($messageattachment->contentid)) {
	list($contenttype,$data) = $c;

	if ($data) {
		$size = strlen($data);

		header("Pragma: private");
		header("Cache-Control: private");
		header("Content-Length: $size");
		header("Content-disposition: attachment; filename=\"" . $messageattachment->filename . "\"");
		header("Content-type: application/octet-stream");

		echo $data;
	}
}


?>