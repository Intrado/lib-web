<?php

/**
 * PdfEditPageTest.php - PHPUnit Test Class for PDF Edit Page
 *
 * NOTE: requires `pecl install phpunit/test_helpers`; this is so that we can
 * turn calls to exit() or die() in the legacy code base into NO-OP's which
 * allows PHPUNIT to continue running and delivering test results. This happens
 * when, for example, the page makes a call to the redirect() utility function:
 *
 * 1) Put test_helpers.so into /usr/commsuite/server/php/lib/php/extensions/
 * 2) Add zend_extension=/usr/commsuite/server/php/lib/php/extensions/test_helpers.so to /usr/commsuite/server/php/lib/php.ini
 *
 * ref: https://github.com/php-test-helpers/php-test-helpers
 * ref: http://thedeveloperworldisyours.com/php/phpunit-tips/
 *
 * @todo - note that runkit functions CAN NOT satisfy the needs that we get out of
 * test_helpers
 *
 * UPDATE 2014-01-02; test_helpers.so is now being loaded dynamically in PhpStub.php,
 * so there is no need to put it into php.ini as described above. It DOES need to be
 * installed and positioned correctly in the filesystem though!
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/PhpStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/ApiStub.php'));
// ----------------------------------------------------------------------------

/**
 * Test Class for PDF Edit Page
 */
class PdfEditPageTest extends PHPUnit_Framework_TestCase {
	const USER_ID = 1;
	const ACCESS_ID = 3;

	var $pdfEditPage = null; // The instance of our class under test
	var $csApi = null;	// An instance of CommsuiteApiClient to satisfy API calls
	var $formName = '';	// The name of our kona form

