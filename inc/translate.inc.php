<?

// TODO Move array of supported languages out of here and update with the most current list of supported languages
$TRANSLATIONLANGUAGECODES = array(
	"ar",
	"bg",
	"ca",
	"cs",
	"da",
	"de",
	"el",
	"en",
	"es",
	"fi",
	"fr",
	"hi",
	"hr",
	"id",
	"it",
	"iw",
	"ja",
	"ko",
	"lt",
	"lv",
	"nl",
	"no",
	"pl",
	"pt", // NOTE: Check for pt-PT before sending to google.
	"ro",
	"ru",
	"sk",
	"sl",
	"sr",
	"sv",
	"tl",
	"uk",
	"vi",
	"zh"
);


function googleTranslateV2($text, $sourcelanguage, $targetlanguages) {
	global $TRANSLATIONLANGUAGECODES, $SETTINGS;
	$translations = array();
	
	if (!isset($text) || !isset($sourcelanguage) || !isset($targetlanguages)) {
		error_log("Illigal argument(s) for googleTranslateV2");
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
	

	if ($sourcelanguage == "pt") // Google uses "pt-PT" for Portuguese.
		$sourcelanguage = "pt-PT";

	foreach ($targetlanguages as $targetlanguage) {
		if(!in_array($targetlanguage,$TRANSLATIONLANGUAGECODES)){
			$translations[] = false;
			continue;
		}
		if ($targetlanguage == "pt") // Google uses "pt-PT" for Portuguese.
			$targetlanguage = "pt-PT";
		
		$post_data = array(
			'key' => $apiKey,
			'q' => $text,
			'source' => $sourcelanguage,
			'target' => $targetlanguage
		);
		
		$data = http_build_query($post_data);
		
		// TODO timeout is currently 30 seconds
		$fp = fsockopen("ssl://" . $host, 443, $errno, $errstr, 30);
		
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
		}
		else {
			$translations[] = false;
		}
		
		fclose($fp);
		
		//TODO Check header, currently only reading content, on error content wikl be blank
		$result = explode("\r\n\r\n", $result, 2);
		$header = isset($result[0]) ? $result[0] : '';
		$content = isset($result[1]) ? $result[1] : '';
		
		$obj = json_decode($content);
		if(isset($obj->data->translations[0]->translatedText)) {
			$translation = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($obj->data->translations[0]->translatedText,ENT_QUOTES,"UTF-8"));
		} else {
			$translation = false;
		}
		
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