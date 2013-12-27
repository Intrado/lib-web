<? 

/**
 * PdfManagerTest.php - PHPUnit test for PdfManager class
 *
 * @package unittests
 * @author Justin Burns, <jburns@schoolmessenger.com>
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));

require_once('../../../ifc/Page.ifc.php');
require_once('../../../obj/PageBase.obj.php');
// ----------------------------------------------------------------------------

require_once("{$konadir}/pdfmanager.php");

// Simple test user class used to set $USER obj
class TestUser {
	var $id;

	public function __construct($id) {
		$this->id = $id;
	}

	// mock needed for action_link calls (html.inc.php) in PdfManager->getBurstListItem()
	public function getSetting() {
		return true;
	}
}

class PdfManagerTest extends PHPUnit_Framework_TestCase {
	
	private $pageBase;
	private $pdfManager;
	private $apiClient;

	public function setup() {
		global $USER, $csApi;

		$USER = new TestUser(1);

		$_SERVER = array(
			'SERVER_NAME' => 'localhost',
			'SCRIPT_NAME' => '/custname/pdfmanager.php'
		);
		$_COOKIE = array('custname_session' => 'cookie123');

		$this->apiClient = new ApiClient(
			$_SERVER['SERVER_NAME'],
			'custname',
			$USER->id,
			$_COOKIE['custname_session']
		);

		// $csApi = new CommsuiteApiClient($this->apiClient);

		$csApi = $this->getMockBuilder('CommsuiteApiClient')
					  ->setConstructorArgs(array($this->apiClient))
					  ->getMock();
		
		$csApi->expects($this->any())
			  ->method('getBurstApiUrl')
			  ->will($this->returnValue('https://localhost/custname/api/2/users/1/bursts'));


		$csApi->expects($this->any())
			  ->method('getBurstList')
			  ->will($this->returnValue(
				  (object) array(
					'bursts' => array(
							(object) array(
								'id' => 14,
								'name' => 'PDF Workflow - single page app - v2',
								'filename' => 'PDF Workflow - Non-wizard Single Page App option.pdf',
								'size' => 330542,
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
								'size' => 6057932,
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

		$csApi->expects($this->any())
			  ->method('deleteBurst')
			  ->will($this->returnValue(true));

		$this->pdfManager = new PdfManager($csApi);

	}

	public function tearDown() {
		$this->pdfManager = null;
	}

	public function test_initialize() {
		global $csApi;

		// creating a new instance of PdfManager calls initialize() in it's constructor,
		// so there's no need to call it after instantiation below, ex. no need for $this->pdfManager->initialize().

		$this->assertEquals($this->pdfManager->options['title'], 'PDF Manager');
		$this->assertEquals($this->pdfManager->options['page'], 'notifications:pdfmanager');

		// custName gets set by call to global function in common.inc: customerUrlComponent()
		$this->assertEquals($this->pdfManager->customerURLComponent, 'custname');

		// assert that burstsURL is set as expected ()
		$this->assertEquals('https://localhost/custname/api/2/users/1/bursts', $this->pdfManager->burstsURL);


	}

	public function test_beforeLoad_NoDelete() {
		global $get, $post, $csApi; 

		$get['ajax'] = true;
		$get['pagestart'] = 123;

		$this->pdfManager->beforeLoad($get, $post);

		$this->assertEquals($this->pdfManager->isAjaxRequest, true);
		$this->assertEquals($this->pdfManager->pagingStart, 123);

	}

	public function test_beforeLoad_WithDelete() {
		global $get, $post, $csApi;

		$post['delete'] = true;
		$post['id'] = 234;

		// mock deleteAjaxResponse() method only, so we don't call the real method;
		// the behaviour of the other methods is not changed
		// $this->pdfManagerMock = $this->getMock('PdfManager', array('deleteAjaxResponse'));

		$this->pdfManagerMock = $this->getMockBuilder('PdfManager')
									 ->setConstructorArgs(array($csApi))
									 ->setMethods(array('deleteAjaxResponse'))
									 ->getMock();

		// define expectation when calling the deleteAjaxResponse() method with the $post[id] data 
		$this->pdfManagerMock->expects($this->once())
						     ->method('deleteAjaxResponse')
						     ->with($post['id'])
						     ->will($this->returnValue(true));

		$this->pdfManagerMock->expects($this->never())
					   		 ->method('setPagingStart');

		// call method under test; above expectations will be evauluated as well as the following assertion
		$this->pdfManagerMock->beforeLoad($get, $post);

		// isAjaxRequest should not get set, 
		$this->assertEquals($this->pdfManagerMock->isAjaxRequest, false);
	}

	public function test_load() {
		global $csApi;
		$this->pdfManagerMock = $this->getMockBuilder('PdfManager')
							 ->setConstructorArgs(array($csApi))
							 ->setMethods(array('getAuthOrgKeys'))
							 ->getMock();
		
		$this->pdfManagerMock->expects($this->any())
					   ->method('getAuthOrgKeys')
					   ->will($this->returnValue(array(
					   		'OrgName 1',
					   		'OrgName 2',
					   		'OrgName 3'
					   	)));

		$this->pdfManagerMock->isAjaxRequest = true;
		$this->pdfManagerMock->load();

		$this->assertEquals($this->pdfManagerMock->authOrgList[0], 'OrgName 1');
		$this->assertEquals($this->pdfManagerMock->authOrgList[1], 'OrgName 2');

		$this->assertEquals($this->pdfManagerMock->feedData, $this->pdfManagerMock->feedResponse->bursts);

		$burstItem = $this->pdfManagerMock->feedResponse->bursts[0];
		$this->assertEquals($burstItem->name, 'PDF Workflow - single page app - v2');
		$this->assertEquals($burstItem->filename, 'PDF Workflow - Non-wizard Single Page App option.pdf');
		$this->assertEquals($burstItem->size, 330542);
		$this->assertEquals($burstItem->contentId, 552);
		$this->assertEquals($burstItem->uploadTimestampMs, 1387570964701);
		$this->assertEquals($burstItem->burstTemplateId, 1);
	}

	public function test_afterLoad() {
		global $csApi;

		$this->pdfManagerMock = $this->getMockBuilder('PdfManager')
					 ->setConstructorArgs(array($csApi))
					 ->setMethods(array('setDisplayPagingDetails', 'burstsAjaxResponse'))
					 ->getMock();

		$this->pdfManagerMock->expects($this->once())
					   ->method('setDisplayPagingDetails')
					   ->with();
		$this->pdfManagerMock->expects($this->once())
					   ->method('burstsAjaxResponse')
					   ->with();

		$this->pdfManagerMock->isAjaxRequest = true;
		$this->pdfManagerMock->afterLoad();
	}

	public function test_setDisplayPagingDetails_NoFeedData() {
		$this->pdfManager->feedData = array();

		$this->pdfManager->setDisplayPagingDetails();

		$this->assertEquals($this->pdfManager->total, 0);
		$this->assertEquals($this->pdfManager->numPages, 0);
		$this->assertEquals($this->pdfManager->curPage, 1);
		$this->assertEquals($this->pdfManager->displayStart, 0);
		$this->assertEquals($this->pdfManager->displayEnd, 0);
	}

	public function test_setDisplayPagingDetails_WithFeedData() {
		global $csApi;

		$feedResponse = $csApi->getBurstList();
		$this->pdfManager->feedData = $feedResponse->bursts;

		$this->pdfManager->setDisplayPagingDetails();

		$this->assertEquals($this->pdfManager->total, 2);
		$this->assertEquals($this->pdfManager->numPages, 1);
		$this->assertEquals($this->pdfManager->curPage, 1);
		$this->assertEquals($this->pdfManager->displayStart, 1);
		$this->assertEquals($this->pdfManager->displayEnd, 2);
	}

	public function test_displayBurstItem() {
		global $csApi;

		$feedResponse = $csApi->getBurstList();
		$this->pdfManager->feedData = $feedResponse->bursts;

		foreach ($this->pdfManager->feedData as $burstObj) {
			$burstItem = $this->pdfManager->getBurstListItem($burstObj);
			$this->assertEquals($burstItem['itemid'], $burstObj->id); // ex. id=14/15
			$this->assertEquals($burstItem['title'], $burstObj->name); // ex. name = 'PDF Workflow - single page app - v2'
			$this->assertEquals($burstItem['defaultlink'], 'pdfedit.php?id=' . $burstObj->id);

			$this->assertContains('<span data-burst-id="'.$burstObj->id.'">', $burstItem['content']);
			$this->assertContains(date("M j, Y g:i a", $burstObj->uploadTimestampMs / 1000), $burstItem['content']);
			
			// check that 'Edit' link points to 'pdfedit.php?id=x'
			$this->assertContains('href="pdfedit.php?id='.$burstObj->id, $burstItem['tools']); // edit
			
			// TODO: check Send Mail link 

			// check that 'Download link points to $this->pdfManager->burstsURL/id/pdf'
			$this->assertContains('href="'.$this->pdfManager->burstsURL.'/'.$burstObj->id.'/pdf', $burstItem['tools']);
			
			// check that Selete link's onclick handler points to deleteBurst(id) JS function
			$this->assertContains('onclick="deleteBurst('.$burstObj->id.');', $burstItem['tools']); // delete link
		}

	}

}

?>