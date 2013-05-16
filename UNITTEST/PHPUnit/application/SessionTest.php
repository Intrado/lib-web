<?php

/**
 * SessionTest.php - PHPUnit test for class Session
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

// Since we don't know what the working directory is, use this script's dir as
// the base and relatively indicate the object class being tested from there
require_once(realpath(dirname(__FILE__) . '/../../../obj/Object.obj.php'));
require_once(realpath(dirname(__FILE__) . '/../../../obj/Session.obj.php'));

class SessionTest extends PHPUnit_Framework_TestCase {

	public function test_basics() {
		$key = 'testkey';
		$value = 'testvalue';

		$sess = new Session();

		// Make sure the key that doesn't exist yet cannot be acknowledged
		$this->assertFalse($sess->check($key));

		// Make sure the test key doesn't already exist
		try {
			// This should cause an exception accessing an undefined key
			$tmp = $sess->get($key);

			// FAIL: We should have thrown an exception above and not gotten here
			$this->assertFalse(true);
		}
		catch (Exception $ex) {
			// If we can catch it, then we're good!
			$this->assertTrue(true);
		}

		// Make sure we can set a value with a successful indication
		$this->assertTrue($sess->set($key, $value));

		// Make sure the key we set is acknowledged
		$this->assertTrue($sess->check($key));

		// Make sure it really is set to what we thought
		$this->assertTrue(($sess->get($key) == $value));

		// Make sure we can delete the entry we added
		$this->assertTrue($sess->delete($key));

		// Make sure the key that no longer exists cannot be acknowledged
		$this->assertFalse($sess->check($key));

		// Make sure what we thought got deleted actually did
		try {
			// This should cause an exception accessing an undefined key
			$tmp = $sess->get($key);

			// FAIL: We should have thrown an exception above and not gotten here
			$this->assertFalse(true);
		}
		catch (Exception $ex) {
			// If we can catch it, then we're good!
			$this->assertTrue(true);
		}

	}
}

?>
