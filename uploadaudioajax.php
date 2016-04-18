<?

include_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
include_once("inc/utils.inc.php");
include_once("obj/AudioFile.obj.php");
include_once("obj/MessageGroup.obj.php");
include_once("inc/content.inc.php");
include_once("obj/Content.obj.php");
include_once("obj/AudioConverter.obj.php");

global $USER, $SETTINGS;

$messageGroupId =  null;
$modifiedFilename = null;

// a response object defaulted to error
$response = array();

// NO FILE
if( empty( $_FILES ) ) {
	$response["status"] = 'error';
	$response["message"] = 'file missing';	

	$responseJSON = json_encode( $response );
	die( $responseJSON ); 
} 

$fileHandle = key($_FILES);

// If we need to modify the name before INSERTing it
if(isset( $_GET["modifiedfilename"] ))  {
	$_FILES[ $fileHandle ]['name'] = $_GET["modifiedfilename"];
}

$converter = new AudioConverter();

// we need to make sure the file extension is valid
$filename = $_FILES[ $fileHandle ]['name'];
$extension = pathinfo( $filename, PATHINFO_EXTENSION );
$extensionExploded = explode(' ', $extension);
$extensionWithoutNumber = $extensionExploded[0];
$extension = $extensionWithoutNumber;

$supportedFormats = $converter->getSupportedFormats();
$supportedFormatsString = implode( ", " , $supportedFormats );

if( ! in_array( $extension, $supportedFormats ) ) {
	error_log($extension);
	$response["status"] = 'error';
	$response["message"] = 'Audio clip must be one of these types (' . $supportedFormatsString . ' )';	

	$responseJSON = json_encode( $response );
	die( $responseJSON );	
}

// If the file is too large send error
$maxFileSize = $SETTINGS["messagesender"]["max_audio_file_upload_bytes"];
$errorTextMegabytes = round( $maxFileSize / 1048576 );

if( $_FILES[ $fileHandle ]['size'] > $maxFileSize ) {
	$response["status"] = 'error';
	$response["message"] = "Audio clip must be " . $errorTextMegabytes . " megabytes or less";	

	$responseJSON = json_encode( $response );
	die( $responseJSON );
}

$audio = new AudioFile();

$errorMessage = "";
$audioId = "";
$audioName = "";
$failedConversion = false;
$convertedFile = false;

try {
	$convertedFile = $converter->getMono8kPcm( $_FILES[ $fileHandle ]['tmp_name'], $_FILES[ $fileHandle ]['type']);
	$audio->contentid = contentPut($convertedFile, 'audio/wav');
} catch (Exception $e) {
	$failedConversion = true;
	error_log( $e->getMessage() );
}

@unlink($convertedFile);

if ( $failedConversion || !$audio->contentid ) {
	$response["status"] = 'error';
	$response["message"] = _L( 'There was an error reading your audio file. Please try another file. Supported formats include: %s', implode(', ', $converter->getSupportedFormats() ) );
	$responseJSON = json_encode( $response );
	die( $responseJSON );
}

$messagegroup = new MessageGroup( $messageGroupId );

//attempt to submit changes
$audio->userid = $USER->id;
$audio->deleted = 0;
$audio->permanent = $messagegroup->permanent;
$audio->messagegroupid = $messagegroup->id;
$audio->recorddate = date('Y-m-d G:i:s');

$filename = $_FILES[ $fileHandle ]['name'];
$audioFileIds = MessageGroup::getReferencedAudioFileIDs( $messagegroup->id );

$audioName = $audio->name = $filename;
$audio->update();
$audioId = $audio->id;

$response["status"] = 'success';
$response["fileId"] = $audioId;
$response["filename"] = $filename;
$responseJSON = json_encode( $response );
die( $responseJSON );

?>
