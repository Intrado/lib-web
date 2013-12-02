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


/**
 * class TipSearchForm
 * 
 * @description: class used for initializing the Tip Submission Search form
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @date: 11/12/2013
 */

class TipSearchForm extends Form {

	var $options;

	function TipSearchForm($name, $options = array()) {
		if (isset($options)) {
			$this->options = $options;
			$this->setFormData();
			if (isset($this->formdata)) {
				parent::Form($name, $this->formdata, null, array( submit_button_with_image(_L(' Search Tips'), 'search', 'img/pictos-search.png')));
			}
		}
	}

	function addFormData($key, $obj) {
		isset($key) ? $this->formdata[$key] = $obj : $this->formdata[] = $obj;
	}

	function setFormData() {

		$orgType = getSystemSetting("organizationfieldname","Organization");

		$orgArray[0] = 'All ' . $orgType . 's';
		$orgList = Organization::getAuthorizedOrgKeys();
		foreach ($orgList as $id => $value) {
			$orgArray[$id] = escapehtml($value);
		}

		$catArray[0] = "All Topics";
		$catList = QuickQueryList("
					SELECT tt.id, tt.name FROM tai_topic tt
					INNER JOIN tai_organizationtopic tot on (tot.topicid = tt.id)
					WHERE tot.organizationid = (SELECT id FROM organization WHERE parentorganizationid IS NULL)
					ORDER BY tt.name ASC", true);
		foreach ($catList as $id => $value) {
			$catArray[$id] = escapehtml($value);
		}

		$dateObj = json_decode($this->options['date'], true);
		$dateType = $dateObj['reldate'];
		$dateArr = array(
			"reldate" 	=> isset($dateObj['reldate']) ? $dateObj['reldate'] : 'today',
			"xdays" 	=> isset($dateObj['xdays']) ? $dateObj['xdays'] : '',
			"startdate" => isset($dateObj['startdate']) ? $dateObj['startdate'] : '',
			"enddate" 	=> isset($dateObj['enddate']) ? $dateObj['enddate'] : ''
		);
		
		$this->addFormData("orgid",
			array(
				"label" 		=> _L($orgType),
				"fieldhelp" 	=> _L("Select a ".getSystemSetting("organizationfieldname","Organization")." to filter Tip search results on. "),
				"value" 		=> isset($this->options['orgid']) ? $this->options['orgid'] : $orgArray[0],
				"validators" 	=> array(array("ValInArray", "values" => array_keys($orgArray))),
				"control" 		=> array("SelectMenu", "values" => $orgArray),
				"helpstep" 		=> 1
			)
		);
		
		$this->addFormData("categoryid",
			array(
				"label" 		=> _L("Topic"),
				"fieldhelp" 	=> _L("Select a Topic to filter Tip search results on."),
				"value" 		=> isset($this->options['categoryid']) ? $this->options['categoryid'] : $catArray[0],
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

}
/////////////// end of TipSearchForm class ////////////////////////


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
	var $tipData;
	var $total;
	var $orgId = 0;
	var $categoryId = 0;
	var $date;
	var $sqlArgs;

	function TipSubmissionViewer($options = array()) {
		if (isset($options)) {
			$this->options = $options;

			$this->orgId 		= $this->options['orgid'];
			$this->categoryId 	= $this->options['categoryid'];
			$this->date 		= $this->options['date'];

			parent::PageBase($options);
		}
	}

	// @override
	function is_authorized($get, $post) {
		global $USER;
		return getSystemSetting('_hasquicktip', false) && $USER->authorize('tai_canbetopicrecipient'); 
	}

	// @override
	function initialize() {
		$this->options["formname"] = 'tips';
		$this->options["title"] = 'Tip Submissions';
		$this->options["page"]  = "notifications:tips";

		$this->tableColumnHeadings = array(
			"3" => _L("Attachment"), 
			"2" => _L('Message'),
			"0" => _L(getSystemSetting("organizationfieldname", "Organization")),
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
	}

	// @override
	function beforeLoad($get, $post) {
		if (!$this->is_authorized($get, $post)) {
			redirect('unauthorized.php');
		}
		$this->setPagingStart((isset($get['pagestart'])) ? $get['pagestart'] : 0);
	}

	// @override
	function load() {
		$this->doSearchQuery();
		$this->form = new TipSearchForm($this->options['formname'], $this->options);
		$this->form->ajaxsubmit = false;
	}

	// @override
	function afterLoad() {
		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			// if user submits a search, update SESSION['tips'] with latest form data 
			$_SESSION['tips'] = $this->form->getData();
			// then reload (redirect to) self (with new data)
			redirect('tips.php');
		}
	}

	// @override
	function sendPageOutput() {
		global $TITLE;

		startWindow($TITLE);

		echo '<div id="tip-icon"></div><div id="tip-search-instruction">Search Tip Submissions based on '.getSystemSetting("organizationfieldname", "Organization").', Topic, and/or Date.</div>';
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

	function getQueryString() {
		$this->sqlArgs = array();

		$query = "
			SELECT SQL_CALC_FOUND_ROWS o.orgkey, tai_topic.name, tm.body, tma.filename, tma.size, tma.messageid, from_unixtime(tm.modifiedtimestamp) as date1, 
					u.firstname, u.lastname, u.email, u.phone FROM tai_message tm 
			INNER JOIN tai_thread tt on (tm.threadid = tt.id) 
			INNER JOIN organization o on (o.id = tt.organizationid)
			INNER JOIN tai_topic on (tt.topicid = tai_topic.id)
			INNER JOIN user u on (u.id = tm.senderuserid) 
			LEFT JOIN tai_messageattachment tma on (tma.messageid = tm.id)
			WHERE 1 ";

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
		$this->tipData = QuickQueryMultiRow($this->getQueryString(), false, false, $this->sqlArgs);

		// get total row count for pager
		$this->total = QuickQuery("select FOUND_ROWS()");
	}

	function setPagingStart($pagestart) {
		$this->pagingStart = 0 + $pagestart;
	}

	function createAttachmentViewerModal() {
		$str =	'
			<div id="tip-view-attachment" class="modal hide">
				<div class="modal-header">
					<h3 id="attachment-details">Tip Attachment</h3>
				</div>
				<div class="modal-body">
					<div id="tip-attachment-content">
						<img id="attachment-image" src="" />
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn" data-dismiss="modal">Close</button>
				</div>
			</div>';
		echo $str;
	}

}

/////////////// end of TipSubmissionViewer class ////////////////////////


// Helper formatter function:
// formats Tip Message with a truncated text at 140 chars max,
// and a 'Read More' link, which shows the full message if clicked
function fmt_tip_message ($row, $index) {
	$txt = fmt_null($row, $index); // returns 'html escaped string'
	$max = 140;
	if (strlen($txt) > $max) {
		$s  = '<span class="tip-message-trimmed">"' . substr($txt, 0, $max - 3) . '..." ';
		$s .= '<a href="#" class="tip-read-more">Read More</a></span>';
		$s .= '<span class="tip-message-full" style="display:none">"' . $txt . '"</span>';
		return $s;
	} else
		return "\"" . $txt . "\"";
}

function fmt_attachment ($row, $index) {
	if (isset($row[$index])) {
		$fileDetailsOrig = $row[$index].' ('. round((($row[$index+1]) / 1024), 1) . 'KB)';
		$max = 12;
		if (strlen($row[$index]) > $max) {
			$fileNameTrimmed = substr($row[$index], 0, $max - 3) . '...';
			return '<a href="#" class="attachment" data-message-id="'.$row[$index+2].'" title="Attachment: '.$fileDetailsOrig .'">'.$fileNameTrimmed.'&nbsp;<span>('. round((($row[$index+1]) / 1024), 1) . 'KB)</span></a>';
		}
		return '<a href="#" class="attachment" data-message-id="'.$row[$index+2].'" title="Attachment: '.$fileDetailsOrig .'">'.$row[$index].'&nbsp;<span>('. round((($row[$index+1]) / 1024), 1) . 'KB)</span></a>';
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
			$str .= '<div>Name:&nbsp;'.$first.' '.$last.'</div>';
		}
		else if ($hasFirst && !$hasLast) {
			$str .= '<div>First&nbsp;Name:&nbsp;'.$first.'</div>';
		}
		else if (!$hasFirst && $hasLast) {
			$str .= '<div>Last&nbsp;Name:&nbsp;'.$last.'</div>';
		}

		if ($hasEmail) {
			$str .= '<div>Email:&nbsp;'.$email.'</div>';
		}
		if ($hasPhone) {
			$str .= '<div>Phone:&nbsp;'.$phone.'</div>';
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

// Initialize SESSION['tips'] data and handle request params;
////////////////////////////////////////////////////////////////////////////////

if (!isset($_SESSION['tips'])) {
	$_SESSION['tips'] = array();
}

$orgid = 		isset($_POST['tips_orgid']) ? $_POST['tips_orgid'] : 
				isset($_SESSION['tips']['orgid']) ? $_SESSION['tips']['orgid'] : null;
$categoryid = 	isset($_POST['tips_categoryid']) ? $_POST['tips_categoryid'] : 
				isset($_SESSION['tips']['categoryid']) ? $_SESSION['tips']['categoryid'] : null;
$date = 		isset($_POST['tips_date']) ? $_POST['tips_date'] : 
				isset($_SESSION['tips']['date']) ? $_SESSION['tips']['date'] : null;

$_SESSION['tips']['orgid'] 		= $orgid;
$_SESSION['tips']['categoryid'] = $categoryid;
$_SESSION['tips']['date'] 		= $date;


// Initialize TipSubmissionViewer with $_SESSION['tips'] data ($options) and exexute (render final page)
////////////////////////////////////////////////////////////////////////////////
$tips = new TipSubmissionViewer($_SESSION['tips']);
executePage($tips);
?>
