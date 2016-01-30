<?php

/**
 * A client for Global Registry API
 */
class GlobalRegistryApiClient {

	private $apiClient;

	const ENDPOINTS = '/STUBS/globalregistry/endpoints';
	const ENDPOINTS_PHONE = '/STUBS/globalregistry/endpoints/phone';
	const ENDPOINTS_SEARCH = '/STUBS/globalregistry/endpoints/search';

	/**
	 * Initialize Client
	 * @param type $apiClient api client
	 */
	public function __construct($apiClient) {
		$this->apiClient = $apiClient;
	}

	/**
	 * Add one or more phones to the registry
	 *
	 * @param $phones An array of strings where each string is a 10 digit phone number
	 *
	 * @return mixed decoded JSON response (array of objects with metadata about each phone added), or false on error
	 */
	public function addPhones($phones) {
		$res = $this->apiClient->post(
			self::ENDPOINTS_PHONE,
			$phones
		);
		return($res['code'] == 201 ? json_decode($res['body']) : false);
	}

	/**
	 * Find one or more destinations and get the metadata for them
	 *
	 * @param $destinations An array of phones/emails/smses/devices, etc to look up
	 *
	 * @return mixed decoded JSON response (array of objects with metadata about each destination requested), or false on error
	 */
	public function getDestinationMetadata($destinations) {
		$data = new StdObj();
		$data->destinations = $destinations;
		$res = $this->apiClient->post(
			self::ENDPOINTS_SEARCH,
			$data
		);
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	/**
	 * Update the metadata for one or more destinations
	 *
	 * @todo: refactor this so that the consumer doesn't need to provide us with the exact metadata structure that the API expects - that's not very friendly/abstractive of us...
	 * @ param $destinationMetadata An array of destination "endpoint" metadata objects to be updated
	 *
	 * @return boolean true on success, else false
	 */
	public function updateDestinationMetaData($destinationMetadata) {
		$res = $this->apiClient->patch(
			self::ENDPOINTS,
			$destinationMetadata
		);
		return ($res['code'] == 200);
	}

	/**
	 * Construct client API and return an instance of Device service client
	 * @param type $settings
	 * @return GlobalRegistryApiClient
	 */
	public static function instance($settings) {
		return new GlobalRegistryApiClient(new ApiClient($settings['globalregistry']['host']));
	}

}

