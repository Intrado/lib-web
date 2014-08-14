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
class GuardianCategoryAssociationTest extends PHPUnit_Framework_TestCase {

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


		// Mock up a USER session
		require_once("{$konadir}/inc/common.inc.php");
		$USER = new User(self::USER_ID);
		$_SESSION['access'] = new Access($USER->accessid);

		require_once("{$konadir}/guardiancategoryassociation.php");
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

		$page = new GuardianCategoryAssociation($mockApi);

		$this->assertTrue($page->isAuthorized(), 'The user should be authorized to access this page');
	}

	public function test_renderAssociationsWithDefaults() {
		$category = new stdClass();
		$category->id = 1;
		$category->name = "existing name";
		$category->sequence = 0;
		$category->profileId = "4";

		$paging = new stdClass();
		$paging->total = 120;
		$paging->limit = 100;
		$associations = new stdClass();
		$associations->paging = $paging;
		$associations->associations = array();
		for ($i = 0; $i < 100; $i++) {
			$a = new stdClass();
			$a->personId = $i;
			$a->gkey = "G-" . $i;
			$a->firstName = "First Name-" . $i;
			$a->lastName = "Last Name-" . $i;
			$associations->associations[] = $a;
		}

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();

		$mockApi->expects($this->any())
				->method('getGuardianCategory')
				->will($this->returnValue($category));

		$mockApi->expects($this->any())
				->method('getGuardianCategoryAssoications')
				->will($this->returnValue($associations));


		$page = new GuardianCategoryAssociation($mockApi);

		$_SESSION['categoryid'] = $category->id;
		$empty = array();
		$page->beforeLoad($empty, $empty, $empty, $_SESSION);

		$page->load();
		$page->afterLoad();


		$this->assertEquals($paging->total, $page->total);
		$this->assertEquals(2, $page->numPages);
		$this->assertEquals(1, $page->curPage);
		$this->assertEquals(0, $page->displayStart);
		$this->assertEquals($paging->limit, $page->displayEnd);

		$assocs = $page->guardianAssocations;

		$this->assertEquals($paging->limit, count($assocs), " number of associations do not match");
		for ($i = 0; $i < count($assocs); $i++) {
			$expected = "First Name-" . $i;
			$returned = $assocs[$i]['firstname'];
			$this->assertEquals($expected, $returned, "Expected \"First Name-" . $i . "\" but got \"" . $assocs[$i]['firstname'] . "\"");
		}
	}

	public function test_renderAssociationsWithNoAssociations() {
		$category = new stdClass();
		$category->id = 1;
		$category->name = "existing name";
		$category->sequence = 0;
		$category->profileId = "4";

		$paging = new stdClass();
		$paging->total = 0;
		$paging->limit = 100;
		$associations = new stdClass();
		$associations->paging = $paging;
		$associations->associations = array();

		$apiClient = new ApiStub('http://localhost/api');
		$mockApi = $this->getMockBuilder('CommsuiteApiClient')
				->setConstructorArgs(array($apiClient))
				->setMethods(array())
				->getMock();

		$mockApi->expects($this->any())
				->method('getGuardianCategory')
				->will($this->returnValue($category));

		$mockApi->expects($this->any())
				->method('getGuardianCategoryAssoications')
				->will($this->returnValue($associations));


		$page = new GuardianCategoryAssociation($mockApi);

		$_SESSION['categoryid'] = $category->id;
		$empty = array();
		$page->beforeLoad($empty, $empty, $empty, $_SESSION);

		$page->load();
		$page->afterLoad();

		$this->assertEquals($paging->total, $page->total);
		$this->assertEquals(0, $page->numPages);
		$this->assertEquals(1, $page->curPage);
		$this->assertEquals(0, $page->displayStart);
		$this->assertEquals(0, $page->displayEnd);
		$assocs = $page->guardianAssociations;
		$this->assertEquals(0, count($assocs), " number of associations do not match");
	}

}

?>
