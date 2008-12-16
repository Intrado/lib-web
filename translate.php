<?
	$supportedlanguages = array("catalan"=>"ca", "mandarin" =>"zh-TW","dutch"=>"nl", "english"=>"en", "finnish"=>"fi", "french"=>"fr", "german"=>"de", "greek"=>"el", "italian"=>"it", "polish"=>"pl", "portuguese"=>"pt-PT", "russian"=>"ru", "spanish"=>"es", "swedish"=>"sv");
	//$googlelanguages = array("arabic"=>"ar", "bulgarian"=>"bg", "catalan"=>"ca", "chinese"=>"zh", "chinese_simplified"=>"zh-CN", "chinese_traditional"=>"zh-TW", "croatian"=>"hr", "czech"=>"cs", "danish"=>"da", "dutch"=>"nl", "english"=>"en", "finnish"=>"fi", "french"=>"fr", "german"=>"de", "greek"=>"el", "hebrew"=>"iw", "hindi"=>"hi", "indonesian"=>"id", "italian"=>"it", "japanese"=>"ja", "korean"=>"ko", "latvian"=>"lv", "lithuanian"=>"lt", "norwegian"=>"no", "polish"=>"pl", "portuguese"=>"pt-PT", "romanian"=>"ro", "russian"=>"ru", "serbian"=>"sr", "slovak"=>"sk", "slovenian"=>"sl", "spanish"=>"es", "swedish"=>"sv", "ukrainian"=>"uk", "vietnamese"=>"vi");
	$url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0";
	$text = "";
	$lang_pairs = "";
	
    $referer = $_SERVER["HTTP_REFERER"];
    if (!$referer) {
    	$referer = "asp.schoolmessanger.com";
    }
    
    if(isset($_POST['english']) && isset($_POST['languages'])) {
	    $languagearray = explode(";",$_POST['languages']);
	    
		$src_text = $_POST['english'];
		
		$destinationlanguages = array();
		foreach($languagearray as $language) {
				$destinationlanguages[] = $language; 
		}
		
		$lang_pairs = "";
		foreach ($destinationlanguages as $destlang){
			$lang = strtolower($destlang);
			if(array_key_exists($lang,$supportedlanguages)) {
				$lang_pairs .= "&langpair=" . urlencode("en|" . $supportedlanguages[$lang]);
			}
		}
		$text = "&q=".urlencode($src_text);
		
    } elseif(isset($_POST['text']) && isset($_POST['language'])){
    	$language = strtolower($_POST['language']);
    	if(array_key_exists($language,$supportedlanguages)) {
    		$text = "&q=" . urlencode($_POST['text']);
    		$lang_pairs = "&langpair=" . urlencode($supportedlanguages[$language] . "|en");
		}
    }
    if($text != "" && $lang_pairs != "") {
	
    	$context_options = array ('http' => array ('method' => 'POST','header'=> "Referer: $referer",'content' => $text . $lang_pairs));
		$context = stream_context_create($context_options);
    	$fp = @fopen($url, 'rb', false, $context);
		if (!$fp) {
			error_log("Unable to send to $url");
			return;
     	}
        $response = @stream_get_contents($fp);
    	if ($response === false) {
    		error_log("Unable to read from $url");
   			return; 		
       	}    	
       	echo $response;
    	/*
    	$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, $url);
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($ch, CURLOPT_REFERER, $referer);   
		$body = curl_exec($ch);
		curl_close($ch);
	
		echo $body;
		*/
    }
    
?>


