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

	function __construct($name, $options) {
		if (isset($options)) {
			$this->options = $options;
			$this->setFormData();
			parent::Form($name, $this->formdata, null, array( submit_button_with_image(_L(' Search Tips'), 'search', 'img/pictos-search.png')));
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

		$catArray[0] = "All Categories";
		$catList = QuickQueryList("
					SELECT tt.id, tt.name FROM tai_topic tt
					INNER JOIN tai_organizationtopic tot on (tot.topicid = tt.id)
					WHERE tot.organizationid = (SELECT id FROM organization WHERE parentorganizationid IS NULL)
					ORDER BY tt.name ASC", true);
		foreach ($catList as $id => $value) {
			$catArray[$id] = escapehtml($value);
		}

		$dateObj = json_decode($this->options['date']);
		$dateType = $dateObj->reldate;
		$dateArr = array(
			"reldate" 	=> isset($dateObj->reldate) ? $dateObj->reldate : 'today',
			"xdays" 	=> isset($dateObj->xdays) ? $dateObj->xdays : '',
			"startdate" => isset($dateObj->startdate) ? $dateObj->startdate : '',
			"enddate" 	=> isset($dateObj->enddate) ? $dateObj->enddate : ''
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
				"label" 		=> _L("Category"),
				"fieldhelp" 	=> _L("Select a Category to filter Tip search results on."),
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
	var $pagingLimit = 30;
	var $ajaxsubmit = false;
	var $searchQuery;
	var $options;
	var $tableColumnHeadings;
	var $tableCellFormatters;
	var $tipData = array();
	var $total;

	function __construct($options) {
		if (isset($options)) {
			$this->options = $options;
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
			"3" => _L('<div class="attachment"></div>'),
			"2" => _L('Message'),
			"0" => _L('Organization'),
			"1" => _L('Category'),
			"6" => _L('Date <div id="carat"></div>'),
			"7" => _L('Contact&nbsp;Info')
		);

		$this->tableCellFormatters = array(
			"3" => "fmt_attachment",
			"2" => "fmt_tip_message",
			"0" => "fmt_escapehtml",
			"1" => "fmt_escapehtml",
			"6" => "fmt_nbr_date",
			"7" => "fmt_contact_info"
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
	function load(TipSearchForm $form = null) {
		$this->doSearchQuery();
		$this->form = (isset($form)) ? $form : new TipSearchForm($this->options['formname'], $this->options);
		$this->form->ajaxsubmit = false;
	}

	// @override
	function afterLoad() {
		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			// if user submits a search, update SESSION['tips'] with latest form data 
			$_SESSION['tips'] = $this->form->getData();
			// then reload (redirect to) self (with new data)
			redirect($_SERVER['PHP_SELF']);
		}
	}

	// @override
	function sendPageOutput() {
		global $TITLE;
		startWindow($TITLE);

		echo '<div id="tip-icon"></div><div id="tip-search-instruction">Search Tip Submissions based on '.getSystemSetting("organizationfieldname", "Organization").'s, Category, and/or Date.</div>';
		// render search form
		echo $this->form->render();

		// top pager
		showPageMenu($this->total, $this->pagingStart, $this->pagingLimit);
		
		// Tip Submissions table
		echo '<table id="tips-table" width="100%" cellpadding="3" cellspacing="1" class="list" style="margin-top:15px;">';
		showTable($this->tipData, $this->tableColumnHeadings, $this->tableCellFormatters, array(), NULL, false);
		echo "</table>";

		// only show bottom pager if there's more than 30 rows
		if ($this->total > 30) {
			showPageMenu($this->total, $this->pagingStart, $this->pagingLimit);
		}

		endWindow();
		echo '<script src="script/tips.js"></script>';
		$this->createAttachmentViewerModal();
	}

	function setPagingStart($pagestart) {
		$this->pagingStart = 0 + $pagestart;
	}

	function doSearchQuery() {
		$this->searchQuery = "
			SELECT SQL_CALC_FOUND_ROWS o.orgkey, tai_topic.name, tm.body, tma.filename, tma.size, tma.contentid, from_unixtime(tm.modifiedtimestamp) as date1, 
					u.firstname, u.lastname, u.email, u.phone FROM tai_message tm 
			INNER JOIN tai_thread tt on (tm.threadid = tt.id) 
			INNER JOIN organization o on (o.id = tt.organizationid)
			INNER JOIN tai_topic on (tt.topicid = tai_topic.id)
			INNER JOIN user u on (u.id = tm.senderuserid) 
			LEFT JOIN tai_messageattachment tma on (tma.messageid = tm.id)
			WHERE 1 ";

		// include user org/category search params, if any
		$this->searchQuery .= isset($this->options['orgid']) && $this->options['orgid'] > 0 ? ' AND o.id = ' . $this->options['orgid'] . ' ' : '';
		$this->searchQuery .= isset($this->options['categoryid']) && $this->options['categoryid'] > 0 ?	' AND tai_topic.id = ' . $this->options['categoryid'] . ' ' : '';

		$datesql = $startdate = $enddate = ' ';

		// get user-specified date options
		if (isset($this->options['date']) && $this->options['date'] != ""){
			$dateObj = json_decode($this->options['date']);
			$dateType = $dateObj->reldate;
			list($startdate, $enddate) = getStartEndDate($dateType, array(
				"reldate" 	=> $dateObj->reldate,
				"lastxdays" => isset($dateObj->xdays) ? $dateObj->xdays : "",
				"startdate" => isset($dateObj->startdate) ? $dateObj->startdate : "",
				"enddate" 	=> isset($dateObj->enddate) ? $dateObj->enddate : ""
			));

			$startdate = date("Y-m-d", $startdate);
			$enddate = date("Y-m-d", $enddate);
			$datesql = " AND (from_unixtime(tm.modifiedtimestamp) >= '$startdate' and from_unixtime(tm.modifiedtimestamp) < date_add('$enddate',interval 1 day) )";
		} else {
			// default to current date (today) if not set by user
			$datesql = " AND Date(from_unixtime(tm.modifiedtimestamp)) = CURDATE()";
			$enddate = $startdate = date("Y-m-d", time());
		}
		$this->searchQuery .= $datesql;
		$this->searchQuery .= " ORDER BY date1 desc limit " . $this->pagingStart .",". $this->pagingLimit;

		// do the query now that the query string is ready
		$tipQueryResult = Query($this->searchQuery);

		while ($row = DBGetRow($tipQueryResult)) {
			$this->tipData[] = $row;
		}
		
		// get total row count for pager
		$this->total = QuickQuery("select FOUND_ROWS()");

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
		// set content allowed for the given image id ($row[$index+2]),
		// so it can be viewable in the attachment modal
		permitContent($row[$index+2]);
		
		$fileDetailsOrig = $row[$index].' ('. round((($row[$index+1]) / 1024), 1) . 'KB)';
		$max = 12;
		if (strlen($row[$index]) > $max) {
			$fileNameTrimmed = substr($row[$index], 0, $max - 3) . '...';
			return '<a href="#" class="attachment" data-image-id="'.$row[$index+2].'" title="Attachment: '.$fileDetailsOrig .'">'.$fileNameTrimmed.'&nbsp;<span>('. round((($row[$index+1]) / 1024), 1) . 'KB)</span></a>';
		}
		return '<a href="#" class="attachment" data-image-id="'.$row[$index+2].'" title="Attachment: '.$fileDetailsOrig .'">'.$row[$index].'&nbsp;<span>('. round((($row[$index+1]) / 1024), 1) . 'KB)</span></a>';
	}
	return "&nbsp;";
}

function fmt_contact_info ($row, $index) {
	$str  = '';

	// get the individual contact fields in the row (and html escape them)
	$first = (isset($row[$index])) 	   ? escapehtml($row[$index]) 	  : null;
	$last  = (isset($row[$index + 1])) ? escapehtml($row[$index + 1]) : null;
	$email = (isset($row[$index + 2])) ? escapehtml($row[$index + 2]) : null;
	$phone = (isset($row[$index + 3])) ? escapehtml($row[$index + 3]) : null;

	// check for existence (to determine if/how we display them)
	$hasFirst = isset($first) && strlen($first) > 0;
	$hasLast  = isset($last)  && strlen($last)  > 0;
	$hasEmail = isset($email) && strlen($email) > 0;
	$hasPhone = isset($phone) && strlen($phone) > 0;

	if ($hasFirst || $hasLast || $hasEmail || $hasPhone) {
		$str .= '<a href="#" class="tip-view-contact">View</a>';
		$str .= '<span class="tip-contact-details" style="display:none">';
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
