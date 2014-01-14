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

    public function __construct($apiClient) {
        $this->apiClient = $apiClient;
    }

    /**
     * Gets categories from CMA API
     *
     * @param integer $appId - CMA app id
     * @return array of objects, ex [{"id":1,"name":"School A"},{"id":2,"name":"School B"}, ...] or false
     */
    public function getCategories($appId) {
        // full endpoint url = $this->apiClient->ApiUrl . "/{$appId}/categories"
        $res = $this->apiClient->get("/{$appId}/categories");
        return($res['code'] == 200 ? json_decode($res['body']) : false);
    }
}

?>