<?php

require_once(realpath(dirname(dirname(__FILE__)) . '/konaenv.php'));
require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/DeviceServiceApiClient.obj.php");

class DeviceServiceApiClientTest extends PHPUnit_Framework_TestCase {

	var $apiClient;
	var $deviceApiClient;
	var $deviceBaseApiUrl = 'https://sandbox.testschoolmessenger.com'; // from settings.ini.php
	var $uuid = 'ht-apple-ipad-123';

	public function setup() {
		$this->apiClient = $this->getMockBuilder('ApiClient')
				->setConstructorArgs(array($this->deviceBaseApiUrl))
				->setMethods(array('get'))
				->getMock();

		// ref: http://phpunit.de/manual/3.6/en/test-doubles.html#test-doubles.stubs.examples.StubTest5.php
		$getValueMap = array(
			array("/api/1/devices/{$this->uuid}",
				array(
					'headers' => "Accept: application/json", // dummy header
					'body' => '{"uuid":"ht-apple-ipad-123","token":"xsadfasdfasdfasdfasdf","name":"HT Ipad","model":"Ipad2","osVersion":"8","osType":"ios","appInstance":{"name":"ICRA","version":"1.1","cmaAppId":1}}',
					'code' => 200
				)
			)
		);



		// Multiple uses of this method controlled by the map above
		$this->apiClient->expects($this->any())
				->method('get')
				->will($this->returnValueMap($getValueMap)
		);

		$this->deviceApiClient = new DeviceServiceApiClient($this->apiClient);
	}

	public function tearDown() {
		unset($this->deviceApiClient);
	}

	public function test_getDevice() {
		$response = $this->deviceApiClient->getDevice("ht-apple-ipad-123");
		$this->assertEquals("HT Ipad", $response->name);
	}

}

?>