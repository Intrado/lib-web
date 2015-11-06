<?
////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/utils.inc.php");
include_once("inc/form.inc.php");
include_once("inc/html.inc.php");
include_once("inc/table.inc.php");
include_once("inc/content.inc.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/MessageGroup.obj.php");
include_once("obj/Content.obj.php");
include_once("obj/AudioConverter.obj.php");
require_once("inc/appserver.inc.php");

global $USER;

$audio = new AudioFile();
$errorMessage = "";
$audioId = "";
$audioName = "";

if (!isset($_GET['api'])) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

if (!($payload = json_decode(file_get_contents("php://input"), true))) {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json');

    exit(json_encode(Array("code" => "invalidSchema")));
}

$msgid = $payload['messageGroupId'];

if (!strlen($msgid)) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

if (!userOwns('messagegroup', $msgid)) {
    header("HTTP/1.1 404 Not Found");
    exit();
}

$audio->contentid = $payload['uploadId'];

if (!contentGet($payload['uploadId'])) {
    header("HTTP/1.1 400 Bad Request");
    header('Content-Type: application/json');

    exit(json_encode(Array("code" => "uploadNotFound")));
}

$messagegroup = new MessageGroup($msgid);

//attempt to submit changes
$audio->userid = $USER->id;
$audio->deleted = 0;
$audio->permanent = $messagegroup->permanent;
$audio->messagegroupid = $messagegroup->id;
$audio->recorddate = date('Y-m-d G:i:s');

$filename = $payload['name'];
$audioFileIds = MessageGroup::getReferencedAudioFileIDs($messagegroup->id);
$duplicateNames = count($audioFileIds) > 0 ? QuickQueryList('select name from audiofile where not deleted and id in (' . implode(',', $audioFileIds) . ') and name like ?', false, false, array($filename . '%')) : array();

// If there are any duplicate names, then find the largest sequence number so that we can set our final name to "$filename " . ($largestSequenceNumber + 1)
if (count($duplicateNames) > 0) {
    $largestSequenceNumber = 1;

    foreach ($duplicateNames as $duplicateName) {
        if (preg_match('/ \d+$/', $duplicateName, $matches)) {
            $sequenceNumber = intval($matches[0]);

            if ($sequenceNumber > $largestSequenceNumber) {
                $largestSequenceNumber = $sequenceNumber;
            }
        }
    }
    $finalAudioFilename = "$filename " . ($largestSequenceNumber + 1);
} else {
    $finalAudioFilename = $filename;
}

$audioName = $audio->name = $finalAudioFilename;
$audio->update();

header('Content-Type: application/json');

exit(json_encode(Array("id" => $audio->id, "audio" => cleanObjects($audio))));

?>
