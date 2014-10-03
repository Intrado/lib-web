<?php
/**
 * TopicDataFormatterTest.php - PHPUnit Test Class for obj/TopicDataFormatter.obj.php
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/obj/TopicDataFormatter.obj.php");

class TopicDataFormatterTest extends PHPUnit_Framework_TestCase {

	public function test_anyTopics() {


		// returns truthy if there is more than one topic present
		$tdf = new TopicDataFormatter(0,25, array('total' => 1, 'data' => array('topic1')));
		$this->assertTrue($tdf->anyTopics());

		// returns falsy if there are no topics on this page
		$tdf = new TopicDataFormatter(1,25, array('total' => 1, 'data' => array()));
		$this->assertFalse($tdf->anyTopics());
	}

	/*
	public function test_showMenu() {
		// untestable while showPageMenu only writes to stdout instead of returning a string
		$this->markTestIncomplete();
	}

	public function test_showTable() {
		// untestable while showTable only writes to stdout instead of returning a string
		$this->markTestIncomplete();
	}
	*/
}

?>
