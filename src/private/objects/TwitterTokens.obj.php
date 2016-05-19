<?

/**
 * TwitterTokens - Storage abstraction class to Twitter oauth access tokens
 *
 * Note that the user_id / "user id" references are for the Twitter account user's ID.
 */
class TwitterTokens {
	protected $tokens;

	/**
	 * Constructor
	 */
	public function __construct() {
		global $USER;
		// Get the collection of tokens from the user's settings
		$this->tokens = json_decode($USER->getSetting('tw_access_token', false));
	}

	/**
	 * Get the complete collection of access tokens
	 */
	public function getAllAccessTokens() {
		return $this->tokens;
	}

	/**
	 * Get a single access token for the specified (Twitter) user ID
	 */
	public function getAccessToken($user_id) {
		// Early exit if there's no token data
		if (! (is_array($this->tokens) && (count($this->tokens) > 0))) return null;

		// So for each of the currently stored access tokens...
		foreach ($this->tokens as $token) {

			// If this is the one we're looking for...
			if ($token->user_id === $user_id) {
				return $token;
			}
		}

		return null;
	}

	/**
	 * Get the user data associated with the access token for the specified user id
	 */
	public function getUserDataForUserId($user_id) {

		// Early exit if there's no token data
		$token = $this->getAccessToken($user_id);
		if (is_null($token)) return null;

		// get twitter connection
		$twitter = new Twitter($token, false);
		$twitter->decode_json = false;
		return $twitter->getUserData();
	}

	/**
	 * Delete the access token for the specified user id from the current set
	 */
	public function deleteAccessTokenForUserId($user_id, $save = true) {

		// Early exit if there's no token data
		if (! (is_array($this->tokens) && (count($this->tokens) > 0))) return null;

		$finalTwAccessTokens = array();

		// so for each of the currently stored access tokens...
		foreach ($this->tokens as $token) {

			// If the account is a different one...
			if ($token->user_id !== $user_id) {

				// Migrate this one into the final collection
				$finalTwAccessTokens[] = $token;
			}
		}

		$this->tokens = $finalTwAccessTokens;
		if ($save) $this->storeAccessTokens();
	}

	/**
	 * Add the access token for to the current set
	 *
	 * @param object $token A stdClass object instance with the properties of the access token to add
	 */
	public function addAccessToken($token) {

		// Initizlize the token collection if it hasn't been already
		if (! is_array($this->tokens)) {
			$this->tokens = array();
		}

		// Make sure what we have is a valid token
		if (! is_object($token)) {
			error_log('TwitterTokens.addAccessToken() - supplied token is not a token: ' . print_r($token, true));
			return false;
		}
		if (! property_exists($token, 'oauth_token')) {
			error_log('TwitterTokens.addAccessToken() - supplied token missing oauth_token property: ' . print_r($token, true));
			return false;
		}

		// Delete any other token that has this same user id in it
		$this->deleteAccessTokenForUserId($token->user_id, false);
		$this->tokens[] = $token;
		$this->storeAccessTokens();

		return true;
	}

	/**
	 * Store the current set of access tokens
	 */
	protected function storeAccessTokens() {
		global $USER;

		// Update the final collection; false if it's an empty set causes the setting to be deleted
		$USER->setSetting('tw_access_token', (count($this->tokens) ? json_encode($this->tokens) : false));
	}
}

