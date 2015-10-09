<?
header('Content-Type: application/json');

require_once("inc/common.inc.php");
require_once("inc/translate.inc.php");

$responseObj = false;
$translationObj = false;

$originalRequestString;
$requestStringContainsHTML = false;

// will be instantiated if we need to parse HTML
$DOMDocumentObj;

// Validate Length of both possible text inputs
if( (isset($_REQUEST['english']) && mb_strlen($_REQUEST['english']) > 5000) || 
	(isset($_REQUEST['text']) && mb_strlen($_REQUEST['text']) > 5000) ) {
		$engLength = isset($_REQUEST['english'])?mb_strlen($_REQUEST['english']):0;
		$textLength = isset($_REQUEST['text'])?mb_strlen($_REQUEST['text']):0;
		error_log("Request is too large to send to Google. Text length: " . max($engLength, $textLength));
		$responseObj->responseData = "";
		$responseObj->responseDetails = NULL;
		$responseObj->responseStatus = 503;
} else {
	$translations = false;
	if(isset($_REQUEST['english']) && isset($_REQUEST['languages'])) {
		
		// save the original request html so we don't mangle it
		$originalRequestString = $_REQUEST['english'];
		
		// do we need to handle HTML parsing?
		if(string_contains_html($originalRequestString)) {
			$requestStringContainsHTML = true;
			$DOMDocumentObj = new DOMDocument();
		}
		
		// a version we may modify if it contains HTML
		$stringToTranslate = $originalRequestString;
		
		// compare string to an HTML-stripped version of the string.
		// if they are different, we have HTML tags
		if($requestStringContainsHTML) {
			$stringToTranslate = parse_html_to_node_string (
					$originalRequestString, 
					'n', 
					$DOMDocumentObj);
		}
    
		$languagearray = explode("|",$_REQUEST['languages']);
		$translations = translate_fromenglish($stringToTranslate, $languagearray);
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
				
				$translatedString = $translation;
				
				if($requestStringContainsHTML) {
					$translatedString = parse_translated_nodes_to_html(
							$originalRequestString, 
							$translation, 
							$DOMDocumentObj);
				}
				
				$responseObj->responseData[$i]->code = $languagearray[$i];
				$responseObj->responseData[$i++]->translatedText = $translatedString;
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
			$translationObj->translatedText = $translations[0];
			$responseObj->responseData = $translationObj;
		}
	}
}
echo json_encode($responseObj);
?>


