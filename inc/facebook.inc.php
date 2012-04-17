<?
// get a new instance of the facebook api
$facebookapi = new FacebookEnhanced(array (
	'appId' => $SETTINGS['facebook']['appid'],
	'cookie' => false,
	'secret' => $SETTINGS['facebook']['appsecret']
));

// get a session
//$facebookapi->getSession();
$fbuser = $facebookapi->getUser();

// test the user's access token
function fb_hasValidAccessToken($accessToken = false) {
	global $facebookapi;
	global $USER;
	
	// get the user's auth data for facebook
	if ($accessToken)
		$access_token = $accessToken;
	else
		$access_token = $USER->getSetting("fb_access_token", false);
	
	// if we have an access token
	if ($access_token) {
		try {
			$data = $facebookapi->api("/me", array('access_token' => $access_token));
		} catch (FacebookApiException $e) {
			//error_log($e);
			return false;
		}
	} else {
		return false;
	}
	
	return true;
}

function fb_post($pageid, $pageAccessToken, $text) {
	global $facebookapi;
	global $USER;
	
	try {
		$facebookapi->api("/$pageid/feed", 'POST', array(
			'access_token' => $pageAccessToken,
			//'name' => $USER->firstname,
			//'caption' => "Caption...",
			//'source' => "http://www.schoolmessenger.com/source",
			//'link' => "http://www.schoolmessenger.com/link",
			//'description' => "Posted by application...",
			//'picture' => "http://schoolmessenger.com/i/home/taut-1.jpg",
			'message' => $text
		));
	} catch (FacebookApiException $e) {
		error_log($e);
		return false;
	}
	
	return true;
}

function fb_getExtendAccessToken($accessToken) {
	global $facebookapi;
	return $facebookapi->getExtendedAccessToken($accessToken);
}

function fb_updateUserAccessToken() {
	global $facebookapi;
	global $USER;
	
	$expiresOn = $USER->getSetting("fb_expires_on", 0);
	// if it will expire within 30 days of now, renew it
	if ($expiresOn && strtotime("now") - $expiresOn < (30*24*60*60)) {
		list($accessToken,$expiresIn) = fb_getExtendAccessToken($USER->getSetting("fb_access_token"));
		$USER->setSetting("fb_access_token", $accessToken);
		$USER->setSetting("fb_expires_on", strtotime("now") + $expiresIn);
	}
}

?>
