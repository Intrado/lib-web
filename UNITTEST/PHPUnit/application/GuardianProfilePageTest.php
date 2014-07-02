<?php

/**

 * @package unittests
 * @version 1.0
 */
require_once(realpath(dirname(dirname(__FILE__)) . '/PhpStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) . '/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) . '/DBStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) . '/ApiStub.php'));
// ----------------------------------------------------------------------------

/**
 * Test Class for Guardian Profile Edit Page
 */
class GuardianProfilePageTest extends PHPUnit_Framework_TestCase {

	const USER_ID = 1;
	const ACCESS_ID = 3;

	var $profileEditPage = null; // The instance of our class under test
	var $csApi = null; // An instance of CommsuiteApiClient to satisfy API calls
	var $formName = ''; // The name of our kona form

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
		$queryRules->add('/from user where id/', array(self::USER_ID), array(
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
		$queryRules->add('/from access where id/', array(self::ACCESS_ID), array(
			array(
				'name',
				'description',
				'cs'
			)
				)
		);

		// 4) SQL response: Permissions for this user's profile
		$queryRules->add('/from permission where accessid/', array(
			array(
				1,
				self::ACCESS_ID,
				'infocenter',
				1
			), array(
				2,
				self::ACCESS_ID,
				'manageprofile',
				1
			)
				)
		);


		// Mock up a USER session
		require_once("{$konadir}/inc/common.inc.php");
		$USER = new User(self::USER_ID);
		$_SESSION['access'] = new Access($USER->accessid);

		require_once("{$konadir}/guardianprofile.php");
	}

	// before each test
	public function setUp() {
		global $USER, $HEADERS;

		// Stub out API access
		$apiClient = new ApiStub('http://localhost/api');
		$this->csApi = new CommsuiteApiClient($apiClient);

		// Then go for launch!
		$this->profileEditPage = new GuardianProfilePage($this->csApi);

		// Grab the formName; we're going to need it!
		$this->formName = $this->profileEditPage->formName;

		// Clear out any previously captured headers
		$HEADERS = array();
	}

	// after each test
	public function tearDown() {
		unset($this->profileEditPage);
	}

	public function test_isAuthorized() {
		$this->assertTrue($this->profileEditPage->isAuthorized(), 'The user should be authorized to access this page');
	}

	// it shows an edit form with some properties of a Guardian Profile
	public function test_newGuardianForm() {
		// Load up the edit form with no burst id specified
		$this->profileEditPage->beforeLoad();
		$this->profileEditPage->load();
		$this->profileEditPage->afterLoad();
		$formhtml = $this->profileEditPage->render();
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_name"), 'Missing name input field');
	}

	// it redirects the request to edit a specific records after stashing the ID into the session
	public function test_editExistingRedirect() {
		global $HEADERS;


		// Load up the edit form with burst id = 1
		$data = array('id' => 1);

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() {
			return(false);
		});

		$this->profileEditPage->beforeLoad($data, $data, $data);

		unset_exit_overload();

		// Strange, but self-referential redirect in CLI actually goes to PHPUNIT binary - hah!
		$location = '';
		foreach ($HEADERS as $header) {
			if (strpos($header, 'Location:') == 0) {
				$location = $header;
				break;
			}
		}
		$this->assertTrue((strpos($location, '/phpunit') !== FALSE), 'Expected a successful redirect to pdfedit.php');
	}

	// it prepopulates the form fields with values from an existing record if specified (converts the file input into a read-only string)
	public function test_editExistingForm() {
		global $queryRules;

		$_SESSION['profileid'] = 1;
		$empty = array();

		$profile = new stdClass();
		$profile->id = 1;
		$profile->name = "existing name";
		$profile->description = "existing name description";
		$profile->type = "guardian";


		//validator for unique names
		$queryRules->add("/from access where type/", array($profile->name, $profile->id), array(array(true)));
		$queryRules->add('/from access where id/', array($profile->id), array(
			array(
				$profile->name,
				$profile->description,
				'guardian'
			))
		);
		$apiClient = new ApiStub('http://localhost/api');

		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();



		$mockApi->expects($this->any())
				->method('getProfile')
				->will($this->returnValue($profile));


		$page = new GuardianProfilePage($mockApi);


		$page->beforeLoad($empty, $empty, $empty, $_SESSION);
		$page->load();
		$page->afterLoad();
		$formhtml = $page->render();


		// The profile name input field should be present
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_name"), 'Missing name input field');

		// Make sure the name input field has the right value from the profile record
		$this->assertTrue(false !== strpos($formhtml, 'type="text" value="existing name"'), 'Name input field did not have the correct default value');

		//checkbox
		$this->assertTrue(false !== strpos($formhtml, 'type="checkbox" value="true" checked'), 'InfoCenter checkbox did not have the right default option pre-selected');
	}

}

?>
