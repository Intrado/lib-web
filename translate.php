<?
    $referer = $_SERVER["HTTP_REFERER"];
    if (!$referer) {
    	$referer = "asp.schoolmessanger.com";
    }
      
    $languagearray = explode(";",$_POST['languages']);
    
	$src_text = $_POST['english'];
	
	$destinationlanguages = array();
	foreach($languagearray as $language) {
			$destinationlanguages[] = $language; 
	}
	
	$supportedlanguages = array("catalan"=>"ca", "mandarin" =>"zh-TW","dutch"=>"nl", "english"=>"en", "finnish"=>"fi", "french"=>"fr", "german"=>"de", "greek"=>"el", "italian"=>"it", "polish"=>"pl", "portuguese"=>"pt-PT", "russian"=>"ru", "spanish"=>"es", "swedish"=>"sv");
	//$googlelanguages = array("arabic"=>"ar", "bulgarian"=>"bg", "catalan"=>"ca", "chinese"=>"zh", "chinese_simplified"=>"zh-CN", "chinese_traditional"=>"zh-TW", "croatian"=>"hr", "czech"=>"cs", "danish"=>"da", "dutch"=>"nl", "english"=>"en", "finnish"=>"fi", "french"=>"fr", "german"=>"de", "greek"=>"el", "hebrew"=>"iw", "hindi"=>"hi", "indonesian"=>"id", "italian"=>"it", "japanese"=>"ja", "korean"=>"ko", "latvian"=>"lv", "lithuanian"=>"lt", "norwegian"=>"no", "polish"=>"pl", "portuguese"=>"pt-PT", "romanian"=>"ro", "russian"=>"ru", "serbian"=>"sr", "slovak"=>"sk", "slovenian"=>"sl", "spanish"=>"es", "swedish"=>"sv", "ukrainian"=>"uk", "vietnamese"=>"vi");
	$lang_pairs = "";
	foreach ($destinationlanguages as $destlang){
		$lang = strtolower($destlang);
		if(array_key_exists($lang,$supportedlanguages)) {
			$lang_pairs .= "&langpair=" . urlencode("en|" . $supportedlanguages[$lang]);
		}
	}
	$src_texts_query = "&q=".urlencode($src_text);

	$url = "http://ajax.googleapis.com/ajax/services/language/translate?v=1.0".$src_texts_query . $lang_pairs;// . "&langpair=en%7Cen";
	
	$ch = curl_init();
	curl_setopt($ch, CURLOPT_URL, $url);
	curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
	curl_setopt($ch, CURLOPT_REFERER, $referer);   
	$body = curl_exec($ch);
	curl_close($ch);

	echo $body;
	
?>


