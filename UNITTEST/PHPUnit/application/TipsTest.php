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

	public function test_fmt_contact_info() {

		// Fully populated contact info should result in a useful string
		$row = array('first', 'last', 'email', 'phone');
		$result = fmt_contact_info($row, 0);
		$this->assertTrue(strlen($result) > 0);

		// Empty contact info should result in an empty string
		$result = fmt_contact_info(array('', '', '', ''), 0);
		$this->assertTrue(strlen($result) == 0);
	}

	public function test_fmt_attachment() {

		// 500000 bytes should become 488.3KB
		$row = array('Attachment name', 500000, 1);
		$result = fmt_attachment($row, 0);
		$this->assertTrue($result != '&nbsp;');
		$this->assertTrue(strpos($result, '488.3KB') > 0);

		// A non-attachment record should deliver a non-breaking space
		$result = fmt_attachment(array(), 0);
		$this->assertTrue($result == '&nbsp;');
	}

	public function test_fmt_tip_message() {

		// The limit is 140 chars; under should be unmodified
		$result = fmt_tip_message(array(str_repeat('a', 130)), 0);
		$this->assertTrue(strlen($result) == 130);

		// On the limit should also be unmodified
		$result = fmt_tip_message(array(str_repeat('a', 140)), 0);
		$this->assertTrue(strlen($result) == 140);

		// But over the limit should return WAY more data
		$result = fmt_tip_message(array(str_repeat('a', 150)), 0);
		$this->assertTrue(strlen($result) > 150);
	}
}
?>
