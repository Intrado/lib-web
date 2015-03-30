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
 * Test Class for Guardian Category Association Page
 */
class ListRecipientModeTest extends PHPUnit_Framework_TestCase {

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
				), array(
					3,
					self::ACCESS_ID,
					'metadata',
					1
				)
			)
		);


		require_once("{$konadir}/inc/common.inc.php");
		require_once("{$konadir}/inc/form.inc.php");
		require_once("{$konadir}/obj/Form.obj.php");
		require_once("{$konadir}/obj/FormItem.obj.php");
		require_once("{$konadir}/obj/PeopleList.obj.php");
		require_once("{$konadir}/obj/RestrictedValues.fi.php");
		require_once("{$konadir}/obj/ListGuardianCategory.obj.php");
		require_once("{$konadir}/obj/ListRecipientMode.obj.php");

		// Mock up a USER session
		$USER = new User(self::USER_ID);
		$_SESSION['access'] = new Access($USER->accessid);
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


	public function test_addToFormNew() {
		global $queryRules;

		$listId = 77;
		$category1 = new stdClass();
		$category1->id = 88;
		$category1->name = "Primary";
		$category1->sequence = 0;
		$category1->profileId = "4";

		$category2 = new stdClass();
		$category2->id = 88;
		$category2->name = "Primary";
		$category2->sequence = 0;
		$category2->profileId = "4";

		$queryRules->add("/select guardianCategoryId from listguardiancategory where listid=$listId/", array(), array(array($category1->id)));

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
			->setConstructorArgs(array($apiClient))
			->setMethods(array())
			->getMock();

		$mockApi->expects($this->any())
			->method('getGuardianCategoryList')
			->will($this->returnValue(array($category1, $category2)));

		$helpStep = 3;
		$maxGuardians = 3;

		$listRecipientMode = new ListRecipientMode ($mockApi, $helpStep, $maxGuardians, null, null);
		$formData = array();
		$listRecipientMode->addToForm($formData);

		$this->assertTrue($listRecipientMode->isEnabled() == 1, " guardian model enabled");


		$this->assertEquals(3, count($formData), " number of form elements do not match");
		$mode = $formData[ListRecipientMode::RECIPIENT_MODE_ELEMENT];
		$this->assertTrue(count($mode) > 0, " recipient mode form element is not set");

		$this->assertEquals(PeopleList::$RECIPIENTMODE_MAP[3], $mode["value"], " recipient mode is not set to default");

		$categoriesField = $formData[ListRecipientMode::RECIPIENT_CATEGORIES_ELEMENT];
		$this->assertTrue(count($categoriesField) > 0, " recipientcategories form element is not set");


		$selectedCategories = $categoriesField["value"];
		$this->assertEquals(0, count($selectedCategories), " one category should be selected");

		$categories = $categoriesField["validators"][0]["values"];
		$this->assertEquals(1, count($categories), " expected one category");
		$this->assertEquals($category1->id, $categories[0], " expected one category");

		$this->assertEquals($helpStep, $categoriesField["helpstep"], " wrong help step");


	}

	public function test_addToFormWithSelectedCategories() {
		global $queryRules;

		$listId = 77;
		$category1 = new stdClass();
		$category1->id = 88;
		$category1->name = "Primary";
		$category1->sequence = 0;
		$category1->profileId = "4";

		$category2 = new stdClass();
		$category2->id = 88;
		$category2->name = "Primary";
		$category2->sequence = 0;
		$category2->profileId = "4";

		$queryRules->add("/select guardianCategoryId from listguardiancategory where listid=$listId/", array(), array(array($category1->id)));

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
			->setConstructorArgs(array($apiClient))
			->setMethods(array())
			->getMock();

		$mockApi->expects($this->any())
			->method('getGuardianCategoryList')
			->will($this->returnValue(array($category1, $category2)));

		$helpStep = 3;
		$maxGuardians = 3;
		$selectedMode = PeopleList::$RECIPIENTMODE_MAP[2];

		$listRecipientMode = new ListRecipientMode ($mockApi, $helpStep, $maxGuardians, $listId, $selectedMode);
		$formData = array();
		$listRecipientMode->addToForm($formData);

		$this->assertTrue($listRecipientMode->isEnabled() == 1, " guardian model enabled");


		$this->assertEquals(3, count($formData), " number of form elements do not match");
		$mode = $formData[ListRecipientMode::RECIPIENT_MODE_ELEMENT];
		$this->assertTrue(count($mode) > 0, " recipient mode form element is not set");

		$this->assertEquals($selectedMode, $mode["value"], " recipient mode is not set to default");

		$categoriesField = $formData[ListRecipientMode::RECIPIENT_CATEGORIES_ELEMENT];
		$this->assertTrue(count($categoriesField) > 0, " recipientcategories form element is not set");


		$selectedCategories = $categoriesField["value"];
		$this->assertEquals(1, count($selectedCategories), " one category should be selected");
		$this->assertEquals($category1->id, $selectedCategories[0], " one category should be selected");

		$categories = $categoriesField["validators"][0]["values"];
		$this->assertEquals(1, count($categories), " expected one category");
		$this->assertEquals($category1->id, $categories[0], " expected one category");

		$this->assertEquals($helpStep, $categoriesField["helpstep"], " wrong help step");

	}


	public function test_addToFormDisabled() {
		global $queryRules;

		$listId = 77;
		$category1 = new stdClass();
		$category1->id = 88;
		$category1->name = "Primary";
		$category1->sequence = 0;
		$category1->profileId = "4";

		$category2 = new stdClass();
		$category2->id = 88;
		$category2->name = "Primary";
		$category2->sequence = 0;
		$category2->profileId = "4";

		$queryRules->add("/select guardianCategoryId from listguardiancategory where listid=$listId/", array(), array(array($category1->id)));

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
			->setConstructorArgs(array($apiClient))
			->setMethods(array())
			->getMock();

		$mockApi->expects($this->any())
			->method('getGuardianCategoryList')
			->will($this->returnValue(array($category1, $category2)));

		$helpStep = 3;
		$maxGuardians = 0;
		$selectedMode = PeopleList::$RECIPIENTMODE_MAP[2];

		$listRecipientMode = new ListRecipientMode ($mockApi, $helpStep, $maxGuardians, $listId, $selectedMode);
		$formData = array();
		$listRecipientMode->addToForm($formData);

		$this->assertTrue($listRecipientMode->isEnabled() == 0, " guardian model enabled");
		$this->assertEquals(0, count($formData), " should not insert form element");

	}

	public function test_addToFormDisabledNoScript() {
		global $queryRules;

		$listId = 77;
		$category1 = new stdClass();
		$category1->id = 88;
		$category1->name = "Primary";
		$category1->sequence = 0;
		$category1->profileId = "4";

		$category2 = new stdClass();
		$category2->id = 88;
		$category2->name = "Primary";
		$category2->sequence = 0;
		$category2->profileId = "4";

		$queryRules->add("/select guardianCategoryId from listguardiancategory where listid=$listId/", array(), array(array($category1->id)));

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
			->setConstructorArgs(array($apiClient))
			->setMethods(array())
			->getMock();

		$mockApi->expects($this->any())
			->method('getGuardianCategoryList')
			->will($this->returnValue(array($category1, $category2)));

		$helpStep = 3;
		$maxGuardians = 0;
		$selectedMode = PeopleList::$RECIPIENTMODE_MAP[2];

		$listRecipientMode = new ListRecipientMode ($mockApi, $helpStep, $maxGuardians, $listId, $selectedMode);
		$formData = array();
		$listRecipientMode->addToForm($formData);

		$this->assertTrue($listRecipientMode->isEnabled() == 0, " guardian model enabled");
		$this->assertEquals(0, count($formData), " should not insert form element");
		$this->assertEquals("", $listRecipientMode->addJavaScript('list'), " should not insert script");

	}

}

?>
