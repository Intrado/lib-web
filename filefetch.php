<?

include_once("inc/common.inc.php");
include_once('inc/securityhelper.inc.php');
include_once('inc/content.inc.php');
include_once('inc/appserver.inc.php');
include_once('obj/Content.obj.php');
require_once("obj/Job.obj.php");
include_once('obj/tai/Tai_MessageAttachment.obj.php');


session_write_close();//WARNING: we don't keep a lock on the session file, any changes to session data are ignored past this point


function authorizeFile($id, $type) {
	switch ($type) {
		case "audiofile":
		case "messageattachment":
		case "tai_messageattachment":
			//all of these are supported in userCanSee
			return userCanSee($type, $id);
		default:
			return false;
	}
}

function getContentInfoForType($id, $type) {
	switch ($type) {
		case "tai_messageattachment":
			if (!userCanSee("tai_messageattachment", $id))
				return false;

			$tma = new Tai_MessageAttachment($id);

			list($contentType, $data) = contentGet($tma->contentid);
			return array(
				"contentType" => $contentType,
				"data" => $data,
				"filename" => $tma->filename
			);
		default:
			return false;
	}
}


/** 
 * checks authorization and sends the file out to the browser
 */
//TODO add download support, but please README about content-disposition security concerns.
function viewFile($id, $type) {
	$isAuthorized = authorizeFile($id, $type);
	if (!isAuthorized) {
		//TODO return unauthorized response
		return;
	}

	$contentInfo = getContentInfoForType($id, $type);

	if (!$contentInfo) {
		//TODO return 404 response
		return;
	}

	header("HTTP/1.0 200 OK");
	header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
	header('Content-type: ' . $contentInfo['contentType']);
	header("Pragma: private");
	header("Cache-Control: private");
	header("Content-Length: " . strlen($contentInfo['data']));
	header("Connection: close");
	echo $contentInfo['data'];

}

if(isset($_GET['id']) && isset($_GET['type'])) {
	viewFile(DBSafe($_GET['id']), $_GET['type']);
} else {
	//TODO return a 400 bad request response. maybe also log, cause this is probably a bug
}

?>
