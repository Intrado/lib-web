<?php

/**
 * ValTranslationLengthTest.php - PHPUnit test for class ValTranslationLength
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/obj/Validator.obj.php");
require_once("{$konadir}/obj/ValTranslationLength.val.php");

class ValTranslationLengthTest extends PHPUnit_Framework_TestCase {

	const CHAR_LIMIT = 5000;

        public function test_Empty() {
		$obj = new ValTranslationLength();
		$this->assertTrue($obj->validate('', array()), 'Failed on empty string');
        }

        public function test_Exact() {
		$obj = new ValTranslationLength();
		$this->assertTrue($obj->validate(str_pad('', self::CHAR_LIMIT, 'A'), array()), 'Failed on exact length string');
        }

        public function test_Oversize() {
		$obj = new ValTranslationLength();
		$this->assertTrue(is_string($obj->validate(str_pad('', (self::CHAR_LIMIT + 1), 'X'), array())), 'Failed to catch over-sized data');
        }

	public function test_JSValidator() {
		$obj = new ValTranslationLength();
		$str = $obj->getJSValidator();
		$this->assertTrue((is_string($str) && strlen($str)), 'Failed to provide a JSValidator code block');
	}
}

?>
