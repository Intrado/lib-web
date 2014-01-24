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
require_once("../inc/appserver.inc.php");
require_once("../obj/Content.obj.php");
require_once("../obj/MessageAttachment.obj.php");
require_once("../obj/ContentAttachment.obj.php");
require_once("../obj/BurstAttachment.obj.php");

// load the thrift api requirements.
$thriftdir = '../Thrift';
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/commsuite/Types.php");
require_once("{$thriftdir}/packages/commsuite/CommSuite.php");

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
if ($d = $messageattachment->getAttachmentData($personid)) {
	list($filename, $contentType, $data) = $d;
	$size = strlen($data);

	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-Length: $size");
	header("Content-disposition: attachment; filename=\"" . $filename . "\"");
	header("Content-type: " . ($contentType ? $contentType : "application/octet-stream"));

	echo $data;
}
?>