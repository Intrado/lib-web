<?

if (!isset($_POST['s']) || !isset($_POST['mal'])) {
	header("HTTP/1.0 400 Bad Request");
	exit();
}

require_once('apiGetDocument.php');

// These "use" statements exist in apiGetDocument, but are limited in scope to THAT file only by default
use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

apiGetDocument($_POST['s'], $_POST['mal'], ((isset($_POST['v']) && $_POST['v'] == true) ? $_POST['p'] : null));
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

