<?

class CommsuiteApiClient {

	const API_BURSTS = '/bursts';
	const API_PROFILES = '/profiles';
	
	private $apiClient = null;

	public function __construct($apiClient) {
		$this->apiClient = $apiClient;
	}
	
	// ---------------------------------------------------------------------
	// Access Profiles
	// ---------------------------------------------------------------------
	
	public function getProfileApiUrl() {
		return($this->apiClient->getApiUrl() . API_PROFILES);
	}
	
	public function getProfileList() {
		//TODO call the API
		//$res = $this->apiClient->get(self::API_PROFILES);
		//return($res['code'] == 200 ? json_decode($res['body']) : false);
		
		$profileList = array();
		$accessList = DBFindMany("Access","from access where name != 'SchoolMessenger Admin'");
		foreach ($accessList as $a) {
			$access = new stdClass();
			$access->id = $a->id;
			$access->name = $a->name;
			$access->description = $a->description;
			$access->type = $a->type;
			$profileList[] = $access;
		}
		
		return $profileList;
	}
		
	// ---------------------------------------------------------------------
	// PDF Bursting
	// ---------------------------------------------------------------------

	public function getBurstApiUrl() {
		return($this->apiClient->getApiUrl() . API_BURSTS);
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

			// Trap file upload errors without having to shove them out to the API
			if (0 != $_FILES[$fileSetKey]['error']) return(false);

			// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
			$data = array(
				'name' => $name,
				'filename' => pathinfo($_FILES[$fileSetKey]['name'], PATHINFO_BASENAME),
				'file' => '@/' . realpath($_FILES[$fileSetKey]['tmp_name']) . ";type={$_FILES[$fileSetKey]['type']}"
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

	/**
	 * Get the list of portions for this burst
	 * 
	 * GET /2/users/{userid}/bursts/{burstid}/portions
	 * 
	 * @param int $burstId the id which identifies the burst
	 * 
	 * @return object which contains the list of portions available
	 */
	public function getBurstPortionList($burstId) {
		$res = $this->apiClient->get(self::API_BURSTS . "/{$burstId}/portions");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}
}

?>
