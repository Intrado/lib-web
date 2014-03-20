<?

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
//require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/messagelink/messagelinkcontroller.obj.php");
require_once("{$konadir}/messagelink/messagelinkitemview.obj.php");
require_once("{$konadir}/messagelink/messagelinkmodel.obj.php");

class MessageLinkControllerTest extends PHPUnit_Framework_TestCase {

	var $controller;
	var $model;
	var $request;
	var $protocol;
	var $transport;

	public function setup() {

		$this->request = array(
			"s" => "1234",
			"mal" => "5678"
		);

		$this->protocol = $this->getMockBuilder('protocol')
			->setMockClassName('TProtocol')
			->setMethods(array())
			->getMock();
		$this->transport = $this->getMockBuilder('transport')
			->setMockClassName('TTransport')
			->setMethods(array('open', 'close'))
			->getMock();

		$this->model = $this->getMockBuilder('MessageLinkModel')
			->setConstructorArgs(array($this->protocol, $this->transport))
			->setMethods(array('initialize'))
			->getMock();

		// define $controller mock object;
		// all methods will retain their existing behavior
		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(null)
			->getMock();
	}

	public function teardown() {
		unset($this->controller);
		unset($this->protocol);
		unset($this->transport);
		unset($this->model);
	}

	/**
	 * It should set $this->request to the request arg passed in constructor, if it exists
	 */
	public function test_initialization() {

		$this->assertEquals($this->request, $this->controller->request);
		$this->assertEquals($this->protocol, $this->controller->protocol);
		$this->assertEquals($this->transport, $this->controller->transport);

		/*************** test with empty request *****************/
		$this->request = array();
		$this->protocol = null;
		$this->transport = null;

		// define $controller mock object;
		// all methods will retain their existing behavior
		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(null)
			->getMock();

		// contoller->request will not be set, if request is empty array
		$this->assertFalse(isset($this->controller->request));
		$this->assertFalse(isset($this->controller->protocol));
		$this->assertFalse(isset($this->controller->transport));
	}

	public function test_getItemView() {
		$template = "foo.tpl.php";
		$this->model->attributes = (object) array("foo" => "bar");
		$this->controller->model = $this->model;

		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array($template, (array) $this->controller->model->getAttributes()))
			->setMethods(null)
			->getMock();

		// getItemView returns new instance of MessageLinkItemView, i.e. our mockItemView above
		$view = $this->controller->getItemView($template);

		$this->assertInstanceOf('MessageLinkItemView', $view);
		$this->assertInstanceOf('MessageLinkItemView', $mockItemView);
		$this->assertEquals($view->template, $mockItemView->template);
	}

	/**
	 * It should call $this->view->render() if $this->view exists
	 */
	public function test_renderView() {

		$this->model->attributes = array('foo' => 'bar');

		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("template.tpl.php"))
			->setMethods(array('render'))
			->getMock();

		$mockItemView->expects($this->once())
			->method('render')
			->with();

		$this->controller->view = $mockItemView;
		$this->controller->renderView();

	}

	/**
 * It should return the SDD view based on request args (s + mal codes present)
 */
	public function test_getAppView_SDD() {

		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array('getItemView'))
			->getMock();

		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedEmailMessage" => (object) array(
					"attachmentLookup" => array(
						"5678" => (object) array(
							"filename" => "attachment.pdf",
							"contentType" => "text/pdf",
							"size" => null,
							"isPasswordProtected" => true,
							"code" => "5678"
						)
					)
				)
			)
		);

		// manually set controller's model since we are bypassing call to initView($model), which sets the controller's model
		$this->controller->model = $this->model;

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("sddmessagelink.tpl.php", (array) $this->controller->model->getAttributes()))
			->setMethods(null)
			->getMock();

