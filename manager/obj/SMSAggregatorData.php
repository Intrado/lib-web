<?

// houses data for the SMS Aggregator form item

class SMSAggregatorData {

	public $customerId;
	public $originalShortcodeGroupId;
	public $shortcodeData;
	public $shortcodeGroups;
	
	public function init($customerId) {

		$this->customerId = $customerId;
		
		$this->originalShortcodeGroupId = $this->getCurrentShortcodeGroupId();
		$this->shortcodeData = $this->fetchRequiredData();
		$this->shortcodeGroups = $this->getAllShortCodeGroups(); 
		
	}

	// gets the current short code group id of the customer
	public function getCurrentShortcodeGroupId() {
		
		$shortCodeGroupId = QuickQuery("select shortcodegroupid from customer where id = ?", null, array($this->customerId));

		return $shortCodeGroupId;
	}

	// check to see if shortcodeGroup selection has changed
	public function shortcodeGroupHasChanged($newShortcodeGroupId) {
		
		return ( $newShortcodeGroupId !== $this->originalShortcodeGroupId ) ? true : false;
	}
	
	// fetches all required data to be displayed below dropdown aggregator form-item
	public function fetchRequiredData() {

		$query = '
		SELECT shortcodegroup.`id` AS shortcodeid,
				shortcodegroup.`description` AS shortcodegroupdescription,
				smsaggregator.`name` AS smsaggregatorname,
				shortcode.shortcode AS shortcode 
		FROM shortcode
		INNER JOIN shortcodegroup ON shortcodegroup.id=shortcode.`shortcodegroupid`
		INNER JOIN smsaggregator ON shortcode.`smsaggregatorid`=smsaggregator.id
		WHERE shortcodegroup.product="cs"
		ORDER BY shortcodeid';

		// query shortcode group names and ids into associative array
		$results = QuickQueryMultiRow($query, true);
		
		return $results;
	}
	
	public function storeSelection ($selection) {
		
		$query = "update customer set shortcodegroupid=$selection where id = ?";
		
		return QuickQuery($query, null, array($this->customerId));
	}
	
	public function jmxUpdateShortcodeGroups () {
		global $SETTINGS;
		
		// init curl with the url we hit with a GET request
		$username = $SETTINGS['jmx']['username'];
		$password = $SETTINGS['jmx']['password'];
		$domains = $SETTINGS['jmx']['domain'];
		
		// one result per cURL request, one cURL request per domain.
		$errors = array();
		
		foreach($domains as $domain) {
			
			$curlResult = $this->executeCurlRequest($username, $password, $domain);
			
			// if the result is not false there must be an error
			if($curlResult) {
				$errors[] = $curlResult;
			}
		}
		
		return $errors;
	}
	
	private function executeCurlRequest($username, $password, $domain) {
		$ch = curl_init(
			"http://$username:$password@$domain/jolokia/exec/commsuite:type=service,name=smsServiceManager/refreshShortcodeGroups()/"
		);

		// returned data will be a string
		curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1); 

		// run it, get result. 
		$result = curl_exec($ch);

		// if cURL could not execute
		if (! $result) {
			$errorString = "cURL request  <b style='color:darkred'>FAILED</b> attempting to update shortcode groups for: (<b>$domain)</b>) --stack trace: " . curl_error($ch);
			
			error_log($errorString);
			
			return $errorString;
		} 
		
		$resultArray = $this->parseJMSShortcodeResponse($result);
		
		if(isset($resultArray['error'])) {
			 $errorString =  "Shortcode groups <b style='color:darkred'>FAILED</b> to refresh for: (<b>$domain</b>) -- stack trace: {$resultArray['error']}";
			 
			 error_log($errorString);
			 
			 return $errorString;
		}
		
		// no problems!
		return false;
	}
	
	// parse response JSON string into an array
	// @param: String $response
	// @returns: Array ('status' => '...', 'error' => '...')
	private function parseJMSShortcodeResponse ($response) {
		
		$jsonResponse = json_decode ($response);
		
		// status should always exist in response
		$returnArray = array(
			'status' => $jsonResponse->status
		);
		
		// and there might be an error if not status 200
		if(isset($jsonResponse->error)) {
			$returnArray['error'] = $jsonResponse->error;
		}
		
		return $returnArray;
	}
	
	// gets all the available short code group id and description columns
	// @returns: array( array( 'id' => 'description' ), ... )
	public function getAllShortCodeGroups () {
		
		$shortCodeGroupValues = array();

		// create a simple array mapping codegroup id to description for use with form item
		foreach($this->shortcodeData as $codeGroup) {
			$shortCodeGroupValues[$codeGroup['shortcodeid']] = $codeGroup['shortcodegroupdescription'];
		}
		
		return $shortCodeGroupValues;
	}
}

?>