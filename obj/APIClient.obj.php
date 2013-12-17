<?

class APIClient {

	protected $apiHostname = '';
	protected $apiCustomer = '';
	protected $apiUser = '';
	protected $apiAuth = '';
	protected $customerURL = '';
	protected $APIURL = '';

	public function __construct($args) {

		$this->apiHostname = $args['apiHostname'];
		$this->apiCustomer = $args['apiCustomer'];
		$this->apiUser = $args['apiUser'];
		$this->apiAuth = $args['apiAuth'];

		$this->customerURL = "https://{$this->apiHostname}/{$this->apiCustomer}/";
		$this->APIURL = "{$this->customerURL}api/2/users/{$this->apiUser}";
	}

	private function sendRequest($method, $node, $data = null) {

		// Make a new curl request object with some default options
		$creq = curl_init($this->APIURL . $node);
		curl_setopt($creq, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($creq, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($creq, CURLOPT_HEADER, 1);

		// Some initial headers we will need, but there may be more...
		$headers = array(
			"Accept: application/json",
			"X-Auth-SessionId: {$this->apiAuth}"
		);

		// Set some options based on the REST method
		switch ($method) {
			case 'GET':
				// Get is the default method type, so maybe there's nothing to do here...
				//curl_setopt($creq, CURLOPT_GET, 1);
				break;

			case 'PUT':
				// ref: http://developer.sugarcrm.com/2011/11/22/howto-do-put-requests-with-php-curl-without-writing-to-a-file/
				$json = json_encode($data);
				curl_setopt($creq, CURLOPT_CUSTOMREQUEST, 'PUT');
				curl_setopt($creq, CURLOPT_POSTFIELDS, $json);
				$headers[] = 'Content-Type: application/json';
				$headers[] = 'Content-Length: ' . strlen($json);
				break;

			case 'POST':
				// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
				curl_setopt($creq, CURLOPT_POST, 1);
				curl_setopt($creq, CURLOPT_POSTFIELDS, http_build_query($data));
				$headers[] = 'Content-type: multipart/form-data';
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

	protected function apiGet($node = '') {
		return($this->sendRequest('GET', $node));
	}

	protected function apiPut($node = '', $data = null) {
		return($this->sendRequest('PUT', $node, $data));
	}

	protected function apiPost($node = '', $data = null) {
		return($this->sendRequest('POST', $node, $data));
	}

	protected function apiDelete($node = '') {
		return($this->sendRequest('DELETE', $node));
	}
}

?>
