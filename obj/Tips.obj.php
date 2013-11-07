<?
/**
 * Tips.obj.php
 * 
 * @description: class used for managing the tips.php page
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @date: 11/6/2013
 */

class Tips {

	private $page = "notifications:tips";
	private $title = '';
	private $formdata = array();
	private $pagingStart = 0;
	private $pagingLimit = 30;
	private $ajaxsubmit = false;
	private $form;
	private $query;

	public function __construct() {}

	public function setTitle($pageTitle) {
		$this->title = _L($pageTitle);
	}

	public function setPagingStart($pagestart) {
		$this->pagingStart = 0 + (isset($pagestart)) ? $pagestart : 0;
	}

	public function getPagingStart() {
		return $this->pagingStart;
	}

	public function getPagingLimit() {
		return $this->pagingLimit;
	}

	public function addFormData($key, $obj) {
		$key != null ? $this->formdata[$key] = $obj : $this->formdata[] = $obj;
	}

	public function setFormData($options) {

		$orgArray[0] = "All Organizations";
		$orgList = Organization::getAuthorizedOrgKeys();
		foreach ($orgList as $id => $value) {
			$orgArray[$id] = escapehtml($value);
		}

		$catArray[0] = "All Categories";
		$catList = QuickQueryList("select id, name from tai_topic", true);
		foreach ($catList as $id => $value) {
			$catArray[$id] = escapehtml($value);
		}

		$dateObj = json_decode($options['date']);
		$dateType = $dateObj->reldate;
		$dateArr = array(
			"reldate" 	=> isset($dateObj->reldate) ? $dateObj->reldate : 'today',
			"xdays" 	=> isset($dateObj->xdays) ? $dateObj->xdays : '',
			"startdate" => isset($dateObj->startdate) ? $dateObj->startdate : '',
			"enddate" 	=> isset($dateObj->enddate) ? $dateObj->enddate : ''
		);		

		$this->addFormData(null, _L('Search Tip Submissions by Organization, Category, and/or Date'));
		
		$this->addFormData("orgid",
			array(
				"label" 		=> _L("Organization"),
				"fieldhelp" 	=> ("Select an Organization to filter Tip search results on. "),
				"value" 		=> isset($options['orgid']) ? $options['orgid'] : $orgArray[0],
				"validators" 	=> array(array("ValInArray", "values" => array_keys($orgArray))),
				"control" 		=> array("SelectMenu", "values" => $orgArray),
				"helpstep" 		=> 1
			)
		);
		
		$this->addFormData("categoryid",
			array(
				"label" 		=> _L("Category"),
				"fieldhelp" 	=> ("Select a Category to filter Tip search results on."),
				"value" 		=> isset($options['categoryid']) ? $options['categoryid'] : $catArray[0],
				"validators" 	=> array(array("ValInArray", "values" => array_keys($catArray))),
				"control" 		=> array("SelectMenu", "values" => $catArray),
				"helpstep" 		=> 1
			)
		);
		
		$this->addFormData("date",
			array(
				"label" 		=> _L("Date"),
				"fieldhelp" 	=> _L("Select the date to filter Tip search results on."),
				"value" 		=> json_encode($dateArr),
				"control" 		=> array("ReldateOptions"),
				"validators" 	=> array(array("ValReldate")),
				"helpstep" 		=> 1
			)
		);
	}

	public function getFormData() {
		return $this->formdata;
	}

	public function getForm() {
		return $this->form;
	}

	public function setForm($form) {
		$this->form = $form;
	}

	public function renderNav() {
		global $USER, $PAGE, $TITLE, $MAINTABS, $SUBTABS;
		$PAGE = $this->page;
		$TITLE = $this->title;
		include_once("nav.inc.php");
	}

	public function renderFooter() {
		global $USER, $LOCALE;
		include_once("navbottom.inc.php");
	}

	public function renderForm() {
		echo $this->form->render();
	}

	public function getSearchQuery() {
		return $this->query;
	}

