<?

// TODO Move array of supported languages out of here and update with the most current list of supported languages
$TRANSLATIONLANGUAGECODES = array(
	"af",
	"ar",
    "az",
	"be",
	"bg",
    "bn",
	"ca",
	"cs",
	"cy",
	"da",
	"de",
	"el",
	"en",
    "eo",
	"es",
	"et",
    "eu",
	"fa",
	"fi",
	"fil",
	"fr",
	"ga",
	"gl",
    "gu",
	"he",
	"hi",
	"hr",
	"ht",
	"hu",
	"id",
	"is",
	"it",
	"iw",
	"ja",
    "ka",
    "kn",
	"ko",
    "la",
	"lt",
	"lv",
	"mk",
	"ms",
	"mt",
	"nl",
	"no",
	"pl",
	"pt",
	"ro",
	"ru",
	"sk",
	"sl",
	"sq",
	"sr",
	"sv",
	"sw",
    "ta",
    "te",
	"th",
    "tl",
	"tr",
    "ur",
	"uk",
	"vi",
	"yi",
	"zh"
);


function googleTranslateV2($text, $sourcelanguage, $targetlanguages) {
	global $TRANSLATIONLANGUAGECODES, $SETTINGS;
	
	//Initialize session translation cache if necessary 
	if (!isset($_SESSION["translationcache"]))
		$_SESSION["translationcache"] = array();
	
	$translations = array();
	
	if (!isset($text) || !isset($sourcelanguage) || !isset($targetlanguages)) {
		error_log("Illigal argument(s) for googleTranslateV2");
		return $translations;
	}
	
	$codeconvertions = array("he" => "iw");
	if (isset($codeconvertions[$sourcelanguage]))
		$sourcelanguage = $codeconvertions[$sourcelanguage];
	
	if(!in_array($sourcelanguage,$TRANSLATIONLANGUAGECODES)){
		error_log("Illigal source language: $sourcelanguage for googleTranslateV2");
		return $translations;
	}
	
	if (!isset($SETTINGS['translation']['apikey'])) {
		error_log("Unable to find translation api key");
		return $translations;
	}
	
	$apiKey = $SETTINGS['translation']['apikey'];
	$referer = (isset($SETTINGS['translation']['referer']) && $SETTINGS['translation']['referer'])?$SETTINGS['translation']['referer']:"http://asp.schoolmessenger.com";
	$apiUrl = 'https://www.googleapis.com/language/translate/v2';
	
	//Cap translation
	if (mb_strlen($text) > 5000) {
		error_log("Request is too large to send to Google, Cap at 5000");
		$text = mb_substr($text,0,5000);
	}
	
	foreach ($targetlanguages as $targetlanguage) {
		if (isset($codeconvertions[$targetlanguage]))
			$targetlanguage = $codeconvertions[$targetlanguage];
		
		if(!in_array($targetlanguage,$TRANSLATIONLANGUAGECODES)){
			$translations[] = false;
			continue;
		}
		// Look for translations in cache
		$key = md5($text . $sourcelanguage . $targetlanguage);
		if (isset($_SESSION["translationcache"][$key])) {
			$translations[] = $_SESSION["translationcache"][$key];
			continue;
		}
		
		$post_data = array(
			'key' => $apiKey,
			'q' => $text,
			'source' => $sourcelanguage,
			'target' => $targetlanguage
		);

		$obj = doGoogleTranslateRequest_curl($apiUrl, $post_data, $referer);

		if(isset($obj->data->translations[0]->translatedText)) {
			$translation = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($obj->data->translations[0]->translatedText,ENT_QUOTES,"UTF-8"));
		} else {
			$translation = false;
		}
		
		$_SESSION["translationcache"][$key] = $translation;
		$translations[] = $translation;
	}
	
	return $translations;
}

/** 
 *  Parses html string into an object containing all the text nodes as both a 
 *  String ready for translation and as an Array of Strings for use after
 *  translation.
 * 
 *  @params: String $htmlString: the HTML string to have text nodes pulled out
 *  @params: String $delimiterTag: what will be used to delimit text nodes
 *  @params: $DOMDocumentObj: a DOMDocument object passed in from translate.php
 * 
 *  @returns String: string representation of XML nodes
 * 
 */

function parse_html_to_node_string ($htmlString, $delimiterTag = 'node', $DOMDocumentObj) {
	
	$DOMDocumentObj->loadHTML($htmlString);
	$xpath = new DOMXPath($DOMDocumentObj);
	
	$textNodes = $xpath->query('//text()');
	
	$nodeCount = $textNodes->length;
	
	// make our root tag a plural of our delimiter tag
	$delimiterRootTag = '<'.$delimiterTag.'s>';
	$delimiterCloseRootTag = '</'.$delimiterTag.'s>';
	
	// create a String which will be sent off for translation
	$nodeValueString = $delimiterRootTag;
	
	for ($i = 0; $i < $nodeCount; $i++) {
		
		$value = (String) $textNodes->item($i)->nodeValue;
		
		$nodeText = "<{$delimiterTag}>"
						. "{$value}"
					. "</{$delimiterTag}>";
		
		$nodeValueString .= $nodeText;
	}
	
	// close off our simple xml string
	$nodeValueString .= $delimiterCloseRootTag;
	
	return $nodeValueString;
}

