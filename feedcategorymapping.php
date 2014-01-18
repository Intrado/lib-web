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

require_once('inc/common.inc.php');
require_once('inc/form.inc.php');
require_once('inc/table.inc.php');
require_once('inc/html.inc.php');
require_once('inc/utils.inc.php');
require_once('inc/securityhelper.inc.php');

// DBMO stuff
require_once('obj/FeedCategory.obj.php');
require_once('inc/DBMappedObject.php');

// Form stuff
require_once('obj/Form.obj.php');
require_once('obj/FormItem.obj.php');
require_once('inc/formatters.inc.php');
require_once('obj/Validator.obj.php');

// Page object stuff
require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

// API stuff
require_once('obj/CmaApiClient.obj.php');


class FeedCategoryMapping extends PageForm {

	private $cmaApi;
	private $formName = 'feedcategorymapping';
	private $pageNav = 'admin:settings';
	private $pageTitle;

	public $formdata;
	public $helpsteps;

	private $feedId;
	private $feedCategory;

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
		return(getSystemSetting("_hasfeed") && $USER->authorize('managesystem') && (intval(getCustomerSystemSetting('_cmaappid') > 0)));
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
		$this->feedCategory = DBFind('FeedCategory', 'FROM `feedcategory` WHERE NOT `deleted` AND `id` = ?;', false, array($this->feedId));
		if (! is_object($this->feedCategory)) {

			// error! deleted feedcategory? nonexistent?
			unset($_SESSION['feedid']);
			redirect('editfeedcategory.php');
		}

		// Fetch CMA Categories; convert array of objects into id=name associative array
		$rawCmaCategories = $this->cmaApi->getCategories();
		if (is_array($rawCmaCategories) && count($rawCmaCategories)) {
			foreach ($rawCmaCategories as $cmacat) {
				$this->cmaCategories[$cmacat->id] = $cmacat->name;
			}
		}

		// fetch kona feed category map for this feed id
		$rawData = QuickQueryMultiRow("SELECT `cmacategoryid` FROM `cmafeedcategory` WHERE `feedcategoryid` = '{$this->feedId}'", true);
		if (is_array($rawData) && count($rawData)) {
			foreach ($rawData as $row) {

				// Otherwise mark this one as being currently selected for this kona feed
				$this->cmaCategoriesMapped[] = $row['cmacategoryid'];
			}
		}
	}

	// @override
	public function afterLoad() {

		$this->setFormData();
		$this->form = new Form($this->formName, $this->formdata, $this->helpsteps, array( submit_button(_L('Map Feed'), 'mapfeed', 'pictos/p1/16/59')));
		$this->form->ajaxsubmit = true;

		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			$postData = $this->form->getData();

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

			$notice = '';

			// 4) Drop the drops
			if (count($categoryDrops)) {
				$qparams = array($this->feedId);
				$qparams = array_merge($qparams, $categoryDrops);
				$qcats = repeatWithSeparator('?', ',', count($categoryDrops));
				Query("DELETE FROM `cmafeedcategory` WHERE `feedcategoryid` = ? AND `cmacategoryid` IN ({$qcats});", false, $qparams);
				$notice .= sprintf(_L('Dropped %d old/invalid CMA category mappings'), count($categoryDrops));
			}

			// 5) Add the adds
			if (count($categoryAdds)) {
				foreach ($categoryAdds as $id) {
					Query("INSERT INTO `cmafeedcategory` SET `feedcategoryid` = ?, `cmacategoryid` = ?;", false, array($this->feedId, $id));
				}
				if (strlen($notice)) $notice .= ' ';
				$notice .= sprintf(_L('Added %d new CMA category mappings'), count($categoryAdds));
			}

			// 6) Wrap-up
			if (strlen($notice)) notice($notice);
			unset($_SESSION['feedid']);

			if ($this->form->isAjaxSubmit()) {
				$this->form->sendTo('editfeedcategory.php');
			} else {
				redirect('editfeedcategory.php');
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
					"html" => '<span style="font-size:14px; vertical-align:sub; font-weight:bold;">' . $this->feedCategory->name . '</span>'
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