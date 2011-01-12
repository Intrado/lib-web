<?

class Twitter extends TwitterOAuth {
	
	function Twitter ($jsonAccessToken = false) {
		global $SETTINGS;
		
		if ($jsonAccessToken) {
			$twitterdata = json_decode($jsonAccessToken);
			TwitterOAuth::__construct(
				$SETTINGS['twitter']['consumerkey'], 
				$SETTINGS['twitter']['consumersecret'],
				$twitterdata->oauth_token,
				$twitterdata->oauth_token_secret);
		} else {
			// no access. get generic connection
			TwitterOAuth::__construct(
				$SETTINGS['twitter']['consumerkey'], 
				$SETTINGS['twitter']['consumersecret']);
		}
	}
	
	function hasValidAccessToken() {
		try {
			$userData = $this->getUserData();
			if (isset($userData->screen_name))
				return true;
		} catch (Exception $e) {
			// nothing
		}
		return false;
	}
	
	function getUserData() {
		try {
			return $this->get('account/verify_credentials');
		} catch (Exception $e) {
			return false;
		}
	}
	
	// tweet a message to the user's status
	function tweet($message) {
		
		$response = false;
		try {
			$response = $this->post('statuses/update', array('status' => $message));
		} catch (Exception $e) {
			error_log("Problem tweeting: ". $e);
			return false;
		}
		
		if ($response)
			return true;
		else
			return false;
	}
}
?>