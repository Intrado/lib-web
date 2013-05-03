<?php

/**
 * AllTests.php - PHPUnit ENVIRONMENT "All Tests" for Kona project
 *
 * The tests included here should check for specific functions/configurations
 * in PHP that are required by the Kona project and which need to be accounted
 * for when PHP is built/installed/configured. By including environmental
 * unit tests for these things, when a PHP configuration change is required,
 * the unit test will quickly alert the administrator to a problem with the
 * environment when the application is deployed to a host that is not ready
 * for use.
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

class environment_AllTests {
	public static function suite() {

		// List of classes that we want to include:
		$classes = Array(
			'ArrayTest'
		);

		$classdir = dirname(__FILE__);

		// Create a new test suite
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit Environment');

		if (count($classes)) foreach ($classes as $class) {

			// Include individual test classes that are to be part of the suite
			require_once("{$classdir}/{$class}.php");
 
			// Add each individual test class to this test suite
			$suite->addTestSuite($class);

		}

		// Then return the whole suite
		return($suite);
	}
}
?>