//		 define expectation and stub for getItemView
		$this->controller->expects($this->once())
			->method('getItemView')
			->will($this->returnValue($mockItemView));

		$view = $this->controller->getAppView('SDD');

		$this->assertEquals($mockItemView, $view);

		// verify that model->attributes contains expected attributes
		$this->assertEquals("Secure Document Delivery from Test Customer - Powered by SchoolMessenger", $this->controller->model->attributes->pageTitle);
		$this->assertEquals($this->controller->model->attributes->messageInfo->selectedEmailMessage, $this->controller->model->attributes->emailMessage);
		$this->assertEquals($this->controller->model->attributes->emailMessage->attachmentLookup[$this->controller->request['mal']], $this->controller->model->attributes->attachmentInfo);

	}

	/**
	 * It should return the SDD view based on request args (s + mal codes present)
	 */
	public function test_getAppView_ML() {

		$this->request = array(
			"s" => "1234"
		);

		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array('getItemView'))
			->getMock();

		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedPhoneMessage" => (object) array(
					"nummessageparts" => "3")));

		// manually set controller's model since we are bypassing call to initView($model), which sets the controller's model
		$this->controller->model = $this->model;

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("sddmessagelink.tpl.php", (array) $this->controller->model->getAttributes()))
			->setMethods(null)
			->getMock();

		// define expectation and stub for getItemView
		$this->controller->expects($this->once())
			->method('getItemView')
			->will($this->returnValue($mockItemView));


		$view = $this->controller->getAppView('ML');

		$this->assertEquals($mockItemView, $view);

		// verify that model->attributes contains expected attribute
		$this->assertEquals("Voice Message Delivery from Test Customer - Powered by SchoolMessenger", $this->controller->model->attributes->pageTitle);


	}

	/**
	 * It should return the error view if s & mal codes provided (implies SDD) but no
	 * selectedEmailMessage object is present in the data
	 */
	public function test_getAppView_Error_SDD_withNoSelectedEmailMessage() {

		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array('getItemView'))
			->getMock();

		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedEmailMessage" => null));

		// manually set controller's model since we are bypassing call to initView($model), which sets the controller's model
		$this->controller->model = $this->model;

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("error.tpl.php", (array) $this->controller->model->getAttributes()))
			->setMethods(null)
			->getMock();

		// define expectation and stub for getItemView
		$this->controller->expects($this->once())
			->method('getItemView')
			->will($this->returnValue($mockItemView));


		$view = $this->controller->getAppView('SDD');

		$this->assertEquals($mockItemView, $view);

	}

	public function test_getAppView_Error_SDD_withNoAttachmentLookupObject() {

		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array('getItemView'))
			->getMock();

		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedEmailMessage" => (object) array(
					"attachmentLookup" => null)));

		// manually set controller's model since we are bypassing call to initView($model), which sets the controller's model
		$this->controller->model = $this->model;

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("error.tpl.php", (array) $this->controller->model->getAttributes()))
			->setMethods(null)
			->getMock();

		// define expectation and stub for getItemView
		$this->controller->expects($this->once())
			->method('getItemView')
			->will($this->returnValue($mockItemView));


		$view = $this->controller->getAppView('SDD');

		$this->assertEquals($mockItemView, $view);

	}


	/**
	 * It should return the error view if s code provided (implies Voice Message Delivery) but no
	 * selectedPhoneMessage object is present in the data
	 */
	public function test_getAppView_Error_ML_withNoSelectedPhoneMessage() {

		$this->request = array(
			"s" => "1234"
		);

		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array('getItemView'))
			->getMock();

		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedPhoneMessage" => null));

		// manually set controller's model since we are bypassing call to initView($model), which sets the controller's model
		$this->controller->model = $this->model;

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("error.tpl.php", (array) $this->controller->model->getAttributes()))
			->setMethods(null)
			->getMock();

		// define expectation and stub for getItemView
		$this->controller->expects($this->once())
			->method('getItemView')
			->will($this->returnValue($mockItemView));


		$view = $this->controller->getAppView('ML');

		$this->assertEquals($mockItemView, $view);

	}

	/**
	 * It should result in a ML view being instantiated and assigned to the controller's $this->view
	 */
	public function test_initView_ML() {

		$this->request = array(
			"s" => "1234"
		);

		// define mock object for MessageLinkModel
		$this->model = $this->getMockBuilder('MessageLinkModel')
			->setConstructorArgs(array($this->protocol, $this->transport))
			->setMethods(array('initialize', 'fetchRequestCodeData'))
			->getMock();

		// dummy model data response
		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedPhoneMessage" => (object) array(
					"nummessageparts" => "3")));

		// create a mock controller and stub all methods in logic path involved in
		// creating the ML view
		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array(
				'getAppView',
				'getErrorMessageView'
			))->getMock();

		$this->model->expects($this->once())
			->method('fetchRequestCodeData')
			->with($this->request['s'])
			->will($this->returnValue($this->model->attributes));

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("sddmessagelink.tpl.php", (array) $this->model->getAttributes()))
			->setMethods(null)
			->getMock();

		$this->controller->expects($this->once())
			->method('getAppView')
			->with('ML')
			->will($this->returnValue($mockItemView));


		// define expectation and stub for getItemView
		$this->controller->expects($this->never())
			->method('getErrorMessageView');

		$this->controller->initView($this->model);

		$this->assertEquals($mockItemView, $this->controller->view);
		$this->assertEquals($this->request['s'], $this->controller->model->attributes->messageLinkCode);

	}

	/**
	 * It should result in a SDD view being instantiated and assigned to the controller's $this->view
	 */
	public function test_initView_SDD() {

		// define mock object for MessageLinkModel
		$this->model = $this->getMockBuilder('MessageLinkModel')
			->setConstructorArgs(array($this->protocol, $this->transport))
			->setMethods(array('initialize', 'fetchRequestCodeData'))
			->getMock();

		// dummy model data response
		$this->model->attributes = (object) array(
			"customerdisplayname" => "Test Customer",
			"productName" => "SchoolMessenger",
			"messageInfo" => (object) array(
				"selectedEmailMessage" => (object) array(
					"attachmentLookup" => array(
						"5678" => (object) array(
							"filename" => "attachment.pdf",
							"contentType" => "text/pdf",
							"size" => null,
							"isPasswordProtected" => true,
							"code" => "5678"
						)
					)
				)
			)
		);

		// create a mock controller and stub all methods in logic path involved in
		// creating the ML view
		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array(
			'getAppView',
			'getErrorMessageView'
		))->getMock();

		$this->model->expects($this->once())
			->method('fetchRequestCodeData')
			->with($this->request['s'])
			->will($this->returnValue($this->model->attributes));

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("sddmessagelink.tpl.php", (array) $this->model->getAttributes()))
			->setMethods(null)
			->getMock();

		$this->controller->expects($this->once())
			->method('getAppView')
			->with('SDD')
			->will($this->returnValue($mockItemView));

		// define expectation and stub for getItemView
		$this->controller->expects($this->never())
			->method('getErrorMessageView');

		$this->controller->initView($this->model);

		$this->assertEquals($mockItemView, $this->controller->view);
		$this->assertEquals($this->request['s'], $this->controller->model->attributes->messageLinkCode);
		$this->assertEquals($this->request['mal'], $this->controller->model->attributes->attachmentLinkCode);

	}

	/**
	 * It should result in an Error view being instantiated and assigned to the controller's $this->view
	 */
	public function test_initView_Error_NoModel() {

		$this->model = null;

		$errorAttributes = array(
			"pageTitle" => "SchoolMessenger",
			"productName" => "SchoolMessenger",
			"errorMessage" => "An error occurred while trying to retrieve your message. Please try again.",
			'customerdisplayname' => ''
		);

		// create a mock controller and stub all methods in logic path involved in
		// creating the ML view
		$this->controller = $this->getMockBuilder('MessageLinkController')
			->setConstructorArgs(array($this->request, $this->protocol, $this->transport))
			->setMethods(array(
			'getItemView',
			'logError'
		))->getMock();

		// define mock itemVIew object for use in return value of getAppView(..)
		$mockItemView = $this->getMockBuilder('MessageLinkItemView')
			->setConstructorArgs(array("error.tpl.php", $errorAttributes))
			->setMethods(null)
			->getMock();

		$this->controller->expects($this->once())
			->method('getItemView')
			->with("error.tpl.php", $errorAttributes);

		// null/undefined model arg passed in
		$this->controller->initView();

	}

}

?>