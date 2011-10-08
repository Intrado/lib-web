<?
header('Content-Type: application/json');

require_once("inc/common.inc.php");
require_once("inc/translate.inc.php");

$responseObj = false;
$translationObj = false;

if(isset($_REQUEST['text']) && isset($_REQUEST['language'])){
	if(strlen($_REQUEST['text']) > 5000){
		error_log("Request is too large to send to Google");
		$responseObj->responseData = "";
		$responseObj->responseDetails = NULL;
		$responseObj->responseStatus = 503;
	} else {
		$translations = translate_toenglish($_REQUEST['text'], $_REQUEST['language']);
		
		if ($translations === false || count($translations) != 1) {
			error_log("Unable to translate request");
			$responseObj->responseData = "";
			$responseObj->responseDetails = NULL;
			$responseObj->responseStatus = 503;
	
		} else {
			$responseObj->responseData = array();
			$responseObj->responseDetails = "";
			$responseObj->responseStatus = 200;
			$translationObj->translatedText = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($translations[0],ENT_QUOTES,"UTF-8"));
			$responseObj->responseData = $translationObj;
		}
	}
}

echo json_encode($responseObj);
?>


