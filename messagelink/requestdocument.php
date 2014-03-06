<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

// $SETTINGS required by appserver.inc.php
$SETTINGS = parse_ini_file("inc/settings.ini.php",true);

date_default_timezone_set("US/Pacific");

//for logging
apache_note("CS_APP","ml");

function escapeHtml($string) {
	return htmlentities($string, ENT_COMPAT, 'UTF-8') ;
}

require_once("inc/appserver.inc.php");

$thriftRequires = array(
	"Base/TBase.php",
	"Protocol/TProtocol.php",
	"Protocol/TBinaryProtocol.php",
	"Protocol/TBinaryProtocolAccelerated.php",
	"Transport/TTransport.php",
	"Transport/TSocket.php",
	"Transport/TBufferedTransport.php",
	"Transport/TFramedTransport.php",
	"Exception/TException.php",
	"Exception/TTransportException.php",
	"Exception/TProtocolException.php",
	"Exception/TApplicationException.php",
	"Type/TType.php",
	"Type/TMessageType.php",
	"StringFunc/TStringFunc.php",
	"Factory/TStringFuncFactory.php",
	"StringFunc/Core.php",
	"packages/messagelink/Types.php",
	"packages/messagelink/MessageLink.php"
);

foreach ($thriftRequires as $require) {
	require_once("Thrift/{$require}");
}

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

/*****************************************************************/

/**
 * @param string $errorMessage message to display to user
 */
function echoErrorResponse($errorMessage) {
	header("HTTP/1.0 400 Bad Request");
	header('Content-Type: application/json');
	echo json_encode(array("success" => false, "errorMessage" => $errorMessage));
	exit();
}

/*****************************************************************/

$response = array();

if (!isset($_GET['messageLinkCode']) || !isset($_GET['attachmentLinkCode'])) {
	header("HTTP/1.0 400 Bad Request");
	exit();
}

$response = array();

list($protocol, $transport) = initMessageLinkApp();

if($protocol == null || $transport == null) {
	error_log("Cannot use AppServer");
	// thrift protocol/transport failed to get initialized, stop now
	echoErrorResponse("An error occurred while trying to retrieve your document. Server unavailable.");

} else {
	$attempts = 0;
	while(true) {
		try {
			$client = new MessageLinkClient($protocol);
			$transport->open();
			try {
				// request attachment
				$attachmentInfo = $client->getAttachmentInfo($_GET['messageLinkCode'], $_GET['attachmentLinkCode'], $_GET['password']);

			// invalid MessageLinkCode ('s')
			} catch (messagelink_MessageLinkCodeNotFoundException $e) {
				error_log("Unable to find the messagelinkcode: " . urlencode($_GET['messageLinkCode']));
				echoErrorResponse("The requested document was not found. The document you are looking for does not exist or has expired.");

			// invalid AttachmentLinkCode ('mal')
			} catch (messagelink_MessageAttachmentCodeNotFoundException $e) {
				error_log("Unable to find the attachmentlinkcode: " . urlencode($_GET['attachmentLinkCode']));
				echoErrorResponse("The requested document was not found. The document you are looking for does not exist or has expired.");

			// invalid password
			} catch (messagelink_MessageAttachmentRequestUnauthorizedException $e) {
				echoErrorResponse("The password entered was incorrect.");
			}

			$transport->close();
			break;

		} catch (TException $tx) {
			// MessageLinkClient failed to get initialized properly
			$attempts++;
			error_log("getInfo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
			$transport->close();

			if($attempts > 2) {
				error_log("getInfo: Failed 3 times to get content from appserver");
				break;
			}

			// MessageLinkClient failed to get initialized properly after 3 attempts, therefore stop now.
			echoErrorResponse("An error occurred while trying to retrieve your document. Please try again.");
		}
	}
}

// if we make it this far, the user was successful at requesting attachment.
header("HTTP/1.0 200 OK");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header('Content-type: ' . $attachmentInfo->info->contentType);
header("Pragma: private");
header("Cache-Control: private");
header("Content-Length: " . strlen($attachmentInfo->data));
header("Connection: close");
echo $contentInfo->data;
exit();

?>