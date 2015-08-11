<?

class Twitter extends TwitterOAuth {

	protected $sesskey = 'twitter_userdata_cache';

	/**
	 * Three different modes of construction:
	 *
	 * 1) No args, makes an unautenticated conneciton with twitter
	 * 2) JSON string encoded twitter access token object
	 * 3) PHP object of the same thing that the JSON version encodes
	 *
	 * @param $accessToken Mixed access token JSON or PHP object or boolean false (opitonal)
	 * @param $fromJson Boolean true if accessToken is a JSON encoded object
	 *
	 */
	public function __construct($accessToken = false, $fromJson = true) {
		global $SETTINGS;
		if ($accessToken ) {
			$twitterdata = $fromJson ? json_decode($accessToken) : $accessToken;
			// Vary sesskey for userdata cache by user_id since we now support multiple accounts
			$this->sesskey .= "_{$twitterdata->user_id}";
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

	/**
	 * Purge the cached user data if it is set
	 * 
	 * @return boolean true if it was cached and removed, false if it wasn't cached
	 */
	public function purgeCachedUserData() {

		// Do we have user data cached?
		if (isset($_SESSION[$this->sesskey])) {

			// Purge it
			unset($_SESSION[$this->sesskey]);
			return(true);
		}

		return(false);
	}

	public function hasValidAccessToken() {

		// try to get the user data normally
		$userData = $this->getUserData();
		$result = isset($userData->screen_name) ? true : false;

		// If the result was negative, purge the cache and try again
		if ((! $result) && $this->purgeCachedUserData()) {
			$userData = $this->getUserData();
			$result = isset($userData->screen_name) ? true : false;
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
