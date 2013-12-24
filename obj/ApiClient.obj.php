<?

class ApiClient {

	public $apiHostname = '';
	public $apiCustomer = '';
	public $apiUser = '';
	public $apiAuth = '';
	public $customerUrl = '';
	public $ApiUrl = '';

	/**
	 * Constructor
	 *
	 * @param string The hostname for REST Api access that we can reach server-side (localhost?)
	 * @param string The "Url Component" for the customer which identifies them uniquely in the Url
	 * @param integer The ID for the logged in user making the request
	 * @param string The contents of the session auth cookie for this logged in customer/user 
	 */
	public function __construct($apiHostname, $apiCustomer, $apiUser, $apiAuth) {

		$this->apiHostname = $apiHostname;
		$this->apiCustomer = $apiCustomer;
		$this->apiUser = $apiUser;
		$this->apiAuth = $apiAuth;

		// TODO - decide if we want to leverage a lower overhead internal, non-SSL Url (localhost?)
		// vs a public facing one and how to split the functionality here (trusted only access, etc)
		// and where to get the configuration data from (settings.ini.php anyone?)
		$this->customerUrl = "https://{$this->apiHostname}/{$this->apiCustomer}/";
		$this->ApiUrl = "{$this->customerUrl}api/2/users/{$this->apiUser}";
	}

	/**
	 * Getter for the ApiUrl that we formulated in the constructor
	 *
	 * This is useful for calling code in some cases because the Api is a public facing Api and
	 * it is sometimes necessary to produce a Url through it which is directly accessible to the
	 * end-user's client.
	 */
	public function getApiUrl() {
		return $this->ApiUrl;
	}

	/**
	 * Do the hard work of sending a request to the REST Api and get the response
	 *
	 * As it is currently written, we use curl library operations to do the socket work for us,
	 * but the overall implementation of this class is intended to be able to replace the curl
	 * solution with another one if a better option presents itself - and all that work may be
	 * performed within this single method; nothing outside of this method is aware that curl
	 * is the mechanism in use.
	 *
	 * Note that different request methods result in different encoding methods for data; PUT
	 * uses JSON encoding where POST uses regular POST data parameterization; GET/DELETE methods
	 * tend not to use the data since their arguments are in the query string ($node).
	 *
	 * @param string $method The REST Api request method, (all caps!) to use for this request
	 * @param string $node Any trailing Url path/node/querystring that is expected to follow
	 * the base ApiUrl; remember that ApiUrl does NOT already include a trailing slash
	 * @param mixed $data The data that will be encoded for sending with the request; optional,
	 * if not supplied, then no data will be sent!
	 *
	 * @return array An associative array with REST interface response information; 'headers'
	 * key supplies all the response headers, 'body' key supplies the entire response body which,
	 * if it is JSON, for example, the caller will need to do the decoding, and 'code' key
	 * supplies the numeric REST server response.
	 */
	public function sendRequest($method, $node, $data = null) {

		// Make a new curl request object with some default options
		$creq = curl_init($this->ApiUrl . $node);
		curl_setopt($creq, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($creq, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($creq, CURLOPT_HEADER, 1);

		// Some initial headers we will need, but there may be more...
		$headers = array(
			"Accept: application/json",
			"X-Auth-SessionId: {$this->apiAuth}"
		);

		// Set some options based on the REST method; GET is default, so no case for that
		switch ($method) {

			case 'PUT':
				// ref: http://developer.sugarcrm.com/2011/11/22/howto-do-put-requests-with-php-curl-without-writing-to-a-file/
				curl_setopt($creq, CURLOPT_CUSTOMREQUEST, 'PUT');
				$json = json_encode($data);
				curl_setopt($creq, CURLOPT_POSTFIELDS, $json);
				$headers[] = 'Content-Type: application/json';
				$headers[] = 'Content-Length: ' . strlen($json);
				break;

			case 'POST':
				// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
				curl_setopt($creq, CURLOPT_POST, 1);
				if (count($_FILES)) {
					curl_setopt($creq, CURLOPT_POSTFIELDS, $data);
					$headers[] = 'Content-type: multipart/form-data';
				}
				else {
					$json = json_encode($data);
					curl_setopt($creq, CURLOPT_POSTFIELDS, $json);
				}
				break;

			case 'DELETE':
				// ref: http://stackoverflow.com/questions/13420952/php-curl-delete-request
				curl_setopt($creq, CURLOPT_CUSTOMREQUEST, 'DELETE');
				break;
		}

		// Finalize the headers
		curl_setopt($creq, CURLOPT_HTTPHEADER, $headers);

		// Fire off the request and capture the response
		$response = curl_exec($creq);

		// Build up a return result array...
		$hdrsize = curl_getinfo($creq, CURLINFO_HEADER_SIZE);
		$res = array(
			'headers' => substr($response, 0, $hdrsize),
			'body' => substr($response, $hdrsize),
			'code' => curl_getinfo($creq, CURLINFO_HTTP_CODE)
		);

		// Close out the curl request
		curl_close($creq);

		return($res);
	}

	/**
	 * RESTFUL GET operation wrapper
	 *
	 * ex: apiGet('bursts/list?start=0&limit=10)
	 *
	 * @param string $node Any trailing Url path/node/querystring that is expected to follow
	 *
	 * @return array Passes through return data from f.sendRequest()
	 */
	public function get($node = '') {
		return($this->sendRequest('GET', $node));
	}

	/**
	 * RESTFUL PUT operation wrapper
	 *
	 * @param string $node Any trailing Url path/node/querystring that is expected to follow
	 * @param mixed $data The data that will be encoded for sending with the request; optional,
	 * if not supplied, then no data will be sent!
	 *
	 * @return array Passes through return data from f.sendRequest()
	 */
	public function put($node = '', $data = null) {
		return($this->sendRequest('PUT', $node, $data));
	}

	/**
	 * RESTFUL POST operation wrapper
	 *
	 * @param string $node Any trailing Url path/node/querystring that is expected to follow
	 * @param mixed $data The data that will be encoded for sending with the request; optional,
	 * if not supplied, then no data will be sent!
	 *
	 * @return array Passes through return data from f.sendRequest()
	 */
	public function post($node = '', $data = null) {
		return($this->sendRequest('POST', $node, $data));
	}

	/**
	 * RESTFUL DELETE operation wrapper
	 *
	 * @param string $node Any trailing Url path/node/querystring that is expected to follow
	 *
	 * @return array Passes through return data from f.sendRequest()
	 */
	public function delete($node = '') {
		return($this->sendRequest('DELETE', $node));
	}
}

?>
