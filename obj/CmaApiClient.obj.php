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
     * @param string $username
     * @param string $password
     */
	public function __construct($apiClient, $appId = null, $username = "", $password = "") {
		$this->apiClient = $apiClient;
		$this->appId = $appId;

        // set curl option with username:password necessary for CMA API calls
        $this->apiClient->setAuth($username, $password);
	}

	/**
	 * Gets categories from CMA API for a given customer's CMA app Id
	 *
	 * @return array of objects, ex [{"id":1,"name":"School A"},{"id":2,"name":"School B"}, ...] or false
	 */
	public function getCategories() {
		$res = $this->apiClient->get("/1/apps/{$this->appId}/categories");
		return ($res['code'] == 200 ? json_decode($res['body']) : false);
	}
}

?>
