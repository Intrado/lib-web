<?

/**
 * Created by IntelliJ IDEA.
 * User: htosun
 * Date: 3/24/15
 * Time: 2:02 PM
 *
 * A Utility class for adding recipient mode
 *
 * call addToForm() to add form data
 * call addJavaScript() to add javascript support for enabling disabling categories section. NOTE: this should be called in a script tag
 * call resetListCategories() to reset categories from post data
 * call addHelpText to add help step
 *
 *
 * It requires following includes
 * require_once("obj/PeopleList.obj.php");
 * require_once("obj/RestrictedValues.fi.php");
 * require_once("obj/ListGuardianCategory.obj.php");
 * require_once("obj/ListRecipientMode.php");
 *
 */
class ListRecipientMode {

	const RECIPIENT_MODE_ELEMENT = "recipientmode";
	const RECIPIENT_CATEGORIES_ELEMENT = "recipientcategories";

	const HELP_TEXT = 'Select the recipients that will be contacted on behalf of this list and select categories to restrict by';
	var $modeTips;
	var $csApi = null;
	var $helpStep;
	var $listId = null;
	var $recipientMode;
	var $enabled = 0;

	function __construct($csApi, $helpStep = 0, $maxGuardian = 0, $listId = null, $currentRecipientMode = null) {
		$this->csApi = $csApi;
		$this->helpStep = $helpStep;
		$this->listId = $listId;
		$this->recipientMode = $currentRecipientMode ? $currentRecipientMode : PeopleList::$RECIPIENTMODE_MAP[3];
		$this->modeTips = array(
			PeopleList::$RECIPIENTMODE_MAP[1] => 'Typically students or staff records',
			PeopleList::$RECIPIENTMODE_MAP[2] => 'Typically parents or guardians',
			PeopleList::$RECIPIENTMODE_MAP[3] => 'Both contact records and/or their associated guardian records'
		);
		if ($maxGuardian > 0) {
			$this->enabled = 1;
		}
	}

	/**
	 * checks to see if guardian mode enabled or not based on maxguardian property
	 * @return int 0: disabled, 1:enabled
	 */
	function isEnabled() {
		return $this->enabled;
	}

	/**
	 * Add recipient mode support to the form data
	 *
	 * @param $formData form data
	 */
	function addToForm(&$formData) {
		if (!$this->enabled) return;

		//get guardian categories
		$categoryList = $this->csApi->getGuardianCategoryList();
		$categories = array();
		foreach ($categoryList as $c) {
			$categories[$c->id] = $c->name;
		}
		$selectedCategories = array();
		if ($this->listId) {
			$selectedCategories = ListGuardianCategory::getGuardiansForList($this->listId);
		}

		$formData[] = _L('Target Recipients - You can select contact records and/or their associated guardian records');
		$formData[self::RECIPIENT_MODE_ELEMENT] = array(
			"label" => _L("Target Recipients"),
			"fieldhelp" => _L('Select the recipients that will be contacted on behalf of this list'),
			"value" => $this->recipientMode,
			"validators" => array(), "control" => array("RadioButton", "values" => array(
				PeopleList::$RECIPIENTMODE_MAP[1] => _L("Contacts"),
				PeopleList::$RECIPIENTMODE_MAP[2] => _L("Associated Guardians"),
				PeopleList::$RECIPIENTMODE_MAP[3] => _L("Both")), "hover" => $this->modeTips),
			"helpstep" => $this->helpStep
		);
		$formData[self::RECIPIENT_CATEGORIES_ELEMENT] = array(
			"label" => _L("Guardian Category Restriction"),
			"fieldhelp" => _L('Select categories to restrict by. When no category is checked, it will be unrestricted'),
			"value" => $selectedCategories,
			"validators" => array(
				array("ValInArray", "values" => array_keys($categories))
			),
			"control" => array("RestrictedValues", "values" => $categories, "label" => _L("Restrict to these categories:")),
			"helpstep" => $this->helpStep
		);

	}


	/**
	 * Extract recipient mode from post data
	 *
	 * @param $postdata post data
	 * @return int recipient mode
	 */
	function getRecipientModeFromPostData($postdata) {
		$mode = PeopleList::$RECIPIENTMODE_MAP[3]; //self
		//1=> self, 2=>guardian 3=> selfAndGuardian
		if (array_key_exists(self::RECIPIENT_MODE_ELEMENT, $postdata) && $postdata[self::RECIPIENT_MODE_ELEMENT]) {
			$mode = $postdata[self::RECIPIENT_MODE_ELEMENT];
		}
		$this->recipientMode = $mode;
		return $this->recipientMode;
	}

	/**
	 * Add java script for this form
	 * @param $formName form name
	 * @return string javascript
	 */
	function addJavaScript($formName) {
		if (!$this->enabled) return "";
		else return "
		function toggleCategory(){
			if($('" . $formName . "_" . self::RECIPIENT_MODE_ELEMENT . "-1').checked)
				$('" . $formName . "_" . self::RECIPIENT_CATEGORIES_ELEMENT . "_fieldarea').hide();
			else
				$('" . $formName . "_" . self::RECIPIENT_CATEGORIES_ELEMENT . "_fieldarea').show();
		}
		document.observe('dom:loaded', function () {
			//first check the initial value
			toggleCategory();
			$('" . $formName . "_" . self::RECIPIENT_MODE_ELEMENT . "').observe('click', function(e) {
				toggleCategory();
			});
		});

	";
	}

	/**
	 * Add help step
	 * @param $helpText
	 */
	function addHelpText(&$helpText) {
		if (!$this->enabled) return;
		if ($this->helpStep) {
			array_splice($helpText, $this->helpStep - 1, 0, self::HELP_TEXT);
		}
	}

	/**
	 * Reset recipient guardian categories based on post data
	 * @param $postdata post data
	 * @param in $listId list id.
	 */
	function resetListCategories($postdata, $listId = null) {
		if (!$this->enabled) return;
		$listId = $listId == null ? $this->$listId : $listId;
		$categories = $postdata[self::RECIPIENT_CATEGORIES_ELEMENT] && $this->recipientMode != PeopleList::$RECIPIENTMODE_MAP[1] ? $postdata[self::RECIPIENT_CATEGORIES_ELEMENT] : array();
		ListGuardianCategory::resetListGuardianCategories($listId, $categories);
	}


}


?>