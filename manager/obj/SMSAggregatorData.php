<?

// houses data for the SMS Aggregator form item

class SMSAggregatorData {
	
	public $currentShortcodeGroupId;
	public $allShortCodeGroups;
	public $allShortCodes;
	
	public function __construct ($customerId) {
		$this->currentShortcodeGroupId = $this->getCurrentShortcodeGroupId ($customerId);
		$this->allShortCodeGroups = $this->getAllShortCodeGroups();
		//$this->allShortCodes = $this->getAllShortCodes();
	}
	
	// gets the current short code group id of the customer
	public function getCurrentShortcodeGroupId ($customerId = '1') {
		
		$shortCodeGroupId = null;
		
		if ($customerId) {
			$shortCodeGroupId = QuickQuery("select shortcodegroupid from customer where id = ?", null, array($customerId));
		} 
		
		return $shortCodeGroupId;
	}
	
	// gets all the available short code group id and description columns
	// @returns: array( array( 'id' => 'description' ), ... )
	public function getAllShortCodeGroups () {
		
		// query shortcode group names and ids into associative array
		$shortCodeGroups = QuickQueryMultiRow("select id, description from shortcodegroup", true);
		
		$shortCodeGroupValues = array();

		// create a simple array mapping codegroup id to description for use with form item
		foreach($shortCodeGroups as $codeGroup) {
			$shortCodeGroupValues[$codeGroup['id']] = $codeGroup['description'];
		}
		
		return $shortCodeGroupValues;
	}
	
	// gets all the available short code group id and description columns
	// @returns: array( array( 'id' => 'description' ), ... )
	public function getAllShortCodes () {
		
		$shortCodeGroupIds = array_keys($this->allShortCodeGroups);
		
//		$shortCodes = array();
//		foreach($shortCodeGroupIds as $groupId) {
//			
//		}
		
		// query shortcode group names and ids into associative array
		$shortCodeGroups = QuickQueryMultiRow("select id, description from shortcodegroup", true);
		
		$shortCodeGroupValues = array();

		// create a simple array mapping codegroup id to description for use with form item
		foreach($shortCodeGroups as $codeGroup) {
			$shortCodeGroupValues[$codeGroup['id']] = $codeGroup['description'];
		}
		
		return $shortCodeGroupValues;
	}
}

?>