<? 

/**
 * PdfManagerTest.php - PHPUnit test for PdfManager class
 *
 * @package unittests
 * @author Justin Burns, <jburns@schoolmessenger.com>
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once("{$konadir}/inc/common.inc.php");
require_once("{$konadir}/pdfmanager.php");

class PdfManagerTest extends PHPUnit_Framework_TestCase {
	
	const USER_ID = 1;

	var $pageBase;
	var $pdfManager;
	var $apiClient;
	var $csApi;

	public function setup() {
		global $USER;

		// create mock User with id=1
		$USER = $this->getMockBuilder('User')
					->setConstructorArgs(array(1))
					->getMock();

		// data for ApiClient
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_COOKIE['custname_session'] = 'cookie123';

		// data for global function: customerUrlComponent()
		$_SERVER['SCRIPT_NAME'] = '/custname/pdfmanager.php';

		// create (unmocked) ApiClient object to be passed into CommsuiteApiClient mock object's constructor below
		$this->apiClient = new ApiClient(
			$_SERVER['SERVER_NAME'],
			'custname',
			$USER->id,
			$_COOKIE['custname_session']
		);

		// create mock object of CommsuiteApiClient and stub only the methods used in pdfmanager.php
		// with fake responses. We don't use/care about the other methods here so by default they will return null
		// by the mock object. 
		$this->csApi = $this->getMockBuilder('CommsuiteApiClient')
							->setConstructorArgs(array($this->apiClient))
							->getMock();
		
		// define stub for getBurstApiUrl
		$this->csApi->expects($this->any())
			  		->method('getBurstApiUrl')
			  		->will($this->returnValue('https://localhost/custname/api/2/users/1/bursts'));

		// define stub for getBurstList
		$this->csApi->expects($this->any())
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

		// define stub for deleteBurst
		$this->csApi->expects($this->any())
					->method('deleteBurst')
					->will($this->returnValue(true));

		// create PdfManager object and pass in our configured mock CommsuiteApiClient object
		$this->pdfManager = new PdfManager($this->csApi);

	}

	public function tearDown() {
		unset($this->pdfManager);
	}

	public function test_initialize() {

		// creating a new instance of PdfManager (in setup() above) calls initialize() in it's parent constructor,
		// so there's no need to call it in this test method, as it has already been called at the start of this test

		$this->assertEquals($this->pdfManager->options['title'], 'PDF Manager');
		$this->assertEquals($this->pdfManager->options['page'], 'notifications:pdfmanager');
	}

	public function test_beforeLoad_NoDelete() {
		global $get, $post; 

		$get['ajax'] = true;
		$get['pagestart'] = 123;

		$this->pdfManager->beforeLoad($get, $post);

		// custName gets set by call to global function in common.inc: customerUrlComponent()
		$this->assertEquals('custname', $this->pdfManager->customerURLComponent);

		// assert that burstsURL is set as expected ()
		$this->assertEquals('https://localhost/custname/api/2/users/1/bursts', $this->pdfManager->burstsURL);

		$this->assertEquals(true, $this->pdfManager->isAjaxRequest);
		$this->assertEquals(123, $this->pdfManager->pagingStart);
	}

	public function test_beforeLoad_WithDelete() {
		global $get, $post;

		$post['delete'] = true;
		$post['id'] = 234;

		// create a mock PdfManager object and only mock the deleteAjaxResponse() method, 
		// the behaviour of the other methods is not changed.
		$this->pdfManagerMock = $this->getMockBuilder('PdfManager')
									 ->setConstructorArgs(array($this->csApi))
									 ->setMethods(array('deleteAjaxResponse'))
									 ->getMock();

		// define expectation (stub) when calling the deleteAjaxResponse() method with the $post[id] data 
		$this->pdfManagerMock->expects($this->once())
						     ->method('deleteAjaxResponse')
						     ->with($post['id'])
						     ->will($this->returnValue(true));

		// define expecation that this method should never get called in this case
		$this->pdfManagerMock->expects($this->never())
					   		 ->method('setPagingStart');

		// call method under test; above expectations will be evauluated as well as the following assertion
		$this->pdfManagerMock->beforeLoad($get, $post);

		// isAjaxRequest should not get set, 
		$this->assertEquals(false, $this->pdfManagerMock->isAjaxRequest);
	}

	public function test_load() {
		// create a mock PdfManager object and only mock the getAuthOrgKeys() method, 
		// the behaviour of the other methods is not changed.
		$this->pdfManagerMock = $this->getMockBuilder('PdfManager')
							 ->setConstructorArgs(array($this->csApi))
							 ->setMethods(array('getAuthOrgKeys'))
							 ->getMock();
		
		// define stub for getAuthOrgKeys
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
		// create a mock PdfManager object and only mock the setDisplayPagingDetails() and 
		// burstsAjaxResponse() methods, the behaviour of the other methods is not changed.
		$this->pdfManagerMock = $this->getMockBuilder('PdfManager')
					 ->setConstructorArgs(array($this->csApi))
					 ->setMethods(array('setDisplayPagingDetails', 'burstsAjaxResponse'))
					 ->getMock();

		// defines expectation that method is called once (with no args)
		$this->pdfManagerMock->expects($this->once())
					   ->method('setDisplayPagingDetails')
					   ->with();

		// defines expectation that method is called once (with no args)
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
		$feedResponse = $this->csApi->getBurstList();
		$this->pdfManager->feedData = $feedResponse->bursts;

		$this->pdfManager->setDisplayPagingDetails();

		$this->assertEquals($this->pdfManager->total, 2);
		$this->assertEquals($this->pdfManager->numPages, 1);
		$this->assertEquals($this->pdfManager->curPage, 1);
		$this->assertEquals($this->pdfManager->displayStart, 1);
		$this->assertEquals($this->pdfManager->displayEnd, 2);
	}

	public function test_displayBurstItem() {
		$feedResponse = $this->csApi->getBurstList();
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