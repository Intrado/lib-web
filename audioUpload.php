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
    $result = Array('status' => 'fail', 'error' => 'Not supported');

    header('Content-Type: application/json');
    echo json_encode($result);

    exit();
}

if (empty($_FILES['audio'])) {
    $result = Array('status' => 'fail', 'error' => 'No upload file');
} else {
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
        $result = Array('status' => 'fail', 'error' => L('There was an error reading your audio file. Please try another file. Supported formats include: %s',
            implode(', ', $converter->getSupportedFormats())));
	} else {
        $result = Array(
            'status' => 'success',
            'upload' => Array(
                'id' => (int)$contentId,
                'name' => $filename,
                'size' => $size));
    }
}

header('Content-Type: application/json');
echo json_encode($result);

?>