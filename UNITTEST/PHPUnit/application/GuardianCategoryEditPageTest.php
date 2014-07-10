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
 * Test Class for Guardian Category Edit Page
 */
class GuardianCategoryEditPageTest extends PHPUnit_Framework_TestCase {

	const USER_ID = 1;
	const ACCESS_ID = 3;

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

		require_once("{$konadir}/guardiancategoryedit.php");
	}

	// before each test
	public function setUp() {
		global $USER, $HEADERS;


		// Clear out any previously captured headers
		$HEADERS = array();
	}

	// after each test
	public function tearDown() {
		
	}

	public function test_isAuthorized() {
		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();

		$page = new GuardianCategoryEditPage($mockApi);

		$this->assertTrue($page->isAuthorized(), 'The user should be authorized to access this page');
	}

	// it shows an edit form with some properties of a Guardian Category
	public function test_newGuardianForm() {

		$profiles = array();
		$profile = new stdClass();
		$profile->id = 4;
		$profile->name = "Admin Profile";
		$profile->description = "existing name description";
		$profile->type = "guardian";
		$permission = new stdClass;
		$permission->name = "infocenter";
		$permission->value = 0;
		$profile->permissions = array($permission);
		$profiles[] = $profile;

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();

		$mockApi->expects($this->any())
				->method('getProfileList')
				->will($this->returnValue($profiles));

		$page = new GuardianCategoryEditPage($mockApi);

		$page->beforeLoad();
		$page->load();
		$page->afterLoad();
		$formhtml = $page->render();
		$formName = $page->formName;
		$this->assertTrue(false !== strpos($formhtml, "{$formName}_name"), 'Missing name input field');
		$this->assertTrue(false !== strpos($formhtml, "{$formName}_profile"), 'Missing profile selection field');
		$this->assertTrue(false !== strpos($formhtml, '<option value="0" selected >' . GuardianCategoryEditPage::$NO_ACCESS . '</option>'), 'Default Access profile is not selected');
	}

	// it redirects the request to edit a specific records after stashing the ID into the session
	public function test_editExistingRedirect() {
		global $HEADERS;
		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();

		$page = new GuardianCategoryEditPage($mockApi);

		// Load up the edit form with category id = 1
		$data = array('id' => 1);

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() {
			return(false);
		});

		$page->beforeLoad($data, $data, $data);

		unset_exit_overload();

		$location = '';
		foreach ($HEADERS as $header) {
			if (strpos($header, 'Location:') == 0) {
				$location = $header;
				break;
			}
		}
		$this->assertTrue((strpos($location, '/phpunit') !== FALSE), 'Expected a successful redirect to guardiancategorymanager.php');
	}

	// it prepopulates the form fields with values from an existing record if specified (converts the file input into a read-only string)
	public function test_editExistingForm() {
		global $queryRules;

		$_SESSION['categoryid'] = 1;
		$empty = array();

		$category = new stdClass();
		$category->id = 1;
		$category->name = "existing name";
		$category->sequence = 0;
		$category->profileId = "4";



		//validator for unique names
		$queryRules->add("/from guardiancategory where name/", array($category->name, $category->id), array(array(true)));
		$queryRules->add('/from guardiancategory where id/', array($category->id), array(
			array($category->id, $category->name, $category->sequence, $category->profileId)
				)
		);
		$apiClient = new ApiStub('http://localhost/api');

		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();



		$mockApi->expects($this->any())
				->method('getGuardianCategory')
				->will($this->returnValue($category));

		$profiles = array();
		$profile = new stdClass();
		$profile->id = 4;
		$profile->name = "Admin Profile";
		$profile->description = "existing name description";
		$profile->type = "guardian";
		$permission = new stdClass;
		$permission->name = "infocenter";
		$permission->value = 0;
		$profile->permissions = array($permission);
		$profiles[] = $profile;

		$mockApi->expects($this->any())
				->method('getProfileList')
				->will($this->returnValue($profiles));

		$page = new GuardianCategoryEditPage($mockApi);


		$page->beforeLoad($empty, $empty, $empty, $_SESSION);
		$page->load();
		$page->afterLoad();
		$formhtml = $page->render();
		$formName = $page->formName;

		// The profile name input field should be present
		$this->assertTrue(false !== strpos($formhtml, "{$formName}_name"), 'Missing name input field');

		// Make sure the name input field has the right value from the profile record
		$this->assertTrue(false !== strpos($formhtml, 'type="text" value="existing name"'), 'Name input field did not have the correct default value');
		$this->assertTrue(false !== strpos($formhtml, "{$formName}_profile"), 'Missing profile selection field');
		$this->assertTrue(false !== strpos($formhtml, '<option value="4" selected >Admin Profile</option>'), 'Profile is not set');
	}

	// it handles form submission success by redirecting the client to profiles.php with a notice() message
	public function test_formSubmit() {
		global $HEADERS;
		global $queryRules;

		$apiClient = new ApiStub('http://localhost/api');

		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array("getGuardianCategory", "setGuardianCategory", "getProfileList"))
				->getMock();
		$page = new GuardianCategoryEditPage($mockApi);


		$formName = $page->formName;

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() {
			return(false);
		});

		// POST the edit form with burst id = 1 so that we don't have to upload a file for this test
		$_POST = $_REQUEST = array(
			'submit' => 'submit',
			'form' => $formName,
			"{$formName}_name" => "existing name",
		);


		$_SESSION['categoryid'] = 1;


		$category = new stdClass();
		$category->id = 1;
		$category->name = "existing name";
		$category->sequence = 0;
		$category->profileId = "4";


		//validator for unique names
		$queryRules->add("/from guardiancategory where name/", array($category->name, $category->id), array(array(false)));
		$queryRules->add('/from guardiancategory where id/', array($category->id), array(
			array($category->id, $category->name, $category->sequence, $category->profileId)
				)
		);

		$mockApi->expects($this->any())
				->method('getGuardianCategory')
				->will($this->returnValue($category));

		$mockApi->expects($this->any())
				->method('setGuardianCategory')
				->will($this->returnValue(true));



		$profiles = array();
		$profile = new stdClass();
		$profile->id = 4;
		$profile->name = "Admin Profile";
		$profile->description = "existing name description";
		$profile->type = "guardian";
		$permission = new stdClass;
		$permission->name = "infocenter";
		$permission->value = 0;
		$profile->permissions = array($permission);
		$profiles[] = $profile;

		$mockApi->expects($this->any())
				->method('getProfileList')
				->will($this->returnValue($profiles));


		$page->beforeLoad($_POST, $_POST, $_REQUEST, $_SESSION);
		$page->load();

		// Extract the correct serialnum out of the form and stick it into the POST!
		$serialnum = $page->form->serialnum;
		$_POST["{$formName}-formsnum"] = $_REQUEST["{$formName}-formsnum"] = $serialnum;

		$page->afterLoad();

		unset_exit_overload();

		// There should be a location redirect header if the PUT was successful
		$this->assertTrue(in_array('Location: guardiancategorymanager.php', $HEADERS), 'Expected a successful redirect to guardiancategorymanager.php');
	}

}

?>
