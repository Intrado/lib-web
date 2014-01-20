<?php

/**
 * class CmaApiClient
 *
 * Fetches "categories" from the CMA API (only requirement as of 1/14/2014; others TBD?)
 *
 * @author Justin Burns
 * @date 1/14/2014
 *
 */

class CmaApiClient {

	private $apiClient;
	private $appId;

	/**
	 * Constructor - initialize CmaApiClient object
	 *
	 * @param ApiClient $apiClient
	 * @param int $appId
	 */
	public function __construct($apiClient, $appId = null) {
		$this->apiClient = $apiClient;
		$this->appId = $appId;
	}

	/**
	 * Gets categories from CMA API for a given customer's CMA app Id
	 *
	 * @return array of objects, ex [{"id":1,"name":"School A"},{"id":2,"name":"School B"}, ...] or false
	 */
	public function getCategories() {
		$res = $this->apiClient->get("/apps/{$this->appId}/categories");
		return ($res['code'] == 200 ? json_decode($res['body']) : false);
	}
}

?>
