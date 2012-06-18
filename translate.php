<?
header('Content-Type: application/json');

require_once("inc/common.inc.php");
require_once("inc/translate.inc.php");

$responseObj = false;
$translationObj = false;

// Validate Length of both possible text inputs
if( (isset($_REQUEST['english']) && mb_strlen($_REQUEST['english']) > 5000) || 
	(isset($_REQUEST['text']) && mb_strlen($_REQUEST['text']) > 5000) ){
	error_log("Request is too large to send to Google. Text length: " . mb_strlen($_REQUEST['text']));
	$responseObj->responseData = "";
	$responseObj->responseDetails = NULL;
	$responseObj->responseStatus = 503;
} else {
	$translations = false;
	if(isset($_REQUEST['english']) && isset($_REQUEST['languages'])) {
		$languagearray = explode("|",$_REQUEST['languages']);
		$translations = translate_fromenglish($_REQUEST['english'], $languagearray);
		if ($translations === false) {
			error_log("Unable to translate request");
			$responseObj->responseData = "";
			$responseObj->responseDetails = NULL;
			$responseObj->responseStatus = 503;
		} else {
			$responseObj->responseData = array();
			$responseObj->responseDetails = "";
			$responseObj->responseStatus = 200;
			$i = 0;
			foreach($translations as $translation){
				$responseObj->responseData[$i]->code = $languagearray[$i];
				$responseObj->responseData[$i++]->translatedText = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($translation,ENT_QUOTES,"UTF-8"));
			}
		}
	} else if(isset($_REQUEST['text']) && isset($_REQUEST['language'])){
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


