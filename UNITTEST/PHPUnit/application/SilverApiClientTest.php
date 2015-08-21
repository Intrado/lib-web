<?php

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/SilverApiClient.obj.php");

class SilverApiClientTest extends PHPUnit_Framework_TestCase {

	var $apiClient;
	var $silverApiClient;
	
	var $silverApiUrl="https://api.schoolmessenger.sauros.hr"; // from settings.ini.php
	var $username = 'fakename';
	var $password = 'fakepass';
	
	var $appId = 109;

	public function setup() {

		// create mock ApiClient object and mock the sendRequest() and get() methods
		$this->apiClient = $this->getMockBuilder('ApiClient')
			->setConstructorArgs(array($this->silverApiUrl))
			->setMethods(array('sendRequest', 'get'))
			->getMock();

		
		$sendRequestSessionIdResponse = array(
			'headers' => "Accept: application/json", 
			'body' => '{"session_id":"905fe8e2-be08-421b-8007-e3531f2fee62"}', //dummy session_id
			'code' => 200
		);

		// mock result from 
		$getRequestCategoryResponse = array(
			'headers' => "Accept: application/json",
			'body' => '{ "data": { "id": 1, "name": "Badgers","iconUrl": "http://s3.amazonaws.com/kanta/apps/109/bt8JeYiwX0-3urfEGcdJWw.png","index": 1,"mandatory": false,"group": "Others"}}',
			'code' => 200
        );

		// $apiClient->sendRequest() is used to authorize and fetch a session id
		$this->apiClient->expects($this->any())
			->method('sendRequest')
			->will($this->returnValue($sendRequestSessionIdResponse));
		
		// $apiClient->get() is used to retrieve categories
		$this->apiClient->expects($this->any())
			->method('get')
			->will($this->returnValue($getRequestCategoryResponse));

		$this->silverApiClient = new SilverApiClient($this->apiClient, $this->username, $this->password, $this->appId);

	}

	public function tearDown() {
		unset($this->silverApiClient);
	}

	public function test_getBasicAuthHeader() {
		$basicAuthHeader = $this->silverApiClient->getBasicAuthHeader();
		
		// header will always return array of length 1
		$this->assertCount(1, $basicAuthHeader);
		
	}
	
	public function test_getSessionId() {
		$sessionId = $this->silverApiClient->getSessionId();
		
		// we should get a result back
		$this->assertNotEmpty($sessionId);
		
		// sessionId should match our dummy data
		$this->assertEquals($sessionId, "905fe8e2-be08-421b-8007-e3531f2fee62");
		
	}
	
	public function test_getCategories() {
		// getCategories() calls apiClient->get(url)
		$response = $this->silverApiClient->getCategories();

		// there should be only 1 element in $response
		$this->assertEquals(1, count($response));

		// verify the values in the response match our dummy data
		$this->assertEquals(1, $response->id);
		$this->assertEquals("Badgers", $response->name);

	}
}

?>
