<?php

/**
 * FormattersTest.php - PHPUnit Test Class for obj/Formatters.obj.php
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/obj/Formatters.obj.php");
require_once("{$konadir}/inc/formatters.inc.php");


/**
 * Test Class for obj/Formatters.obj.php
 */
class FormattersTest extends PHPUnit_Framework_TestCase {

	private $fmt = null;
	private $method = '';

	public function __construct() {
		// Because formatters is a stateless library, we can just keep a copy of it around
		$this->fmt = new Formatters();
	}

	private function try_expect($supplied, $expected, $method = '') {
		$thismethod = (strlen($method)) ? $method : $this->method;
		if (! method_exists($this->fmt, $thismethod)) {
			$this->assertTrue(false, "Method under test '{$thismethod}' does not exist in Formatters object!");
		}

		$value = $this->fmt->$thismethod($supplied);
		$this->assertTrue(($value == $expected), "Expected '{$expected}', but got '{$value}'");
	}

	public function test_fmt_field_messagekey() {

		// Stub our Message Data Cache translation matrix that this method requires
		global $messagedatacache;
		$messagedatacache = array(
			'en' => array(
				'unittest' => 'tsettinu'
			)
		);

		// We're testing this method:
		$this->method = 'fmt_field_messagekey';

		// Try something that should be translated
		$this->try_expect('unittest', 'tsettinu');

		// Try something that should pass thorugh unmodified
		$this->try_expect('unscathed', 'unscathed');
	}

	public function test_fmt_field_phone_result() {

		// We're testing this method:
		$this->method = 'fmt_field_phone_result';

		// With all these permutations:
		$this->try_expect('A', 'Answered');
		$this->try_expect('M', 'Machine');
		$this->try_expect('B', 'Busy');
		$this->try_expect('N', 'No Answer');
		$this->try_expect('X', 'Disconnect');
		$this->try_expect('F', 'Unknown');
		$this->try_expect('C', 'In Progress');
		$this->try_expect('blocked', 'Blocked');
		$this->try_expect('duplicate', 'Duplicate');
		$this->try_expect('nocontacts', 'No Contacts');
		$this->try_expect('sent', 'Sent');
		$this->try_expect('unsent', 'Unsent');
		$this->try_expect('notattempted', 'Not Attempted');
		$this->try_expect('declined', 'No Destination Selected');
		$this->try_expect('confirmed', 'Confirmed');
		$this->try_expect('notconfirmed', 'Not Confirmed');
		$this->try_expect('noconfirmation', 'No Confirmation Response');
		$this->try_expect('arbitrarytext', 'Arbitrarytext');
	}

	public function test_fmt_field_email_result() {

		// We're testing this method:
		$this->method = 'fmt_field_email_result';

		for ($code = 200; $code <= 299; $code++) $this->try_expect($code, 'Delivered');
		for ($code = 300; $code <= 399; $code++) $this->try_expect($code, 'Unknown Status');
		for ($code = 400; $code <= 499; $code++) $this->try_expect($code, 'Soft Bounced');
		for ($code = 500; $code <= 599; $code++) {
			switch ($code) {
				case 510:
				case 512:
				case 515:
				case 550:
				case 553:
					$this->try_expect($code, 'Invalid Address');
					break;

				default:
					$this->try_expect($code, 'Bounced');
					break;
			}
		}
	}

	public function test_fmt_field_phone_or_email_result() {

		// Since this method is just a wrapper for the email and phone result methods
		// we can just test one variant for each and one that's invlaid and leave the
		// permutations to the unit tests for the other methods out.

		// Email variant
		$value = $this->fmt->fmt_field_phone_or_email_result(200, array('type' => 'email'));
		$this->assertTrue(($value == 'Delivered'), "Expected 'Delivered', but got '{$value}'");

		// Phone variant
		$value = $this->fmt->fmt_field_phone_or_email_result('A', array('type' => 'phone'));
		$this->assertTrue(($value == 'Answered'), "Expected 'Answered', but got '{$value}'");

		// Other/invalid variant
		$value = $this->fmt->fmt_field_phone_or_email_result('arbitrarytext', array('type' => ''));
		$this->assertTrue(($value == 'arbitrarytext'), "Expected 'arbitrarytext', but got '{$value}'");
	}

	public function test_fmt_csv_line() {
		$data = array(
			'phone' => 'A',
			'email' => '200',
			'arbitrarytext' => 'whatever'
		);

		$keys = array('phone', 'email');


		// Try it with no formatters first
		$formatters = array();
		$value = $this->fmt->fmt_csv_line($data, $keys, $formatters);
		$this->assertTrue(($value == '"A","200"'), "Expected '\"A\",\"200\"', but got '{$value}'");

		// Try it with both formatters
		$formatters = array(
			'phone' => 'fmt_field_phone_result',
			'email' => 'fmt_field_email_result'
		);
		$value = $this->fmt->fmt_csv_line($data, $keys, $formatters);
		$this->assertTrue(($value == '"Answered","Delivered"'), "Expected '\"Answered\",\"Delivered\"', but got '{$value}'");
	}

}

?>
