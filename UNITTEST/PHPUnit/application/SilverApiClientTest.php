<?php

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));

global $incdir;

require_once("{$konadir}/inc/common.inc.php");   
require_once("{$konadir}/obj/SilverApiClient.obj.php");

class SilverApiClientTest extends PHPUnit_Framework_TestCase {

	var $apiClient;
	var $silverApiClient;
	
	var $silverApiUrl="https://api.schoolmessenger.sauros.hr"; // from settings.ini.php
	var $username = 'fakename';
	var $password = 'fakepass';
	
	var $appId = 109;
	
	// Convenience vars for getting setup data
	var $getRequestCategoryResponse;

	public function setup() {

		// create mock ApiClient object and mock the sendRequest() and get() methods
		$this->apiClient = $this->getMockBuilder('ApiClient')
			->setConstructorArgs(array($this->silverApiUrl))
			->setMethods(array('sendRequest', 'get'))
			->getMock();

		
		$sendRequestSessionIdResponse = array(
			'headers' => "Accept: application/json", 
			'body' => '{
				"session_id":"905fe8e2-be08-421b-8007-e3531f2fee62"
			}', //dummy session_id
			'code' => 200
		);

		// mock result from 
		$getRequestCategoryResponse = array(
			'body' => '{
				"data": [
					{
						"id": 1,
						"name": "Badgers",
						"iconUrl": "http://not.a.url",
						"index": 0,
						"mandatory": false,
						"group": "GJBGroup",
						"_notificationGroup": {
							"applicationId": 109,
							"tag": "c:155",
							"name": "Cornholio",
							"imageUrl": "http://also.not.a.url.png",
							"orderIndex": 0,
							"hidden": false,
							"id": 58
						}
					}
				]
			}',
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
		
		$this->getRequestCategoryResponse = $getRequestCategoryResponse;

	}

	public function tearDown() {
		unset($this->silverApiClient);
		unset($this->getRequestCategoryResponse);
	}
	
	public function test_getSessionId() {
		$sessionId = $this->silverApiClient->getSessionId();
		
		// we should get a result back
		$this->assertNotEmpty($sessionId);
		
		// sessionId should match our dummy data
		$this->assertEquals($sessionId, "905fe8e2-be08-421b-8007-e3531f2fee62");
		
	}
	
	public function test_getCategories() {
		
		// obj so we can conveniently access mock request body data
		$jsonRequestBody = json_decode($this->getRequestCategoryResponse['body']);
		
		// getCategories() calls apiClient->get(url)
		$response = $this->silverApiClient->getCategories();
		
		// the response should be an array
		$this->assertTrue(is_array($response));
		
		// there should be only 1 element in $response
		$this->assertEquals(1, count($response));
		
		// it should be an object
		$this->assertTrue(is_object($response[0]));
		
		// the category ID should match the _notificationGroup ID from the mock
		$notificationGroupId = $jsonRequestBody->data[0]->_notificationGroup->id;
		$this->assertEquals($notificationGroupId, $response[0]->id);
		
		// the category name should match the _notificationGroup name from the mock
		$notificationGroupName = $jsonRequestBody->data[0]->_notificationGroup->name;
		$this->assertEquals($notificationGroupName, $response[0]->name);
		
	}
	
}

?>
