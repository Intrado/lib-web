<?

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once("{$konadir}/messagelink/messagelinkitemview.obj.php");

class MessageLinkItemViewTest extends PHPUnit_Framework_TestCase {

	var $view;
	var $template = "sddmessagelink.tpl.php";
	var $modelData;

	public function setup() {

		$this->modelData = array(
			"templateVar1" => "Test Data 1",
		);

	}

	public function teardown() {
		unset($this->view);
	}

	/**
	 * test if $view->template and $view->vars get set as expected (from args) upon instantiation
	 */
	public function test_initialization() {
		global $konadir;

		$this->view = new MessageLinkItemView($this->template, $this->modelData);

		$this->assertEquals("{$konadir}/messagelink/templates/{$this->template}", $this->view->template);
		$this->assertEquals($this->modelData, $this->view->vars);
	}

	/**
	 * test render() with existing file;
	 * expected: includeTemplate($template) should be called;
	 */
	public function test_render_withValidTemplate() {
		global $konadir;

		// define $view mock object; stub includeTemplate();
		// all other methods will retain their existing behavior
		$this->view = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array($this->template, $this->modelData))
			->setMethods(array('includeTemplate'))
			->getMock();

		// defines expectation that method is called once with valid/existing template
		$this->view->expects($this->once())
			->method('includeTemplate')
			->with("{$konadir}/messagelink/templates/{$this->template}")
			->will($this->returnValue(true));

		$this->view->render();

	}

	/**
	 * test render() with non-existing template file;
	 * expected:
	 * 1) this->includeTemplate($template) should not be called
	 * 2) this->throwException() should be called
	 */
	public function test_render_withoutValidTemplate() {
		global $konadir;

		$invalidTemplate = "non-existant-template.tpl.php";

		// define $view mock object; stub throwException();
		// all other methods will retain their existing behavior
		$this->view = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array($invalidTemplate, $this->modelData))
			->setMethods(array('throwException'))
			->getMock();

		// defines expectation that method is never called
		$this->view->expects($this->never())
			->method('includeTemplate');

		// defines expectation that method is called once with invalid/non-existing template
		$this->view->expects($this->once())
			->method('throwException')
			->with("Template file: {$konadir}/messagelink/templates/{$invalidTemplate} does not exist.");

		$this->view->render();

	}

}

?>