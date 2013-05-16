<?php

/**
 * ObjectTest.php - PHPUnit test for abstract base class, "Object"
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

// Since we don't know what the working directory is, use this script's dir as
// the base and relatively indicate the object class being tested from there
require_once(realpath(dirname(__FILE__) . '/../../../obj/Object.obj.php'));

class TestObj extends Object {

	public $linenum = 0;

	public function set() {
		$this->set_classname(__CLASS__);
	}

	public function exc($msg) {
		$this->except($msg, ($this->linenum = __LINE__));
	}
}

class ObjectTest extends PHPUnit_Framework_TestCase {

	public function test_basics() {
		$obj = new TestObj();

		// First check that an exception can be thrown and that it has an undefined classname
		try {
			$obj->exc('CLASSLESS');

			// FAIL: If we got here then we failed to except properly
			$this->assertFalse(true);
		}
		catch (Exception $ex) {

			// Did our test phrase show up in the exception message?
			$msg = $ex->getMessage();
			$this->assertTrue(strpos($msg, 'CLASSLESS') !== false);

			// Did the expected line number show up in the exception message?
			$this->assertTrue(strpos($msg, "{$obj->linenum}") !== false);
		}

		// Now set the class name
		$obj->set();

		// And do the exception test again, only this time we expect to see the class name in there
		try {
			$obj->exc('CLASSY');

			// FAIL: If we got here then we failed to except properly
			$this->assertFalse(true);
		}
		catch (Exception $ex) {

			// Did our test phrase show up in the exception message?
			$msg = $ex->getMessage();
			$this->assertTrue(strpos($msg, 'CLASSY') !== false);

			// Did the expected line number show up in the exception message?
			$this->assertTrue(strpos($msg, "{$obj->linenum}") !== false);

			// Did the class name show up in the exception message?
			$this->assertTrue(strpos($msg, 'TestObj') !== false);
		}
	}
}

?>
