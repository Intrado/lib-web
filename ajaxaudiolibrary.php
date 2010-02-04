<?php
require_once("inc/common.inc.php");
require_once("obj/AudioFile.obj.php");
require_once("inc/securityhelper.inc.php");

// All actions require a valid audiofile; the user must own the audiofile.
function handleRequest() {
	if (isset($_REQUEST['id']))
		$audiofileid = $_REQUEST['id'] + 0;
	
	if (!isset($_REQUEST['action']) || !isset($audiofileid) || !userOwns('audiofile', $audiofileid))
		return false;
	
	switch($_REQUEST['action']) {
		case 'renameaudiofile':
			if (isset($_REQUEST['newname']))
				$newname = trim($_REQUEST['newname']);
			
			// Check for blank name.
			if (!isset($newname) || strlen($newname) < 1) {
				return array(
					'error' => _L('The audio file name cannot be blank.')
				);
			}
			
			$audiofile = new AudioFile($audiofileid);
			
			// Check for duplicate names.
			if (QuickQuery('select id from audiofile where not deleted and id != ? and messagegroupid = ? and name = ? limit 1',
				false,
				array($audiofile->id, $audiofile->messagegroupid, $newname))
			) {
				return array(
					'error' => _L('There is already an audio file with that name.')
				);
			}
			
			// Update the audiofile's name.
			$audiofile->name = $newname;
			$audiofile->update();
			
			return true;
			
		// set the delete flag on an audio file
		case 'deleteaudiofile':
			// Get the audiofile object and set it to deleted
			$audiofile = new AudioFile($audiofileid);
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
