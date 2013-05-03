<?php

/**
 * AllTests.php - PHPUnit MASTER "All Tests" for Kona project
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

class AllTests {
	public static function suite() {

		// List of second tier "All Tests"; TODO: expand as needed:
		$tests = Array(
			'environment',
			'application'
		);

		// Get the directory of this file (CWD for CLI is unknown)
		$testdir = dirname(__FILE__);

		// Create a new test suite 
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit');

		// If any tests are listed, then for each one...
		if (count($tests)) foreach ($tests as $test) {

                        // Include the second tier test suite here;
			// no individual tests should be in this tier
                        require_once("{$testdir}/{$test}/AllTests.php");

                        // Add each test suite to this master test suite
			$class = "{$test}_AllTests";
			$suite->addTest($class::suite());
                }

                // Then return the whole master suite
                return($suite);
	}
}
?>

