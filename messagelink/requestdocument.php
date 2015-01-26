<?
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

// $SETTINGS required by appserver.inc.php
$SETTINGS = parse_ini_file("messagelinksettings.ini.php",true);

date_default_timezone_set("US/Pacific");

//for logging
apache_note("CS_APP","ml");

require_once("inc/appserver.inc.php");

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
require_once("{$thriftdir}/Exception/TTransportException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/messagelink/Types.php");
require_once("{$thriftdir}/packages/messagelink/MessageLink.php");

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;
use messagelink\MessageAttachmentCodeNotFoundException;
use messagelink\MessageAttachmentRequestUnauthorizedException;

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

if (!isset($_POST['s']) || !isset($_POST['mal'])) {
	header("HTTP/1.0 400 Bad Request");
	exit();
}

$response = array();

// if SDD portionList is not in cache, then it may take some time to fetch a portion attachment
if (isset($SETTINGS['appserver']['requestAttachmentTimeout'])) {
	$timeout = $SETTINGS['appserver']['requestAttachmentTimeout'];
} else {
	$timeout = 55000; // about 55seconds
}

list($protocol, $transport) = initMessageLinkApp($timeout);

if($protocol == null || $transport == null) {
	error_log("Cannot use AppServer; MessageLinkClient failed to be initialized");
	// thrift protocol/transport failed to get initialized, stop now
	echoErrorResponse("An error occurred while trying to retrieve your document. Server unavailable.");

} else {
	$attempts = 0;
	while(true) {
		try {
			$client = new MessageLinkClient($protocol);
			$transport->open();
			try {
				if (isset($_POST['v']) && $_POST['v'] == true) {
					// NOTE: getAttachmentInfo() currently used to verify user-entered password
					// returning an AttachmentInfo object if successful, else an exception
					$attachmentInfo = $client->getAttachmentInfo($_POST['s'], $_POST['mal'], $_POST['p']);
				} else {
					// get attachment (pdf) content
					$emailAttachment = $client->getEmailAttachment($_POST['s'], $_POST['mal'], $_POST['p']);
				}

			// invalid MessageLinkCode ('s')
			} catch (MessageLinkCodeNotFoundException $e) {
				error_log("Unable to find the messagelinkcode: " . urlencode($_POST['s']));
				echoErrorResponse("The requested document was not found. The document you are looking for does not exist or has expired.");

			// invalid AttachmentLinkCode ('mal')
			} catch (MessageAttachmentCodeNotFoundException $e) {
				error_log("Unable to find the attachmentlinkcode: " . urlencode($_POST['mal']));
				echoErrorResponse("The requested document was not found. The document you are looking for does not exist or has expired.");

			// invalid password
			} catch (MessageAttachmentRequestUnauthorizedException $e) {
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

// if we make it this far, the user was successful at verifying password / requesting attachment.

header("HTTP/1.0 200 OK");
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Pragma: private");
header("Cache-Control: private");
header("Connection: close");

if (isset($attachmentInfo)) {
	header('Content-type: application/json');
	echo json_encode($attachmentInfo);

} else if (isset($emailAttachment)) {
	header('Content-type: ' . $emailAttachment->info->contentType);
	header("Content-Length: " . strlen($emailAttachment->data));
	header("Content-disposition: attachment; filename=\"" . $emailAttachment->info->filename . "\"");
	echo $emailAttachment->data;
}

?>