<?php

/**
 * HeadersTest.php - PHPUnit Test Class for obj/Headers.obj.php
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/obj/Headers.obj.php");


/**
 * Test Class for obj/Headers.obj.php
 */
class HeadersTest extends PHPUnit_Framework_TestCase {

	/**
	 * @runInSeparateProcess
	 */
	public function test_send_csv_headers() {
		$hdr = new Headers();

		$hdr->send_csv_headers('testfile.csv');
		
		$headers_sent = apache_response_headers();
		$this->assertTrue((is_array($headers_sent) && count($headers_sent)), 'The list of headers sent was empty - that should not be');

		$found = false;
		$mimetype = '';
		foreach ($headers_sent as $header => $value) {
			switch (strtolower($header)) {
				case 'content-disposition':
					$found = (strpos($value, 'testfile.csv') !== false);
					break;

				case 'content-type':
					$mimetype = trim(strtolower($value));
					break;
			}
		}
		$this->assertTrue($found, 'The filename does not appear in any of the headers as expected');
		$this->assertTrue(($mimetype == 'application/vnd.ms-excel'), "Expected mimetype='application/vnd.ms-excel', but got '{$mimetype}' instead");
	}
}

?>
