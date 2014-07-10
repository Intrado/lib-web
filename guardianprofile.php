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
 * Guardian Profile Editor
 */
class GuardianProfilePage extends PageForm {

	protected $profile = null; //current profile 
	protected $profileId = null; //profile id
	protected $error = '';
	protected $csApi = null;

	/**
	 * Constructor
	 *
	 * Use dependency injection to make those external things needed separately testable.
	 *
	 * @param object $csApi An instance of CommsuiteApiClient
	 */
	public function __construct($csApi) {
		$this->csApi = $csApi;
		$this->options['validators'] = array('ValDupeProfileName');
		parent::__construct($this->options);
	}

	public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		global $USER;
		return $USER->authorize('manageprofile');
	}

	public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		if (isset($request['id']) && $request['id'] == "new") {
			//reset previous id.
			unset($session['profileid']);
			redirect();
		} else if (isset($request['id']) && intval($request['id'])) {
			$session['profileid'] = intval($request['id']);
			// .. then redirect back to ourselves to clean up the URL
			redirect();
		} else if (isset($session['profileid'])) {
			$this->profileId = $session['profileid'];
		} else {
			$this->profileId = null;
		}
	}

	public function load(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {

		// If we're editing an existing one, get its data
		if (!is_null($this->profileId)) {
			$this->profile = $this->csApi->getProfile($this->profileId);
		} else {
			$this->profile = new stdClass;
			$this->profile->name = '';
			$this->profile->description = '';
			$this->profile->type = 'guardian';
			$permission = new stdClass;
			$permission->name = "infocenter";
			$permission->value = 0;
			$this->profile->permissions = array($permission);
		}

		if (!is_null($this->profileId) && isset($session['profilereload'])) {
			unset($session['profilereload']);
			$this->error = _L("This record was changed in another window or session during the time you've had it open. Please review the current data. You may need to redo your changes and resubmit.");
		}
		// Make the edit FORM
		$this->form = $this->factoryProfileForm();
	}

	public function afterLoad() {
		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();
		// If the form was submitted...
		if ($this->form->getSubmit()) {
			if (!is_null($this->profileId) && $this->form->checkForDataChange()) {
				$_SESSION['profilereload'] = true;
				redirect("?id={$this->profileId}");
			}
			// Check for validation errors
			$action = (is_null($this->profileId)) ? 'created' : 'updated';
			if (($errors = $this->form->validate()) === false) {
				//get data
				$postdata = $this->form->getData();
				$name = $postdata['name'];
				$description = $postdata['description'];
				$type = "guardian";
				$permissions = array();
				$infocenter = (bool) $postdata['infocenter'];
				$permissions[] = array("name" => "infocenter", "value" => $infocenter ? "1" : "0");
				$result = $this->csApi->setProfile($this->profileId, $name, $description, $type, $permissions);
				if ($result) {
					unset($_SESSION['profileid']);
					notice(_L("The profile was successfully {$action}."));
					redirect('profiles.php');
				} else {
					//NOTE:error is set outside the if condition
					unset($_SESSION['profileid']);
				}
			}
			$this->error = _L("The profile could not be {$action}. Please try again later.");
		}
	}

	public function render() {
		// define main:subnav tab settings
		$this->options["page"] = 'admin:profiles';
		$this->options['windowTitle'] = _L('Guardian Profile');
		$this->options['title'] = _L('Edit Guardian Profile: %1$s ', escapehtml(($this->profileId && is_object($this->profile)) ? $this->profile->name : "New Guardian Profile"));

		if (strlen($this->error)) {
			$html = $this->error;
		} else if (!(is_null($this->profileId) || is_object($this->profile))) {
			unset($_SESSION['profileid']);
			$html = _L('The profile you have requested could not be found. It may not exist or your account does not have permission to view it.') . "<br/>\n";
		} else {
			$html = $this->form->render();
		}
		return($html);
	}

	/**
	 *  Check to see if given profile has a specific permission
	 * @param Access $profile access profile
	 * @param string $permission permission
	 * @param boolean $default default value
	 * @return boolean 
	 */
	public function hasPermission($profile, $permission, $default = false) {
		$permission = $this->getPermissionFromProfile($profile, $permission);
		return $permission ? $permission->value : $default;
	}

	/**
	 *  Return permission from access profile
	 * @param Access $profile access profile
	 * @param string $permission permission
	 * @return permission
	 */
	public function getPermissionFromProfile($profile, $permission) {
		if (is_null($profile) || !isset($profile->permissions)) {
			return false;
		}
		foreach ($profile->permissions as $p) {
			if ($p->name == $permission) {
				return $p;
			}
		}
	}

	/**
	 * Method to create form object content for this page, including guide and mouseover help.
	 *
	 * @return object Form
	 */
	public function factoryProfileForm() {
		$infocenter = $this->hasPermission($this->profile, "infocenter");
		$formdata = array(
			"name" => array(
				"label" => _L('Name'),
				"fieldhelp" => _L('The name for this access profile.'),
				"value" => $this->profile->name,
				"validators" => array(
					array("ValRequired"),
					array("ValLength", "max" => 50),
					array("ValDupeProfileName", "accessid" => $this->profileId)
				),
				"control" => array("TextField", "size" => "20", "maxsize" => 50),
				"helpstep" => 1
			),
			"description" => array(
				"label" => _L('Description'),
				"fieldhelp" => _L('The description of this access profile.'),
				"value" => $this->profile->description,
				"validators" => array(
					array("ValLength", "max" => 50)
				),
				"control" => array("TextField", "size" => "30", "maxsize" => 50),
				"helpstep" => 1
			),
			_L('Access Options'), //access options
			"infocenter" => array(
				"label" => _L('InfoCenter'),
				"fieldhelp" => _L('Allows access to dependents information in InfoCenter'),
				"value" => (!$infocenter) ? "" : "infocenter",
				"validators" => array(),
				"control" => array("CheckBox"),
				"helpstep" => 2
			)
		);

		$helpsteps = array(
			_L('Enter a name and optional description for this Access Profile.'),
			_L('Choose how you want guardians with this profile to be able to access the system.'),
		);

		$buttons = array(
			submit_button(_L('Save'), 'submit', 'tick'),
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

class ValDupeProfileName extends Validator {

	var $onlyserverside = true;

	function validate($value, $args) {
		// unique name within same type of profile
		$query = "select count(*) from access where type = 'guardian' and name = ? and id != ?";
		$res = QuickQuery($query, false, array($value, $args['accessid'] + 0));
		if ($res)
			return _L('An access profile with that name already exists. Please choose another.');

		return true;
	}

}

// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

executePage(new GuardianProfilePage(isset($csApi) ? $csApi : null));
