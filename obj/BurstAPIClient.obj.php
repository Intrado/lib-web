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
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	public function getBurstData($id) {
		$res = $this->apiGet("/{$id}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	public function putBurstData($id, $name, $template) {

		// We don't support PUTting ALL fields here, only some...
		$data = (object) null;
		//$data->id = intval($id); // redundant. redundant.
		$data->name = $name;
		$data->burstTemplateId = $template;

		$res = $this->apiPut("/{$id}", $data);
		return($res['code'] == 200 ? true : false);
	}

	public function postBurst($name, $template) {

		// this method requires a file to have been uploaded...
		if (! count($_FILES)) return(false);

		// Get the key for the fileSet (in case some screwy future
		// version supplies multiple sets in a single POST)
		$fileSetKeys = array_keys($_FILES);
		$fileSetKey = array_shift($fileSetKeys);

		// Get the first filename in this file set
		$filename = $_FILES[$fileSetKey]['name'][0];
		$filetype = $_FILES[$fileSetKey]['type'][0];
		$tempfile = $_FILES[$fileSetKey]['tmp_name'][0];
		$filesize = $_FILES[$fileSetKey]['size'][0];

		// We are only going to allow PDF file mime types through to the API
		if ($filetype != 'application/pdf') {
			return(false);
		}

		// On some clients filename may include the whole path - we want JUST the filename portion at the end
		$filenameParts1 = explode('/', $filename);
		$filenameParts2 = explode('\\', $filename);
		// for clients with a frontslash path separator...
		if (count($filenameParts1) > 1) {
			$finalFilename = $filenameParts1[count($filenameParts1) - 1];
		}
		// for clients with a backslash path separator...
		else if (count($filenameParts2) > 1) {
			$finalFilename = $filenameParts2[count($filenameParts2) - 1];
		}
		// for clients that didn't send the path at all...
		else {
			$finalFilename = $filename;
		}

		// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
		$data = array(
			'name' => $name,
			'filename' => $finalFilename,
			'file' => '@/' . realpath($tempfile) . ";type={$filetype}"
		);

		// If a burst template ID was selected, it will be a number, otherwise an empty string (which we will not send)
		if (is_numeric($template) && (intval($template) != 0)) {
			$data['burstTemplateId'] = intval($template);
		}
		$res = $this->apiPost('/upload', $data);

		return($res['code'] == 201 ? true : false);
	}

	public function deleteBurst($id) {
		$res = $this->apiDelete("/{$id}");
		return($res['code'] == 200 ? true : false);
	}
}

?>
