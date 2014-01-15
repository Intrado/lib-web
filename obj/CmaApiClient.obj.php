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
     *  Constructor - initialize CmaApiClient object
     *
     *  @param array $options options/config array
     */
    public function __construct($options = array()) {
        if (count($options)) {
            $this->apiClient = $options['apiClient'];
            $this->appId     = $options['appId'];
        }
    }

    /**
     * Gets categories from CMA API for a given customer's CMA app Id
     *
     * @return array of objects, ex [{"id":1,"name":"School A"},{"id":2,"name":"School B"}, ...] or false
     */
    public function getCategories() {
        $res = $this->apiClient->get("/{$this->appId}/categories");
        return ($res['code'] == 200 ? json_decode($res['body']) : false);
    }
}

?>