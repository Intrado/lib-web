<?php

/**
 * ReportClassroomMessagingTest.php - PHPUnit test for obj/ReportClassroomMessaging.obj.php
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

require_once('../DBStub.php');
require_once('../konaenv.php');
// ----------------------------------------------------------------------------
require_once("{$konadir}/inc/utils.inc.php");
require_once("{$konadir}/obj/ReportClassroomMessaging.obj.php");

class ReportClassroomMessagingTest extends PHPUnit_Framework_TestCase {

	public function test_summary_report() {
		$rcm = new ReportClassroomMessaging();

		// Make sure it gives us a result object
		$options = array();
		$result = $rcm->get_csvdata($options);
		$condition = is_object($result);
		$this->assertTrue($condition, 'get_csvdata() did not return a query object');
		if (! $condition) return; // TODO - is this necessary or will assertTrue throw an exception and abort the rest?

// FIXME All of this is disabled because it generates output headers which breaks PHPUnit(); overriding the header() function is
// not possible because we don't have PECL atd compiled in. We may have to eval() or exec() some code to get the CSV output and
// process it. One option is to suppress header output with an optional argument, but I'm not sure how well received instrumenting
// code for test purposes would be...
/*
		// Make sure it sends CSV to STDOUT - output buffering needed accordingly
		$fakedata = array(1, 2, 3, 4, 5, 6, 7, 8, 9, 10, 11, 12, 13, 14, 15, 16);
		$result->__results($fakedata);
		ob_start();
		$rcm->summary_csv_to_stdout($result);
		$csv = ob_get_contents();
		ob_end_clean();
		$condition = (strlen($csv) > 0);
		$this->assertTrue($condition, 'summary_csv_to_stdout() did not generate any output');
		if (! $condition) return; // TODO - is this necessary or will assertTrue throw an exception and abort the rest?

		// Make sure the CSV data line has the data we expect in it
		$csvlines = explode("\n", $csv);
		$condition = (count($csvlines) == 2);
		$this->assertTrue($condition, 'summary_csv_to_stdout() CSV output line count was not 2');
		if (! $condition) return; // TODO - is this necessary or will assertTrue throw an exception and abort the rest?

		// Extract the data from the scond line, eliminating the outer and inner quote marks and delimiters
		$csvdata = explode('","', substr($csvlines[1], 1, strlen($csvlines) - 2));
		$condition = (count($csvdata) == 16);
		$this->assertTrue($condition, 'summary_csv_to_stdout() CSV output line did not have 16 data fields');
		if (! $condition) return; // TODO - is this necessary or will assertTrue throw an exception and abort the rest?

		// Iterate over the CSV values to make sure they are what the query returned
		$condition = true;
		for ($i = 0; $i < 16; $i++) {
			if ($csvdata[$i] != ($i + 1)) {
				$condition = false;
				break;
			}
		}
		$this->assertTrue($condition, 'summary_csv_to_stdout() CSV data had values that did not match what we expected');
		if (! $condition) return; // TODO - is this necessary or will assertTrue throw an exception and abort the rest?
*/
	}
}

?>
