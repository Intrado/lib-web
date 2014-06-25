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

		// List of test classes to exclude
		$exceptions = Array(
			'FailSampleTest'	// TODO - disable this exception if you want to see the CI server catch an error and stop the build process.
		);

		// Create a new test suite
		$suite = new PHPUnit_Framework_TestSuite('PHPUnit Application');

		// Get a list of all the files in this directory (includes ourself, so watch it!)
		$classdir = dirname(__FILE__);
		$testclasses = self::get_file_list($classdir);
		if ($testclasses === false) die("Failed to open working dir [{$classdir}]\n\n");
		if (! count($testclasses)) {
			print("Didn't find any files in working dir [{$classdir}]\n\n");
			return($suite);
		}
		sort($testclasses, SORT_STRING);

		foreach ($testclasses as $testclass) {
			// We expect the classname to be the same as the filename sans extension
			$classname = substr($testclass, 0, strlen($testclass) - 4);
			print "class: {$testclass} -> {$classname} ... ";
			if (in_array($classname, $exceptions)) {
				print "SKIPPING!\n";
				continue;
			}
			print "ADDING to test suite...\n";

			// Include individual test classes that are to be part of the suite
			require_once("{$classdir}/{$testclass}");

			// Add each individual test class to this test suite
			$suite->addTestSuite($classname);
		}

		// Then return the whole suite
		return($suite);
	}

	public static function get_file_list($dir) {
		$fileset = array();

		if (! ($dh = opendir($dir))) return(false);
		while (($filename = readdir($dh)) !== false) {

			// Skip "hidden" files beginning with a '.'
			if ($filename{0} == '.') continue;

			// And skip anything not ending with '*Test.php' (which includes ourselves)
			if (strpos($filename, 'Test.php') === false) continue;

			array_push($fileset, $filename);
		}
		closedir($dh);

		return($fileset);
	}
}
?>
