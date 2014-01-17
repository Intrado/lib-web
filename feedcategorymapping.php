<?php
/**
 * class FeedCategoryMapping
 *
 * Simple page to map feeds (ex RSS) to a CMA category,
 * mainly used to support Push Notifications
 *
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @mechanic: skelly
 * @date: 1/15/2014
 */

require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once('inc/table.inc.php');
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("inc/securityhelper.inc.php");

require_once('obj/Form.obj.php');
require_once('obj/FormItem.obj.php');
require_once("inc/formatters.inc.php");
require_once("obj/Validator.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

require_once('obj/CmaApiClient.obj.php');


class FeedCategoryMapping extends PageForm {

	private $cmaApi;
	private $formName = 'feedcategorymapping';
	private $pageNav = 'admin:settings';
	private $pageTitle;

	public $formdata;
	public $helpsteps;

	private $feedId;
	private $feedInfo;
	private $rawCmaCategories = array();
	private $cmaCategories = array();
	private $cmaCategoriesMapped = array();

	public function __construct($cmaApi) {
		$this->cmaApi = $cmaApi;
		$this->pageTitle = _L('Map Feed to CMA Category(s)');
		parent::__construct();
	}

	// @override
	public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		global $USER;
		return(getSystemSetting("_hasfeed") && $USER->authorize('managesystem'));
	}

	// @override
	public function initialize() {
		// override some options set in PageBase
		$this->options["page"]	= $this->pageNav;
		$this->options["title"] = $this->pageTitle;
	}

	// @override
	public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		if (isset($get['id']) && intval($get['id'])) {
			$session['feedid'] = intval($get['id']);
			redirect();
		}
		else if (isset($session['feedid'])) {
			$this->feedId = $session['feedid'];
		}
	}

	// @override
	public function load() {

		// Fetch/query FeedCategory data based on $this->feedId, so we can set the Feed Name label in the form, etc
		$res = Query("SELECT * FROM `feedcategory` WHERE `id` = '{$this->feedId}'");
		if (is_object($res)) {
			$this->feedInfo = $res->fetch(PDO::FETCH_ASSOC);
			$res = null;
		}

		// Fetch CMA Categories; convert array of objects into id=name associative array
		$rawCmaCategories = $this->cmaApi->getCategories();
		if (is_array($rawCmaCategories) && count($rawCmaCategories)) {
			foreach ($rawCmaCategories as $cmacat) {
				$this->cmaCategories[$cmacat->id] = $cmacat->name;
			}
		}

		// fetch kona feed category map for this feed id
		$rawData = QuickQueryMultiRow("SELECT `cmacategory` FROM `feedcat2cmacatmap` WHERE `fk_feedcategory` = '{$this->feedId}'", true);
		if (is_array($rawData) && count($rawData)) {
			foreach ($rawData as $row) {

				// Otherwise mark this one as being currently selected for this kona feed
				$this->cmaCategoriesMapped[] = $row['cmacategory'];
			}
		}
	}

	// @override
	public function afterLoad() {

		$this->setFormData();
		$this->form = new Form($this->formName, $this->formdata, $this->helpsteps, array( submit_button(_L('Map Feed'), 'mapfeed', 'pictos/p1/16/59')));
		$this->form->ajaxsubmit = false;

		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			$postData = $this->form->getData();

			// TODO: handle response
			$categoryDrops = $categoryAdds = Array();

			// 1) Make sure all selected categories are in the list of available CMS categories at the time of submission
			$postedCategories = array();
			foreach ($postData['cmacategories'] as $id) {
				if (isset($this->cmaCategories[$id])) {
					$postedCategories[] = $id;
				}
			}

			// 2) Drop any categories currently mapped that are NOT in the postedCategories
			foreach ($this->cmaCategoriesMapped as $id) {
				if (! in_array($id, $postedCategories)) {
					$categoryDrops[] = $id;
				}
			}	

			// 3) Add any categories not currently mapped that are in the postedCategories
			foreach ($postedCategories as $id) {
				if (! in_array($id, $this->cmaCategoriesMapped)) {
					$categoryAdds[] = $id;
				}
			}

			// TODO - DELETE FROM feedcat2cmacatmap WHERE fk_feedcategory = $this->feedId AND cmacategory IN ($categoryDrops);
			// TODO - INSERT INTO feedcat2cmacatmap SET fk_feedcategory = $this->feedId, cmacategory = $categoryAdds[] -- foreach!

			if ($this->form->isAjaxSubmit()) {
				unset($_SESSION['feedid']);
				$this->form->sendTo("editfeedcategory.php");
			} else {
				redirect("editfeedcategory.php");
			}
		}
	}

	public function setFormData() {

		// define help steps used in form
		$this->helpsteps = array(
			_L('The name of the feed'),
			_L('Select one or more CMA Categories to map this feed to'),
		);

		$this->formdata = array(
			_L('Select one or more CMA Categories to map this feed to'),
			"feedname" => array(
				"label" => _L('Feed Name'),
				'control' => array(
					"FormHtml",
					"html" => '<span style="font-size:14px; vertical-align:sub; font-weight:bold;">' . $this->feedInfo['name'] . '</span>'
				),
				"helpstep" => 1
			),
			"cmacategories" => array(
				"label" => _L('CMA Categories'),
				"fieldhelp" => $this->helpsteps[1],
				"value" => $this->cmaCategoriesMapped,
				"validators" => array(),
				"control" => array('MultiCheckBox', 'values' => $this->cmaCategories),
				"helpstep" => 2
			),
		);
	}
}

// Initialize FeedCategoryMapping and render page
// ================================================================

$cmaApi = new CmaApiClient(
	array(
		// TODO: use CMA API url from $SETTINGS once CMA API ready
		// 'apiClient' => new ApiClient($SETTINGS['cmaserver']['apiurl']),

		// use CMA api stub until CMA API ready
		'apiClient' => new ApiClient("https://{$_SERVER['SERVER_NAME']}/".customerUrlComponent().'/_cma_api_stub.php'),
		'appId' => getCustomerSystemSetting("_cmaappid") ? getCustomerSystemSetting("_cmaappid") : 1 // TODO: add appropriate logic/handling
	)
);
executePage(new FeedCategoryMapping($cmaApi));

?>
