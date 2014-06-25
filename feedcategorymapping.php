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
	private $pageNav = 'admin:settings';

	private $feedId;
	private $feedCategory;

	private $cmaCategories = array();
	private $cmaCategoriesMapped = array();

	public function __construct($cmaApi) {
		$this->cmaApi = $cmaApi;
		parent::__construct();
	}

	// @override
	public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		global $USER;
		return(getSystemSetting("_hasfeed") && $USER->authorize('managesystem') && getSystemSetting('_cmaappid'));
	}

	// @override
	public function initialize() {
		// override some options set in PageBase
		$this->options["page"]	= $this->pageNav;
		$this->options["title"] = _L('Map Feed to CMA Category(s)');
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
		$this->options["windowTitle"] = $this->feedCategory->name;

		// Fetch CMA Categories; convert array of objects into id=name associative array
		$rawCmaCategories = $this->cmaApi->getCategories();
		if (is_array($rawCmaCategories) && count($rawCmaCategories)) {
			foreach ($rawCmaCategories as $cmacat) {
				$this->cmaCategories[$cmacat->id] = $cmacat->name;
			}
		}

		// sort Categories A-Z (API response returns random/different order each request)
		asort($this->cmaCategories);

		// fetch kona feed category map for this feed id
		$rawData = QuickQueryMultiRow("SELECT `cmacategoryid` FROM `cmafeedcategory` WHERE `feedcategoryid` = '{$this->feedId}'", true);
		if (is_array($rawData) && count($rawData)) {
			foreach ($rawData as $row) {

				// Otherwise mark this one as being currently selected for this kona feed
				$this->cmaCategoriesMapped[] = $row['cmacategoryid'];
			}
		}

		// Make the edit FORM
		$this->form = $this->factoryFormCmaFeedCategories();
	}

	// @override
	public function afterLoad() {

		$this->form->handleRequest();

		if ($this->form->getSubmit()) {

			// run server-side validation...
			if (($errors = $this->form->validate()) !== false) {

				// not good: there was a server-side validation error if we got here...
				return;
			}
				
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
					$categoryDrops[] = intval($id);
				}
			}	

			// 3) Add any categories not currently mapped that are in the postedCategories
			foreach ($postedCategories as $id) {
				if (! in_array($id, $this->cmaCategoriesMapped)) {
					$categoryAdds[] = intval($id);
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
					Query("INSERT INTO cmafeedcategory SET feedcategoryid = ?, cmacategoryid = ?;", false, array($this->feedId, $id));
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

	public function factoryFormCmaFeedCategories() {

		// define help steps used in form
		$helpsteps = array(
			_L('By adding one or more Custom Mobile Apps categories to this Feed category, you can enable mobile device delivery.<br>'.
				'Messages will be sent to the mobile devices via "Push" notification for any CMA users which have subscribed to the categories selected here.'),
		);

		$formdata = array(
			"cmacategories" => array(
				"label" => _L('CMA Category(s)'),
				"fieldhelp" => _L('Select one or more CMA Categories to map this feed to'),
				"value" => $this->cmaCategoriesMapped,
				"validators" => array(),
				"control" => array('MultiCheckBox', 'values' => $this->cmaCategories),
				"helpstep" => 1
			)
		);

		$form = new Form($this->formName, $formdata, $helpsteps, array( submit_button(_L('Done'), 'mapfeed', 'tick')));
		$form->ajaxsubmit = true;

		return($form);
	}
}

// Initialize FeedCategoryMapping and render page
// ================================================================
global $SETTINGS;
$cmaApi = new CmaApiClient(
	new ApiClient($SETTINGS['cmaserver']['apiurl']),
	getCustomerSystemSetting("_cmaappid") ? getCustomerSystemSetting("_cmaappid") : ''
);
executePage(new FeedCategoryMapping($cmaApi));

?>
