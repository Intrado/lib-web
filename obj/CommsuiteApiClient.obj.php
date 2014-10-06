<?

class CommsuiteApiClient {

	const API_BURSTS = '/bursts';
	const API_PROFILES = '/profiles';
	const API_GUARDIAN_CATEGORIES = '/settings/guardiancategories';
	const API_PEOPLE = '/people';

	/** @var $apiClient ApiClient */
	private $apiClient;
	private $burstsBaseUrl;

	/**
	 * @param ApiClient $apiClient
	 */
	public function __construct($apiClient) {
		global $USER;
		$this->apiClient = $apiClient;
		$this->burstsBaseUrl = "/users/{$USER->id}" . self::API_BURSTS;
	}

	/**
	 * @param string $resourceUriFragment the fragment which identifies this resource. Example: "/organizations/1"
	 * @return bool|mixed the object or false if the request failed
	 */
	public function getObject($resourceUriFragment) {
		$res = $this->apiClient->get($resourceUriFragment);
		return($res['code'] == 200 ? json_decode($res['body']) : false);
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
	 * @param int $id profile id
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
			return ($res['code'] == 201 ? json_decode($res['body']) : false);
		} else {
			$profile->id = $id;
			$res = $this->apiClient->put(self::API_PROFILES."/".$id, $profile);
			return ($res['code'] == 200 ? json_decode($res['body']) : false);
		}

	}

	public function deleteProfile($id) {
		$res = $this->apiClient->delete(self::API_PROFILES . "/{$id}");
		return($res['code'] == 200 ? true : false);
	}

	// ---------------------------------------------------------------------
	// Guardian Categories
	// ---------------------------------------------------------------------
	
	/**
	 * get full list of guardian categories in the system
	 * @return boolean
	 */
	public function getGuardianCategoryList() {
		$res = $this->apiClient->get(self::API_GUARDIAN_CATEGORIES);
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}
	
	/**
	 * Get guardian category for given id
	 *
	 * @param int $id category id
	 * @return object GuardianCategory
	 */
	public function getGuardianCategory($id) {
		$res = $this->apiClient->get(self::API_GUARDIAN_CATEGORIES . "/{$id}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	/**
	 * Get guardian category associations for given id
	 *
	 * @param int $id category id
	 * @param int $start
	 * @param int $limit
	 * @return array of association data
	 */
	public function getGuardianCategoryAssoications($id, $start = null, $limit = null) {
		$queryParms = '?';
		if ($start != null)
			$queryParms .= '&start=' . intval($start);
		if ($limit)
			$queryParms .= '&limit=' . intval($limit);
		$url = self::API_GUARDIAN_CATEGORIES . "/{$id}/associations/{$queryParms}";
		$res = $this->apiClient->get($url);
		$associations = ($res['code'] == 200) ? json_decode($res['body']) : false;
		return $associations;
	}

	/**
	 * update/create guardian category
	 *
	 * @param string $id category id
	 * @param string $name category name
	 * @param int $accessid guardian profile id
	 * @param $sequence
	 * @return boolean true if success else false
	 */
	public function setGuardianCategory($id, $name, $accessid, $sequence) {
		$category =(object) null;
		$category->name = $name;
		$category->profileId = $accessid;
		$category->sequence = $sequence;
		if (is_null($id)) {
			$res = $this->apiClient->post(self::API_GUARDIAN_CATEGORIES."/", $category);
			return ($res['code'] == 201 ? json_decode($res['body']) : false);
		} else {
			$category->id = $id;
			$res = $this->apiClient->put(self::API_GUARDIAN_CATEGORIES."/".$id, $category);
			return ($res['code'] == 200 ? json_decode($res['body']) : false);
		}
	
	}
	
	/**
	 * delete the specified guardian category
	 * @param int $id
	 * @return boolean
	 */
	public function deleteGuardianCategory($id) {
		$res = $this->apiClient->delete(self::API_GUARDIAN_CATEGORIES . "/{$id}");
		return($res['code'] == 200 ? true : false);
	}
	
	
	//Person
	/**
	 * Get person and associations
	 *
	 * @param int $id person id
	 * @param string $expansions (csv of values)expansions: dependents | guardians
	 * @return object association data
	 */
	public function getPerson($id, $expansions) {
		$queryParms = '?';
		if ($expansions != null) {
			$queryParms .= 'expansions=' . $expansions;
		}
		$url = self::API_PEOPLE . "/{$id}/{$queryParms}";
		$res = $this->apiClient->get($url);
		$person = ($res['code'] == 200) ? json_decode($res['body']) : false;
		return $person;
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
	 * @return mixed object which contains the list of portions available on success or boolean false
	 */
	public function getBurstPortionList($burstId) {
		$res = $this->apiClient->get($this->burstsBaseUrl . "/{$burstId}/portions");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	// ---------------------------------------------------------------------
	// Organizations
	// ---------------------------------------------------------------------
	/**
	 * Get the organization
	 *
	 * GET /2/organizations/{orgId}
	 *
	 * @param string $orgId id of the organization to request data for
	 * @return mixed object which contains the organization and also contains a list of it's child organizations
	 */
	public function getOrganization($orgId) {
		return $this->getObject("/organizations/$orgId");
	}

	// ---------------------------------------------------------------------
	// Settings
	// ---------------------------------------------------------------------
	/**
	 * Get the specified feature setting (returns enable state of feature for organizations)
	 *
	 * GET /2/settings/features/{featureName}
	 *
	 * @param string $featureName name of the feature to request data for
	 * @return mixed object which contains the list of feature settings per organization for the requested feature
	 */
	public function getFeature($featureName) {
		return $this->getObject("/settings/features/$featureName");
	}

	public function setFeature($featureName, $newStateData) {
		$res = $this->apiClient->put("/settings/features/$featureName", $newStateData);
		return($res['code'] == 200 ? true : false);
	}
}

?>
