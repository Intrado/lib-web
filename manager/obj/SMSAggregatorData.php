<?

// houses data for the SMS Aggregator form item

class SMSAggregatorData {

	public $currentShortcodeGroupId;
	public $shortcodeData;
	public $shortcodeGroups;
	

	public function __construct($customerId) {
		$this->currentShortcodeGroupId = $this->getCurrentShortcodeGroupId($customerId);
		$this->shortcodeData = $this->fetchRequiredData();
		$this->shortcodeGroups = $this->getAllShortCodeGroups(); 
	}

	// gets the current short code group id of the customer
	public function getCurrentShortcodeGroupId($customerId = '1') {

		$shortCodeGroupId = null;

		if ($customerId) {
			$shortCodeGroupId = QuickQuery("select shortcodegroupid from customer where id = ?", null, array($customerId));
		}

		return $shortCodeGroupId;
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
		ORDER BY shortcodeid';

		// query shortcode group names and ids into associative array
		$results = QuickQueryMultiRow($query, true);
		
		return $results;
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