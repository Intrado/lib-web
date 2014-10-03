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
class LinkedAccountManagerTest extends PHPUnit_Framework_TestCase {

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

		require_once("{$konadir}/inc/common.inc.php");
		require_once("{$konadir}/obj/LinkedAccountManager.obj.php");
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

	public function test_getAccountAssociations() {
		global $queryRules;
		$personId = 888;
		$queryRules->add('/portaluserid from user where id/', array($personId), array(array(1), array(2), array(3)));
		$manager = $this->getMockBuilder('LinkedAccountManager')
				->setMethods(array('getAccountDetails'))
				->getMock();

		$manager->expects($this->any())
				->method('getAccountDetails')
				->will($this->returnCallback(function($ids) {
							$associations = array();
							foreach ($ids as $id) {
								$associations[$id] = array("portaluser.firstname" => "John" . $id, "portaluser.lastname" => "Doe" . $id, "portaluser.username" => "john.doe@somemail.com", "portaluser.lastlogin" => time());
							}
							return $associations;
						}
		));

		$results = $manager->getAssociatedAccounts($personId);

		$this->assertEquals(3, count($results), " Number of associations do not match");
		foreach ($results as $id => $assoc) {
			$this->assertEquals("John" . $id, $assoc['portaluser.firstname'], "Expected \"John" . $id . "\" but got \"" . $assoc['portaluser.firstname'] . "\"");
			$this->assertEquals("Doe" . $id, $assoc['portaluser.lastname'], "Expected \"Doe" . $id . "\" but got \"" . $assoc['portaluser.lastname'] . "\"");
		}
	}

}

?>
