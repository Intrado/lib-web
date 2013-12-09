<?

/**
 * TipsTest.php - PHPUnit test for class Tips
 *
 * @package unittests
 * @author Justin Burns, <jburns@schoolmessenger.com>
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
require_once(realpath(dirname(dirname(__FILE__)) .'/DBStub.php'));
// ----------------------------------------------------------------------------

require_once("{$konadir}/tips.php");


class TipsTest extends PHPUnit_Framework_TestCase {

	const USER_ID = 1;
	const ACCESS_ID = 3;

	public function test_isAuthorized() {
		global $queryRules, $USER;

		// Make some query rules:

		// 1) The getCustomerSetting('_hasquicktip') query
		$queryRules->add('/select value from setting where name = ?/', array('_hasquicktip'), 0);

		// 2) The user DBMO initialization query for userID=1
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

		// 3) The profile (access) record for this user
		$queryRules->add('/from access where id/', array(self::ACCESS_ID),
			array(
				array(
					'name',
					'description'
				)
			)
		);

		// 4) Permissions for this user's profile
		$queryRules->add('/from permission where accessid/',
			array(
				array(
					1,
					self::ACCESS_ID,
					'tai_canbetopicrecipient',
					1
				)
			)
		);

		// Make a new user object for userID=1
		$USER = new User(self::USER_ID);

		// Make a new access object
		$ACCESS = $_SESSION['access'] = new Access($USER->accessid);

		$tipSubmissionViewer = new TipSubmissionViewer();

		// By default, without staging something special, we should be denied authorization
		$result = $tipSubmissionViewer->isAuthorized();
		$this->assertTrue($result);
	}
/*
	private $sessionData;

	function setUp() {
		$this->sessionData = array(
			"tips" => array(
				"orgid" => "25",
				"categoryid" => "15",
				"date" => '{"reldate":"today","xdays":"","startdate":"","enddate":""}'
			)
		);

		// $this->$mock_searchForm = $this->getMock("TipSearchForm");
		// $this->tipSubmissionViewer = new TipSubmissionViewer($sessionData);

	}

	// sets options with the formname, title and page (nav), 
	public function test_initialize() {
		$this->tipSubmissionViewer = new TipSubmissionViewer($this->sessionData);

		$this->assertEquals($this->tipSubmissionViewer->options["formname"], 'tips');
		$this->assertEquals($this->tipSubmissionViewer->options["title"], 'Tip Submissions');
		$this->assertEquals($this->tipSubmissionViewer->options["page"], 'notifications:tips');

		$this->assertEquals(count($this->tipSubmissionViewer->tableColumnHeadings), 6);

		$this->assertEquals($this->tipSubmissionViewer->tableCellFormatters["2"], "fmt_tip_message");
		$this->assertEquals($this->tipSubmissionViewer->tableCellFormatters["3"], "fmt_attachment");
		$this->assertEquals($this->tipSubmissionViewer->tableCellFormatters["6"], "fmt_nbr_date");
		$this->assertEquals($this->tipSubmissionViewer->tableCellFormatters["7"], "fmt_contact_info");

		$this->assertEquals($this->tipSubmissionViewer->tableColumnHeadingFormatters["3"], "fmt_attach_col_heading");
		$this->assertEquals($this->tipSubmissionViewer->tableColumnHeadingFormatters["6"], "fmt_date_col_heading");
		$this->assertEquals($this->tipSubmissionViewer->tableColumnHeadingFormatters["7"], "fmt_contactinfo_col_heading");
	}

	public function test_load() {
		// needs database access to test
		$this->markTestIncomplete();
	}

	public function test_sendPageOutput() {
		// untestable while start(end)Window, showTable, showPageMenu only writes to stdout instead of returning a string
		$this->markTestIncomplete();
	}

	public function test_doSearchQuery() {
		// needs database access to test
		$this->markTestIncomplete();
	}

	public function test_setPagingStart() {
		$this->tipSubmissionViewer = new TipSubmissionViewer($this->sessionData);
		$this->tipSubmissionViewer->setPagingStart(123);
		$this->assertEquals($this->tipSubmissionViewer->pagingStart, 123);
	}
*/
}
?>
