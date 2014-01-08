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

	var $pageTitle = 'PDF Manager';
	var $pageNav = 'notifications:pdfmanager';
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

	function __construct($csApi) {
		$this->csApi = $csApi;
		parent::__construct();
	}
	
	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return $USER->authorize('canpdfburst');
	}

	// @override
	public function initialize() {
		// override title and page options on PageBase
		$this->options["title"] = $this->pageTitle;
		$this->options["page"]  = $this->pageNav;
	}

	// @override
	public function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {	
		$this->customerURLComponent = customerUrlComponent();
		$this->burstsURL = $this->csApi->getBurstApiUrl();

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
		startWindow(_L('PDF Report Manager'), 'padding: 3px;', false, true);
		$feedButtons = array(icon_button(_L(' Upload New PDF'), "pdficon_16", null, "pdfedit.php"));
		feed($feedButtons, null);
		echo '<script type="text/javascript" src="script/feed.js.php"></script>
		<script type="text/javascript">
			document.observe("dom:loaded", function() {
			feed_applyDefault("'. $_SERVER["REQUEST_URI"] .'","name","pdfmanager");
		});
		</script>';
		endWindow();
	}

	public function burstsAjaxResponse() {
		$data = (object) array(
			'list' 		=> array(),
			'pageinfo' 	=> array()
		);

		if($this->total == 0) {
			$data->list[] = array("itemid" 		=> "",
								  "defaultlink" => "",
								  "icon" 		=> "img/largeicons/information.jpg",
								  "title" 		=> _L("No PDF files available."),
								  "content" 	=> "",
								  "tools" 		=> "");
		} else {
			foreach ($this->feedData as $burstObj) {
				$data->list[] = $this->getBurstListItem($burstObj);
			}
		}

		$data->pageinfo = array($this->numPages,
								$this->pagingLimit,
								$this->curPage, 
								"Showing $this->displayStart - $this->displayEnd of $this->total records on $this->numPages pages &nbsp;"
								);

		header('Content-Type: application/json');
		echo json_encode(!empty($data) ? $data : false);
		exit();
	}

	public function getBurstListItem($burstObj) {
		$id 			= $burstObj->id;
		$defaultlink 	= 'pdfedit.php?id=' . $id;

		$title 			= escapehtml($burstObj->name);
		$fileName 		= escapehtml($burstObj->filename);
		$uploadDate 	= date("M j, Y g:i a", $burstObj->uploadTimestampMs / 1000);
		$fileSize 		= $burstObj->size;
		$status 		= $burstObj->status;

		$tools = action_links (
			action_link(" Edit", "pencil", 'pdfedit.php?id=' . $id),
			action_link(" Send Email", "email_go", 'pdfsendmail.php?id=' . $id),
			action_link(" Download", "pdficon_16", $this->burstsURL . '/' . $id. '/pdf'),
			action_link(" Delete", "cross", '#', "deleteBurst(".$id.");")
		);

		$content = '<span data-burst-id="'.$id.'">';
		$content .= 'File: &nbsp;<a href="'.$this->burstsURL . '/' . $id. '/pdf" title="Download File: '.$fileName.'">' .$fileName. '</a><br>';
		$content .= 'Size: &nbsp;<strong>' . number_format(($fileSize / pow(2,20)), 1, '.', '') . 'MB</strong><br>';
		$content .= 'Upload Date: &nbsp;<strong>' .$uploadDate.'</strong><br>';
		$content .= 'Status: &nbsp;<strong>' .ucwords($status).'</strong></span>';

		$arrItem = array("itemid" 		=> $id,
						  "defaultlink"	=> $defaultlink,
						  "icon" 		=> 'img/pdficon_32.png',
						  "title" 		=> $title,
						  "content" 	=> $content,
						  "tools" 		=> $tools);
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

}

// Initialize PdfManager and render page
// ================================================================
executePage(new PdfManager($csApi));

?>
