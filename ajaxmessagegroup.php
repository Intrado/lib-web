<?
require_once("inc/common.inc.php");
require_once("obj/AudioFile.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("inc/securityhelper.inc.php");

// All actions require a valid messagegroupid; the user must own the messagegroup.
function handleRequest() {
	if (isset($_REQUEST['messagegroupid']))
		$messagegroupid = $_REQUEST['messagegroupid'] + 0;
	
	if (!isset($_REQUEST['action']) || !isset($messagegroupid) || !userOwns('messagegroup', $messagegroupid))
		return false;
	
	switch($_REQUEST['action']) {
		case 'assignaudiofile':
			if (isset($_REQUEST['audiofileid']))
				$audiofileid = $_REQUEST['audiofileid'] + 0;
			
			if (!isset($audiofileid) || !userOwns('audiofile', $audiofileid))
				return false;
			
			$audiofile = new AudioFile($audiofileid);
			
			$audiofile->messagegroupid = $messagegroupid;
			$audiofile->deleted = 0;
			
			// NOTE: The audio file already has a name, so we only need to worry about having to change its name due to duplicates.
			// NOTE: It is assumed that the sequence number does not go over 99; it is unlikely that the user has 99 duplicate audiofile names within a single messagegroup.
			// Truncate the preferred name to 47 characters, leaving 3 characters for " " . ($largestsequencenumber + 1).
			$preferredaudiofilename = SmartTruncate($audiofile->name, 47);
			
			// Find out if this messagegroup contains any audiofiles whose name begins with the preferred name.
			// Do not include this audiofile.
			$audiofileids = MessageGroup::getReferencedAudioFileIDs($messagegroupid);
			$i = array_search($audiofile->id, $audiofileids);
			if ($i !== false)
				array_splice($audiofileids, $i, 1);
			$duplicatenames = count($audiofileids) > 0 ? QuickQueryList('select name from audiofile where not deleted and id in (' . implode(',', $audiofileids) . ') and name like ?', false, false, array($preferredaudiofilename . '%')) : array();
			
			// If there are any duplicate names, then find the largest sequence number so that we can set our final name to "$preferredaudiofilename " . ($largestsequencenumber + 1)
			if (count($duplicatenames) > 0) {
				$largestsequencenumber = 1;
				
				foreach ($duplicatenames as $duplicatename) {
					if (preg_match('/ \d+$/', $duplicatename, $matches)) {
						$sequencenumber = intval($matches[0]);
						
						if ($sequencenumber > $largestsequencenumber) {
							$largestsequencenumber = $sequencenumber;
						}
					}
				}
				
				$finalaudiofilename = "$preferredaudiofilename " . ($largestsequencenumber + 1);
				$audiofile->name = $finalaudiofilename;
			}
			
			$audiofile->update();
			
			return $audiofile->name;
		
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
