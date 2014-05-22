<?php

/**
 * FieldMapTest.php - PHPUnit test for FieldMap DBMO
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

require(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));

require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/obj/FieldMap.obj.php");

class FieldMapTest extends PHPUnit_Framework_TestCase {

	var $fieldMap;

	public function setup() {
                global $queryRules, $USER;

		// 1) Hit the reset switch!
		$queryRules->reset();

		// Something about being a static method called before instantiation prevents
		// $konadir from being seen as a regular global as it is normally...
		$konadir = $GLOBALS['konadir'];
		
		// Here's what we're testing:
		$this->fieldMap = new FieldMap();
	}

	private function mockFieldMaps($defaultsOnly = false) {
		global $queryRules;

		$results = array(
			array(
				'id' => '1',
				'fieldnum' => '$d01',
				'name' => '%Date%',
				'options' => 'text,systemvar'
			),
			array(
				'id' => '2',
				'fieldnum' => '$d02',
				'name' => "%Tomorrow's Date%",
				'options' => 'text,systemvar'
			),
			array(
				'id' => '3',
				'fieldnum' => '$d03',
				'name' => "%Yesterday's Date%",
				'options' => 'text,systemvar'
			),
			array(
				'id' => '4',
				'fieldnum' => 'f01',
				'name' => 'First Name',
				'options' => 'searchable,text,firstname,subscribe,dynamic',
				'type' => 'firstname'
			),
			array(
				'id' => '5',
				'fieldnum' => 'f02',
				'name' => 'Last Name',
				'options' => 'searchable,text,lastname,subscribe,dynamic',
				'type' => 'lastname'
			),
			array(
				'id' => '6',
				'fieldnum' => 'f03',
				'name' => 'Language',
				'options' => 'searchable,multisearch,language,subscribe,static',
				'type' => 'language'
			)
		);

		// Add some optional customer-defined fields
		if ($defaultsOnly === false) {
			$results[] = array(
				'id' => '7',
				'fieldnum' => 'f04',
				'name' => 'Grade',
				'options' => 'searchable,multisearch,grade',
				'type' => 'grade'
			);

			$results[] = array(
				'id' => '8',
				'fieldnum' => 'f05',
				'name' => 'Lunch Balance',
				'options' => 'searchable,multisearch,lunchbalance',
				'type' => 'lunchbalance'
			);

			$results[] = array(
				'id' => '9',
				'fieldnum' => 'f06',
				'name' => 'Tardy Count',
				'options' => 'searchable,multisearch,tardycount,numeric',
				'type' => 'tardycount'
			);

			$results[] = array(
				'id' => '10',
				'fieldnum' => 'f07',
				'name' => 'Absence Count',
				'options' => 'searchable,multisearch,absencecount,numeric',
				'type' => 'absencecount'
			);
		}


		// 1) SQL response: The fieldmap collection for this customer
		$queryRules->add('/from fieldmap order by fieldnum/', $results);
		FieldMap::retrieveFieldMaps(true);

		return($results);
	}

	// Make sure the individual fieldnum getter methods return the ones that we expect
	public function test_getters() {
		global $queryRules;
		$this->mockFieldMaps(); // Use ALL the "special" fields
		$this->assertTrue(($this->fieldMap->getFirstNameField() == 'f01'), 'FieldNum for first name was wrong');
		$this->assertTrue(($this->fieldMap->getLastNameField() == 'f02'), 'FieldNum for last name was wrong');
		$this->assertTrue(($this->fieldMap->getLanguageField() == 'f03'), 'FieldNum for language was wrong');
		$this->assertTrue(($this->fieldMap->getGradeField() == 'f04'), 'FieldNum for grade was wrong');
		$this->assertTrue(($this->fieldMap->getLunchBalanceField() == 'f05'), 'FieldNum for lunch balance was wrong');
		$this->assertTrue(($this->fieldMap->getTardyCountField() == 'f06'), 'FieldNum for tardy count was wrong');
		$this->assertTrue(($this->fieldMap->getAbsenceCountField() == 'f07'), 'FieldNum for absence count was wrong');
	}


	// Make sure the getter for the entire set of fields limits itself to the special fields
	public function test_getSpecialFieldNumbers() {
		global $queryRules;
		$this->mockFieldMaps(true); // Use ONLY the default fields

		// This should return the entire set of customer defined fields (ONLY first/lastname and language)
		$fieldNums = FieldMap::getSpecialFieldNumbers();
		$this->assertTrue((count($fieldNums) == 3), "There should have been three fields, but got: " . count($fieldNums));

		// This should return the subset with default field numbers defined
		$fieldNums = FieldMap::getSpecialFieldNumbers(true);
		$this->assertTrue((count($fieldNums) == 3), "There should have been three fields, but got: " . count($fieldNums));
	}

	// Make sure the getter for the entire set of fields defined in the customer database
	public function test_getSpecialFieldNumbers_allFields() {
		$this->mockFieldMaps(); // Use ALL the "special" fields

		// This should return the entire set
		$fieldNums = $this->fieldMap->getSpecialFieldNumbers();
		$this->assertTrue((count($fieldNums) == 7), "There should have been seven fields, but got: " . count($fieldNums));

		// This should return the subset with default field numbers defined
		$fieldNums = $this->fieldMap->getSpecialFieldNumbers(true);
		$this->assertTrue((count($fieldNums) == 3), "There should have been three fields, but got: " . count($fieldNums));
	}

	// Make sure this instance of FieldMap tells us what it's type is
	public function test_getOptionType() {
		$results = $this->mockFieldMaps(); // Use ALL the "special" fields

		foreach ($results as $result) {
			// Skip the first three "D-fields" which are just here to make sure they get filtered out correctly
			if (! isset($result['type'])) continue;

			// Set the options for our fieldmap
			$this->fieldMap->optionsarray = false;
			$this->fieldMap->options = $result['options'];

			// Make sure the type we think it is, is what comes back
			$type = $this->fieldMap->getOptionType();
			$this->assertTrue(($type == $result['type']), "Expected type for field [" . $result['name'] . "] to be [" . $result['type'] . "] but got [" . $type ."]");
		}

		// For generic types
		foreach (array("text", "reldate", "multisearch", "numeric") as $expectedType) {
			$this->fieldMap->optionsarray = false;
			$this->fieldMap->options = $expectedType;

			// Make sure the type we think it is, is what comes back
			$type = $this->fieldMap->getOptionType();
			$this->assertTrue(($type == $expectedType), "Expected type to be [" . $expectedType . "] but got [" . $type ."]");
		}

		// default
		$this->fieldMap->optionsarray = false;
		$this->fieldMap->options = "somethingunexpected!";
		// Make sure the type we think it is, is what comes back
		$type = $this->fieldMap->getOptionType();
		$this->assertTrue(($type == 'text'), "Expected type to be [text] but got [" . $type ."]");
	}
}
?>
