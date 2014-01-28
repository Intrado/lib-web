<?php

/**
 * FeedCategoryMappingTest.php - PHPUnit Test Class for FeedCategoryMapping Page
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
 * Test Class for FeedCategoryMapping Page
 *
 * What does it do?
 *  + It checks authorization and that a CMA application ID exists for the customer
 *  + It transfers GET parameters to the SESSION before redirecting back to itself
 *  + It shows an HTML form with a list of all available CMA feed categories, preselecting ones previously "mapped"
 *  + It deletes mapping records that are invalid or deselected from the database
 *  + It adds new mapping records for those that are selected and not already in the database
 *  + It returns to the editfeedcategory.php page after submitting changes
 */
class FeedCategoryMappingTest extends PHPUnit_Framework_TestCase {
	const USER_ID = 1;
	const ACCESS_ID = 3;
	const CMA_APPID = 555;
	const FEED_ID = 2020;

	var $formName = '';     // The name of our kona form

	private $testPage = null;

	private static $rulekey_drops = '';
	private static $rulekey_adds = '';

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

		// 4) SQL response: The customer setting for cmaappid
		$queryRules->add('/from setting where name/', array('_cmaappid'),
			array(
				array(
					self::CMA_APPID
				)
			)
		);

		// 5) SQL response: The customer setting for hasfeed is enabled too
		$queryRules->add('/from setting where name/', array('_hasfeed'),
			array(
				array(
					1
				)
			)
		);

                // 6) SQL response: Permissions for this user's profile
		$queryRules->add('/from permission where accessid/',
			array(
				array(
					1,
					self::ACCESS_ID,
					'managesystem',
					1
				)
			)
		);

		// 7) SQL response: Get the feedcategory record for the one we're editing
		$queryRules->add("/FROM `feedcategory` WHERE NOT `deleted` AND `id` =/", array(self::FEED_ID),
			array(
				array(
					'id' => self::FEED_ID,
					'name' => 'bogus feed name',
					'description' => 'bogus feed description',
					'deleted' => 0
				)
			)
		);

		// 8) API response: Get the list of CMA categories for this appId
		$cma_categories = array();
		for ($i = 0; $i < 10; $i += 1) {
			$cma_categories[] = (object) array('id' => $i, 'name' => 'School ' . $i );
		}

		$queryRules->add('|{"method":"GET","node":"\\\/1\\\/apps\\\/\\\/streams\\\/categories","data":null}|',
			array(
				array(
					'headers' => 'Content-type: application/json',
					'body' => json_encode($cma_categories),
					'code' => 200
				)
			)
		);

		// 9) SQL response: Get the list of CMA category ID's already mapped to this feed
		$queryRules->add('/SELECT `cmacategoryid` FROM `cmafeedcategory` WHERE `feedcategoryid`/',
			array(
				array(
					'cmacategoryid' => 5
				),
				array(
					'cmacategoryid' => 6
				),
				array(
					'cmacategoryid' => 888
				)
			)
		);

		// 10) SQL response: We need a hit count on deleting cmafeedcategory records
		self::$rulekey_drops = $queryRules->add('/DELETE FROM `cmafeedcategory` WHERE `feedcategoryid`/', Array(self::FEED_ID, 6, 888),
			true
		);

		// 11) SQL response: We need a hit cound on adding cmafeedcategory records
		self::$rulekey_adds = $queryRules->add('/INSERT INTO cmafeedcategory SET feedcategoryid/', Array(self::FEED_ID, 7),
			true
		);

		// Mock up a USER session
		require_once("{$konadir}/inc/common.inc.php");
		$USER = new User(self::USER_ID);
		$_SESSION['access'] = new Access($USER->accessid);

		require_once("{$konadir}/feedcategorymapping.php");
	}

	// before each test
	public function setUp() {
		global $USER, $HEADERS;

		// Stub out API access
		$apiClient = new ApiStub('http://localhost/api');
		$this->cmaApi = new CmaApiClient($apiClient);

		// Then go for launch!
		$this->testPage = new FeedCategoryMapping($this->cmaApi);

		// Grab the formName; we're going to need it!
		$this->formName = $this->testPage->formName;

		// Clear out any previously captured headers
		$HEADERS = array();
	}

	// It checks authorization and that a CMA application ID exists for the customer
	public function test_authorization() {
		$this->assertTrue($this->testPage->isAuthorized(), 'Authorization check failed!');
	}

	// It transfers GET parameters to the SESSION before redirecting back to itself
	public function test_getRedirect() {
		global $HEADERS;

		$empty = $session = array();
		$get = array('id' => self::FEED_ID);

		// Load up the form
		$this->testPage->initialize();

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() { return(false); });

		$this->testPage->beforeLoad($get, $empty, $empty, $session);

		unset_exit_overload();

		$this->assertTrue(isset($session['feedid']), 'Feed ID was not transferred to the session!');

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

	// It shows an HTML form with a list of all available CMA feed categories, preselecting ones previously "mapped"
	public function test_formPresentation() {

		$empty = array();
		$session = array('feedid' => self::FEED_ID);

		// Load up the form
		$this->testPage->initialize();
		$this->testPage->beforeLoad($empty, $empty, $empty, $session);
		$this->testPage->load();
		$this->testPage->afterLoad();
		$formhtml = $this->testPage->render();

		// There should be 10 CMA category checkboxen
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_cmacategories-1"), 'Missing the first CMA category');
		$this->assertTrue(false !== strpos($formhtml, "{$this->formName}_cmacategories-10"), 'Missing the last CMA category');

		// There should be 2 CMA categories pre-checked
		$this->assertTrue(false !== strpos($formhtml, 'value="5" checked'), 'Missing a check for the fifth CMA category');
		$this->assertTrue(false !== strpos($formhtml, 'value="6" checked'), 'Missing a check for the sixth CMA category');
	}
	
	// It deletes mapping records that are invalid or deselected from the database
	// It adds new mapping records for those that are selected and not already in the database
	// It returns to the editfeedcategory.php page after submitting changes
	public function test_formSubmission() {
		global $HEADERS, $queryRules;

		// Any call to exit/die will end up in this anonymous function now:
		set_exit_overload(function() { return(false); });

		// POST the edit form with burst id = 1 so that we don't have to upload a file for this test
		$_POST = $_REQUEST = array(
			'submit' => 'mapfeed',
			'form' => $this->formName,
			"{$this->formName}_cmacategories" => array(5, 7)
		);

		$_SESSION = array('feedid' => self::FEED_ID);

		$this->testPage->initialize();
		$this->testPage->beforeLoad($_POST, $_POST, $_REQUEST, $_SESSION);
		$this->testPage->load();

		// Extract the correct serialnum out of the form and stick it into the POST!
		$_POST["{$this->formName}-formsnum"] = $_REQUEST["{$this->formName}-formsnum"] = $this->testPage->form->serialnum;

		$this->testPage->afterLoad();

		unset_exit_overload();

		// Make sure the DELETE operation occurred for the ID's that are stored, but no longer selected
		$this->assertEquals(1, $queryRules->getHits(self::$rulekey_drops), 'There should have been a query to delete the unselected category IDs');

		// Make sure the INSERT operation occurred for the ID'c that are not stored, but are selected for addition
		$this->assertEquals(1, $queryRules->getHits(self::$rulekey_adds), 'There should have been a query to insert the selected category IDs that are not already mapped');

		// There should be a redirect to the editfeedcategory.php page
		$this->assertTrue(in_array('Location: editfeedcategory.php', $HEADERS), 'There should have been a redirect to editfeedcategory.php');
	}
}

?>
