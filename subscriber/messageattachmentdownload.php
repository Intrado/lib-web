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

$personid = $_SESSION['personid'];

// get the jobid
if (isset($_GET['jid'])) {
	$jobid = $_GET['jid']+0;
} else {
	exit();
}

// verify attachment was for message for job for this person
if (isset($_GET['attid'])) {
	$attachmentid = $_GET['attid']+0;
} else {
	exit();
}

$messageattachment = new MessageAttachment($attachmentid);
$messageid = $messageattachment->messageid;
if (!QuickQuery("select 1 from job j left join message m on (m.messagegroupid = j.messagegroupid) where j.id = ? and m.id = ?", null, array($jobid, $messageid))) {
	redirect("unauthorized.php");
}

if (!QuickQuery("select 1 from reportperson where jobid = ? and personid = ? and type='email'", null, array($jobid, $personid))) {
	redirect("unauthorized.php");
}

////////////////////////////////////////////////////////////////////////////////
// Data Handling
////////////////////////////////////////////////////////////////////////////////

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
