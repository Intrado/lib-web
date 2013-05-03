<?php

/**
 * ValTranslationLengthTest.php - PHPUnit test for class ValTranslationLength
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

// Since we don't know what the working directory is, use this script's dir as
// the base and relatively indicate the object class being tested from there
require_once(realpath(dirname(__FILE__) . '/../../../obj/Validator.obj.php'));
require_once(realpath(dirname(__FILE__) . '/../../../obj/ValTranslationLength.val.php'));

class ValTranslationLengthTest extends PHPUnit_Framework_TestCase {

	private $CHAR_LIMIT = 5000;

        public function test_Empty() {
		$obj = new ValTranslationLength();
		$this->assertTrue($obj->validate('', array()), 'Failed on empty string');
        }

        public function test_Exact() {
		$obj = new ValTranslationLength();
		$this->assertTrue($obj->validate(str_pad('', $this->CHAR_LIMIT, 'A'), array()), 'Failed on exact length string');
        }

        public function test_Oversize() {
		$obj = new ValTranslationLength();
		$this->assertTrue(is_string($obj->validate(str_pad('', ($this->CHAR_LIMIT + 1), 'X'), array())), 'Failed to catch over-sized data');
        }

	public function test_JSValidator() {
		$obj = new ValTranslationLength();
		$str = $obj->getJSValidator();
		$this->assertTrue((is_string($str) && strlen($str)), 'Failed to provide a JSValidator code block');
	}
}

?>
