<?php

/**
 * A client for Global Registry API
 */
class GlobalRegistryApiClient {

	private $apiClient;

	const STATUS = '/boguscustomer/STUBS/globalregistry/status/';
	const ROOT = '/boguscustomer/STUBS/globalregistry/endpoints/';
	const PHONE = '/boguscustomer/STUBS/globalregistry/endpoints/phone/';
	const SEARCH = '/boguscustomer/STUBS/globalregistry/endpoints/search/';

	/**
	 * Initialize Client
	 * @param type $apiClient api client
	 */
	public function __construct($apiClient) {
		$this->apiClient = $apiClient;
	}

	/**
	 * Get the status of the GRAPI
	 *
	 * @return boolean true if it is UP and available, else false
	 */
	public function getStatus() {
		$res = $this->apiClient->get(
			self::STATUS
		);
		if (200 !== $res['code']) return false;
		$response = json_decode($res['body']);
		return ('UP' === $response->status);
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
			self::PHONE,
			$phones
		);
		return($res['code'] == 201 ? json_decode($res['body']) : false);
	}

	/**
	 * Find one or more destinations and get the metadata for them
	 *
	 * @param $destinations An array of phones/emails/smses/devices, etc to look up
	 *
	 * @return mixed decoded JSON response (array of quasi-DestinationMetadata DTO's), or false on error
	 */
	public function getDestinationMetadata($destinations) {
		$data = new StdObj();
		$data->destinations = $destinations;
		$res = $this->apiClient->post(
			self::SEARCH,
			$data
		);
		return($res['code'] == 200 ? json_decode($res['body']) : false);
	}

	/**
	 * Update the metadata for one or more destinations
	 *
	 * @param $destinationMetadatas An array of DestinationMetadata DTO's to be updated
	 *
	 * @return boolean true on success, else false
	 */
	public function updateDestinationMetadata($destinationMetadatas) {
		$res = $this->apiClient->patch(
			self::ROOT,
			$destinationMetadatas
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

