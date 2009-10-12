<?

// Message Attachments for Subscriber only

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("common.inc.php");
require_once("../obj/FieldMap.obj.php");
require_once("../inc/html.inc.php");
require_once("../inc/table.inc.php");
require_once("../inc/utils.inc.php");
require_once("../obj/Message.obj.php");
require_once("../inc/content.inc.php");
require_once("../obj/Content.obj.php");
require_once("../obj/MessageAttachment.obj.php");


////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

$personid = $_SESSION['personid'];

if(isset($_GET['mid'])) {
	$mid = $_GET['mid']+0;
} else {
	exit();
}

$messageattachment = new MessageAttachment($mid);

$messageid = $messageattachment->messageid;
if(!QuickQuery("select count(*) from reportperson where personid = $personid and messageid = $messageid and type='email'")){
	redirect("unauthorized.php");
}

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
