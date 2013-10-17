<?php

/**
 * _skeletest.php - PHPUnit Skeleton Test Class
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------

/**
 * Skeleton Test Class
 *
 * @todo Make sure your text class name bears the same name of the file less the
 * '.php. extension - so MyClassTest.php should have a class in it called
 * 'MyClassTest' - that's how PHPUnit knows what to look for.
 *
 * @todo Make sure each of your test methods begins with test_[something] - that's
 * how PHPUnit knows that it is a test method that should be invoked with the batch.
 * Methods with non-confirming names will not be invoked automatically, and can
 * therefore be used as supporting methods for doing something before/after each
 * test, etc. as needed.
 *
 * @todo Create a test method for each thing that you want to test as a "unit";
 * note that a given test method may invoke any number of assertions and if any
 * one fails, it will throw an exception which is caught by PHPUnit to prevent
 * the code from executing further within that method (but will still continue
 * to the next test method). This allows a test to require multiple steps to get
 * from the start to what you finally want to know, and if any of the preconditions
 * fail, get out before continuing. For example if the thing you are testing is
 * supposed to return an array, assert that it in fact is_array() before attempting
 * to extract a specific data element from it. Remember, PHPUnit itself is a PHP
 * script, so fatal errors introduced here will cause phpunit to stop processing.
 *
 */
class SkeleTest extends PHPUnit_Framework_TestCase {

	public function test_something() {
		$this->assertTrue(false, 'This test failed as it should!');
	}

	public function test_something_else() {
		$this->assertTrue(true, 'This test passes as it should!');
	}
}

?>
