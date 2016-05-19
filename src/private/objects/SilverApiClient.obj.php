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
	
	// retrieve a session id to use with subsequent requests
	public function getSessionId() {
		$res = $this->apiClient->sendRequest('GET',"/api/account/verify_credentials.json?nid={$this->appId}", null, $this->getBasicAuthHeader());
		
		return ($res['code'] == 200 ? json_decode($res['body'])->session_id : false);
	}
	
	/**
	 *  getCategories() - retrieve list of categories 
	 *  @return array 
	 */
	public function getCategories() {
		$res = $this->apiClient->get('/' . $this->appId . '/channels/objects/channels?embed=_notificationGroup&session_id=' . $this->sessionId);
		
		if (isset(json_decode($res['body'])->data)) {
			$rawCategoryArray = json_decode($res['body'])->data;
			
			$newCategoryArray = array();
			
			if (!empty($rawCategoryArray)) {
				$newCategoryArray = $this->createCategoryObjectArray($rawCategoryArray);
			}
		}
		
		return ($res['code'] == 200 ? $newCategoryArray: false);
	}
	
	// create a header for the basic auth request
	private function getBasicAuthHeader() {
		return array(
			"Authorization: Basic " . base64_encode($this->username . ':' . $this->password)
		);
	}
	
	/**
	 *  getCategories() - convert category array from Silver API to simply object array
	 *  @param array 
	 *  @return array - example: [object(name: 'category-name', id: 'category-name')]
	 */
	private function createCategoryObjectArray($rawCategoryArray) {
		
		$customCategoryObjs = array();
		
		foreach($rawCategoryArray as $categoryObj) {
			$newCategoryObj = $this->createCategoryObject($categoryObj);
			$customCategoryObjs[] = $newCategoryObj;
		}
		
		return $customCategoryObjs;
	}
	
	
	/**
	 *  createCategoryObject() - strip out category props we don't need
	 *  @param Object
	 *  @return Object
	 */
	private function createCategoryObject($categoryObj) {
		
		$newCategoryObj = (object) array(
			'name' => $categoryObj->_notificationGroup->name,
			'id'=> $categoryObj->_notificationGroup->id
		);
		
		return $newCategoryObj;
	}
	
}

?>
