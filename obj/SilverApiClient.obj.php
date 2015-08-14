<?php

/**
 *  @author: Nathan Nicholas 
 */

class SilverApiClient {

	private $apiClient;
	private $appId;
	
	// basic auth credentials=
	private $username = 'silverappbuilder@relianceco.com';
	private $password = 'Z5h^w}3F?L(8U2D2';
	
	private $sessionId;

	/**
	 * Constructor - initialize CmaApiClient object
	 *
	 * @param ApiClient $apiClient
	 * @param int $appId
	 */
	public function __construct($apiClient, $appId = null) {
		$this->apiClient = $apiClient;
		$this->appId = $appId;
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
		
		return ($res['code'] == 200 ? json_decode($res['body'])->data : false);
	}

	
//	public function isValidAppId() {
//		$res = $this->apiClient->get("/1/apps/{$this->appId}");
//		return ($res['code'] == 200);
//	}
}

?>
