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

	private function sendRequest($method, $node, $data = Array()) {

		// Make a new curl request object with some default options
		$creq = curl_init($this->APIURL . $node);
		curl_setopt($creq, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($creq, CURLOPT_SSL_VERIFYPEER, 0);

		// Some initial headers we will need, but there may be more...
		$headers = array(
			"Accept: application/json",
			"X-Auth-SessionId: {$this->apiAuth}"
		);

		// Set some options based on the REST method
		switch ($method) {
			case 'GET':
				curl_setopt($creq, CURLOPT_GET, 1);
				break;

			case 'PUT':
				curl_setopt($creq, CURLOPT_PUT, 1);
				curl_setopt($creq, CURLOPT_POSTFIELDS, http_build_query($postData));
				break;

			case 'POST':
				// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
				curl_setopt($creq, CURLOPT_POST, 1);
				curl_setopt($creq, CURLOPT_POSTFIELDS, http_build_query($postData));
				$headers[] = 'Content-type: multipart/form-data';
				break;

			case 'DELETE':
				curl_setopt($creq, CURLOPT_DELETE, 1);
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

	protected function apiPut($node = '', $data = Array()) {
		return($this->sendRequest('PUT', $node, $data));
	}

	protected function apiPost($node = '', $data = Array()) {
		return($this->sendRequest('POST', $node, $data));
	}

	protected function apiDelete($node = '') {
		return($this->sendRequest('DELETE', $node));
	}
}

?>
