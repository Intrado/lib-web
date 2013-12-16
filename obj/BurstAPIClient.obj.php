<?

class BurstAPIClient extends APIClient {

	public function __construct($args) {
		parent::__construct($args);
		$this->APIURL .= '/bursts'; // The API endpoint for PDF bursting
	}

	public function getBurstList($start = null, $limit = null) {
		$querystring = '?';
		if ($start) $querystring .= '&start=' . intval($start);
		if ($limit) $querystring .= '&limit=' . intval($limit);

		$res = $this->apiGet("/{$querystring}");
		return($res['code'] == 200 ? json_parse($res['body']) : false);
	}

	public function getBurstData($id) {
		$res = $this->apiGet("/{$id}");
		return($res['code'] == 200 ? json_parse($res['body']) : false);
	}

	public function putBurstData($id, $name, $template) {

		// We don't support PUTting ALL fields here, only some...
		$data = array(
			'name' => $name,
			'bursttemplateid' => $template
		);

		$res = $this->apiPut("/{$id}", $data);
		return($res['code'] == 200 ? true : false);
	}

	public function postBurst($name, $template) {

		// this method requires a file to have been uploaded...
		if (! count($_FILES)) return(false);

		// get all the uploaded files names (there should be one)
		$uploadedNames = array_keys($_FILES);
		// get the first one
		$uploadedName = $uploadedNames[0];
		// get the details for this one
		$uploadedFile = $_FILES[$uploadedName];

		// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
		$data = array(
			'name' => $name,
			'templateid' => $template,
			$uploadedName => "@{$uploadedFile['tmp_name']};type=application/pdf"
		);

		$res = $this->apiPost('/upload', $data);
		return($res['code'] == 200 ? true : false);
	}

	public function deleteBurst($id) {
		$res = $this->apiDelete("/{$id}");
		return($res['code'] == 200 ? true : false);
	}
}

?>