/** 
 *  Takes string from translation service and substitutes the original text nodes 
 *  from the given HTML for the newly translated nodes.
 * 
 *  @params: String $templateString: the HTML string to have translated text nodes substituted
 *  @params: String $translatedNodeString: string of XML nodes returned from translation service
 *  @params: $DOMDocumentObj: a DOMDocument object passed in from translate.php
 * 
 *  @returns String: compiled HTML after translated text nodes have been inserted
 * 
 */

function parse_translated_nodes_to_html ($templateHtml, $translatedNodeString, $DOMDocumentObj) {
	
	$DOMDocumentObj->loadHTML($templateHtml);
	$xpath = new DOMXPath($DOMDocumentObj);
	
	$textNodes = $xpath->query('//text()');
	$nodeCount = $textNodes->length;
	
	$xml = simplexml_load_string($translatedNodeString);
	$translatedNodes = $xml->children();
		
	for ($i = 0; $i < $nodeCount; $i++) {
		
		if(trim($translatedNodes[$i]) !== '' ) {
			$textNodes->item($i)->nodeValue = (String) $translatedNodes[$i];
		}
	}
	
	// pull out the body tag of our document to reflect original HTML string.
	$restoredHTML = $xpath->document->saveHTML(
		$DOMDocumentObj->getElementsByTagName('body')->item(0)
	);
	
	// convert the UTF-8 characters created by xPath back into HTML entities
	// MAY BE OPTIONAL.
	//$restoredHTML = mb_convert_encoding($restoredHTML, "HTML-ENTITIES", "UTF-8");
	
	// remove the left-over body tag
	$restoredHTML = str_replace('<body>', '', $restoredHTML);
	$restoredHTML = str_replace('</body>', '', $restoredHTML);
	
	$restoredHTML = preg_replace('/<input value="(<<.+?>>)"[\\/]?>/', html_entity_decode('$1'), $restoredHTML);
	$restoredHTML = preg_replace('/<input value="(\&lt;\&lt;.+?\&gt;\&gt;)"[\\/]?>/', html_entity_decode('$1'), $restoredHTML);
	$restoredHTML = preg_replace('/<input value="(.+?)"[\\/]?>/', html_entity_decode('$1'), $restoredHTML);
	
	return $restoredHTML;
}

function set_error_response_and_log ($responseObj, $errorMsg) {
	error_log($errorMsg);
	$responseObj->responseData = "";
	$responseObj->responseDetails = NULL;
	$responseObj->responseStatus = 503;
	
	return $responseObj;
}

function translate_fromenglish($englishtext,$languagearray) {
	return googleTranslateV2($englishtext,'en',$languagearray);
}

function translate_toenglish($anytext,$anylanguage) {
	return googleTranslateV2($anytext,$anylanguage,array('en'));
}

function makeTranslatableString($str) {
	
	$str = preg_replace('/(<<.*?>>)/', '<input value="$1"/>', $str);
	$str = preg_replace('/({{.*?}})/', '<input value="$1"/>', $str);
	$str = preg_replace('/(\\[\\[.*?\\]\\])/', '<input value="$1"/>', $str);
	return $str;
}

function doGoogleTranslateRequest_curl($apiUrl, $post_data, $referrer) {
	
	$headers = array(
		"Referrer: $referrer",
		"Content-type: application/x-www-form-urlencoded",
		"X-HTTP-Method-Override: GET",
		"Connection: close"
	);

	// set up the curl request to the api url with post data and custom headers
	$handle = curl_init($apiUrl);
	curl_setopt($handle, CURLOPT_HTTPHEADER, $headers);
	curl_setopt($handle, CURLOPT_POST, count($post_data));
	curl_setopt($handle, CURLOPT_POSTFIELDS, http_build_query($post_data));
	curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);

	$response = curl_exec($handle);
	$statusCode = curl_getinfo($handle, CURLINFO_HTTP_CODE);
	if ($statusCode != 200)
		error_log("Google Translation Error $statusCode: $response");
	$obj = json_decode($response);
	
	curl_close($handle);

	return $obj;
}

function doGoogleTranslateRequest_fp($apiUrl, $post_data, $referer) {
	$apiUrl_parsed = parse_url($apiUrl);
	$host = $apiUrl_parsed['host'];
	$path = $apiUrl_parsed['path'];

	$data = http_build_query($post_data);

	// TODO timeout is currently 5 seconds
	$fp = fsockopen("ssl://" . $host, 443, $errno, $errstr, 5);

	if ($fp){
		// send the request headers:
		fputs($fp, "POST $path HTTP/1.1\r\n");
		fputs($fp, "Host: $host\r\n");

		if ($referer != '')
			fputs($fp, "Referer: $referer\r\n");
		fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");
		fputs($fp, "Content-length: ". strlen($data) ."\r\n");
		fputs($fp, "X-HTTP-Method-Override: GET\r\n");
		fputs($fp, "Connection: close\r\n\r\n");
		fputs($fp, $data);

		$result = '';
		while(!feof($fp)) {
			$result .= fgets($fp, 128);
		}
		fclose($fp);

		$result = explode("\r\n\r\n", $result, 2);
		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
		$statuscode = substr($header, 9, 3);

		if ($statuscode !== "200")
			error_log("Google Translation Error: $content");
		$obj = json_decode($content);
	} else {
		error_log("Unable to send translation request: (From: {$post_data['source']} to {$post_data['target']}) Truncated text: " . substr($post_data['q'],0, 20));
		$obj = false;
	}

	return $obj;
}

?>