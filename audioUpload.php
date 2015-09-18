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
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");
include_once("obj/AudioConverter.obj.php");

global $USER;

if (!isset($_GET['api'])) {
	header("HTTP/1.1 404 Not Found");
	header('Content-Type: application/json');

    exit(json_encode(Array("code" => "resourceNotFound")));
}

if (empty($_FILES['audio'])) {
	header("HTTP/1.1 400 Bad Request");
	header('Content-Type: application/json');

	exit(Array('code' => 'fileUploadNotFound'));
}

$failedConversion = false;
$converter = new AudioConverter();
$convertedFile = false;

$filename = $_FILES['audio']['name'];
$size = $_FILES['audio']['size'];

try {
	$convertedFile = $converter->getMono8kPcm($_FILES['audio']['tmp_name'], $_FILES['audio']['type']);
	$contentId = contentPut($convertedFile, 'audio/wav');
} catch (Exception $e) {
	$failedConversion = true;
	error_log($e->getMessage());
}

@unlink($convertedFile);

if ($failedConversion || !$contentId) {
	header("HTTP/1.1 500 Internal Server Error");
	header('Content-Type: application/json');

	exit(json_encode(Array('code' => 'internalError', 'message' => $e->getMessage())));
}

$result = Array(
	'id' => $contentId,
	'name' => $filename,
	'size' => $size);

header('Content-Type: application/json');
exit(json_encode($result));

?>