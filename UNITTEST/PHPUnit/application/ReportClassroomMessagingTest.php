<?php

/**
 * ReportClassroomMessagingTest.php - PHPUnit test for obj/ReportClassroomMessaging.obj.php
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/inc/utils.inc.php");
require_once("{$konadir}/obj/Headers.obj.php");
require_once("{$konadir}/obj/ReportGenerator.obj.php");
require_once("{$konadir}/obj/ReportClassroomMessaging.obj.php");
require_once("{$konadir}/obj/Formatters.obj.php");
require_once("{$konadir}/inc/formatters.inc.php");
require_once("{$konadir}/messagedata/en/targetedmessage.php");

class ReportClassroomMessagingTest extends PHPUnit_Framework_TestCase {

	const NUM_EXPECTED_FIELDS = 12;

	public function test_summary_report() {

		// Providing orglabel in options allows RCM constructor to skip a getSystemSetting() call for it
		$options = array(
			'orglabel' => 'School'
		);
		$rcm = new ReportClassroomMessaging($options);

		// (1) Make sure it gives us a result object
		$condition = $rcm->generateQuery();
		$this->assertTrue($condition, 'generateQuery() indicated error result');


		// (2) Make sure it sends CSV to STDOUT - output buffering needed accordingly
		$fakedata = array(
			array(
				'login' => 1,
				'teacher' => 2,
				'orgkey' => 3,
				'skey' => 4,
				'studentid' => 5,
				'student' => 6,
				'messagekey' => 'absent',
				'notes' => 8,
				'occurrence' => 9,
				'lastattempt' => 10,
				'destination' => 11,
				'result' => 'A',
				'type' => 'phone'
			)
		);
		$rcm->query->__results($fakedata);
		ob_start();
		$rcm->runCSV();
		$csv = ob_get_contents();
		ob_end_clean();
		$condition = (strlen($csv) > 0);
		$this->assertTrue($condition, 'runCSV() did not generate any output');


		// (3) Make sure the CSV data line has the data we expect in it
		$csvlines = explode("\n", $csv);
		// trim off the last one if the final entry ends with a \n and results in an empty array element
		if (($num = count($csvlines)) && (! strlen($csvlines[$num - 1]))) {
			unset($csvlines[$num - 1]);
			$num--;
		}
		$condition = ($num == 2);
		$this->assertTrue($condition, "runCSV() CSV output line count was not 2 ({$num})");


		// (4) Extract the data from the scond line, eliminating the outer and inner quote marks and delimiters
		$csvdata = explode('","', substr($csvlines[1], 1, strlen($csvlines[1]) - 2));
		$condition = (self::NUM_EXPECTED_FIELDS == ($num = count($csvdata)));
		$this->assertTrue($condition, 'summary_csv_to_stdout() CSV output line did not have ' . self::NUM_EXPECTED_FIELDS . " data fields ({$num})");


		// (5) Iterate over the CSV values to make sure they are what the query returned
		$condition = true;
		for ($i = 0; $i < self::NUM_EXPECTED_FIELDS; $i++) {
			// Fields 6 and 12 are exceptions; because we use a formatter to
			// modify their values, we expect them to NOT equal their index+1
			if (($i == 6) || ($i == 11)) {
				if ($csvdata[$i] == ($i + 1)) {
					$condition = false;
					break;
				}
			}
			else {
				// Otherwise each field should retain the value that we staged in the fakedata
				if ($csvdata[$i] != ($i + 1)) {
					$condition = false;
					break;
				}
			}
		}
		$this->assertTrue($condition, 'summary_csv_to_stdout() CSV data had values that did not match what we expected');
	}
}

?>
