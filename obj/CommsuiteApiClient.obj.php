<?

class CommsuiteApiClient {

	const API_BURSTS = '/bursts';
	const API_PROFILES = '/profiles';
	
	private $apiClient = null;
	private $burstsBaseUrl = null;

	public function __construct($apiClient) {
		global $USER;
		$this->apiClient = $apiClient;
		$this->burstsBaseUrl = "/users/{$USER->id}" . self::API_BURSTS;
	}
	
	// ---------------------------------------------------------------------
	// Access Profiles
	// ---------------------------------------------------------------------
	
	public function getProfileList() {
		$res = $this->apiClient->get(self::API_PROFILES);
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}
	
	/**
	 * Get profile for given id
	 * 
	 * @param type $id profile id
	 * @return \Access
	 */
	public function getProfile($id) {
		$res = $this->apiClient->get(self::API_PROFILES . "/{$id}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	/**
	 *  update/create profile
	 * @@param string $id profile id 
	 * @param string $name profile name
	 * @param string $description profile description
	 * @param string $type profile type (cs, guardian)
	 * @param string $permissions array of permissions
	 * @return boolean true if success else false
	 */
	public function setProfile($id, $name, $description, $type, $permissions) {
		$profile =(object) null;
		$profile->name = $name;
		$profile->description = $description;
		$profile->type = $type;
		$profile->permissions = $permissions;
		if (is_null($id)) {
			$res = $this->apiClient->post(self::API_PROFILES."/", $profile);
		} else {
			$profile->id = $id;
			$res = $this->apiClient->put(self::API_PROFILES."/".$id, $profile);
		}
		return ($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	public function deleteProfile($id) {
		$res = $this->apiClient->delete(self::API_PROFILES . "/{$id}");
		return($res['code'] == 200 ? true : false);
	}

	// ---------------------------------------------------------------------
	// PDF Bursting
	// ---------------------------------------------------------------------

	public function getBurstApiUrl() {
		return($this->apiClient->getApiUrl() . $this->burstsBaseUrl);
	}

	public function getBurstList($start = null, $limit = null) {
		$querystring = '?';
		if ($start) $querystring .= '&start=' . intval($start);
		if ($limit) $querystring .= '&limit=' . intval($limit);

		$res = $this->apiClient->get($this->burstsBaseUrl . "/{$querystring}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	public function getBurstData($id) {
		$res = $this->apiClient->get($this->burstsBaseUrl . "/{$id}");
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
			$res = $this->apiClient->post($this->burstsBaseUrl . '/upload', $data);
			return($res['code'] == 201 ? true : false);
		}

		// Otherwise, a non-null burst id means we're modifying an existing one
		else {
			// We don't support PUTting ALL fields here, only some...
			$data = (object) null;
			$data->name = $name;
			$data->burstTemplateId = $template;

			$res = $this->apiClient->put($this->burstsBaseUrl . "/{$id}", $data);
			return($res['code'] == 200 ? true : false);
		}
	}

	public function deleteBurst($id) {
		$res = $this->apiClient->delete($this->burstsBaseUrl . "/{$id}");
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
		$res = $this->apiClient->get($this->burstsBaseUrl . "/{$burstId}/portions");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}
}

?>
