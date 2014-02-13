<?php

/**
 * class CmaApiClient
 *
 * Fetches "categories" from the CMA API (only requirement as of 1/14/2014; others TBD?)
 *
 * @author Justin Burns
 * @date 1/14/2014
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
	* The CMA categories for an individual app are obtained from
	* the following CMA endpoint: GET /apps/{appid}/streams/categories
	*
	* The response from requesting the endpoint above returns an object with a 'stream' property,
	* which contains the array of the categories we are seeking.
	*
	* for more details, see: https://reliance.atlassian.net/wiki/display/CMA/CMA+Service+API#CMAServiceAPI-GET/apps/{appid}/streams/categories
	 *
	 * @return array of objects, ex [{"id":1,"name":"School A"},{"id":2,"name":"School B"}, ...] or false
	 */
	public function getCategories() {
		$res = $this->apiClient->get("/1/apps/{$this->appId}/streams/categories");

		// now get the categories array from the 'stream' prop
		return ($res['code'] == 200 ? json_decode($res['body'])->stream : false);
	}

	/**
	 * Checks whether our appId is valid
	 *
	 * Also useful as a CMA API service "ping" for a known-valid appId.
	 *
	 * @return boolean true if our current appId is valid according to the remote service, else false
	 */
	public function isValidAppId() {
		$res = $this->apiClient->get("/1/apps/{$this->appId}");
		return ($res['code'] == 200);
	}
}

?>
