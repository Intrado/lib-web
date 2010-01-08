<?php
require_once("inc/common.inc.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("inc/securityhelper.inc.php");

// All actions require a valid messagegroupid; the user must own the messagegroup.
function handleRequest() {
	if (!isset($_REQUEST['action']) || !isset($_REQUEST['messagegroupid']) || !userOwns('messagegroup', $_REQUEST['messagegroupid']+0))
		return false;

	$messagegroupid = $_REQUEST['messagegroupid'] + 0;

	switch($_REQUEST['action']) {
		case 'assignaudiofile':
			if (!isset($_REQUEST['audiofileid']) && !userOwns('audiofile', $_REQUEST['audiofileid'] + 0))
				return false;
			$audiofile = new AudioFile($_REQUEST['audiofileid'] + 0);
			$audiofile->messagegroupid = $messagegroupid;
			$audiofile->deleted = 0;
			$audiofile->update();
			return true;
			
		// set the delete flag on an audio file
		case 'deleteaudiofile':
			// check that audiofileid was passed in the request and that it is owned by this user
			if (!isset($_REQUEST['audiofileid']) && !userOwns('audiofile', $_REQUEST['audiofileid'] + 0))
				return false;

			// get the audiofile object and set it to deleted
			$audiofile = new AudioFile($_REQUEST['audiofileid']+0);
			$audiofile->deleted = 1;
			$audiofile->update();

			return true;

		default:
			error_log("Unknown request " . $_REQUEST['action']);
			return false;
	}

	return false;
}

header('Content-Type: application/json');
$data = handleRequest();
echo json_encode(!empty($data) ? $data : false);
?>
