<?php
/**
 * TopicDataManagerTest.php - PHPUnit Test Class for obj/TopicDataManager.obj.php
 *
 * @package unittests
 * @version 1.0
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------
require_once("{$konadir}/obj/TopicDataManager.obj.php");


class OrganizationTopic { // Mock Organization Topic class (used by TopicDataManager)

	public $id, $organizationid, $topicid, $createCalled = false;

	public static $instances = array();

	function OrganizationTopic() {
		self::$instances[] = $this;
	}

	public function create() {
		$this->id = 1434;
		$this->createCalled = true;
	}
}

class FakeTopic {

	public $name, $id, $createCalled = false, $updateCalled = false;

	function FakeTopic() {
	}

	public function create() {
		$this->id = 123;
		$this->createCalled = true;
	}

	public function update() {
		$this->updateCalled = true;
	}

}


class TopicDataManagerTest extends PHPUnit_Framework_TestCase {
	var $tdm;

	public function setUp() {
		$this->tdm = $this->getMock('TopicDataManager', array('rootOrganizationId'));
	}
	/*
	public function test_rootOrganizationId() {
		// needs database access to test
		$this->markTestIncomplete();
	}

	public function test_topicsInfo() {
		// needs database access to test
		$this->markTestIncomplete();
	}

	public function test_deleteTopic() {
		// needs database access to test
		$this->markTestIncomplete();
	}
	*/

	public function test_updateTopicName() {
		$fakeTopic = new FakeTopic();
		$this->tdm->updateTopicName($fakeTopic, "bar");
		$this->assertEquals($fakeTopic->name, "bar");
		$this->assertTrue($fakeTopic->updateCalled);
	}

	public function test_createTopic() {
		$fakeTopic = new FakeTopic();
		$this->tdm->createTopic($fakeTopic, "foo");

		$this->assertEquals($fakeTopic->name, "foo");
		$this->assertTrue($fakeTopic->createCalled);
		$this->assertTrue(count(OrganizationTopic::$instances) > 0);
		$this->assertTrue(OrganizationTopic::$instances[0]->createCalled);

	}

}

?>
