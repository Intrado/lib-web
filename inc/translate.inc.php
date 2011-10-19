<?

// TODO Move array of supported languages out of here and update with the most current list of supported languages
$TRANSLATIONLANGUAGECODES = array(
	"af",
	"ar",
	"be",
	"bg",
	"ca",
	"cs",
	"cy",
	"da",
	"de",
	"el",
	"en",
	"es",
	"et",
	"fa",
	"fi",
	"fil",
	"fr",
	"ga",
	"gl",
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
	"ko",
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
	"th",
	"tr",
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

	$apiUrl = parse_url($apiUrl);
	
	$host = $apiUrl['host'];
	$path = $apiUrl['path'];
	
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
		} else {
			error_log("Unable to send translation request: (From: $sourcelanguage to $targetlanguage) Truncated text: " . substr($text,0, 20));
			$translations[] = false;
			continue;
		}
		
		$result = explode("\r\n\r\n", $result, 2);
		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
		
		$statuscode = substr($header, 9, 3);
		if ($statuscode !== "200")
			error_log("Google Translation Error: $content");
		
		$obj = json_decode($content);
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

?>