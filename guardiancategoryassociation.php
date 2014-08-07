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


require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');

class GuardianCategoryAssociation extends PageBase {

	var $isAjaxRequest = true;
	var $csApi;
	var $pagingStart = 0;
	var $pagingLimit = 100;
	var $total = 0;
	var $numPages;
	var $curPage;
	var $displayEnd;
	var $displayStart;
	var $responseData;
	var $guardianAssociations = array();
	var $categoryid;
	var $category;

	function __construct($csApi) {
		$this->csApi = $csApi;
		parent::__construct();
	}

	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return $USER->authorize('viewcontacts');
	}

	// @override
	public function initialize() {
		$this->options["page"] = 'admin:settings';
	}

	// @override
	public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		$this->setPagingStart((isset($get['pagestart'])) ? $get['pagestart'] : 0);

		if (isset($request['categoryid']) && intval($request['categoryid'])) {
			$session['categoryid'] = intval($request['categoryid']);
		}

		if (isset($session['categoryid'])) {
			$this->categoryid = $session['categoryid'];
		} else {
			$this->categoryid = null;
		}
	}

	// @override
	public function load() {
		if ($this->isAjaxRequest) {
			$this->category = $this->csApi->getGuardianCategory($this->categoryid);
			$this->options["title"] = $this->category->name;
			$this->responseData = $this->csApi->getGuardianCategoryAssoications($this->categoryid, $this->pagingStart, $this->pagingLimit);
			if ($this->responseData) {
				foreach ($this->responseData->associations as $association) {
					$this->guardianAssocations[] = $this->getAssociation($association);
				}
			}
		}
	}

	// @override
	public function afterLoad() {
		if ($this->isAjaxRequest) {
			$this->setDisplayPagingDetails();
		}
	}

	// @override
	public function sendPageOutput() {
		$titles = array("pkey" => "Unique ID", "firstname" => 'First Name', "lastname" => 'LastName', "guardian" => 'Guardian');
		buttons(icon_button(_L("Back"), "fugue/arrow_180", "document.location='guardiancategorymanager.php';"));

		startWindow("Associations");
		if (count($this->guardianAssocations)) {
			?><div style="float: right"><?
			showPageMenu($this->total, $this->displayStart, $this->pagingLimit);
			?></div><div style="clear:both"></div>
			<table width="100%" cellpadding="3" cellspacing="1" class="list"><?
				showTable($this->guardianAssocations, $titles, array("pkey" => "fmt_persontip"));
				?></table><?
			showPageMenu($this->total, $this->displayStart, $this->pagingLimit);
		} else {
			?><div><img src='img/largeicons/information.jpg' /><?= escapehtml(_L("This Guardian category has no associations")) ?></div><?
		}

		endWindow();
		buttons();
	}

	public function getAssociation($person) {
		$arrItem = array("personid" => $person->personId, "pkey" => $person->pkey, "firstname" => $person->firstName, "lastname" => $person->lastName, "guardian" => $person->guardianName);
		return $arrItem;
	}

	public function setDisplayPagingDetails() {
		if ($this->responseData) {
			$this->total = $this->responseData->paging->total;
		}
		$this->numPages = ceil($this->total / $this->pagingLimit);
		$this->curPage = ceil($this->pagingStart / $this->pagingLimit) + 1;

		$this->displayStart = ($this->total) ? $this->pagingStart : 0;
		$this->displayEnd = ($this->pagingStart + $this->pagingLimit) > $this->total ? $this->total : ($this->pagingStart + $this->pagingLimit);
	}

	public function setPagingStart($pagestart) {
		$this->pagingStart = 0 + $pagestart;
	}

}

// Initialize GuardianAssociation and render page
// ================================================================
executePage(new GuardianCategoryAssociation($csApi));
?>
