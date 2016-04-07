<?
/**
 * description: performs the same parsing and string length
 * check as done in translate.php for html-based, i.e. email, translations
 */

header('Content-Type: application/json');
require_once("inc/translate.inc.php");

$responseObj = new stdClass();
$responseObj->isValid = false;

if (isset($_POST['stringToTranslate'])) {
	$requestString = $_POST['stringToTranslate'];	
} else {
	die(json_encode($responseObj));	
}

// uses same method as in translate.php to determine length of string to be translated
$DOMDocumentObj = new DOMDocument();
$stringToTranslate = parse_html_to_node_string ($requestString, 'n', $DOMDocumentObj);
$stringToTranslateLength = mb_strlen($stringToTranslate);

$responseObj->isValid = $stringToTranslateLength <= 5000;
$responseObj->stringToTranslateLength = $stringToTranslateLength;

die(json_encode($responseObj));

?>