	/**
	 * Before any tests run, before we even include the source file for the class under test
	 * we may want to prepare some things that the file depends on such as environment variables
	 * and such things that are normally set under an Apache context before loading...
	 */
	public static function setUpBeforeClass() {
		global $queryRules, $USER;

		// 1) Hit the reset switch!
		$queryRules->reset();

		// Something about being a static method called before instantiation prevents
		// $konadir from being seen as a regular global as it is normally...
		$konadir = $GLOBALS['konadir'];

		// Mock up some superglobal "server" data to make it look like we were requested through apache
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/';

		// 2) SQL response: The User DBMO initialization query for userID=1
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

		// 3) SQL response: The profile (access) record for this user
		$queryRules->add('/from access where id/', array(self::ACCESS_ID),
			array(
				array(
					'name',
					'description'
				)
			)
		);

		// 4) SQL response: Permissions for this user's profile
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

		// 5) SQL response: A list of burst template records
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

		// 6) API response: retrieve a single burst record (with id=1)
		$queryRules->add('|"method":"GET","node":"\\\/bursts\\\/1","data":null|',
			array(
				array(
					'headers' => 'Content-type: application/json',
					'body' => '{"id":1,"name":"testname","filename":"testfile.pdf","size":1234,"status":"new","contentId":1,"ownerUser":{"id":1},"uploadTimeStampms":1234,"burstTemplateId":1}',
					'code' => 200
				)
			)
		);

		// 7) API response: update a single burst record (with id=1)
		$queryRules->add('|"method":"PUT","node":"\\\/bursts\\\/1","data":{"name":"newname!","burstTemplateId":1}|',
			array(
				array(
					'headers' => 'Content-type: application/json',
					'body' => '{"id":1,"name":"testname","filename":"testfile.pdf","size":1234,"status":"new","contentId":1,"ownerUser":{"id":1},"uploadTimeStampms":1234,"burstTemplateId":1}',
					'code' => 200
				)
			)
		);

		// 8) API response: update a single burst record (with id=3, but invalid template id=3)
		$queryRules->add('|"method":"PUT","node":"\\\/bursts\\\/1","data":{"name":"this one has an invalid template id!","burstTemplateId":3}|',
			array(
				array(
					'headers' => 'Content-type: text/plain',
					'body' => '',
					'code' => 404
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
	public function setUp() {
		global $USER, $HEADERS;

		// Stub out API access
		$apiClient = new ApiStub('localhost', 'unrealcustomer', $USER->id, 'unrealauthcookiedata');
		$this->csApi = new CommsuiteApiClient($apiClient);

		// Then go for launch!
		$this->pdfEditPage = new PdfEditPage($this->csApi);

		// Grab the formName; we're going to need it!
		$this->formName = $this->pdfEditPage->formName;

		// Clear out any previously captured headers
		$HEADERS = array();
	}

	// after each test
	public function tearDown() {
		unset($this->pdfEditPage);
	}

	public function test_isAuthorized() {
		$this->assertTrue($this->pdfEditPage->isAuthorized(), 'The user should be authorized to access this page');
	}

	// it shows an edit form with some properties of a PDF burst record
	public function test_newUploadForm() {

		// Load up the edit form with no burst id specified
		$this->pdfEditPage->beforeLoad();
		$this->pdfEditPage->load();
		$this->pdfEditPage->afterLoad();
		$formhtml = $this->pdfEditPage->render();

		$this->assertTrue(false !== strpos($formhtml, 'multipart/form-data'), 'Missing expected enctype="multipart/form-data"');
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_name"), 'Missing name input field');
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_bursttemplateid"), 'Missing burst template select field');
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_thefile"), 'Missing file input field');
	}

	// it redirects the request to edit a specific records after stashing the ID into the session
	public function test_editExistingRedirect() {
		global $HEADERS;


		// Load up the edit form with burst id = 1
		$data = array('id' => 1);

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() { return(false); });

		$this->pdfEditPage->beforeLoad($data, $data, $data);

		unset_exit_overload();

		// Strange, but self-referential redirect in CLI actually goes to PHPUNIT binary - hah!
		$this->assertTrue(in_array('Location: /usr/commsuite/server/php/bin/phpunit', $HEADERS), 'Expected a successful redirect to pdfedit.php');
	}


	// it prepopulates the form fields with values from an existing record if specified (converts the file input into a read-only string)
	public function test_editExistingForm() {

		// Load up the edit form with burst id = 1
		$_SESSION['burstid'] = 1;
		$empty = array();

		$this->pdfEditPage->beforeLoad($empty, $empty, $empty, $_SESSION);
		$this->pdfEditPage->load();
		$this->pdfEditPage->afterLoad();
		$formhtml = $this->pdfEditPage->render();

		// The burst name input field should be present
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_name"), 'Missing name input field');

		// Make sure the name input field has the right value from the burst record
		$this->assertTrue(false !== strpos($formhtml, 'type="text" value="testname"'), 'Name input field did not have the correct default value');

		// The burst template selection should be present
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_bursttemplateid"), 'Missing burst template select field');

		// The first burst template should be preselected (since that is what our sample data for burst id=1 says)
		$this->assertTrue(false !== strpos($formhtml, 'option value="1" selected'), 'Burst template field did not have the right default option pre-selected');

		// There should be no file input field now because file is read-only for editing existing records
		$this->assertFalse(strpos($formhtml, "{$this->formName}_thefile"), 'File input field should NOT be present on this form');

		// There should be a hidden text field though with the burst ID so that the post handler knows where to send the data
		//$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_id\" type=\"hidden\""), 'Missing hidden id field');

		// And the output should contain some read-only text with the name of the PDF file as well
		$this->assertTrue(false !== strpos($formhtml, 'class="formcontrol cf">testfile.pdf'), 'Missing read-only PDf filename text');
	}

	// it handles form submission success by redirecting the client to pdfmanager.php with a notice() message
	public function test_formSubmitSuccess() {
		global $HEADERS;

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() { return(false); });
		
		// POST the edit form with burst id = 1 so that we don't have to upload a file for this test
		$_POST = $_REQUEST = array(
			'submit' => 'submit',
			'form' => $this->formName,
			"{$this->formName}_name" => 'newname!',
			"{$this->formName}_bursttemplateid" => 1
		);

		$_SESSION['burstid'] = 1;

		$this->pdfEditPage->beforeLoad($_POST, $_POST, $_REQUEST, $_SESSION);
		$this->pdfEditPage->load();

		// Extract the correct serialnum out of the form and stick it into the POST!
		$serialnum = $this->pdfEditPage->form->serialnum;
		$_POST["{$this->formName}-formsnum"] = $_REQUEST["{$this->formName}-formsnum"] = $serialnum;
		
		$this->pdfEditPage->afterLoad();
		
		unset_exit_overload();

		// There should be a location redirect header if the PUT was successful
		$this->assertTrue(in_array('Location: pdfmanager.php', $HEADERS), 'Expected a successful redirect to pdfmanager.php');
	}

	// it handles form submission errors by displaying a modal overlay and redisplaying the form
	public function test_formSubmitFailure() {
		global $HEADERS;

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() { return(false); });
		
		// POST the edit form with burst id = 1 so that we don't have to upload a file for this test
		$_POST = $_REQUEST = array(
			'submit' => 'submit',
			'form' => $this->formName,
			"{$this->formName}_name" => 'this one has an invalid template id!',
			"{$this->formName}_bursttemplateid" => 3
		);

		$_SESSION['burstid'] = 1;

		$this->pdfEditPage->beforeLoad($_POST, $_POST, $_REQUEST, $_SESSION);
		$this->pdfEditPage->load();

		// Extract the correct serialnum out of the form and stick it into the POST!
		$serialnum = $this->pdfEditPage->form->serialnum;
		$_POST["{$this->formName}-formsnum"] = $_REQUEST["{$this->formName}-formsnum"] = $serialnum;
		
		$this->pdfEditPage->afterLoad();
		$formhtml = $this->pdfEditPage->render();
		
		unset_exit_overload();

		// There should NOT be a redirect to the pdfmanager.php page
		$this->assertFalse(in_array('Location: pdfmanager.php', $HEADERS), 'There should not have been a redirect to pdfmanager.php');

		// There should be an error modal present in the page output which displays automatically on page load
		$this->assertTrue(false !== strpos($formhtml, "('#pdfeditmodal').modal('show');"), 'Missing expected error modal');
	}
}

?>
