<?

class Twitter extends TwitterOAuth {

	protected $sess;
	protected $sesskey = 'twitter_userdata';

	public function __construct($jsonAccessToken = false, $sess = 0) {
		global $SETTINGS;

		if (is_object($sess)) {
			$this->sess = $sess;
		}

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
			if (is_object($this->sess) && $this->sess->check($this->sesskey)) {

				// Blow away the cache and try again - maybe something in the cache broke!
				$this->sess->del($this->sesskey);
				$userData = $this->getUserData();
				$result = isset($userData->screen_name) ? true : false;
			}
		}
		return($result);
	}

	public function getUserData() {

		// Try to get userData from the session data cache
		$userData = 0;

		if (is_object($this->sess) && $this->sess->check($this->sesskey)) {
			$userData = $this->sess->get($this->sesskey);
		}

		if (! $userData) {
			try {

				// Otherwise get the userData fresh from Twitter
				$userData = $this->get('account/verify_credentials');

				// And add it to the user session cache
				if (is_object($this->sess)) {
					$this->sess->set($this->sesskey, $userData);
				}

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
