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

require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

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
	var $sortBy = '';
	var $orderBy = 'uploaddatems desc';
	var $isAjaxRequest = false;
	var $deleteID;
	var $sqlArgs = array();
	var $feedData;
	var $total = 0;
	var $numPages;
	var $curPage;
	var $displayEnd;
	var $displayStart;
	var $custName;
	var $baseCustomerURL;
	var $burstsURL;
	var $curlOptions;

	function PdfManager($options = array()) {
		if (isset($options)) {
			$this->options = $options;
			parent::PageBase($options);
		}
	}

	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return true; //$USER->authorize('canpdfburst');
	}

	// @override
	public function initialize() {
		// override some options on PageBase
		$this->options["title"] = $this->pageTitle;
		$this->options["page"]  = $this->pageNav;

		$this->setCustomerName();
		$this->setBaseCustomerURL();
		$this->setBurstsURL();

		// define common curl options (for both fetch and delete API calls)
		$this->curlOptions = array(
			array('option' => CURLOPT_RETURNTRANSFER, 'value' => 1),
			array('option' => CURLOPT_SSL_VERIFYPEER, 'value' => 0),
			array('option' => CURLOPT_HTTPHEADER, 'value' => array(
											"Accept: application/json",
											"X-Auth-SessionId: " . $_COOKIE[$this->custName . '_session']))
		);

	}

	// @override
	public function beforeLoad($get, $post) {
		$this->isAjaxRequest = isset($get['ajax']); 
		
		// if delete request, execute delete API call and exit, 
		// which upon a successful response (200) will reload pdfmanager.php page (via JS in pdfmanager.js)
		if (isset($post['delete'])) {
			$this->deleteID = $post['id'];
			$deleteURL = $this->burstsURL . '/' . $this->deleteID;
			$deleteCurlOptions = array_merge($this->curlOptions, array(array('option' => CURLOPT_CUSTOMREQUEST, 'value' => 'DELETE')));
			$response = $this->curlRequest($deleteURL, $deleteCurlOptions);
			
			header('Content-Type: application/json');
			echo json_encode($response);
			exit();
		}

		if (isset($get['feed_sortby'])) {
			$this->sortBy = $get['feed_sortby'];
		}

		$this->setPagingStart((isset($get['pagestart'])) ? $get['pagestart'] : 0);
	}

	// @override
	public function load() {
		if ($this->isAjaxRequest) {
			$this->authOrgList 	= Organization::getAuthorizedOrgKeys();

			// fetch all existing burst records
			$this->feedResponse = json_decode($this->curlRequest($this->burstsURL, $this->curlOptions), true);
			$this->feedData = $this->feedResponse['bursts'];
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
		$sortoptions = array(
			"name" => array("icon" => "img/largeicons/tiny20x20/pencil.jpg", "name" => "Filename"),
			"date" => array("icon" => "img/largeicons/tiny20x20/clock.jpg", "name" => "Upload Date")
		);
		feed($feedButtons,$sortoptions);
		echo '<script type="text/javascript" src="script/feed.js.php"></script>
		<script type="text/javascript">
			document.observe("dom:loaded", function() {
			feed_applyDefault("'. $_SERVER["REQUEST_URI"] .'","name","pdfmanager");
		});
		</script>';
		endWindow();

	}

	/*
	 * Description: performs a curl (API) request using the specified $url and $curlOptions
	 * @param $url string - request url; should be full absolute path, ex. https://...
	 * @param $curlOptions ex. array(array('option' => CURLOPT_RETURNTRANSFER, 'value' => 1), array(...) ...)
	 * return JSON response from API call or null? for delete call
	 */
	public function curlRequest($url, $curlOptions) {
		$curl = curl_init($url);
		foreach ($curlOptions as $obj) {
			curl_setopt($curl, $obj['option'], $obj['value']);
		}
		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
 	}

	public function burstsAjaxResponse() {
		if(empty($this->feedData)) {
			$data->list[] = array("itemid" => "",
								  "defaultlink" => "",
								  "icon" => "img/largeicons/information.jpg",
								  "title" => _L("No PDF files available."),
								  "content" => "",
								  "tools" => "");
		} else {
			
			while(!empty($this->feedData)) {
				$item 			= array_shift($this->feedData);
				$itemid 		= $item['id'];
				$defaultlink 	= $this->burstsURL . "/" . $itemid. "/pdf";

				$title 			= escapehtml($item['name']);
				$fileName 		= escapehtml($item['filename']);
				$uploadDate 	= date("M j, Y g:i a", $item['uploaddatems']);
				$fileSize 		= $item['bytes'];
				$status 		= $item['status'];
				
				$icon 			= 'img/pdficon_32.png';

				// if (userOwns("messagegroup", $itemid)) { //TODO: add proper privilege checks for PDFs
				if (true) {	
					$tools = action_links (
						action_link(" Edit", "pencil", 'pdfedit.php?id=' . $itemid),
						action_link(" Send Email", "email_go", $this->burstsURL . "/" . $itemid. "/send"),
						action_link(" Download", "disk", $this->burstsURL . "/" . $itemid. "/pdf"),
						action_link(" Delete", "cross", '#', "deleteBurst('".$this->burstsURL."', ".$itemid.");")
						// action_link(" Delete", "cross", 'pdfmanager.php?deleteid=' . $itemid, "return confirmDelete();")
					);
				} 
				
				$content = '<span>';
				$content .= 'File: &nbsp;<strong>' .$fileName. '</strong><br>';
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

			$data->pageinfo = array($this->numPages,
									$this->pagingLimit,
									$this->curPage, 
									"Showing $this->displayStart - $this->displayEnd of $this->total records on $this->numPages pages &nbsp;"
									);

			header('Content-Type: application/json');
			echo json_encode(!empty($data) ? $data : false);
			exit();
		}
	}

	public function setCustomerName() {
		// scrape customer 'name' out of the URL (for use in 'baseCustomerURL')	
		$uriParts 	= explode('/', $_SERVER['REQUEST_URI']); // ex /custname/...
		$this->custName = $uriParts[1];
	}

	public function setBaseCustomerURL() {
		$this->baseCustomerURL = (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '/' . $this->custName;
	}

	public function setBurstsURL() {
		global $USER;
		// burst API url to fetch all bursts for a given user
		$this->burstsURL = $this->baseCustomerURL . '/api/2/users/' . $USER->id . '/bursts';
	}

	public function setDisplayPagingDetails() {
		$this->total = count($this->feedData);

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