<?php

/**
 * ArrayTest.php - PHPUnit sample test of the PHP Array object class
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

class ArrayTest extends PHPUnit_Framework_TestCase {
	public function testNewArrayIsEmpty() {
		// Create the Array fixture.
		$fixture = array();

		// Assert that the size of the Array fixture is 0.
		$this->assertEquals(0, sizeof($fixture));
	}
}
?>
