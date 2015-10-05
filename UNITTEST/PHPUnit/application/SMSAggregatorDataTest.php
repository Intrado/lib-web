<?php

global $incdir;

$curpath = realpath(dirname(dirname(__FILE__)));

require_once($curpath .'/konaenv.php');
require_once("{$konadir}/inc/common.inc.php"); 
require_once($curpath . '/DBStub.php');
require_once("{$konadir}/manager/obj/SMSAggregatorData.php");

class SMSAggregatorDataTest extends PHPUnit_Framework_TestCase {

	const CUSTOMER_ID = 1;
	
	public $SMSAggregatorData;

	public function setup() {
		global $queryRules;
		
		// For DB mocking
		$queryRules->reset();
		
		// for test_getCurrentShortcodeGroupId()
		$queryRules->add('/shortcodegroupid from customer/', array(self::CUSTOMER_ID), array(array(1)));
		
		// for fetchRequiredData()
		$queryRules->add('/SELECT shortcodegroup\.\`id\` AS shortcodeid/', 
			array(
				array(
					'shortcodeid' => 1,
					'shortcodegroupdescription' => 'SchoolMessenger',
					'smsaggregatorname' => 'air2web',
					'shortcode' => 86753
				)
			)
		);
		
		// for test_storeSelection()
		$queryRules->add('/update customer set shortcodegroupid/', array(array(true)));
		

		$this->SMSAggregatorData = new SMSAggregatorData();
	}
	
	public function tearDown() {
		unset($this->SMSAggregatorData);
	}
	
	public function test_getCurrentShortcodeGroupId() {
		$shortcodeGroupId = $this->SMSAggregatorData->getCurrentShortcodeGroupId(self::CUSTOMER_ID);
		
		// is the shortcodegroup an int and 1?
		$this->assertTrue(is_int($shortcodeGroupId));
		$this->assertEquals($shortcodeGroupId, 1);
	}
	
	// gets the current short code group id of the customer
	public function test_fetchRequiredData() {
		$queryResults = $this->SMSAggregatorData->fetchRequiredData();
		
		// do the results match our test data?
		$this->assertEquals($queryResults[0]['shortcodeid'], 1);
		$this->assertEquals($queryResults[0]['shortcodegroupdescription'], 'SchoolMessenger');
		$this->assertEquals($queryResults[0]['smsaggregatorname'], 'air2web');
		$this->assertEquals($queryResults[0]['shortcode'], 86753);
	}
	
	public function test_storeSelection () { 
		// do we get true for succesful update query
		$result = $this->SMSAggregatorData->storeSelection(self::CUSTOMER_ID, '2');
		
		$this->assertTrue($result);
	}
	
	public function test_jmxUpdateShortcodeGroups() {
		
		// mock executeCurlRequest as it's necessary but outside scope of test
		$aggremocker = $this->getMock('SMSAggregatorData');
		$aggremocker
				->expects($spy = $this->any())
				->method('executeCurlRequest')
				->will($this->returnValue('This is an error'));
		
		$resultArray = $this->SMSAggregatorData->jmxUpdateShortcodeGroups();
		print_r($resultArray);
				
		
	}
	
}

?>
