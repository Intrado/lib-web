<?php

/**
 * A client for Device Registration service API
 */
class DeviceServiceApiClient {

	private $apiClient;

	const DEVICE = "/api/1/devices";

	/**
	 * Initialize Client
	 * @param type $apiClient api client
	 */
	public function __construct($apiClient) {
		$this->apiClient = $apiClient;
	}

	public function getDevice($uuid) {
		$res = $this->apiClient->get(self::DEVICE . "/{$uuid}");
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	/**
	 * Construct client API and return an instance of Device service client
	 * @param type $settings
	 * @return \DeviceServiceApiClient
	 */
	public static function instance($settings) {
		return new DeviceServiceApiClient(new ApiClient($settings['deviceserver']['host']));
	}

}

?>
