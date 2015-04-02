<?

/**
 * A simple API client wrapper for integrating with NetSuite
 *
 * First pass implementation has only support for posting feedback form data, however it is not
 * assumed that this will be the only (ever) form of NetSuite integration. As such, the feedback
 * related data/operations are named and commented accordingly so that other unrelated data / 
 * operations may be added (hopefully) without having to refactor the feedback code.
 */
class NetsuiteApiClient {

	/**
	 * Our working REST/JSON ApiClient class instance
	 */
	protected $apiClient;

	/**
	 * The feedback POST URI relative to the ApiClient's base URL (settings.ini.php::netsuite)
	 */
	protected $feedbackUri;

	/**
	 * An internally accumulated associative array of feedback data points from f.feedbackSet()
	 */
	protected $feedbackData;

	/**
	 * The previous raw response
	 */
	protected $response;


	/**
	 * @param object $apiClient ApiClient class instance
	 * @param string $feedbackUri The NetSuite feedback URI that we will POST to
	 */
	public function __construct($apiClient, $feedbackUri) {
		$this->apiClient = $apiClient;
		$this->feedbackUri = $feedbackUri;
		$this->feedbackReset();
	}

	/**
	 * Getter for our response
	 */
	public function getResponse() {
		return $this->response;
	}

	/**
	 * Reset (empty) the feedbackData structure in preparation for receiving new feedback data
	 *
	 * @return object $this for chaining...
	 */
	public function feedbackReset() {
		$this->feedbackData = array();
		return $this;
	}

	/**
	 * Set one of the feeback data properties
	 *
	 * This setter lets us check that the named property is a valid one (or record an error
	 * message if not). It also forces the value to be a string so that when it comes time to
	 * JSON encode the entire set, everything will be a quoted string in the result instead
	 * of treating some things as numbers  (like asp_id, userid, etc).
	 *
	 * @param string $name The name of the feedback data property we want to set
	 * @param string $value The value of the data property to set
	 *
	 * @return object $this for chaining...
	 */
	public function feedbackSet($name, $value) {
		switch (strtolower($name)) {
			case 'asp_id':		// Customer identifier which will be unique.
			case 'firstname':	// First name of the person providing feedback
			case 'lastname':	// Last name of the person providing feedback
			case 'emailaddress':	// Email address of the person providing feedback
			case 'phonenum':	// Phone number of the person providing feedback
			case 'feedbackcategory':// Feedback Category
			case 'feedbacktext':	// Long text for Feedback
			case 'sessiondata':	// Long text to store session information
			case 'userid':		// User Id of person providing feedback
			case 'feedbacktype':	// Type of feedback
			case 'trackingid':	// Tracking Id
			case 'userpage':	// Page user is on when providing feedback

				// Spec says all values are expected to be strings so we'll make sure of that...
				$this->feedbackData[$name] = "{$value}";
				break;

			default:
				error_log("NetsuiteApiClient::feedbackSet() - Attempted to set feedback data for an unknown property: '{$name}'");
				break;
		}

		return $this;
	}

	/**
	 * POST the feedback data to the NetSuite API service
	 *
	 * @return bool|mixed the object or false if the request failed
	 */
	public function captureUserFeedback() {
		$this->response = $this->apiClient->post($this->feedbackUri, $this->feedbackData);
		return($this->response['code'] == 200 ? json_decode($this->response['body']) : false);
	}
}

