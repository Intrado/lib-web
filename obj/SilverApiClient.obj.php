<?php

/**
 *  @author: Nathan Nicholas 
 */

class SilverApiClient {

	private $apiClient;
	private $appId;
	
	// basic auth credentials=
	private $username = '';
	private $password = '';
	
	private $sessionId;

	/**
	 * Constructor - initialize CmaApiClient object
	 *
	 * @param ApiClient $apiClient
	 * @param String username
	 * @param String password
	 * @param int $appId
	 */
	public function __construct($apiClient, $username, $password, $appId) {
		$this->apiClient = $apiClient;
		$this->appId = $appId;
		
		$this->username = $username;
		$this->password = $password;
		
		$this->sessionId = $this->getSessionId($appId);
	}
	
	// create a header for the basic auth request
	public function getBasicAuthHeader() {
		return array(
			"Authorization: Basic " . base64_encode($this->username . ':' . $this->password)
		);
	}
	
	// retrieve a session id to use with subsequent requests
	public function getSessionId() {
		$res = $this->apiClient->sendRequest('GET',"/api/account/verify_credentials.json?nid={$this->appId}", null, $this->getBasicAuthHeader());
		
		return ($res['code'] == 200 ? json_decode($res['body'])->session_id : false);
	}
	
	// retrieve list of categories 
	public function getCategories() {
		$res = $this->apiClient->get('/' . $this->appId . '/channels/objects/channels?embed=_notificationGroup&session_id=' . $this->sessionId);
		
		if (isset(json_decode($res['body'])->data)) {
			$data = json_decode($res['body'])->data;
			
			// if no categories at least return empty array
			$swappedCategories = array();
			
			// if array of categories is not empty
			if (!empty($data)) {
				$swappedCategories = $this->swapCategoryIdForNotificationGroupId($data);
			}
		}
		
		return ($res['code'] == 200 ? $swappedCategories : false);
	}
	
	private function swapCategoryIdForNotificationGroupId($categoryArray) {
		
		$arrayCount = count($categoryArray);
		
		for($i = 0; $i < $arrayCount; $i++) {
			$categoryArray[$i]->categoryId = $categoryArray[$i]->id;
			$categoryArray[$i]->id = $categoryArray[$i]->_notificationGroup->id;
		}
		
		return $categoryArray;
	}
}

?>
