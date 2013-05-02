<?php
class ArrayTest extends PHPUnit_Framework_TestCase {
	public function testNewArrayIsEmpty() {
		// Create the Array fixture.
		$fixture = array();

		// Assert that the size of the Array fixture is 0.
		$this->assertEquals(0, sizeof($fixture));
	}
}
?>
