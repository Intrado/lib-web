<?php

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/CmaApiClient.obj.php");

class CmaApiClientTest extends PHPUnit_Framework_TestCase {

    var $apiClient;
    var $cmaApiClient;
    var $cmaBaseApiUrl = 'https://sandbox.testschoolmessenger.com/cma/1'; // from settings.ini.php
    var $appId = 1000;

    public function setup() {

        // create mock ApiClient object and mock the get() method only (since it's the only method used in CmaApiClient)
        $this->apiClient = $this->getMockBuilder('ApiClient')
            ->setConstructorArgs(array($this->cmaBaseApiUrl))
            ->setMethods(array('get'))
            ->getMock();

        // define stub response for apiClient.get() used for fetching CMA category data
        $this->apiClient->expects($this->any())
            ->method('get')
            ->with("/{$this->appId}/categories")
            ->will($this->returnValue(
                array(
                    'headers' => "Accept: application/json", // dummy header
                    'body' => '[{"id":"1","name":"School A"},{"id":"2","name":"School B"}]', // dummy CMA categories response
                    'code' => 200
                )
        ));

        // create SUT
        $this->cmaApiClient = new CmaApiClient($this->apiClient);

    }

    public function tearDown() {
        unset($this->cmaApiClient);
    }

    public function test_getCategories() {
        // getCategories($appId) calls apiClient->get(url); ie stub above
        // gets back an array ob objects (from json_decode)
        $response = $this->cmaApiClient->getCategories($this->appId);

        // there should be only 2 elements in the $response response
        $this->assertEquals(2, count($response));

        // verify the values in the response
        $this->assertEquals(1, $response[0]->id);
        $this->assertEquals("School A", $response[0]->name);
        $this->assertEquals(2, $response[1]->id);
        $this->assertEquals("School B", $response[1]->name);

    }
}

?>