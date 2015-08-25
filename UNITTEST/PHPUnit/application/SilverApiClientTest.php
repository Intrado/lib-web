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
						"iconUrl": "http://s3.amazonaws.com/kanta/apps/109/iMa1l_eX10-K_cFY768jyA.png",
						"index": 0,
						"mandatory": false,
						"group": "GJBGroup",
						"_notificationGroup": {
							"applicationId": 109,
							"tag": "c:155",
							"name": "Gretel",
							"imageUrl": "http://s3.amazonaws.com/kanta/apps/109/iMa1l_eX10-K_cFY768jyA.png",
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

	}

	public function tearDown() {
		unset($this->silverApiClient);
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
		
		$this->assertEquals(1, $response[0]->categoryId);
		
		// the id should match the _notificationGroup->id as getCategories() calls
		// a private class which makes the values the same.
		$this->assertEquals(58, $response[0]->id);
		$this->assertEquals(58, $response[0]->_notificationGroup->id);
		
		// still Badgers I hope
		$this->assertEquals("Badgers", $response[0]->name);
		
	}
}

?>
