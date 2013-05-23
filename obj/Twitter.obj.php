<?

class Twitter extends TwitterOAuth {

	protected $sesskey = 'twitter_userdata';

	public function __construct($jsonAccessToken = false) {
		global $SETTINGS;

		if ($jsonAccessToken) {
			$twitterdata = json_decode($jsonAccessToken);
			parent::__construct(
				$SETTINGS['twitter']['consumerkey'], 
				$SETTINGS['twitter']['consumersecret'],
				$twitterdata->oauth_token,
				$twitterdata->oauth_token_secret);
		} else {
			// no access. get generic connection
			parent::__construct(
				$SETTINGS['twitter']['consumerkey'], 
				$SETTINGS['twitter']['consumersecret']);
		}
	}
	
	public function hasValidAccessToken() {
		$userData = $this->getUserData();
		$result = isset($userData->screen_name) ? true : false;

		// If the result was negative...
		if (! $result) {

			// And we were using cached data
			if (isset($_SESSION[$this->sesskey])) {

				// Blow away the cache and try again - maybe something in the cache broke!
				unset($_SESSION[$this->sesskey]);
				$result = isset($userData->screen_name) ? true : false;
			}
		}
		return($result);
	}

	public function getUserData() {

		// Try to get userData from the session data cache
		$userData = null;

		if (isset($_SESSION[$this->sesskey])) {

			// Pull the userData from cache
			$userData = $_SESSION[$this->sesskey];

			// Note that this copy of userData did not come from cache
			$userData->fromCache = true;
		}

		if (! is_object($userData)) {
			try {

				// Otherwise get the userData fresh from Twitter
				$userData = $this->get('account/verify_credentials');

				// And add it to the user session cache
				$_SESSION[$this->sesskey] = $userData;

				// Note that this copy of userData did not come from cache
				$userData->fromCache = false;

			} catch (Exception $e) {
				// TODO - add a general exception catcher/logger class
				// nothing
				return false;
			}
		}

		return($userData);
	}
	
	// tweet a message to the user's status
	public function tweet($message) {
		
		$response = false;
		try {
			$response = $this->post('statuses/update', array('status' => $message));
		} catch (Exception $e) {
			error_log("Problem tweeting: ". $e);
			return false;
		}
		
		return($response ? true : false);
	}
}
?>
