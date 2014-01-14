<? 

/**
 * PdfSendMailTest.php - PHPUnit test for PdfSendMail class
 *
 * @package unittests
 * @author Justin Burns, <jburns@schoolmessenger.com>
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/pdfsendmail.php");

class PdfSendMailTest extends PHPUnit_Framework_TestCase {

	var $pdfSendMail;
	var $apiClient;
	var $csApi;
	var $userBroadcastTypes;
	var $userID = 1;

	public function setup() {
		global $USER;

		// create mock User
		$USER = $this->getMockBuilder('User')
					->setConstructorArgs(array($this->userID))
					->getMock();

		// data for ApiClient
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_COOKIE['custname_session'] = 'cookie123';

		// data for global function: customerUrlComponent()
		$_SERVER['SCRIPT_NAME'] = '/custname/pdfmanager.php';

		// create (unmocked) ApiClient object to be passed into CommsuiteApiClient mock object's constructor below
		$this->apiClient = new ApiClient(
			$_SERVER['SERVER_NAME'],
			'custname',
			$USER->id,
			$_COOKIE['custname_session']
		);

		$this->userBroadcastTypes = array(
							1 => (object) array(
									'name' => 'Emergency',
									'systempriority' => 1,
									'info' => 'Emergencies Only',
									'issurvey' => 0,
									'deleted' => 0,
									'id' => 1
								), 
							2 => (object) array(
									'name' => 'Attendance',
									'systempriority' => 2,
									'info' => 'Attendance',
									'issurvey' => 0,
									'deleted' => 0,
									'id' => 2
								),
							3 => (object) array(
									'name' => 'General',
									'systempriority' => 3,
									'info' => 'General',
									'issurvey' => 0,
									'deleted' => 0,
									'id' => 3
								)
						);

		$this->csApi = $this->getMockBuilder('CommsuiteApiClient')
							->setConstructorArgs(array($this->apiClient))
							->setMethods(array('getBurstData', 'getBurstPortionList'))
							->getMock();

		$burstObj = (object) null;
		$burstObj->id = 1;
		$burstObj->name = 'MyBurst';
		$this->csApi->expects($this->any())->method('getBurstData')->will($this->returnValue($burstObj));

		$burstPortionObj = (object) null;
		$burstPortionObj->identifierText = "student_id";
		$burstPortionObj->firstPage = 1;
		$burstPortionObj->lastPage = 2;
		$burstListObj = (object) null;
		$burstListObj->portions = array($burstPortionObj);
		$this->csApi->expects($this->any())->method('getBurstPortionList')->will($this->returnValue($burstListObj));

		// define PdfSendMail mock object
		$this->pdfSendMail = $this->getMockBuilder('PdfSendMail')
							->setConstructorArgs(array($this->csApi))
							->setMethods(array('getUserBroadcastTypes', 'getUserEmailDomain'))
							->getMock();

		// define stub for getUserEmailDomain
		$this->pdfSendMail->expects($this->any())
			  		->method('getUserEmailDomain')
			  		->will($this->returnValue('schoolmessenger.com'));

		// define stub for getUserBroadcastTypes
		$this->pdfSendMail->expects($this->any())
			  		->method('getUserBroadcastTypes')
			  		->will($this->returnValue($this->userBroadcastTypes));

	}

	public function teardown() {
		unset($this->pdfSendMail);
	}


	public function test_load() {
		$session = array('pdfsendmail_burstid' => 1);
		$this->pdfSendMail->beforeLoad($get = array(), $post = array(), $request = array(), $session);
		$this->pdfSendMail->load();
		
		// check jobtypes and email domain instance vars for pdfSendMail object
		$jobType_1 = $this->pdfSendMail->userBroadcastTypes[1];
		$this->assertEquals(1, $jobType_1->id);
		$this->assertEquals('Emergency', $jobType_1->name);
		$this->assertEquals(1, $jobType_1->systempriority);

		$jobType_2 = $this->pdfSendMail->userBroadcastTypes[2];
		$this->assertEquals(2, $jobType_2->id);
		$this->assertEquals('Attendance', $jobType_2->name);
		$this->assertEquals(2, $jobType_2->systempriority);

		$jobType_3 = $this->pdfSendMail->userBroadcastTypes[3];
		$this->assertEquals(3, $jobType_3->id);
		$this->assertEquals('General', $jobType_3->name);
		$this->assertEquals(3, $jobType_3->systempriority);

		// check that email domain got set as expected from calling getUserEmailDomain stub
		$this->assertEquals('schoolmessenger.com', $this->pdfSendMail->emailDomain);
	}

	public function test_setFormData() {
		// call load() to set userBroadcastTypes & emailDomain vars
		$this->pdfSendMail->load();

		// setFormData() iterates over user userBroadcastTypes, sorts them A-Z, and is used to create the dropdown control
		// it also sets the formdata instance var with the big array of control/form inputs
		$this->pdfSendMail->setFormData();

		// check a few formdata values
		$this->assertEquals('Broadcast Name', $this->pdfSendMail->formdata['broadcastname']['label']);
		//$this->assertEquals(3, $this->pdfSendMail->formdata['broadcasttype']['value']);
		$this->assertEquals($this->pdfSendMail->emailDomain, $this->pdfSendMail->formdata['fromemail']['validators'][2]['domain']);
	}

	public function test_fillListFromBurst() {
		$list = new MyPersonList();
		$this->pdfSendMail->fillListFromBurst($list, 1);

		$this->assertCount(1, $list->pkeys);
		$this->assertContains("student_id", $list->pkeys, "the pkey was not added to the list");
	}
}

/* Spy class to capture the pkeys being added to the new list */
class MyPersonList extends PeopleList {
	var $pkeys;

	function updateManualAddByPkeys($pkeys, $removeExisting = true) {
		$this->pkeys = $pkeys;
	}
}

?>