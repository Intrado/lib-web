<?

require_once('inc/common.inc.php');
require_once('inc/securityhelper.inc.php');
require_once('inc/table.inc.php');
require_once('inc/html.inc.php');
require_once('inc/utils.inc.php');
require_once('obj/Validator.obj.php');
require_once('obj/Form.obj.php');
require_once('obj/FormItem.obj.php');

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

/**
 * Guardian Category Editor
 */
class GuardianCategoryEditPage extends PageForm {

	public static $NO_ACCESS = "-- Select a Profile --";
	protected $guardianCategory = null; //current guardian category 
	protected $categoryId = null; //category id
	protected $error = '';
	protected $csApi = null;
	//existing profiles
	protected $profiles = array();

	/**
	 * Constructor
	 *
	 * Use dependency injection to make those external things needed separately testable.
	 *
	 * @param object $csApi An instance of CommsuiteApiClient
	 */
	public function __construct($csApi) {
		$this->csApi = $csApi;
		$this->options['validators'] = array('ValDupeCategoryName');
		parent::__construct($this->options);
	}

	public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		global $USER;
		return $USER->authorize('manageprofile');
	}

	public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		if (isset($request['id']) && $request['id'] == "new") {
			//reset previous id.
			unset($session['categoryid']);
			redirect();
		} else if (isset($request['id']) && intval($request['id'])) {
			$session['categoryid'] = intval($request['id']);
			// .. then redirect back to ourselves to clean up the URL
			redirect();
		} else if (isset($session['categoryid'])) {
			$this->categoryId = $session['categoryid'];
		} else {
			$this->categoryId = null;
		}
	}

	public function load(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		$this->profiles = $this->csApi->getProfileList();
		// If we're editing an existing one, get its data
		if (!is_null($this->categoryId)) {
			$this->guardianCategory = $this->csApi->getGuardianCategory($this->categoryId);
		} else {
			$this->guardianCategory = new stdClass;
			$this->guardianCategory->name = '';
			$this->guardianCategory->sequence = 0;
			$this->guardianCategory->profileId = 0;
		}

		if (!is_null($this->categoryId) && isset($session['categoryreload'])) {
			unset($session['categoryreload']);
			$this->error = _L("This record was changed in another window or session during the time you've had it open. Please review the current data. You may need to redo your changes and resubmit.");
		}
		// Make the edit FORM
		$this->form = $this->pageForm();
	}

	public function afterLoad() {
		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();
		// If the form was submitted...
		if ($this->form->getSubmit()) {
			if (!is_null($this->categoryId) && $this->form->checkForDataChange()) {
				$_SESSION['categoryreload'] = true;
				redirect("?id={$this->categoryId}");
			}
			// Check for validation errors
			$action = (is_null($this->categoryId)) ? 'created' : 'updated';
			if (($errors = $this->form->validate()) === false) {
				//get data
				$postdata = $this->form->getData();
				$name = $postdata['name'];
				$profileId = $postdata['profile'];
				$result = $this->csApi->setGuardianCategory($this->categoryId, $name, $profileId == "0" ? null : $profileId, $this->guardianCategory->sequence);
				if ($result) {
					unset($_SESSION['categoryid']);
					notice(_L("The Guardian Category was successfully {$action}."));
					redirect('guardiancategorymanager.php');
				} else {
					//NOT: a generic error is set outside if condition
					unset($_SESSION['categoryid']);
				}
			}
			$this->error = _L("The Guardian Category could not be {$action}. Please try again later.");
		}
	}

	public function render() {
		// define main:subnav tab settings
		$this->options["page"] = 'admin:settings';
		$this->options['windowTitle'] = _L('Guardian Category');
		$this->options['title'] = _L('Edit Guardian Category: %1$s ', escapehtml(($this->categoryId && is_object($this->guardianCategory)) ? $this->guardianCategory->name : "New Guardian Category"));

		if (strlen($this->error)) {
			//TODO:should we create modal display? 
			$html = $this->error;
		}
		//category not created or updated.?
		else if (!(is_null($this->categoryId) || is_object($this->guardianCategory))) {
			unset($_SESSION['categoryid']);
			$html = _L('The Guardian Category you have requested could not be found. It may not exist or your account does not have permission to view it.') . "<br/>\n";
		} else {
			$html = $this->form->render();
		}

		return($html);
	}

	/**
	 * Method to create form object content for this page, including guide and mouseover help.
	 *
	 * @return object Form
	 */
	public function pageForm() {
		$profileNames = array("0" => GuardianCategoryEditPage::$NO_ACCESS);
		$validSelections = array();
		foreach ($this->profiles as $p) {
			if ($p->type === "guardian") {
				$profileNames[$p->id] = $p->name;
				$validSelections[] = $p->id;
			}
		}
		$formdata = array(
			"name" => array(
				"label" => _L('Name'),
				"fieldhelp" => _L('The name for this category.'),
				"value" => $this->guardianCategory->name,
				"validators" => array(
					array("ValRequired"),
					array("ValLength", "max" => 50),
					array("ValDupeCategoryName", "categoryid" => $this->categoryId)
				),
				"control" => array("TextField", "size" => "20", "maxsize" => 50),
				"helpstep" => 1
			),
			"profile" => array(
				"label" => _L('Guardian Profile'),
				"fieldhelp" => _L('The the profile for this category.'),
				"value" => isset($this->guardianCategory->profileId) ? $this->guardianCategory->profileId : "0",
				"validators" => array(array("ValInArray", "values" => $validSelections),),
				"control" => array("SelectMenu", "values" => $profileNames),
				"helpstep" => 2
			)
		);

		$helpsteps = array(
			_L('Enter a name for Guardian Category.'),
			_L('Select a Guardian Profile for this category. In order to create a Guardian Profile, navigate to Admin -> Profiles and click on the "Add New Guardian Profile" button.')
		);

		$buttons = array(
			submit_button(_L('Save'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'guardiancategorymanager.php')
		);

		// A new form with some defaults overridden...
		$form = new Form($this->formName, $formdata, $helpsteps, $buttons);
		$form->ajaxsubmit = false;

		return($form);
	}

}

///////////////////////////////////////////////////////////////////////////////
// Custom Form Controls And Validators
////////////////////////////////////////////////////////////////////////////////

class ValDupeCategoryName extends Validator {

	var $onlyserverside = true;

	function validate($value, $args) {
		// unique name within same type of profile
		$query = "select count(*) from guardiancategory where name = ? and id != ?";
		$res = QuickQuery($query, false, array($value, $args['categoryid'] + 0));
		if ($res)
			return _L('An Guardian Category with that name already exists. Please choose another');

		return true;
	}

}

// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

executePage(new GuardianCategoryEditPage(isset($csApi) ? $csApi : null));
