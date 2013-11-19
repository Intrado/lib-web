<?

/**
 * TipsTest.php - PHPUnit test for class Tips
 *
 * @package unittests
 * @author Justin Burns, <jburns@schoolmessenger.com>
 */

require_once(realpath(dirname(dirname(__FILE__)) .'/konaenv.php'));
// ----------------------------------------------------------------------------

require_once("{$konadir}/tips.php");


class TipsTest extends PHPUnit_Framework_TestCase {
	private $tips;
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

	public function test_getQueryString() {
		$this->tipSubmissionViewer = new TipSubmissionViewer($this->sessionData);
		$queryBase = "
			SELECT SQL_CALC_FOUND_ROWS o.orgkey, tai_topic.name, tm.body, tma.filename, tma.size, tma.contentid, from_unixtime(tm.modifiedtimestamp) as date1, 
					u.firstname, u.lastname, u.email, u.phone FROM tai_message tm 
			INNER JOIN tai_thread tt on (tm.threadid = tt.id) 
			INNER JOIN organization o on (o.id = tt.organizationid)
			INNER JOIN tai_topic on (tt.topicid = tai_topic.id)
			INNER JOIN user u on (u.id = tm.senderuserid) 
			LEFT JOIN tai_messageattachment tma on (tma.messageid = tm.id)
			WHERE 1 ";
		$queryWhere = " AND Date(from_unixtime(tm.modifiedtimestamp)) = CURDATE() ORDER BY date1 desc limit " . $this->tipSubmissionViewer->pagingStart .",". $this->tipSubmissionViewer->pagingLimit;
		
		// check default query
		$this->assertEquals($this->tipSubmissionViewer->getQueryString(), $queryBase . $queryWhere);

		// simulate change of some form setting
		$this->tipSubmissionViewer->options['orgid'] = 123;
		$this->tipSubmissionViewer->options['categoryid'] = 567;
		$this->tipSubmissionViewer->setPagingStart(7);
		
		$queryOrgCat = ' AND o.id = '.$this->tipSubmissionViewer->options['orgid'].'  AND tai_topic.id = '.$this->tipSubmissionViewer->options['categoryid']. ' ';
		$queryWhere = " AND Date(from_unixtime(tm.modifiedtimestamp)) = CURDATE() ORDER BY date1 desc limit " . $this->tipSubmissionViewer->pagingStart .",". $this->tipSubmissionViewer->pagingLimit;

		// check updated query contains newly set values for orgid, topicid, etc.
		$this->assertEquals($this->tipSubmissionViewer->getQueryString(), $queryBase . $queryOrgCat . $queryWhere);

	}

}
?>