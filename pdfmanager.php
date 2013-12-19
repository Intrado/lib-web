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

require_once("obj/FieldMap.obj.php");
require_once("inc/feed.inc.php");

// require_once("obj/Form.obj.php");
// require_once("obj/FormItem.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
// require_once('obj/PageForm.obj.php');

require_once('obj/APIClient.obj.php');
require_once('obj/BurstAPIClient.obj.php');

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
	var $custName;
	var $burstsURL;
	var $burstAPIClient;

	function PdfManager($options = array()) {
		if (isset($options)) {
			$this->options = $options;
			parent::PageBase($options);
		}
	}

	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return $USER->authorize('canpdfburst');
	}

	// @override
	public function initialize() {
		global $USER;

		// override some options on PageBase
		$this->options["title"] = $this->pageTitle;
		$this->options["page"]  = $this->pageNav;

		// scrape customer 'name' out of the URL (for use in 'BurstAPIClient')	
		$uriParts 	= explode('/', $_SERVER['REQUEST_URI']); // ex /custname/...
		$this->custName = $uriParts[1];

		// args array to pass to BurstAPIClient contstructor
		$apiClientArgs = array(
			'apiHostname' 	=> $_SERVER['SERVER_NAME'],
			'apiCustomer' 	=> $this->custName,
			'apiUser'		=> $USER->id,
			'apiAuth'		=> $_COOKIE[$this->custName . '_session']
		);

		// create new instance of BurstAPIClient for use in burst API curl calls 
		$this->burstAPIClient = new BurstAPIClient($apiClientArgs);
		$this->burstsURL = $this->burstAPIClient->getAPIURL();
	}

	// @override
	public function beforeLoad($get, $post) { 
		// if delete request, execute delete API call and exit, 
		// which upon a successful response (200) will reload pdfmanager.php page (via JS in pdfmanager.js)
		if (isset($post['delete'])) {
			$response = $this->burstAPIClient->deleteBurst($post['id']);
			
			header('Content-Type: application/json');
			echo json_encode($response);
			exit();
		}

		$this->isAjaxRequest = isset($get['ajax']);
		$this->setPagingStart((isset($get['pagestart'])) ? $get['pagestart'] : 0);
	}

	// @override
	public function load() {
		if ($this->isAjaxRequest) {
			$this->authOrgList 	= Organization::getAuthorizedOrgKeys();

			// fetch all existing burst records
			$this->feedResponse = $this->burstAPIClient->getBurstList($this->pagingStart, $this->pagingLimit);
			
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
		if($this->total == 0) {
			$data->list[] = array("itemid" => "",
								  "defaultlink" => "",
								  "icon" => "img/largeicons/information.jpg",
								  "title" => _L("No PDF files available."),
								  "content" => "",
								  "tools" => "");
		} else {
			while(!empty($this->feedData)) {
				$item 			= array_shift($this->feedData);
				$itemid 		= $item->id;
				$defaultlink 	= 'pdfedit.php?id=' . $itemid;

				$title 			= escapehtml($item->name);
				$fileName 		= escapehtml($item->filename);
				$uploadDate 	= date("M j, Y g:i a", $item->uploadTimestampMs / 1000);
				$fileSize 		= $item->bytes;
				$status 		= $item->status;
				
				$icon 			= 'img/pdficon_32.png';

				// if (userOwns("messagegroup", $itemid)) { //TODO: add proper privilege checks for PDFs
				if (true) {	
					$tools = action_links (
						action_link(" Edit", "pencil", 'pdfedit.php?id=' . $itemid),
						action_link(" Send Email", "email_go", $this->burstsURL . "/" . $itemid. "/send"),
						action_link(" Delete", "cross", '#', "deleteBurst(".$itemid.");")
					);
				} 

				$content = '<span data-burst-id="'.$itemid.'">';
				$content .= 'File: &nbsp;<a href="'.$this->burstsURL . '/' . $itemid. '/pdf" title="Download File: '.$fileName.'">' .$fileName. '</a><br>';
				$content .= 'Size: &nbsp;<strong>' . number_format(($fileSize / pow(2,20)), 1, '.', '') . 'MB</strong><br>';
				$content .= 'Upload Date: &nbsp;<strong>' .$uploadDate.'</strong><br>';
				$content .= 'Status: &nbsp;<strong>' .ucwords($status).'</strong></span>';

				$data->list[] = array("itemid" 			=> $itemid,
									  "defaultlink"		=> $defaultlink,
									  "icon" 			=> $icon,
									  "title" 			=> $title,
									  "content" 		=> $content,
									  "tools" 			=> $tools,
									  "publishmessage" 	=> null);
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

}

// Initialize PdfManager and execute (render final page)
// ================================================================
executePage(new PdfManager());

?>