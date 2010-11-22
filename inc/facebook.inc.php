<?
		
// get a new instance of the facebook api
$facebookapi = new Facebook(array (
	'appId' => $SETTINGS['facebook']['appid'],
	'cookie' => false,
	'secret' => $SETTINGS['facebook']['appsecret']
));

// get a session
$facebookapi->getSession();

// test the user's access token
function fb_hasValidAccessToken() {
	global $facebookapi;
	global $USER;
	
	// get the user's auth data for facebook
	$access_token = $USER->getSetting("fb_page_access_token", false);
	$pageid = $USER->getSetting("fb_pageid", "me");
	
	try {
		$data = $facebookapi->api("/$pageid", array('access_token' => $access_token));
	} catch (FacebookApiException $e) {
		error_log($e);
		return false;
	}
	
	return true;
}

function fb_post($text) {
	global $facebookapi;
	global $USER;
	
	// get the user's auth data for facebook
	$access_token = $USER->getSetting("fb_page_access_token", false);
	$pageid = $USER->getSetting("fb_pageid", "me");
	
	try {
		$facebookapi->api("/$pageid/feed", 'POST', array(
			'access_token' => $access_token,
			//'name' => "name field: ". $USER->firstname,
			//'caption' => "caption field: Caption...",
			//'source' => "http://www.schoolmessenger.com/source",
			//'link' => "http://www.schoolmessenger.com/link",
			//'description' => "description field: Posted by application...",
			//'picture' => "http://schoolmessenger.com/i/home/taut-1.jpg",
			'message' => "message field: ". $text
		));
	} catch (FacebookApiException $e) {
		error_log($e);
		return false;
	}
	
	return true;
}

?>
