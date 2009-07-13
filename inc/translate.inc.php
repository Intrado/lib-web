<?



function getSupportedLanguages() {
	return array("arabic"=>"ar", "bulgarian"=>"bg", "catalan"=>"ca", "chinese"=>"zh", "croatian"=>"hr", "czech"=>"cs", "danish"=>"da", "dutch"=>"nl", "english"=>"en", "finnish"=>"fi", "french"=>"fr", "german"=>"de", "greek"=>"el", "hebrew"=>"iw", "hindi"=>"hi", "indonesian"=>"id", "italian"=>"it", "japanese"=>"ja", "korean"=>"ko", "latvian"=>"lv", "lithuanian"=>"lt", "norwegian"=>"no", "polish"=>"pl", "portuguese"=>"pt-PT", "romanian"=>"ro", "russian"=>"ru", "serbian"=>"sr", "slovak"=>"sk", "slovenian"=>"sl", "spanish"=>"es", "swedish"=>"sv", "ukrainian"=>"uk", "vietnamese"=>"vi");
}

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

		//error_log($response);
		
		$decoded = json_decode($response);
		if($decoded->responseStatus == 200) {
			if(is_array($decoded->responseData)){
				foreach($decoded->responseData as $obj){
					$obj->responseData->translatedText = html_entity_decode($obj->responseData->translatedText,ENT_QUOTES,"UTF-8");
				}
			} else {
				$decoded->responseData->translatedText = html_entity_decode($decoded->responseData->translatedText,ENT_QUOTES,"UTF-8");
			}
			return $decoded->responseData;
		} else {
			error_log("Google Translation Error: " . $response);
			return false;
		}
		//return $decoded;
		//echo json_encode($decoded);
	}
}

function translate_fromenglish($englishtext,$languagearray) {
		
	if(!isset($englishtext) || !isset($languagearray)) {
		return false;
	}

	$supportedlanguages = getSupportedLanguages();

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
		if(array_key_exists($lang,$supportedlanguages)) {
			$lang_pairs .= "&langpair=" . urlencode("en|" . $supportedlanguages[$lang]);
		} else {
			$lang_pairs .= "&langpair=" . urlencode("en|en"); //  If translation to a non suported language google has to return a value for that to not interfere with the ordering
		}
		
	}
	$text = "&q=".urlencode($src_text);

	//error_log($text . $lang_pairs );
	
	return googletranslate($text, $lang_pairs);
}

function translate_toenglish($anytext,$anylanguage) {
	if(!isset($anytext) || !isset($anylanguage)){
		return false;
	}

	$lang_pairs = "";
	$supportedlanguages = getSupportedLanguages();
	$language = $anylanguage;
	$text = $anytext;

	if(mb_strlen($text) > 4000) {//Cap translation
		error_log("Request is too large to send to Google, Cap at 4000");
    	$text = mb_substr($text,0,4000);
	}
	
	$language = strtolower($language);
	if(array_key_exists($language,$supportedlanguages)) {
		$text = "&q=" . urlencode($text);
		$lang_pairs = "&langpair=" . urlencode($supportedlanguages[$language] . "|en");
	}
	return googletranslate($text, $lang_pairs);
}




?>