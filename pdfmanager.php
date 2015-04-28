<?
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");
require_once("inc/date.inc.php");
require_once("obj/Validator.obj.php");

require_once("obj/FieldMap.obj.php");
require_once("inc/feed.inc.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');

/**
 * class PdfManager
 * 
 * @description: TODO
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @date: 12/10/2013
 */
class PdfManager extends PageBase {
	var $pagingStart = 0;
	var $pagingLimit = 100;
	var $isAjaxRequest = false;
	var $feedResponse;
	var $feedData;
	var $total = 0;
	var $numPages;
	var $curPage;
	var $displayEnd;
	var $displayStart;
	var $customerURLComponent;
	var $burstsURL;
	var $authOrgList;
	var $csApi;
	var $sortby = "status";

	function __construct($csApi) {
		$this->csApi = $csApi;
		parent::__construct();
	}
	
	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return getSystemSetting("_haspdfburst", false) && $USER->authorize('canpdfburst');
	}

	// @override
	public function initialize() {
		// override page option on PageBase
        $this->options["title"] = 'Secure Document Delivery';
		$this->options["page"]  = 'notifications:pdfmanager';
	}

	// @override
	public function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {	
		$this->customerURLComponent = customerUrlComponent();
		$this->burstsURL = $this->csApi->getBurstApiUrl();

		$this->sortby = isset($get['feed_sortby']) ? $get['feed_sortby'] : "status";
		// if delete request, execute delete API call and exit,
		// which upon a successful response (200) will reload pdfmanager.php page (via JS in pdfmanager.js)
		if (isset($post['delete']) && $post['delete']) {
			$this->deleteAjaxResponse($post['id']);
		} else {
			$this->isAjaxRequest = isset($get['ajax']);
			$this->setPagingStart((isset($get['pagestart'])) ? $get['pagestart'] : 0);
		}

		// In case a previous attempt to edit a burst record was canceled...
		if (isset($session['burstid'])) {
			unset($session['burstid']);
		}
	}

	// @override
	public function load() {
		if ($this->isAjaxRequest) {
			$this->authOrgList 	= $this->getAuthOrgKeys();

			// fetch all existing burst records
			$this->feedResponse = $this->csApi->getBurstList($this->pagingStart, $this->pagingLimit);
			
			if ($this->feedResponse) {
				$this->feedData = $this->feedResponse->bursts;
			} 
		}
	}

	// @override
	public function afterLoad() {
		if ($this->isAjaxRequest) {
			// sets paging-related numPages, curPage, displayStart, displayEnd from $total result above
			$this->setDisplayPagingDetails();
			$this->burstsAjaxResponse();
		}
	}

	// @override
	public function sendPageOutput() {
		echo '<link rel="stylesheet" type="text/css" href="css/pdfmanager.css">';
		echo '<script type="text/javascript" src="script/pdfmanager.js"></script>';
		startWindow(_L("My Documents"));

		$sortoptions = array(
			"name" => array("icon" => "img/largeicons/tiny20x20/pencil.jpg", "name" => "Name"),
			"date" => array("icon" => "img/largeicons/tiny20x20/clock.jpg", "name" => "Date"),
			"status" => array("icon" => "img/largeicons/tiny20x20/email.jpg", "name" => "Status")
		);

		$feedButtons = array(icon_button(_L(' Create New Document'), "pdficon_16", null, "pdfedit.php"));
		feed($feedButtons, $sortoptions);
		echo '<script type="text/javascript" src="script/feed.js.php"></script>
		<script type="text/javascript">
			document.observe("dom:loaded", function() {
			feed_applyDefault("'. $_SERVER["REQUEST_URI"] .'","status","pdfmanager");
		});
		</script>';
		endWindow();
	}

	public function burstsAjaxResponse() {
		$data = (object) array(
			'list' => array(),
			'pageinfo' => array()
		);

		if ($this->total == 0) {
			$data->list[] = array(
				"itemid" => "",
				  "defaultlink" => "",
				  "icon" => "img/largeicons/information.jpg",
				  "title" => _L("No Documents available."),
				  "content" => "",
				  "tools" => ""
			  );
		} else {
			usort( $this->feedData, $this->sorterOf($this->sortby));
			foreach ( $this->feedData as $burstObj) {
				$data->list[] = $this->getBurstListItem($burstObj);
			}
		}

		$data->pageinfo = array(
			$this->numPages,
			$this->pagingLimit,
			$this->curPage, 
			sprintf(_L('Showing %d - %d of %d records on %d pages'), $this->displayStart, $this->displayEnd, $this->total, $this->numPages) . '&nbsp;'
		);

		header('Content-Type: application/json');
		echo json_encode(!empty($data) ? $data : false);
		exit();
	}

	public function getBurstListItem($burstObj) {
		$id = $burstObj->id;

		$defaultlink = $burstObj->status == "sent" ? 'reportsdd.php?id=' . $id : 'pdfedit.php?id=' . $id;

		$title = escapehtml($burstObj->name);
		$fileName = escapehtml($burstObj->filename);
		$uploadDate = date("M j, Y g:i a", $burstObj->uploadTimestampMs / 1000);
		$fileSize = $burstObj->size;
		$status = $burstObj->status;

        if ($fileSize < 1000000) {
            $formattedFileSize = number_format(($fileSize / pow(2,10)), 2, '.', '') . 'KB';
        } else {
            $formattedFileSize = number_format(($fileSize / pow(2,20)), 2, '.', '') . 'MB';
        }

		$burstStatusTag = "burst-status-new";
		$icon = 'img/largeicons/email.jpg';
		if ($burstObj->status == "sent") {
			$burstStatusTag = "burst-status-sent";
			$icon = 'img/largeicons/checked.jpg';
			$tools = action_links(
				action_link(_L(" Report"), "layout", 'reportsdd.php?id=' . $id),
				action_link(_L(" Edit"), "pencil", 'pdfedit.php?id=' . $id),
				action_link(_L(" Download"), "pdficon_16", $this->burstsURL . '/' . $id . '/pdf'),
				action_link(_L(" Delete"), "cross", '#', "deleteBurst(" . $id . ");")
			);
		} else {

			$tools = action_links(
				action_link(_L(" Edit"), "pencil", 'pdfedit.php?id=' . $id),
				action_link(_L(" Send Email"), "email_go", 'pdfsendmail.php?id=' . $id),
				action_link(_L(" Download"), "pdficon_16", $this->burstsURL . '/' . $id . '/pdf'),
				action_link(_L(" Delete"), "cross", '#', "deleteBurst(" . $id . ");")
			);
		}


		$content = '<span data-burst-id="'.$id.'">';
		$content .= _L('File') . ': &nbsp;<a href="'.$this->burstsURL . '/' . $id. '/pdf" title="' . _L('Download File') . ': '.$fileName.'">' .$fileName. '</a><br>';
		$content .= _L('Size') . ': &nbsp;<strong>' . $formattedFileSize . '</strong><br>';
		$content .= _L('Upload Date') . ': &nbsp;<strong>' .$uploadDate.'</strong><br>';
		$content .= _L('Status') . ': &nbsp;<span class="' . $burstStatusTag . '">' . ucwords($status) . '</span></span>';

		$arrItem = array(
			"itemid" => $id,
			"defaultlink" => $defaultlink,
			"icon" => $icon,
			"title" => $title,
			"content" => $content,
			"tools" => $tools
		);
		return $arrItem;
	}

	public function deleteAjaxResponse($id) {
		$response = $this->csApi->deleteBurst($id);
		header('Content-Type: application/json');
		echo json_encode($response);
		exit();
	}

	public function setDisplayPagingDetails() {
		if ($this->feedData) {
			$this->total = count($this->feedData);
		}

		$this->numPages 	= ceil($this->total / $this->pagingLimit);
		$this->curPage 		= ceil($this->pagingStart / $this->pagingLimit) + 1;
		$this->displayEnd 	= ($this->pagingStart + $this->pagingLimit) > $this->total ? $this->total : ($this->pagingStart + $this->pagingLimit);
		$this->displayStart = ($this->total) ? $this->pagingStart + 1 : 0;
	}

	public function setPagingStart($pagestart) {
		$this->pagingStart = 0 + $pagestart;
	}

	public function getAuthOrgKeys() {
		return Organization::getAuthorizedOrgKeys();
	}


	function sorterOf($key) {
		return function ($a, $b) use ($key) {
			switch ($key) {
				case "status":
					return strnatcmp($a->status, $b->status);
				case "date":
					return strnatcmp($a->uploadTimestampMs, $b->uploadTimestampMs);
				case "name":
					return strnatcmp($a->name, $b->name);
			}
		};
	}

}

// Initialize PdfManager and render page
// ================================================================
executePage(new PdfManager($csApi));

?>
