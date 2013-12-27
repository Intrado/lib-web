<?php

/**
 * PdfEditPageTest.php - PHPUnit Test Class for PDF Edit Page
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/ApiStub.php'));
// ----------------------------------------------------------------------------

/**
 * Test Class for PDF Edit Page
 */
class PfdEditPageTest extends PHPUnit_Framework_TestCase {
	const USER_ID = 1;
	const ACCESS_ID = 3;
	const FORMNAME = 'pdfuploader';	// The name of our kona form; if it changes in the class, we'll need to update this

	var $pdfEditPage = null; // The instance of our class under test
	var $csApi = null;	// An instance of CommsuiteApiClient to satisfy API calls

	/**
	 * Before any tests run, before we even include the source file for the class under test
	 * we may want to prepare some things that the file depends on such as environment variables
	 * and such things that are normally set under an Apache context before loading...
	 */
	public static function setUpBeforeClass() {
		global $konadir, $queryRules, $USER;


		// Mock up some superglobal "server" data to make it look like we were requested through apache
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/';

		// 2) The user DBMO initialization query for userID=1
		$queryRules->add('/from user where id/', array(self::USER_ID),
			array(
				array(
					self::ACCESS_ID,
					'first.last',
					'',
					'first',
					'last',
					'description',
					'email',
					'autoreportemail',
					'8316001335',
					1,
					null,
					0,
					0,
					'staff10124',
					null,
					'2013-01-01 12:00:00',
					null
				)
			)
		);

		// 3) The profile (access) record for this user
		$queryRules->add('/from access where id/', array(self::ACCESS_ID),
			array(
				array(
					'name',
					'description'
				)
			)
		);

		// 4) Permissions for this user's profile
		$queryRules->add('/from permission where accessid/',
			array(
				array(
					1,
					self::ACCESS_ID,
					'canpdfburst',
					1
				)
			)
		);

		// 5) A list of burst template records
		$queryRules->add('/FROM `bursttemplate` WHERE NOT `deleted`/',
			array(
				array(
					'id' => 1,
					'name' => 'first template'
				),
				array(
					'id' => 2,
					'name' => 'second template'
				)
			)
		);

		// 6) API response for a single burst record (with id=1)
		$queryRules->add('|"method":"GET","node":"\\\/bursts\\\/1","data":null|',
			array(
				array(
					'headers' => 'Content-type: application/json',
					'body' => '{"id":1,"name":"testname","filename":"testfile.pdf","size":1234,"status":"new","contentId":1,"ownerUser":{"id":1},"uploadTimeStampms":1234,"burstTemplateId":1}',
					'code' => 200
				)
			)
		);


		// Mock up a USER session
		require_once("{$konadir}/inc/common.inc.php");
		$USER = new User(self::USER_ID);
		$_SESSION['access'] = new Access($USER->accessid);

		require_once("{$konadir}/pdfedit.php");
	}

	// before each test
	protected function setUp() {
		global $USER;

		// Stub out API access
		$apiClient = new ApiStub('localhost', 'unrealcustomer', $USER->id, 'unrealauthcookiedata');
		$this->csApi = new CommsuiteApiClient($apiClient);

		// Then go for launch!
		$this->pdfEditPage = new PDFEditPage($this->csApi);
	}

	// after each test
	protected function tearDown() {
		unset($this->pdfEditPage);
	}

	public function test_isAuthorized() {
		$this->assertTrue($this->pdfEditPage->isAuthorized(), 'The user should be authorized to access this page');
	}

	public function test_newUploadForm() {

		// Load up the edit form with no burst id specified
		$this->pdfEditPage->beforeLoad();
		$this->pdfEditPage->load();
		$this->pdfEditPage->afterLoad();
		$formhtml = $this->pdfEditPage->render();

		$this->assertTrue(false !== strpos($formhtml, 'multipart/form-data'), 'Missing expected enctype="multipart/form-data"');
		$this->assertTrue(false !== strpos($formhtml, self::FORMNAME . '_name'), 'Missing name input field');
		$this->assertTrue(false !== strpos($formhtml, self::FORMNAME . '_bursttemplateid'), 'Missing burst template select field');
		$this->assertTrue(false !== strpos($formhtml, self::FORMNAME . '_thefile'), 'Missing file input field');
	}

	public function test_editExistingForm() {

		// Load up the edit form with burst id = 1
		$data = array('id' => 1);

		$this->pdfEditPage->beforeLoad($data, $data, $data);
		$this->pdfEditPage->load();
		$this->pdfEditPage->afterLoad();
		$formhtml = $this->pdfEditPage->render();

		$this->assertTrue(false !== strpos($formhtml, 'multipart/form-data'), 'Missing expected enctype="multipart/form-data"');
		$this->assertTrue(false !== strpos($formhtml, self::FORMNAME . '_name'), 'Missing name input field');
		$this->assertTrue(false !== strpos($formhtml, self::FORMNAME . '_bursttemplateid'), 'Missing burst template select field');

		// There should be no file input field now because file is read-only for editing existing records
		$this->assertFalse(strpos($formhtml, self::FORMNAME . '_thefile'), 'Missing file input field');
	}
}

?>
