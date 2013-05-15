<?php

/**
 * Session data access abstraction class
 *
 * This will allow us to change the behavior of various aspects of session data
 * access in a centralized location rather than having to scour the entire
 * application looking for pieces of code that touch what we are interested in.
 *
 * Note that this is an ACCESSOR class for an existing session data model and
 * does not itself create or destroy sessions (at this time). We could move all
 * our session management code into here...
*/
class Session extends Object {

	public function __construct() {
		parent::__construct();
		$this->set_classname(__CLASS__);
	}

	/**
	 * Check that the supplied key name is a valid string
	 *
	 * @return String key value unmodified if valid, otherwise throws exception
	 */
	private function check_key($key) {

		// Key must be a string data type or the request is invalid
		if (is_string($key) && strlen($key)) {
			switch ($key) {
				// TODO - add keys with special handling here
				default:
					return($key);
			}
		}

		$this->except("Requested session key [{$key}] is invalid", __LINE__);
	}

	/**
	 * Get the value from the session that maches the specified key
	 *
	 * @return mixed Value of session data with value associated with key on success else Exception
	 */
	public function get($key) {

		// If the key check doesn't return then it was no good
		switch ($this->check_key($key)) {
			// TODO - add keys with special handling here
			default:
				if ($this->check($key)) {
					return($_SESSION[$key]);
				}
				$this->except("Requested session key [{$key}] is undefined", __LINE__);
		}
	}

	/**
	 * Set an entry in the session with the supplied key/value pair
	 */
	public function set($key, $value) {

		// If the key check doesn't return then it was no good
		switch ($this->check_key($key)) {
			// TODO - add keys with special handling here
			default:
				$_SESSION[$key] = $value;
				return(true);
		}
	}

	/**
	 * Delete the specified key from the session
	 *
	 * @return boolean true on success else Exception
	 */
	public function delete($key) {

		// If the key check doesn't return then it was no good
		switch ($this->check_key($key)) {
			// TODO - add keys with special handling here
			default:
				if ($this->check($key)) {
					unset($_SESSION[$key]);
					return(true);
				}
				$this->except("Requested session key [{$key}] is undefined", __LINE__);
		}
	}


	/**
	 * Check that the specified key is set for the session
	 *
	 * @return boolean true if the key is set in session data, else false
	 */
	public function check($key) {

		// If the key check doesn't return then it was no good
		switch ($this->check_key($key)) {
			// TODO - add keys with special handling here
			default:
				return(isset($_SESSION[$key]) ? true : false);
		}
	}
}

?>
