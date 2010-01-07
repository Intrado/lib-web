<?
header('Content-Type: application/json');

require_once("inc/common.inc.php");
require_once("inc/translate.inc.php");

	$supportedlanguages = getTranslationLanguages(false);
	$url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0";
	$url .= (isset($SETTINGS['translation']['apikey']) && $SETTINGS['translation']['apikey'])?"&key=" . $SETTINGS['translation']['apikey']:"";
	$text = "";
	$lang_pairs = "";

    $referer = $_SERVER["HTTP_REFERER"];
    if (!$referer) {
    	$referer = (isset($SETTINGS['translation']['referer']) && $SETTINGS['translation']['referer'])?$SETTINGS['translation']['referer']:"http://asp.schoolmessenger.com";
    }

    if(isset($_POST['english']) && isset($_POST['languages'])) {
    	$languages = $_POST['languages'];
    	$src_text = $_POST['english'];
    	if(get_magic_quotes_gpc()) {
    		$languages = stripslashes($_POST['languages']);
    		$src_text = stripslashes($_POST['english']);
    	}

    	if(mb_strlen($src_text) > 4000) //Cap translation
    		$src_text = mb_substr($src_text,0,4000);
    	
	    $languagearray = explode(";",$languages);

		$destinationlanguages = array();
		foreach($languagearray as $language) {
				$destinationlanguages[] = $language;
		}

		$lang_pairs = "";
		foreach ($destinationlanguages as $destlang){
			$lang = strtolower($destlang);
			if(array_key_exists($lang,$supportedlanguages)) {
				$lang_pairs .= "&langpair=" . urlencode("en|" . $lang);
			}
		}
		$text = "&q=".urlencode($src_text);

    } elseif(isset($_POST['text']) && isset($_POST['language'])){
        $language = $_POST['language'];
    	$text = $_POST['text'];
    	    	
    	if(get_magic_quotes_gpc()) {
    		$language = stripslashes($_POST['language']);
    		$text = stripslashes($_POST['text']);
    	}
    	if(mb_strlen($text) > 4000) //Cap translation
    		$text = mb_substr($text,0,4000);
   
    	$language = strtolower($language);
    	if(array_key_exists($language,$supportedlanguages)) {
    		$text = "&q=" . urlencode($text);
    		$lang_pairs = "&langpair=" . urlencode($language . "|en");
		}
    }
     elseif(isset($_GET['text']) && isset($_GET['language'])){
        $language = $_GET['language'];
    	$text = $_GET['text'];
    	if(get_magic_quotes_gpc()) {
    		$language = stripslashes($_POST['language']);
    		$text = stripslashes($_POST['text']);
    	}


    	$language = strtolower($language);
    	if(array_key_exists($language,$supportedlanguages)) {
    		$text = "&q=" . urlencode($text);
    		$lang_pairs = "&langpair=" . urlencode($language . "|en");
		}
    }
    if($text != "" && $lang_pairs != "") {
    	$content = $text . $lang_pairs;
    	if(strlen($content) > 4800){
    		error_log("Request is too large to send to Google");
			$enc->responseData = "";
    		$enc->responseDetails = NULL;
    		$enc->responseStatus = 503;
    		echo json_encode($enc);
   			exit();
    	}

    	$context_options = array ('http' => array ('method' => 'POST','header'=> "Referer: $referer",'content' => $content));
		$context = stream_context_create($context_options);
    	$fp = @fopen($url, 'rb', false, $context);
		if (!$fp) {
			error_log("Unable to send to $url");
			$enc->responseData = "";
    		$enc->responseDetails = NULL;
    		$enc->responseStatus = 503;
    		echo json_encode($enc);
   			exit();
		}
        $response = @stream_get_contents($fp);
    	if ($response === false) {
    		error_log("Unable to read from $url");
			$enc->responseData = "";
    		$enc->responseDetails = NULL;
    		$enc->responseStatus = 503;
    		echo json_encode($enc);
   			exit();
       	}
       	//$start = microtime(true);
		$decoded = json_decode($response);
       	if($decoded->responseStatus == 200) {
			if(is_array($decoded->responseData)){
				foreach($decoded->responseData as $obj){
					$obj->responseData->translatedText = reg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($obj->responseData->translatedText,ENT_QUOTES,"UTF-8"));
				}
			} else {
				$decoded->responseData->translatedText = preg_replace('/<input value="(.+?)"\\/>/', '$1', html_entity_decode($decoded->responseData->translatedText,ENT_QUOTES,"UTF-8"));
			}
       	} else {
			error_log("Google Translation Error: " . $response);
       	}
       	echo json_encode($decoded);
       	//$end = microtime(true);
		//error_log("Time to JSON_decode->html_entity_decode->JSON_encode: " . ($end - $start));
    }

?>


