<?

/**
 * EasyCallTest.php - PHPUnit test for class EasyCall
 *
 * @package unittests
 * @author Max Tilford, <mtilford@schoolmessenger.com>
 */

require_once(realpath(dirname(__FILE__) . '/../../../obj/dmapi/EasyCall.obj.php'));

class FakeSpecialTask {
	public $updateCalled, $data;

	function FakeSpecialTask () {
		$this->data = array();
	}

	public function update() {
		$this->updateCalled = true;
	}

	public function setData($field, $value) {
		$this->data[$field] = $value;
	}

	public function getData($field) {
		if (isset($this->data[$field])) {
			return $this->data[$field];
		} else {
			return "";
		}
	}
}

class EasyCallTest extends PHPUnit_Framework_TestCase {
	private $specialtask, $easycall;

	function setUp() {
		$this->specialtask = new FakeSpecialTask();
		$this->easycall = new EasyCall($this->specialtask);
	}

	public function test_startCall() {
		$this->easycall->startCall();
		$this->assertEquals($this->specialtask->getData("progress"), "Calling");
		$this->assertTrue($this->specialtask->updateCalled);
	}

	public function test_endCall() {
		$this->easycall->endCall();
		$this->assertEquals($this->specialtask->getData("progress"), "Call Ended");
		$this->assertEquals($this->specialtask->getData("error"), "callended");
		$this->assertEquals($this->specialtask->status, "done");
		$this->assertTrue($this->specialtask->updateCalled);
	}

	public function test_hasExtension() {
		$this->assertFalse($this->easycall->hasExtension());
		$this->specialtask->setData('phoneextension', "1234");
		$this->assertTrue($this->easycall->hasExtension());
	}

}
?>