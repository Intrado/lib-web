<?
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
include_once("obj/Phone.obj.php");
require_once("inc/date.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');


// Table Formatters

// Formats Tip Message with a truncated text at 140 chars max,
// and a 'Read More' link, which shows the full message if clicked
function fmt_tip_message ($row, $index) {
	$msg = $row[$index];
	$max = 140;
	if (strlen($msg) > $max) {
		$s  = '<span class="tip-message-trimmed">' . escapehtml(substr($msg, 0, $max - 3)) . '... ';
		$s .= '<a href="#" class="tip-read-more">' . _L('Read More') . '</a></span>';
		$s .= '<span class="tip-message-full" style="display:none">' . escapehtml($msg) . '</span>';
		return $s;
	} else
		return escapehtml($msg);
}

function fmt_attachment ($row, $index) {
	if (isset($row[$index])) {
		$fileDetailsOrig = $row[$index].' ('. round((($row[$index+1]) / 1024), 1) . 'KB)';
		return '<a href="#" class="attachment" data-message-id="'.$row[$index+2].'" title="Attachment: '.$fileDetailsOrig .'"></a>';
	}
	return "&nbsp;";
}

function fmt_contact_info ($row, $index) {
	$str  = '';

	// get the individual contact fields in the row (and html escape them)
	$first = (isset($row[$index])) 	   ? escapehtml($row[$index]) 	  : "";
	$last  = (isset($row[$index + 1])) ? escapehtml($row[$index + 1]) : "";
	$email = (isset($row[$index + 2])) ? escapehtml($row[$index + 2]) : "";
	$phone = (isset($row[$index + 3])) ? escapehtml($row[$index + 3]) : "";

	// check for existence (to determine if/how we display them)
	$hasFirst = strlen($first) > 0;
	$hasLast  = strlen($last)  > 0;
	$hasEmail = strlen($email) > 0;
	$hasPhone = strlen($phone) > 0;

	if ($hasFirst || $hasLast || $hasEmail || $hasPhone) {
		$str .= '<span class="tip-contact-details">';
		if ($hasFirst && $hasLast) {
			$str .= '<div>' . _L('Name:') . '&nbsp;'.$first.' '.$last.'</div>';
		}
		else if ($hasFirst && !$hasLast) {
			$str .= '<div>' . _L('First&nbsp;Name') . '&nbsp;'.$first.'</div>';
		}
		else if (!$hasFirst && $hasLast) {
			$str .= '<div>' . _L('Last&nbsp;Name') . ':&nbsp;'.$last.'</div>';
		}

		if ($hasEmail) {
			$str .= '<div>' . _L('Email:') . '&nbsp;'.$email.'</div>';
		}
		if ($hasPhone) {
			$str .= '<div>' . _L('Phone:') . '&nbsp;'.$phone.'</div>';
		}
		$str .= '</span>';
	}
		
	return $str;
}

// table column heading <th> formatters
function fmt_attach_col_heading() {
	return '<div class="attachment"></div>';
}

function fmt_date_col_heading() {
	return _L('Date') . '&nbsp;<div id="carat"></div>';
}

function fmt_contactinfo_col_heading() {
	// non-breaking so it doesn't wrap on 2 lines
	return _L('Contact&nbsp;Info'); 
}


/**
 * class TipSubmissionViewer
 * 
 * @description: class used for managing the Tip Submission viewer table/page (tips.php), 
 * which is dependant on the TipSearchForm fields 
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @date: 11/12/2013
 */
class TipSubmissionViewer extends PageForm {

	var $pagingStart = 0;
	var $pagingLimit = 100;
	var $options;
	var $tableColumnHeadings;
	var $tableCellFormatters;
	var $orgFieldName;
	var $authOrgList;
	var $tipData;
	var $total;
	var $orgId = null;
	var $categoryId = null;
	var $date = null;
	var $sqlArgs;

	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return getSystemSetting('_hasquicktip', false) && $USER->authorize('tai_canbetopicrecipient'); 
	}

	// @override
	function initialize() {
		// override some options set in PageBase
		$this->options["title"] = _L('Tip Submissions');
		$this->options["page"]  = 'notifications:tips';
	}

	// @override
	function beforeLoad($get = array(), $post = array()) {
		$tipState = isset($_SESSION['tips']) ? $_SESSION['tips'] : array() ;

		$this->orgId 		= $tipState['orgid'];
		$this->categoryId 	= $tipState['categoryid'];
		$this->date 		= $tipState['date'];

		$this->setPagingStart((isset($get['pagestart'])) ? $get['pagestart'] : 0);
	}

	// @override
	function load() {
		// fetch org field name and auth key list as these are needed in setFormData()
		$this->authOrgList 	= Organization::getAuthorizedOrgKeys();
		$this->orgFieldName = getSystemSetting("organizationfieldname", "Organization");

		// gets table data based on search/filter settings
		$this->doSearchQuery();

		// Make the edit FORM
		$this->form = $this->factoryFormTipSubmissions();
	}

	// @override
	function afterLoad() {
		$this->tableColumnHeadings = array(
			"3" => _L("Attachment"), 
			"2" => _L('Message'),
			"0" => _L($this->orgFieldName),
			"1" => _L('Topic'),
			"6" => _L('Date'), 
			"7" => _L('Contact Info')
		);

		$this->tableCellFormatters = array(
			"3" => "fmt_attachment",
			"2" => "fmt_tip_message",
			"6" => "fmt_nbr_date",
			"7" => "fmt_contact_info"
		);

		$this->tableColumnHeadingFormatters = array(
			"3" => "fmt_attach_col_heading",
			"6" => "fmt_date_col_heading",
			"7" => "fmt_contactinfo_col_heading",
		);

		$this->form->handleRequest();

		if ($this->form->getSubmit()) {

			// run server-side validation...
			if (($errors = $this->form->validate()) === false) {

				// if user submits a search, update SESSION['tips'] with latest form data 
				$_SESSION['tips'] = $this->form->getData();
				// then reload (redirect to) self (with new data)
				redirect('tips.php');
			}

			// not good: there was a server-side validation error if we got here...
		}
	}

	// @override
	function sendPageOutput() {
		global $TITLE;
		startWindow($TITLE);

		echo '<link rel="stylesheet" type="text/css" href="css/tips.css">
			<div id="tip-icon"></div><div id="tip-search-instruction">' .
			sprintf(_L('Search Tip Submissions based on %s, Topic, and/or Date.'), $this->orgFieldName) . '</div>';

		// render search form
		echo $this->form->render();

		// top pager
		showPageMenu($this->total, $this->pagingStart, $this->pagingLimit);
		
		// Tip Submissions table
		echo '<table id="tips-table" width="100%" cellpadding="3" cellspacing="1" class="list" style="margin-top:15px;">';
		showTable($this->tipData, $this->tableColumnHeadings, $this->tableCellFormatters, array(), NULL, $this->tableColumnHeadingFormatters);
		echo "</table>";

		// only show bottom pager if there's more than 100 ($this->pagingLimit) rows
		if ($this->total > $this->pagingLimit) {
			showPageMenu($this->total, $this->pagingStart, $this->pagingLimit);
		}

		endWindow(); 
		echo '<script src="script/tips.js"></script>';
		$this->createAttachmentViewerModal();
	}

	/*=============== non-override helper methods below =================*/

	function factoryFormTipSubmissions() {
		$orgArray[0] = 'All ' . $this->orgFieldName . 's';
		foreach ($this->authOrgList as $id => $value) {
			$orgArray[$id] = $value;
		}

		$catArray[0] = "All Topics";
		$catList = QuickQueryList("
			SELECT tt.id, tt.name FROM tai_topic tt
			INNER JOIN tai_organizationtopic tot on (tot.topicid = tt.id)
			WHERE tot.organizationid = (SELECT id FROM organization WHERE parentorganizationid IS NULL)
			ORDER BY tt.name ASC", true);
		foreach ($catList as $id => $value) {
			$catArray[$id] = $value;
		}

		$dateObj = json_decode($this->date, true);
		$dateType = $dateObj['reldate'];
		$dateArr = array(
			"reldate" => isset($dateObj['reldate']) ? $dateObj['reldate'] : 'today',
			"xdays" => isset($dateObj['xdays']) ? $dateObj['xdays'] : '',
			"startdate" => isset($dateObj['startdate']) ? $dateObj['startdate'] : '',
			"enddate" => isset($dateObj['enddate']) ? $dateObj['enddate'] : ''
		);
		
		$formdata['orgid'] = array(
			"label" => _L($this->orgFieldName),
			"fieldhelp" => sprintf(_L('Select a %s to filter Tip search results on.'), $this->orgFieldName),
			"value" => $this->orgId ? $this->orgId : $orgArray[0],
			"validators" => array(array("ValInArray", "values" => array_keys($orgArray))),
			"control" => array("SelectMenu", "values" => $orgArray),
			"helpstep" => 1
		);

		$formdata['categoryid'] = array(
			"label" => _L("Topic"),
			"fieldhelp" => _L('Select a Topic to filter Tip search results on.'),
			"value" => $this->categoryId ? $this->categoryId : $catArray[0],
			"validators" => array(array("ValInArray", "values" => array_keys($catArray))),
			"control" => array("SelectMenu", "values" => $catArray),
			"helpstep" => 1
		);

		$formdata['date'] = array(
			"label" => _L("Date"),
			"fieldhelp" => _L('Select the date to filter Tip search results on.'),
			"value" => json_encode($dateArr),
			"control" => array("ReldateOptions"),
			"validators" => array(array("ValReldate")),
			"helpstep" => 1
		);

		$form = new Form('tips', $formdata, null, array( submit_button(_L('Search Tips'), 'search', 'pictos/p1/16/64')));
		$form->ajaxsubmit = false;

		return($form);
	}

	function getQueryString() {
		$this->sqlArgs = array();

		$query = "
			SELECT SQL_CALC_FOUND_ROWS
				o.orgkey, tai_topic.name, tm.body, tma.filename, tma.size, tma.messageid,
				from_unixtime(tm.modifiedtimestamp) as date1, u.firstname, u.lastname, u.email, u.phone 
			FROM
				tai_message tm 
				INNER JOIN tai_thread tt on (tm.threadid = tt.id) 
				INNER JOIN organization o on (o.id = tt.organizationid)
				INNER JOIN tai_topic on (tt.topicid = tai_topic.id)
				INNER JOIN user u on (u.id = tm.senderuserid) 
				LEFT JOIN tai_messageattachment tma on (tma.messageid = tm.id)
			WHERE 1 ";

		// limit results to only those orgs in the user's authorized org list
		$query .= " AND o.id IN (" . implode(', ', array_keys($this->authOrgList)) . ") ";

		// include user org/category search params, if any
		if ($this->orgId) {
			$query .= ' AND o.id = ?';
			$this->sqlArgs[] = $this->orgId;
		}

		if ($this->categoryId) {
			$query .= ' AND tai_topic.id = ?';
			$this->sqlArgs[] = $this->categoryId;
		}

		// get user-specified date options
		if ($this->date){
			$dateObj = json_decode($this->date, true);
			$dateType = $dateObj['reldate'];
			list($startdate, $enddate) = getStartEndDate($dateType, array(
				"reldate" 	=> $dateObj['reldate'],
				"lastxdays" => isset($dateObj['xdays']) ? $dateObj['xdays'] : "",
				"startdate" => isset($dateObj['startdate']) ? $dateObj['startdate'] : "",
				"enddate" 	=> isset($dateObj['enddate']) ? $dateObj['enddate'] : ""
			));

			$this->sqlArgs[] = date("Y-m-d", $startdate);
			$this->sqlArgs[] = date("Y-m-d", $enddate);
			$query .= " AND (from_unixtime(tm.modifiedtimestamp) >= ? and from_unixtime(tm.modifiedtimestamp) < date_add(?, interval 1 day) )";
		
		} else {
			// default to current date (today) if not set by user
			$query .= " AND Date(from_unixtime(tm.modifiedtimestamp)) = CURDATE()";
		}

		$query .= " ORDER BY date1 desc limit " .$this->pagingStart. ", " .$this->pagingLimit;

		return $query;

	}

	function doSearchQuery() {
		$this->tipData 	= QuickQueryMultiRow($this->getQueryString(), false, false, $this->sqlArgs);
		$this->total 	= QuickQuery("select FOUND_ROWS()");
	}

	function setPagingStart($pagestart) {
		$this->pagingStart = 0 + $pagestart;
	}

	function createAttachmentViewerModal() {
		$str =	'
			<div id="tip-view-attachment" class="modal hide">
				<div class="modal-header">
					<h3 id="attachment-details">' . _L('Tip Attachment') . '</h3>
				</div>
				<div class="modal-body">
					<div id="tip-attachment-content">
						<img id="attachment-image" src="" />
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn" data-dismiss="modal">' . _L('Close') . '</button>
				</div>
			</div>';
		echo $str;
	}

}

// Initialize TipSubmissionViewer and execute (render final page)
// ================================================================
executePage(new TipSubmissionViewer());
?>

