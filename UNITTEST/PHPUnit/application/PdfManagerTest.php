<? 

/**
 * TipsTest.php - PHPUnit test for class Tips
 *
 * @package unittests
 * @author Justin Burns, <jburns@schoolmessenger.com>
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));

require_once('../../../ifc/Page.ifc.php');
require_once('../../../obj/PageBase.obj.php');
require_once('../../../obj/APIClient.obj.php');
require_once('../../../obj/BurstAPIClient.obj.php');
// ----------------------------------------------------------------------------

require_once("{$konadir}/pdfmanager.php");

class PdfManagerTest extends PHPUnit_Framework_TestCase {
	
	protected $pageBase;
	protected $pdfManager;
	protected $apiClientStub;

	public function setup() {
		global $USER;
		$USER = new StdClass;
		$USER->id = 1;
		
		$_SERVER = array(
			'REQUEST_URI' => '/custname/pdfmanager.php',
			'SERVER_NAME' => 'localhost',
			'SCRIPT_NAME' => '/custname/pdfmanager.php'
		);
		$_COOKIE = array('custname_session' => 'cookie123');

		$this->apiClientStub = $this->getMockBuilder('BurstAPIClient')
							  ->setConstructorArgs(array($_SERVER['SERVER_NAME'], 'custname', $USER->id, $_COOKIE['custname_session']))
							  ->getMock();
		
		$this->apiClientStub->expects($this->any())
					  ->method('getAPIURL')
					  ->will($this->returnValue('https://localhost/custname/api/2/users/1/bursts'));


		$this->apiClientStub->expects($this->any())
					  ->method('getBurstList')
					  ->will($this->returnValue(
					  (object) array(
						'bursts' => array(
								(object) array(
									'id' => 14,
									'name' => 'PDF Workflow - single page app - v2',
									'filename' => 'PDF Workflow - Non-wizard Single Page App option.pdf',
									'bytes' => 330542,
									'status' => 'new',
									'contentId' => 552,
									'deleted' => 0,
									'ownerUser' => (object) array(
										'id' => 8629,
										'login' => 'admin',
										'firstName' => 'Admin',
										'lastName' => 'McAdmin',
										'displayName' => 'Admin McAdmin',
										'enabled' => 1,
										'phone' => 8312393132,
										'email' => 'admin@schoolmessenger.com'
									),
									'uploadTimestampMs' => 1387570964701,
									'burstTemplateId' => 1
								),
								(object) array(
									'id' => 15,
									'name' => 'PDF Workflow - single page app - v3',
									'filename' => 'PDF Workflow - v3 - Non-wizard Single Page App option.pdf',
									'bytes' => 6057932,
									'status' => 'new',
									'contentId' => 553,
									'deleted' => 0,
									'ownerUser' => (object) array(
										'id' => 8629,
										'login' => 'admin',
										'firstName' => 'Admin',
										'lastName' => 'McAdmin',
										'displayName' => 'Admin McAdmin',
										'enabled' => 1,
										'phone' => 8312393132,
										'email' => 'admin@schoolmessenger.com'
									),
									'uploadTimestampMs' => 1387571964701,
									'burstTemplateId' => 1
								),
						),
						'paging' => (object) array(
							'total' => 1,
							'limit' => 100
						)
					  )));

		$this->apiClientStub->expects($this->any())
					  ->method('deleteBurst')
					  ->will($this->returnValue(true));

	}

	public function tearDown() {

	}

	public function test_initialize() {

		// creating a new instance of PdfManager calls initialize() in it's constructor,
		// so there's no need to call it after instantiation below, ex. no need for $pdfManager->initialize().
		$pdfManager = new PdfManager();

		$this->assertEquals($pdfManager->options['title'], 'PDF Manager');
		$this->assertEquals($pdfManager->options['page'], 'notifications:pdfmanager');

		// custName gets set by call to global function in common.inc: customerUrlComponent()
		$this->assertEquals($pdfManager->customerURLComponent, 'custname');

	}

	public function test_beforeLoad_NoDelete() {
		global $get, $post;

		$get['ajax'] = true;
		$get['pagestart'] = 123;

		$pdfManagerMock = $this->getMock('PdfManager', array('initialize', 'getBurstAPIClient'));
		$pdfManagerMock->expects($this->any())
					   ->method('getBurstAPIClient')
					   ->will($this->returnValue($this->apiClientStub));

		// deleteAjaxResponse() should not be called
		$pdfManagerMock->expects($this->never())
					   ->method('deleteAjaxResponse');

		$pdfManagerMock->beforeLoad($get, $post);

		$this->assertEquals($pdfManagerMock->isAjaxRequest, true);
		$this->assertEquals($pdfManagerMock->pagingStart, 123);
		
		// assert that burstsURL is set as expected 
		$this->assertEquals($pdfManagerMock->burstsURL, 'https://localhost/custname/api/2/users/1/bursts');
	}

	public function test_beforeLoad_WithDelete() {
		global $get, $post;

		$post['delete'] = true;
		$post['id'] = 234;

		// mock deleteAjaxResponse() method so we don't want call the real method
		$pdfManagerMock = $this->getMock('PdfManager', array('initialize', 'getBurstAPIClient', 'deleteAjaxResponse'));
		$pdfManagerMock->expects($this->any())
					   ->method('getBurstAPIClient')
					   ->will($this->returnValue($this->apiClientStub));

		// define expectation when calling the deleteAjaxResponse() method with the $post[id] data 
		$pdfManagerMock->expects($this->once())
					   ->method('deleteAjaxResponse')
					   ->with($post['id'])
					   ->will($this->returnValue(true));

		$pdfManagerMock->expects($this->never())
					   ->method('setPagingStart');

		// since initialize() is mocked, $this->customerURLComponent doesn't get set for us, so set it manually to simulate
		$pdfManagerMock->customerURLComponent = 'custname';
		// call method under test; above expectations will be evauluated as well as the following assertion
		$pdfManagerMock->beforeLoad($get, $post);

		// isAjaxRequest should not get set, 
		$this->assertEquals($pdfManagerMock->isAjaxRequest, false);
		$this->assertEquals($pdfManagerMock->burstsURL, null);
	}

	public function test_load() {
		$pdfManagerMock = $this->getMock('PdfManager', array('initialize', 'getAuthOrgKeys'));
		$pdfManagerMock->burstAPIClient = $this->apiClientStub;
		
		$pdfManagerMock->expects($this->any())
					   ->method('getAuthOrgKeys')
					   ->will($this->returnValue(array(
					   		'OrgName 1',
					   		'OrgName 2',
					   		'OrgName 3'
					   	)));

		$pdfManagerMock->isAjaxRequest = true;
		$pdfManagerMock->load();

		$this->assertEquals($pdfManagerMock->authOrgList[0], 'OrgName 1');
		$this->assertEquals($pdfManagerMock->authOrgList[1], 'OrgName 2');

		$this->assertEquals($pdfManagerMock->feedData, $pdfManagerMock->feedResponse->bursts);

		$burstItem = $pdfManagerMock->feedResponse->bursts[0];
		$this->assertEquals($burstItem->name, 'PDF Workflow - single page app - v2');
		$this->assertEquals($burstItem->filename, 'PDF Workflow - Non-wizard Single Page App option.pdf');
		$this->assertEquals($burstItem->bytes, 330542);
		$this->assertEquals($burstItem->contentId, 552);
		$this->assertEquals($burstItem->uploadTimestampMs, 1387570964701);
		$this->assertEquals($burstItem->burstTemplateId, 1);
	}

	public function test_afterLoad() {
		$pdfManagerMock = $this->getMock('PdfManager', array('initialize', 'setDisplayPagingDetails', 'burstsAjaxResponse'));
		$pdfManagerMock->expects($this->once())
					   ->method('setDisplayPagingDetails')
					   ->with();
		$pdfManagerMock->expects($this->once())
					   ->method('burstsAjaxResponse')
					   ->with();

		$pdfManagerMock->isAjaxRequest = true;
		$pdfManagerMock->afterLoad();
	}

	public function test_setDisplayPagingDetails_NoFeedData() {
		$pdfManagerMock = $this->getMock('PdfManager', array('initialize'));
		$pdfManagerMock->feedData = array();

		$pdfManagerMock->setDisplayPagingDetails();

		$this->assertEquals($pdfManagerMock->total, 0);
		$this->assertEquals($pdfManagerMock->numPages, 0);
		$this->assertEquals($pdfManagerMock->curPage, 1);
		$this->assertEquals($pdfManagerMock->displayStart, 0);
		$this->assertEquals($pdfManagerMock->displayEnd, 0);
	}

	public function test_setDisplayPagingDetails_WithFeedData() {
		$pdfManagerMock = $this->getMock('PdfManager', array('initialize'));
		$feedResponse = $this->apiClientStub->getBurstList();
		$pdfManagerMock->feedData = $feedResponse->bursts;

		$pdfManagerMock->setDisplayPagingDetails();

		$this->assertEquals($pdfManagerMock->total, 2);
		$this->assertEquals($pdfManagerMock->numPages, 1);
		$this->assertEquals($pdfManagerMock->curPage, 1);
		$this->assertEquals($pdfManagerMock->displayStart, 1);
		$this->assertEquals($pdfManagerMock->displayEnd, 2);
	}

}

?>