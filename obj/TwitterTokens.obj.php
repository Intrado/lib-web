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
	 * Get the user data associated with the access token for the specified user id
	 */
	public function getUserDataForUserId($user_id) {

		// Early exit if there's no token data
		if (! (is_array($this->tokens) && (count($this->tokens) > 0))) return null;

		// So for each of the currently stored access tokens...
		foreach ($this->tokens as $token) {

			// If this is the one we're looking for...
			if ($token->user_id === $user_id) {

				// get twitter connection
				$twitter = new Twitter($token, false);
				$twitter->decode_json = false;
				return $twitter->getUserData();
			}
		}
		return null;
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
	 * @param object $token A stdClass object instance with the propertice of the access token to add
	 */
	public function addAccessToken($token) {
		if (! is_array($this->tokens)) {
			$this->tokens = array();
		}

		// Delete any other token that has this same user id in it
		$this->deleteAccessTokenForUserId($token->user_id, false);
		$this->tokens[] = $token;
		$this->storeAccessTokens();
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

