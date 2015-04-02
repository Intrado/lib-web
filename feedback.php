<?

require_once('inc/common.inc.php');
require_once('inc/securityhelper.inc.php');
require_once('inc/table.inc.php');
require_once('inc/html.inc.php');
require_once('inc/utils.inc.php');
require_once('obj/Phone.obj.php');
require_once('obj/Validator.obj.php');
require_once('obj/Form.obj.php');
require_once('obj/FormItem.obj.php');

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

require_once('obj/NetsuiteApiClient.obj.php');


// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------

class FeedbackPage extends PageForm {

	protected $id = null; // The ID of the TEMPLATE thing that our form is for

	protected $userId;
	protected $userPage;
	protected $firstName;
	protected $lastName;
	protected $email;
	protected $phone;

	// Output formatting controls
	protected $showForm = true;
	protected $message = '';

	public $formName = 'feedbackform';

	function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		return(true); // open to the world, unconditionally!
	}

	function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
	}

	function load(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $USER;

		// Initialize these from user data
		$this->userId = $USER->id;
		$this->firstName = $USER->firstname;
		$this->lastName = $USER->lastname;
		$this->email = $USER->email;
		$this->phone = $USER->phone;
		$this->userPage = $request['from'];
		$this->form = $this->factoryFeedbackPageForm();
	}

	function afterLoad() {

		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();

		// If the form was submitted; (AJAX request requires JSON response...)
		if ($this->form->getSubmit()) {

			// The form's been submitted... we won't show it again.
			$this->showForm = false;

			// Check for validation errors (server-side; client-side have already passed)
			if (($errors = $this->form->validate()) === false) {

				// No errors - take the feedback form data, add in
				// extra attributes, and  send it all over to NetSuite
				$postdata = $this->form->getData();

				// Send the data to NetSuite
				$netsuiteApi = setupNetsuiteApi();
				$netsuiteApi->feedbackSet('ASP_Id', getSystemSetting('_customerid', 0));
				$netsuiteApi->feedbackSet('firstName', $postdata['firstName']);
				$netsuiteApi->feedbackSet('lastName', $postdata['lastName']);
				$netsuiteApi->feedbackSet('emailAddress', $postdata['email']);
				$netsuiteApi->feedbackSet('phoneNum', $postdata['phone']);
				$netsuiteApi->feedbackSet('feedbackCategory', $postdata['feedbackCategory']);
				$netsuiteApi->feedbackSet('feedbackText', $postdata['feedbackText']);
				$netsuiteApi->feedbackSet('userId', $this->userId);
				$netsuiteApi->feedbackSet('feedbackType', $postdata['feedbackType']);
				$netsuiteApi->feedbackSet('userPage', $this->userPage);
				$netsuiteApi->feedbackSet('trackingId', getUserSessionTrackingId());
				$netsuiteApi->feedbackSet('sessionData', 'n/a');
				if ($netsuiteApi->captureUserFeedback()) {

					// Success message
					$this->message = _L('Thank you for your feedback! Our Product team will review your comments, and may contact you with questions. Should you need immediate assistance, please contact our Support team at (800) 920-3897.');
					return;
				}
			}

			$this->message = _L('There was a problem recording your feedback - please try again later.');
		}
	}

	function beforeRender() {
		// Do any additional work needed to prepare for rendering a page
	}

	function render() {

		// Note: This is the title of the page. It should not also be the header of the form "window".
		// This 'title' is set to global $TITLE in the base class.
		$this->options['title'] = '';

		if ($this->showForm) {
			$this->options['windowTitle'] = _L('Provide Feedback...');
			$html = parent::render();
		}
		else {
			$html = "<h3>{$this->message}</h3>";
		}

		return($html);
	}

	function getFeedbackCategories() {
		global $SETTINGS;
		$feedbackCategories = explode(',', $SETTINGS['netsuite']['feedbackCategories']);
		array_unshift($feedbackCategories, 'Select a Category...');
		for ($i = 0; $i < count($feedbackCategories); $i++) {
			$feedbackCategories[$i] = _L(trim($feedbackCategories[$i]));
		}
		return $feedbackCategories;
	}

	function getFeedbackTypes() {
		global $SETTINGS;
		$feedbackTypes = explode(',', $SETTINGS['netsuite']['feedbackTypes']);
		array_unshift($feedbackTypes, 'Select a Type...');
		for ($i = 0; $i < count($feedbackTypes); $i++) {
			$feedbackTypes[$i] = _L(trim($feedbackTypes[$i]));
		}
		return $feedbackTypes;
	}

	function factoryFeedbackPageForm() {

		$formdata = array(
			_L('Reported by'),

			'firstName' => array(
				'label' => _L('First Name'),
				'fieldhelp' => _L('This is your first name.'),
				'value' => $this->firstName,
				'validators' => array(
					array('ValLength', 'min' => 3, 'max' => 50)
				),
				'control' => array('TextField', 'size' => 30, 'maxlength' => 51, 'autocomplete' => 'test'),
				'helpstep' => 1
			),

			'lastName' => array(
				'label' => _L('Last Name'),
				'fieldhelp' => _L('This is your last name.'),
				'value' => $this->lastName,
				'validators' => array(
					array('ValLength', 'min' => 2, 'max' => 50)
				),
				'control' => array('TextField', 'size' => 30, 'maxlength' => 51, 'autocomplete' => 'test'),
				'helpstep' => 2
			),

			'email' => array(
				'label' => _L('Email'),
				'fieldhelp' => _L('This is the email address associated with your account.'),
				'value' => $this->email,
				'validators' => array(
					array('ValLength', 'min' => 9, 'max' => 50),
					array('ValEmail')
				),
				'control' => array('TextField', 'size' => 30, 'maxlength' => 51, 'autocomplete' => 'test'),
				'helpstep' => 3
			),

			'phone' => array(
				'label' => _L('Phone'),
				'fieldhelp' => _L('This is your direct contact number.'),
				'value' => $this->phone,
				'validators' => array(
					array('ValLength', 'min' => 10, 'max' => 50),
					array('ValPhone')
				),
				'control' => array('TextField', 'size' => 30, 'maxlength' => 51, 'autocomplete' => 'test'),
				'helpstep' => 4
			),

			_L('Feedback'),

			'feedbackCategory' => array(
				'label' => _L('Feedback Category'),
				'fieldhelp' => _L('This is the category of feedback.'),
				'value' => 0,
				'validators' => array(
					array('ValInArray', 'values' => array_keys($this->getFeedbackCategories()))
				),
				'control' => array('SelectMenu', 'values' => $this->getFeedbackCategories()),
				'helpstep' => 5
			),

			'feedbackType' => array(
				'label' => _L('Feedback Type'),
				'fieldhelp' => _L('This is the type of feedback you.d like to leave.'),
				'value' => 0,
				'validators' => array(
					array('ValInArray', 'values' => array_keys($this->getFeedbackTypes()))
				),
				'control' => array('SelectMenu', 'values' => $this->getFeedbackTypes()),
				'helpstep' => 6
			),

			'feedbackText' => array(
				'label' => _L('Message'),
				'fieldhelp' => _L('This text box is where you enter your feedback.'),
				'value' => '',
				'validators' => array(
					// Note: API host's max for this field is 100K
					array('ValLength', 'min' => 10, 'max' => 5000)
				),
				'control' => array('TextArea', 'size' => 30, 'rows' => 15, 'cols' => 34),
				'helpstep' => 7
			)
		);

		$helpsteps = array (
			_L('The first name listed in your SchoolMessenger account should be pre-populated here. If missing or incorrect, please update.'),
			_L('The last name listed in your SchoolMessenger account should be pre-populated here. If missing or incorrect, please update.'),
			_L('The email address listed in your SchoolMessenger account should be pre-populated here. If missing or incorrect, please update. We.ll use this to contact you to follow up on your feedback.'),
			_L('The phone number listed in your SchoolMessenger account should be pre-populated here. If missing or incorrect, please update. We.ll use this to contact you to follow up on your feedback.'),
			_L('Select a Feedback Category from the dropdown. This helps us categorize what part of SchoolMessenger your feedback references.'),
			_L('Select a Feedback Type from the dropdown. This provides us with the context needed to evaluate your feedback.'),
			_L('Please provide us with your feedback.')
		);

		$buttons = array(
			submit_button(_L('Send Feedback'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross',  "window.parent.jQuery('#feedbackModal').find('button').click();", '#')
		);

		$form = new Form($this->formName, $formdata, $helpsteps, $buttons);
		$form->ajaxsubmit = false; // AJAX submission is pointless complexity since we're in an iframe!

		return($form);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

$page = new FeedbackPage(Array(
	'formname' => 'templateform',
	'validators' => Array()
));

executePage($page);

