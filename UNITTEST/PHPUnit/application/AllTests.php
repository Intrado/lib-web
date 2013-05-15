<?php

/**
 * AllTests.php - PHPUnit APPLICATION  "All Tests" for Kona project
 *
 * All the Kona application unit tests go in here. As there are quite a number
 * of classes, the directory will fill up substantially. Each test class must
 * be included in the "List of classes" within the suite method below in order
 * to be included in the tests suite(s), otherwise they will only be available
 * if explicity and individually invoked.
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

class application_AllTests {
	public static function suite() {

		// List of test classes to include; TODO: expand as needed:
		$classes = Array(
			//'FailSampleTest', // TODO - activate this line if you want to see the CI server catch an error and stop the build process.
			'SessionTest',
			'ValTranslationLengthTest'
		);

		$classdir = dirname(__FILE__);

		// Create a new test suite
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit Application');

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

