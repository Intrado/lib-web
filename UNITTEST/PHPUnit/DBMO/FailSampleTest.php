<?php

/**
 * FAIL_SAMPLE_TEST.php - PHPUnit test for to verify that failures are captured
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

class FailSampleTest extends PHPUnit_Framework_TestCase {

	public function test_fail() {
		$this->assertTrue(false, 'This test failed as it should!');
	}
}

?>
