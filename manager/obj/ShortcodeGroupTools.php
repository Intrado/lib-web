<?

class ShortcodeGroupTools {

	public $smsAggregatorData; 

	function init( $smsAggregatorData ) {
		$this->smsAggregatorData = $smsAggregatorData;
	}

	// Returns the form data structures with a wrapper for additional formatting/actions
	function getUploadFormWrapper() {

		$uploadFormData = array(
			"fileUpload" => array(
				"label" => _L('File Selection (CSV and TXT files only)'),
				"value" => 'upload',
				"validators" => array(),
				"control" => array("FileUpload", "acceptExts" => array("txt", "csv")),

			),
			"shortcodegroup" => array(
				"label" => _L('Shortcode Group'),
				"value" => 1,
				"validators" => array(
					array("ValInArray", "values" => array_keys(
							$this->smsAggregatorData->shortcodeGroups
						))
				),
				"control" => array("SMSAggregator", "values" => 
					array(
						"form" => $this->smsAggregatorData->shortcodeGroups,
						"js" => $this->smsAggregatorData->shortcodeData
				),
				"helpstep" => 1)
			)
		);

		$uploadFormWrapper = array(
			"header" => "Choose a csv formatted file with the first column consisting of the customer IDs to update",
			"action" => "handleCSVUpload",
			"multipart" => true,
			"ajaxsubmit" => false,
			"buttons" => array(submit_button(_L('Preview'), "submit", "eye")),
			"form" => $uploadFormData
		);

		return $uploadFormWrapper;
	}

	function getPreviewFormWrapper( $customerIDString, $newShortcodeGroup ) {

		$previewFormData = array(
			"customerIDString" => array(
				"value" => $customerIDString,
				"control" => array("HiddenField")
			),
			"newShortcodeGroup" => array(
				"value" => $newShortcodeGroup,
				"control" => array("HiddenField")
			)
		);

		$previewFormWrapper = array(
			"header" => "Clicking confirm will execute the query to make these changes",
			"action" => "confirmNewShortcodeGroups",
			"multipart" => false,
			"ajaxsubmit" => false,
			"buttons" => array(submit_button(_L('Confirm'), "submit", "tick")),
			"form" => $previewFormData
		);

		return $previewFormWrapper;

	}

	function getCustomerIDStringFromFile( $fileContents ) {

		$customerIDs = array();

		$fileContents = str_replace('\r\n', '\n', $fileContents);
		$fileContents = str_replace('\r', '\n', $fileContents);

		$csvRows = explode("\n", $fileContents);
		foreach($csvRows as $row) {
			$parsedRow = str_getcsv($row);

			// push to array if row is not blank (accidentally added to CSV)
			if($parsedRow[0]) {

				if( intval($parsedRow[0]) ) {

					$customerIDs[] = $parsedRow[0];
				}
			}
		}

		$customerIDString = implode(',',$customerIDs);

		return $customerIDString;
	}

	function getCustomerShortcodeData( $customerIDString, $newShortcodeGroup ) {

		$customerIDArray = explode(',', $customerIDString);

		// For the bound variables in prepared statement
		$boundCustomerIDVars = str_repeat("?,", count($customerIDArray));

		// Remove trailing ','
		$boundCustomerIDVars = rtrim($boundCustomerIDVars, ',');

		// Values to insert into prepared statement
		$preparedVals = array();
		foreach($customerIDArray as $customerID) {
			$preparedVals[] = $customerID;
		}

		// Add the new shortcode group
		$preparedVals[] = $newShortcodeGroup;

		$query = "SELECT c.id as id,
						 c.urlcomponent as url, 
						 scg1.description as currentShortcodeGroup,
						 scg2.description as newShortcodeGroup
				  FROM customer c, 
				  	   shortcodegroup scg1,
				  	   shortcodegroup scg2
				  WHERE c.id in (". $boundCustomerIDVars .") 
				  AND scg1.id = c.shortcodegroupid
				  AND scg2.id = ?";

		$previewTableData = QuickQueryMultirow($query, true, false, $preparedVals);

		return $previewTableData;
	}

	function updateCustomerShortcodes( $newShortcodeGroup, $customerIDString ) {

		$customerIDArray = explode(',', $customerIDString);

		// For the bound variables in prepared statement
		$boundCustomerIDVars = str_repeat("?,", count($customerIDArray));

		// Remove trailing ','
		$boundCustomerIDVars = rtrim($boundCustomerIDVars, ',');

		// Values to insert into prepared statement
		$preparedVals = array();

		// Add the new shortcode group
		$preparedVals[] = $newShortcodeGroup;

		foreach($customerIDArray as $customerID) {
			$preparedVals[] = $customerID;
		}

		$query = "UPDATE customer c
				  SET c.shortcodegroupid = ?
				  WHERE c.id in (". $boundCustomerIDVars .")";

		$updateResult = QuickUpdate($query, false, $preparedVals);

		return $updateResult;

	}

	function createForm( $formWrapper ) {

		$formObj = new Form(
			$formWrapper["action"], 
			$formWrapper["form"], 
			null, 
			$formWrapper["buttons"]
		);

		// Handle form options
		$multipart = $formWrapper["multipart"] ? $formWrapper["multipart"] : false;
		$ajaxsubmit = $formWrapper["ajaxsubmit"] ? $formWrapper["ajaxsubmit"] : false;

		$formObj->multipart = $multipart;
		$formObj->ajaxsubmit = $ajaxsubmit;

		return $formObj;

	}

	function drawPreviewTable( $customerDisplayRows ) {

		// Table information
		$titles = array(
			"id" => "Customer ID",
			"url" => 'Customer URL',
			"currentShortcodeGroup" => 'Current Shortcode Group',
			"newShortcodeGroup" => 'New Shortcode Group'
		);

		// Are there rows to display in said table?
		if(! empty($customerDisplayRows )) {

			echo '<table class="list sortable" id="shortcodegroup_customers">';
			showTable($customerDisplayRows, $titles);
			echo '</table>';
		}
	}
}

?>