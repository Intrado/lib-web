<?

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

function googletranslate($text, $lang_pairs) {
	if($text == "" || $lang_pairs == "") {
		return false;
	} else {
		$url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0";
		$url .= (isset($SETTINGS['translation']['apikey']) && $SETTINGS['translation']['apikey'])?"&key=" . $SETTINGS['translation']['apikey']:"";
		
		if (!isset($_SERVER["HTTP_REFERER"]) || !$_SERVER["HTTP_REFERER"]) {
			$referer = (isset($SETTINGS['translation']['referer']) && $SETTINGS['translation']['referer'])?$SETTINGS['translation']['referer']:"http://asp.schoolmessenger.com";
		} else {
			$referer = $_SERVER["HTTP_REFERER"];
		}
		$content = $text . $lang_pairs;
		
		if(strlen($content) > 4800){
			error_log("Request is too large to send to Google");
			return false;
		}

		$context_options = array ('http' => array ('method' => 'POST','header'=> "Referer: $referer",'content' => $content));
		$context = stream_context_create($context_options);
		$fp = @fopen($url, 'rb', false, $context);
		if (!$fp) {
			error_log("Unable to send to $url");		
		}
		$response = @stream_get_contents($fp);
		if ($response === false) {
			error_log("Unable to read from $url");
			return false;
		}
		
		$decoded = json_decode($response);
		if($decoded->responseStatus == 200) {
			if(is_array($decoded->responseData)){
				foreach($decoded->responseData as $obj){
					$obj->responseData->translatedText = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($obj->responseData->translatedText,ENT_QUOTES,"UTF-8"));
				}
			} else {
				$decoded->responseData->translatedText = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($decoded->responseData->translatedText,ENT_QUOTES,"UTF-8"));
			}
			return $decoded->responseData;
		} else {
			error_log("Google Translation Error: " . $response);
			return false;
		}
	}
}

function translate_fromenglish($englishtext,$languagearray) {
	global $TRANSLATIONLANGUAGECODES;
	
	if(!isset($englishtext) || !isset($languagearray)) {
		return false;
	}
	
	$src_text = $englishtext;

	if(mb_strlen($src_text) > 4000) {//Cap translation
		error_log("Request is too large to send to Google, Cap at 4000");
    	$src_text = mb_substr($src_text,0,4000);
	}
	
	$destinationlanguages = array();
	foreach($languagearray as $language) {
		$destinationlanguages[] = $language;
	}

	$lang_pairs = "";
	foreach ($destinationlanguages as $destlang){
		$lang = strtolower($destlang);
		if(in_array($lang,$TRANSLATIONLANGUAGECODES)) {
			if ($lang == "pt") // Google uses "pt-PT" for Portuguese.
				$lang = "pt-PT";
			$lang_pairs .= "&langpair=" . urlencode("en|" . $lang);
		} else {
			$lang_pairs .= "&langpair=" . urlencode("en|en"); //  If translation to a non suported language google has to return a value for that to not interfere with the ordering
		}
		
	}
	$text = "&q=".urlencode($src_text);

	return googletranslate($text, $lang_pairs);
}

function translate_toenglish($anytext,$anylanguage) {
	global $TRANSLATIONLANGUAGECODES;
	
	if(!isset($anytext) || !isset($anylanguage)){
		return false;
	}

	$lang_pairs = "";
	$language = $anylanguage;
	$text = $anytext;

	if(mb_strlen($text) > 4000) {//Cap translation
		error_log("Request is too large to send to Google, Cap at 4000");
    	$text = mb_substr($text,0,4000);
	}
	
	$language = strtolower($language);
	if(in_array($language,$TRANSLATIONLANGUAGECODES)) {
		$text = "&q=" . urlencode($text);
		if ($language == "pt") // Google uses "pt-PT" for Portuguese.
			$language = "pt-PT";
		$lang_pairs = "&langpair=" . urlencode($language . "|en");
	}
	return googletranslate($text, $lang_pairs);
}

function makeTranslatableString($str) {
	$str = preg_replace('/(<<.*?>>)/', '<input value="$1"/>', $str);
	$str = preg_replace('/({{.*?}})/', '<input value="$1"/>', $str);
	$str = preg_replace('/(\\[\\[.*?\\]\\])/', '<input value="$1"/>', $str);
	return $str;
}

?>