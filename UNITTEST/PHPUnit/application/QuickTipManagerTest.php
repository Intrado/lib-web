<?
/**
 * User: nrheckman
 * Date: 9/17/14
 * Time: 1:22 PM
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/PhpStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/ApiStub.php'));

require_once("{$konadir}/quicktipmanager.php");

class QuickTipManagerTest extends PHPUnit_Framework_TestCase {
	/**
	 * @var QuickTipManager
	 */
	private $page;
	private $mockApi;
	private $mockForm;
	private $mockOrgQtFeatureList;
	private $mockRootOrg;

	public static function setUpBeforeClass() {
		$_SERVER['SERVER_NAME'] = 'localhost';
		$_SERVER['REQUEST_URI'] = '/';
	}

	// before each test
	public function setUp() {
		global $USER;

		function getSystemSetting_stub() {
			return true;
		}
		runkit_function_rename('getSystemSetting', 'orig_getSystemSetting');
		runkit_function_rename('getSystemSetting_stub', 'getSystemSetting');

		$USER = $this->getMockBuilder('User')
			->disableOriginalConstructor()
			->getMock();

		$USER->expects($this->any())
			->method('authorize')
			->will($this->returnValue(true));

		$this->mockRootOrg = json_decode('{
			"id": 123,
			"name": "District",
			"subOrganizations": [{
				"id": 124,
				"name": "Anytown High School",
				"parentOrganizationId": 123
			},
			{
				"id": 125,
				"name": "Anytown Middle School",
				"parentOrganizationId": 123
			}]
		}');

		$this->mockOrgQtFeatureList = json_decode('[{
				"organizationId": 123,
				"isEnabled": true
			},
			{
				"organizationId": 125,
				"isEnabled": false
		}]');

		$apiClient = new ApiStub('http://localhost/api');
		$this->mockApi = $this->getMockBuilder('CommsuiteApiClient')
			->setConstructorArgs(array($apiClient))
			->setMethods(array())
			->getMock();

		$this->mockApi->expects($this->any())
			->method('getOrganization')
			->will($this->returnValue((object) $this->mockRootOrg));

		$this->mockApi->expects($this->any())
			->method('getFeature')
			->will($this->returnValue((object) $this->mockOrgQtFeatureList));

		$this->mockForm = $this->getMockBuilder('Form')
			->disableOriginalConstructor()
			->getMock();

		$this->page = new QuickTipManager(array(), $this->mockApi);
	}

	// after each test
	public function tearDown() {

	}

	public function test_isAuthorized() {
		$this->assertTrue($this->page->isAuthorized(), 'The user should be authorized to access this page');
	}

	public function test_formFactory() {
		$form = $this->page->formFactory($this->mockRootOrg, $this->mockOrgQtFeatureList);

		$formData = $form->getFormdata();
		$orgKeys = array();
		foreach ($formData as $key => $item) {
			if (strpos($key, 'org#') === 0) {
				$orgKeys[] = $key;
			}
		}

		$this->assertEquals('Form', get_class($form), 'form should be a Form');
		$this->assertEquals(3, count($orgKeys), 'should contain the correct number of organizations');
	}

	public function test_load() {
		$this->page->load();

		$this->assertTrue(is_object($this->page->form), 'form should be defined');
	}

	public function test_render() {
		$this->page->form = new Form('mockForm', array());
		$rendered = $this->page->render();

		$this->assertTrue(is_string($rendered), 'should have rendered a string');
		$this->assertTrue(mb_strlen($rendered) > 0, 'should have rendered some characters');
	}

	public function test_afterLoad() {
		$this->mockForm->expects($this->once())
			->method('handleRequest')
			->will($this->returnValue(true));

		$this->mockForm->expects($this->once())
			->method('getSubmit')
			->will($this->returnValue(true));

		$this->mockForm->expects($this->once())
			->method('getData')
			->will($this->returnValue(array('field' => 'value')));

		$this->mockForm->expects($this->once())
			->method('sendTo');

		$this->mockApi->expects($this->once())
			->method('setFeature')
			->will($this->returnValue(array('field' => 'value')));

		$this->page->form = $this->mockForm;

		// call the method, expecting all the above mock methods to be called
		$this->page->afterLoad();
	}
}