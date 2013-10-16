<?php

/**
 * ReportClassroomMessagingTest.php - PHPUnit test for obj/ReportClassroomMessaging.obj.php
 *
 * @package unittests
 * @author Sean M. Kelly, <skelly@schoolmessenger.com>
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/inc/utils.inc.php");
require_once("{$konadir}/obj/ReportClassroomMessaging.obj.php");

class ReportClassroomMessagingTest extends PHPUnit_Framework_TestCase {

	public function test_summary_report() {
		$rcm = new ReportClassroomMessaging();

		// (1) Make sure it gives us a result object
		$options = array();
		$condition = $rcm->queryexec_csvdata($options);
		$this->assertTrue($condition, 'queryexec_csvdata() indicated error result');


		// (2) Make sure it sends CSV to STDOUT - output buffering needed accordingly
		$fakedata = array(
			array(
				"login" => 1,
				"teacher" => 2,
				"school" => 3,
				"section" => 4,
				"student id" => 5,
				"student" => 6,
				"message" => 7,
				"notes" => 8,
				"message time" => 9,
				"last attempt" => 10,
				'type' => 11,
				"destination" => 12,
				"result" => 13,
				"status" => 14
			)
		);
		$rcm->csvdata->__results($fakedata);
		ob_start();
		$rcm->send_csvdata();
		$csv = ob_get_contents();
		ob_end_clean();
		$condition = (strlen($csv) > 0);
		$this->assertTrue($condition, 'csvdata() did not generate any output');


		// (3) Make sure the CSV data line has the data we expect in it
		$csvlines = explode("\n", $csv);
		// trim off the last one if the final entry ends with a \n and results in an empty array element
		if (($num = count($csvlines)) && (! strlen($csvlines[$num - 1]))) {
			unset($csvlines[$num - 1]);
			$num--;
		}
		$condition = ($num == 2);
		$this->assertTrue($condition, "csvdata() CSV output line count was not 2 ({$num})");


		// (4) Extract the data from the scond line, eliminating the outer and inner quote marks and delimiters
		$csvdata = explode('","', substr($csvlines[1], 1, strlen($csvlines[1]) - 2));
		$condition = (($num = count($csvdata)) == 14);
		$this->assertTrue($condition, "summary_csv_to_stdout() CSV output line did not have 14 data fields ({$num})");


		// (5) Iterate over the CSV values to make sure they are what the query returned
		$condition = true;
		for ($i = 0; $i < 13; $i++) {
			if ($csvdata[$i] != ($i + 1)) {
				$condition = false;
				break;
			}
		}
		$this->assertTrue($condition, 'summary_csv_to_stdout() CSV data had values that did not match what we expected');
	}
}

?>