	public function setSearchQuery($options) {
		$this->query = "
			SELECT SQL_CALC_FOUND_ROWS o.orgkey, tai_topic.name, tm.body, from_unixtime(tm.modifiedtimestamp) as date1, 
					(select concat(u.firstname, ' ', u.lastname)), u.email, u.phone FROM tai_message tm 
			INNER JOIN tai_thread tt on (tm.threadid = tt.id) 
			INNER JOIN organization o on (o.id = tt.organizationid)
			INNER JOIN tai_topic on (tt.topicid = tai_topic.id)
			INNER JOIN user u on (u.id = tm.senderuserid) 
			WHERE 1 ";

		// include user org/category search params, if any
		$this->query .= isset($options['orgid']) && $options['orgid'] > 0 ? ' AND o.id = ' . $options['orgid'] . ' ' : '';
		$this->query .= isset($options['categoryid']) && $options['categoryid'] > 0 ?	' AND tai_topic.id = ' . $options['categoryid'] . ' ' : '';

		$datesql = $startdate = $enddate = ' ';

		// get user-specified date options
		if (isset($options['date']) && $options['date'] != ""){
			$dateObj = json_decode($options['date']);
			$dateType = $dateObj->reldate;
			list($startdate, $enddate) = getStartEndDate($dateType, array(
				"reldate" 	=> $dateObj->reldate,
				"lastxdays" => $dateObj->xdays,
				"startdate" => $dateObj->startdate,
				"enddate" 	=> $dateObj->enddate
			));

			$startdate = date("Y-m-d", $startdate);
			$enddate = date("Y-m-d", $enddate);
			$datesql = " AND (from_unixtime(tm.modifiedtimestamp) >= '$startdate' and from_unixtime(tm.modifiedtimestamp) < date_add('$enddate',interval 1 day) )";
		} else {
			// default to current date (today) if not set by user
			$datesql = " AND Date(from_unixtime(tm.modifiedtimestamp)) = CURDATE()";
			$enddate = $startdate = date("Y-m-d", time());
		}
		$this->query .= $datesql;
		$this->query .= " ORDER BY date1 desc limit " . $this->getPagingStart() .",". $this->getPagingLimit();

	}

	public function renderSearchResults($options) {
		startWindow(_L('Tip Submissions'));
		
		$tipData = array();
		$tipQueryStr = $this->getSearchQuery();
		$tipQueryResult = Query($tipQueryStr);

		while ($row = DBGetRow($tipQueryResult)) {
			$tipData[] = $row;
		}

		$tableColumnHeadings = array(
			"0" => _L('Organization'),
			"1" => _L('Category'),
			"2" => _L('Message'),
			"3" => _L('Date'),
			"4" => _L('Name'),
			"5" => _L('Email'),
			"6" => _L('Phone')
		);

		$tableCellFormatters = array(
			"2" =>  "fmt_tip_message",
			"3" => "fmt_nbr_date",
			"5" => "fmt_email"
		);
		
		// get total row count for pager
		$totalQueryStr = "select FOUND_ROWS()";
		$total = QuickQuery($totalQueryStr);
		
		$this->renderForm(); // search form
		showPageMenu($total, $this->getPagingStart(), $this->getPagingLimit());
		
		echo '<table id="tips-table" width="100%" cellpadding="3" cellspacing="1" class="list" style="margin-top:15px;">';
		showTable($tipData, $tableColumnHeadings, $tableCellFormatters);
		echo "</table>";

		// only show bottom pager if there's at least 10 rows;
		if ($total >= 10) {
			showPageMenu($total, $this->getPagingStart(), $this->getPagingLimit());
		}

		endWindow();
	}

	// Attach click event handlers to 'Read More' links on Tip Messages
	// and append descending sort carat in Date table column heading
	public function renderJavascript() {
		echo '
			<script>
				(function($) {
					$(".tip-read-more").on("click", function(e) {
						e.preventDefault();
						var parent = $(this).parent();
						parent.fadeOut(200, function() {
							parent.next().fadeIn(200);
						});
					});
					// show descending carat to give user visual indication on how data is sorted (desc);
					// only show carat if 1 or more actual rows of data are present, not header only
					if ($("#tips-table tbody tr").length > 1) {
						$("#tips-table.list .listHeader th:nth-child(4)").append("<div id=\"carat\"></div>");
					}
					$(".pagetitle").parent().remove();
				})(jQuery);
			</script>
		';
	}
}

// Helper formatter function:
// formats Tip Message with a truncated text at 140 chars max,
// and a 'Read More' link, which shows the full message if clicked
function fmt_tip_message ($row, $index) {
	$txt = fmt_null($row, $index);
	$max = 140;
	if (strlen($txt) > $max) {
		$s  = '<span class="tip-message-trimmed">"' . substr($txt, 0, $max - 3) . '..." ';
		$s .= '<a href="#" class="tip-read-more">Read More</a></span>';
		$s .= '<span class="tip-message-full" style="display:none">"' . $txt . '"</span>';
		return $s;
	} else
		return "\"" . $txt . "\"";
}

?>
