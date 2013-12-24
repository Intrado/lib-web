<?

class CommsuiteApiClient {

	const API_BURSTS = '/bursts';

	private $apiClient = null;

	public function __construct($apiClient) {
		$this->apiClient = $apiClient;
	}

	// ---------------------------------------------------------------------
	// PDF Bursting
	// ---------------------------------------------------------------------

	public function getBurstApiUrl() {
		return($this->apiClient->getApiUrl() . '/bursts');
	}

	public function getBurstList($start = null, $limit = null) {
		$querystring = '?';
		if ($start) $querystring .= '&start=' . intval($start);
		if ($limit) $querystring .= '&limit=' . intval($limit);

		$res = $this->apiClient->get(self::API_BURSTS . "/{$querystring}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	public function getBurstData($id) {
		$res = $this->apiClient->get(self::API_BURSTS . "/{$id}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	public function setBurstData($id, $name, $template) {

		// If the burst ID is null then we're creating a new one
		if (is_null($id)) {

			// Get the key for the fileSet (in case some screwy future
			// version supplies multiple sets in a single POST)
			$fileSetKeys = array_keys($_FILES);
			$fileSetKey = array_shift($fileSetKeys);

			// Get the first filename in this file set
			$filename = $_FILES[$fileSetKey]['name'];
			$filetype = $_FILES[$fileSetKey]['type'];
			$tempfile = $_FILES[$fileSetKey]['tmp_name'];
			$filesize = $_FILES[$fileSetKey]['size'];

			// On some clients filename may include the whole path - we want JUST the filename portion at the end
			// TODO - see if there's a library function for filename extraction or factor this out into a helper function?
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
			$res = $this->apiClient->post(self::API_BURSTS . '/upload', $data);
			return($res['code'] == 201 ? true : false);
		}

		// Otherwise, a non-null burst id means we're modifying an existing one
		else {
			// We don't support PUTting ALL fields here, only some...
			$data = (object) null;
			$data->name = $name;
			$data->burstTemplateId = $template;

			$res = $this->apiClient->put(self::API_BURSTS . "/{$id}", $data);
			return($res['code'] == 200 ? true : false);
		}
	}

	public function deleteBurst($id) {
		$res = $this->apiClient->delete(self::API_BURSTS . "/{$id}");
		return($res['code'] == 200 ? true : false);
	}
}

?>
