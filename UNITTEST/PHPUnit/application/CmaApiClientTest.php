<?php

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/CmaApiClient.obj.php");

class CmaApiClientTest extends PHPUnit_Framework_TestCase {

	var $apiClient;
	var $cmaApiClient;
	var $cmaBaseApiUrl = 'https://sandbox.testschoolmessenger.com'; // from settings.ini.php
	var $appId = 1000;

	public function setup() {

		// create mock ApiClient object and mock the get() method only (since it's the only method used in CmaApiClient)
		$this->apiClient = $this->getMockBuilder('ApiClient')
			->setConstructorArgs(array($this->cmaBaseApiUrl))
			->setMethods(array('get'))
			->getMock();


		// ref: http://phpunit.de/manual/3.6/en/test-doubles.html#test-doubles.stubs.examples.StubTest5.php
		$getValueMap = array(

			// define stub response for apiClient.get() used for fetching CMA category data
			array("/1/apps/{$this->appId}/streams/categories",
				array(
					'headers' => "Accept: application/json", // dummy header
					'body' => '{"id":"categories","stream":[{"id":"1","name":"School A"},{"id":"2","name":"School B"}]}', // dummy CMA categories response
					'code' => 200
				)
			),
	
			// define stub response for apiClient.get() used for validating appId
			array("/1/apps/{$this->appId}",
				array(
					'headers' => "Accept: application/json", // dummy header
					'body' => '{}', // uninteresting response object
					'code' => 200
				)
			)
		);

		// Multiple uses of this method controlled by the map above
		$this->apiClient->expects($this->any())
			->method('get')
			->will($this->returnValueMap($getValueMap)
		);

		$this->cmaApiClient = new CmaApiClient($this->apiClient, $this->appId);

	}

	public function tearDown() {
		unset($this->cmaApiClient);
	}

	public function test_getCategories() {
		// getCategories() calls apiClient->get(url)
		// gets back an array ob objects (from json_decode)
		$response = $this->cmaApiClient->getCategories();

		// there should be only 2 elements in $response
		$this->assertEquals(2, count($response));

		// verify the values in the response
		$this->assertEquals(1, $response[0]->id);
		$this->assertEquals("School A", $response[0]->name);
		$this->assertEquals(2, $response[1]->id);
		$this->assertEquals("School B", $response[1]->name);

	}

	public function test_isValidAppId() {

		// Here's a good one
		$res = $this->cmaApiClient->isValidAppId();
		$this->assertTrue($res, "Our appId is not valid? Should be if numeric ({$this->appId})");

		// Here's a bad one
		$cmaApiClientBadId = new CmaApiClient($this->apiClient, 'invalidAppId');
		$res = $cmaApiClientBadId->isValidAppId();
		$this->assertFalse($res, 'Invalid appId is valid???');
	}
}

?>
