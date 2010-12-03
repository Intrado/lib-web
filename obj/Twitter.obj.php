<?

class Twitter extends TwitterOAuth {

	function Twitter ($oauth_token = false, $oauth_token_secret = false) {
		global $SETTINGS;
		
		if ($oauth_token && $oauth_token_secret) {
			TwitterOAuth::__construct(
				$SETTINGS['twitter']['consumerkey'], 
				$SETTINGS['twitter']['consumersecret'],
				$oauth_token,
				$oauth_token_secret);
		} else {
			// no access. get generic connection
			TwitterOAuth::__construct(
				$SETTINGS['twitter']['consumerkey'], 
				$SETTINGS['twitter']['consumersecret']);
		}
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
		
		try {
			$twitter->post('statuses/update', array('status' => $message));
		} catch (Exception $e) {
			error_log("Problem tweeting: ". $e);
			return false;
		}
		return true;
	}
}
?>