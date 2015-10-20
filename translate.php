<?
header('Content-Type: application/json');

require_once("inc/common.inc.php");
require_once("inc/translate.inc.php");

$responseObj = false;
$translationObj = false;

// will be instantiated if we need to parse HTML
$DOMDocumentObj;

// get type translation request
$requestType = '';
$requestString = '';
$requestStringLength = 0;

if ( isset($_REQUEST['text'] ) ) {
	
	$requestType = 'text';
	$requestString = $_REQUEST['text'];
	$requestStringLength = mb_strlen($requestString);
	
} else if ( isset($_REQUEST['english'] ) ) { 
	
	$requestType = 'english';
	$requestString = $_REQUEST['english'];
	$requestStringLength = mb_strlen($requestString);
	
}

// does request string contain HTML?
$requestStringContainsHTML = false;

if (isset($_REQUEST['html'])) {
	if ( $_REQUEST['html'] === 'string' ) {
		$requestStringContainsHTML = true;
	}
}

// do we have any languages to translate to? 
$languages = '';

if ( isset( $_REQUEST['languages'] ) ) {
	
	$languages = explode("|",$_REQUEST['languages']);
	
} else if ( $_REQUEST['language']) {
	
	$languages = $_REQUEST['language'];
}

// if request is for plain text to be translated and it's too long, send response now
if ($requestType === 'text' && $requestStringLength > 5000) {
	
	$responseObj = set_error_response_and_log(
			$responseObj, 
			'Request is too large to send to Google. Text length: '.mb_strlen($_REQUEST['text'])
	);
	
	die(json_encode($responseObj));
}

// if the text may have markup 
if ($requestType === 'english') {
	$stringToTranslate = $requestString;
	
	if($requestStringContainsHTML) {
		// create a DOMDocument object to pass into functions
		$DOMDocumentObj = new DOMDocument();

		// like this one; here we rip out the HTML which our translation service
		// does not care about
		$stringToTranslate = parse_html_to_node_string ($requestString, 'n', $DOMDocumentObj);
	}
	
	// is it still too large after HTML is stripped out? if so send error response 503
	if( $stringToTranslate > 5000 ) {
		$responseObj = set_error_response_and_log($responseObj, 'Request is too large to send to Google. Text length: '. mb_strlen($stringToTranslate));
		
		die(json_encode($responseObj));
	}
	
	$translations = translate_fromenglish($stringToTranslate, $languages);
	
	// if failed to translate, respond with error
	if($translations === false) {
		$responseObj = set_error_response_and_log($responseObj, 'Unable to translate request');
		
		die(json_encode($responseObj));
		
	}
	
	// otherwise, add our translated responses for each requested language
	$languageCounter = 0;
	foreach ($translations as $translation) {
		$translatedString = $translation;

		// if we stripped out HTML then we need to restore it
		if($requestStringContainsHTML) {
			$translatedString = parse_translated_nodes_to_html($requestString, $translation, $DOMDocumentObj);
		}

		// set response to success
		$responseObj->responseData = array();
		$responseObj->responseDetails = "";
		$responseObj->responseStatus = 200;
		
		// add in our translated langauges
		$responseObj->responseData[$languageCounter]->code = $languages[$languageCounter];
		$responseObj->responseData[$languageCounter]->translatedText = $translatedString;
		
		$languageCounter++;
	}
	
	die(json_encode($responseObj));
}

// this is used when translating text back to english
if($requestType === 'text') {

	if($languages === '') {
		set_error_response_and_log($responseObj, 'No language selected');
		
		die(json_encode($responseObj));
	}
	
	// in this instance $languages should only contain 1 language
	$translation = translate_toenglish($requestString, $languages);
	
	if($translation == false || count($translation) != 1) {
		$responseObj = set_error_response_and_log($responseObj, 'Unable to translate request');
		
		die(json_encode($responseObj));
	}
	
	$responseObj->responseData = array();
	$responseObj->responseDetails = "";
	$responseObj->responseStatus = 200;
	
	$translationObj->translatedText = $translation[0];
	$responseObj->responseData = $translationObj;
	
	die(json_encode($responseObj));
	
}

?>


