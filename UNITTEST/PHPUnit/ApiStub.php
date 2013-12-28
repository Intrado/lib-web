<?

/**
 * REST API Client data stubbing class
 *
 * Substitute for obj/ApiClient.obj.php
 *
 * We're going to leverage the database stubbing class by making the sendRequest method
 * use a "database query" with a specially formatted, bogus query and respond with result
 * data that comes from that.
 *
 */
class ApiStub {

	protected $apiHostname = '';
	protected $apiCustomer = '';
	protected $apiUser = '';
	protected $apiAuth = '';
	protected $customerUrl = '';
	protected $ApiUrl = '';

	public function __construct($apiHostname, $apiCustomer, $apiUser, $apiAuth) {
		$this->apiHostname = $apiHostname;
		$this->apiCustomer = $apiCustomer;
		$this->apiUser = $apiUser;
		$this->apiAuth = $apiAuth;

		$this->customerUrl = "https://{$this->apiHostname}/{$this->apiCustomer}/";
		$this->ApiUrl = "{$this->customerUrl}api/2/users/{$this->apiUser}";
	}

        public function getApiUrl() {
		return $this->ApiUrl;
	}

	public function sendRequest($method, $node, $data = null) {

		// Our bogus query is designed to match a regular expression in the QueryRules
		// class of DBStub.php - it is in no way intended to include proper SQL syntax
		$bogusRequestObject = (object) null;
		$bogusRequestObject->method = $method;
		$bogusRequestObject->node = $node;
		$bogusRequestObject->data = $data;
		$bogusQuery = json_encode($bogusRequestObject);;
		$queryResult = Query($bogusQuery);

		// The one and only data result from the fake query will be the API Response
		$data = $queryResult->fetch(PDO::FETCH_ASSOC);

		// If QueryRules didn't give us anything useful (i.e. a response with headers/body/code defined)
		if (! is_array($data)) {

			// Then we'll structure a custom, default reply with nothing interesting in it
			$data = array(
				'headers' => 'Content-type: text/plain',
				'body' => 'No result data was returned for this operation',
				'code' => 0
			);
		}

		return($data);
	}

	public function get($node = '') {
		return($this->sendRequest('GET', $node));
	}

	public function put($node = '', $data = null) {
		return($this->sendRequest('PUT', $node, $data));
	}

	public function post($node = '', $data = null) {
		return($this->sendRequest('POST', $node, $data));
	}

	public function delete($node = '') {
		return($this->sendRequest('DELETE', $node));
	}
}

?>
