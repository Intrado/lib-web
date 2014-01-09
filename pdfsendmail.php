<?
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
require_once("obj/ValDuplicateNameCheck.val.php");
require_once("obj/ValMessageBody.val.php");

require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");
require_once("obj/JobList.obj.php");
require_once("obj/MessageGroup.obj.php");
require_once("obj/Message.obj.php");
require_once("obj/MessagePart.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/Voice.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

/**
 * class PdfSendMail
 * 
 * @description: TODO
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @date: 12/27/2013
 */
class PdfSendMail extends PageForm
{
	
	private $csApi;
	private $formName = 'pdfsendmail';
	private $pageNav = 'notifications:pdfmanager';

	public $formdata;
	public $helpsteps;
	public $userBroadcastTypes;
	public $defaultJobTypeId = '';
	public $emailDomain;

	private $burstId;
	private $burst;


	public function __construct($csApi) {
		$this->csApi = $csApi;
		parent::__construct();
	}

	// @override
	public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		global $USER;
		if ($USER->authorize('canpdfburst') &&
				(isset($get['id']) || isset($session['pdfsendmail_burstid']))) {
			return true;
		}
		return false;
	}

	// @override
	public function initialize() {
		// override some options set in PageBase
		$this->options["page"]  = $this->pageNav;
		$this->options['validators'] = array("ValDuplicateNameCheck", "ValMessageBody");
	}

	// @override
	public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		if (isset($get['id']) && $get['id']) {
			$session['pdfsendmail_burstid'] = intval($get['id']);
			redirect();
		}
		$this->burstId = $session['pdfsendmail_burstid'];
	}

	// @override
	public function load() {
		// fetch individual burstData for given burstId
		$this->burst = $this->csApi->getBurstData($this->burstId);
		// TODO: Does this make sense here? Should we have another type of authorization check to be sure the loaded objects are any good?
		if (!$this->burst)
			redirect('unauthorized.php');

		// set page title using burst->name data (also used for startWindow title)
		$this->options['title'] = 'Email PDFs from: &nbsp;' . $this->burst->name;

		// fetch user's broadcastTypes and email domain; used in formdata definition in setFormData()
		$this->userBroadcastTypes 	= $this->getUserBroadcastTypes();
		$this->emailDomain 			= $this->getUserEmailDomain();
	}

	// @override
	public function afterLoad() {
		global $USER;
		global $ACCESS;

		$this->setFormData();
		$this->form = new Form($this->formName, $this->formdata, $this->helpsteps, array( submit_button(_L(' Send Now'), 'send', 'pictos/p1/16/59')));
		$this->form->ajaxsubmit = true;

		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			$postData = $this->form->getData();
			$doPasswordProtect = $postData['dopasswordprotect'];

			Query("BEGIN");

			// FIXME: Create and use an API end point for setting up the job and associated child objects
			// Create a new job object directly in the DB
			$job = Job::jobWithDefaults();
			// If the destination has more than one student, we should send all duplicates
			$job->setOption("skipemailduplicates",0);
			$job->name = $postData['broadcastname'];
			$job->description = "Secure Document Delivery notification";
			$job->jobtypeid = $postData['broadcasttype'];
			$job->type = 'notification';
			// FIXME: We assume the user is sending this notification inside their call window. If not, the job will cancel immediately
			// FIXME: Maybe we should calculate based off the user's preferences first, then use the profile only if the current time falls
			// outside their chosen defaults
			$callEarly = ($ACCESS->getValue("callearly") ? $ACCESS->getValue("callearly") : "12:00 AM");
			$callLate = ($ACCESS->getValue("calllate") ? $ACCESS->getValue("calllate") : "11:59 PM");
			$job->starttime = date("H:i", strtotime($callEarly));
			$job->endtime = date("H:i", strtotime($callLate));

			// set burst options for this job
			$job->setOption("burst_id", $this->burst->id);
			// if password protection for each individually generated bursted pdf...
			if ($doPasswordProtect) {
				// FIXME: At some point, the user needs to be able to select the field they wish to use for password protection
				$job->setOption("burst_passwordprotect", 'pkey');
			}

			// Create a message group which has the email message created by the user in this form.
			// NOTE: No additional messaging types are supported right now
			$messageGroup = new MessageGroup();
			$messageGroup->userid = $USER->id;
			$messageGroup->name = $job->name;
			$messageGroup->description = $job->description;
			$messageGroup->modified = $job->modifydate;
			$messageGroup->deleted = 1;
			$messageGroup->create();

			// Create the email message and attach it to the message group
			$message = new Message();
			$message->userid = $USER->id;
			$message->messagegroupid = $messageGroup->id;
			$message->name = $job->name;
			$message->description = $job->description;
			$message->type = 'email';
			$message->subtype = 'plain';
			$message->autotranslate = 'none';
			$message->modifydate = $job->modifydate;
			$message->languagecode = 'en';
			$message->subject = $postData['subject'];
			$message->fromname = $postData['fromname'];
			$message->fromemail = $postData['fromemail'];
			$message->stuffHeaders();
			$message->create();

			$messageParts = $message->parse($postData['messagebody']);
			foreach ($messageParts as $messagePart) {
				$messagePart->messageid = $message->id;
				$messagePart->create();
			}

			// FIXME: Adding a dummy list with the current user's email, till job processor creates one from the burst object
			// Constants
			$langfield = FieldMap::getLanguageField();
			$fnamefield = FieldMap::getFirstNameField();
			$lnamefield = FieldMap::getLastNameField();

			// New Person
			$person = new Person();
			$person->userid = $USER->id;
			$person->deleted = 0; // NOTE: This person must not be set as deleted, otherwise the list will not include him/her.
			$person->type = "manualadd";
			$person->$fnamefield = $USER->firstname;
			$person->$lnamefield = $USER->lastname;
			$person->$langfield = "en";
			$person->create();

			$emailDestination = new Email();
			$emailDestination->personid = $person->id;
			$emailDestination->email = $USER->email;
			$emailDestination->sequence = 0;
			$emailDestination->editlock = 0;
			$emailDestination->create();

			$list = new PeopleList();
			$list->userid = $USER->id;
			$list->name = $job->name;
			$list->description = $job->description;
			$list->modifydate = $job->modifydate;
			$list->deleted = 1;
			$list->create();

			$listEntry = new ListEntry();
			$listEntry->listid = $list->id;
			$listEntry->type = "add";
			$listEntry->personid = $person->id;
			$listEntry->create();

			$job->messagegroupid = $messageGroup->id;
			$job->create();

			$jobList = new JobList();
			$jobList->jobid = $job->id;
			$jobList->listid = $list->id;
			$jobList->create();

			// run the job
			$job->runNow();

			Query("COMMIT");

			unset($_SESSION['pdfsendmail_burstid']);

			if ($this->form->isAjaxSubmit()) {
				$this->form->sendTo("start.php");
			} else {
				redirect("start.php");
			}

		}
	}

	// @override
	public function render() {
		$html = '<link rel="stylesheet" type="text/css" href="css/pdfmanager.css">';
		$html .= parent::render();
		return $html;
	}

	/*=============== helper/wrapper methods below =================*/

	public function getUserBroadcastTypes() {
		return JobType::getUserJobTypes(false);
	}

	public function getUserEmailDomain() {
		return getSystemSetting('emaildomain');
	}

	public function setFormData() {
		// TODO: preselect a valid and applicable job type
		$broadcastTypeNames = array();
		foreach ($this->userBroadcastTypes as $id => $jobType)
			$broadcastTypeNames[$id] = $jobType->name;

		// define help steps used in form 
		$this->helpsteps = array(
			_L('Enter a unique name for your email broadcast'),
			_L('Select a Broadcast type for your email broadcast'),
			_L('Select (check) the "Require Password" checkbox if you require your recipients to enter a password to view their PDF'),
			_L('Enter the full name you want users to view when they receive your email'),
			_L('Enter the email address you want users to view when they receive your email. NOTE: make sure the email address used includes the following domain name: ' . $this->emailDomain),
			_L('Enter the subject for your email message'),
			_L('Enter the text for your email message')
		); 

		$this->formdata = array(
			_L("Broadcast Settings"),
			"broadcastname" => array(
				"label" => _L('Broadcast Name'),
				"fieldhelp" => $this->helpsteps[0],
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","min" => 3,"max" => 50),
					array("ValDuplicateNameCheck", "type" => "job")
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			),
			"broadcasttype" => array(
				"label" => _L('Broadcast Type'),
				"fieldhelp" => $this->helpsteps[1],
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValInArray", "values" => array_keys($broadcastTypeNames))),
				"control" => array('RadioButton', 'values' => $broadcastTypeNames),
				"helpstep" => 2
			),
			_L("Optional Configuration"),
			"passwordhelp" => array(
				'label' => '',
				'control' => array("FormHtml", 'html' => '<span class="secure-lock"></span>
					You have the option to password-protect all PDF reports, which will require the recipient to enter a password (i.e. individual ID#) to view their report.'),
				'helpstep' => 3
			),
			"dopasswordprotect" => array(
				"label" => _L("Require Password"),
				"fieldhelp" => $this->helpsteps[2],
				"value" => '',
				"validators" => array(),
				"control" => array("Checkbox"),
				"helpstep" => 3
			),
			_L("Email Details"),
			// email form fields
			"fromname" => array(
				"label" => _L('From Name'),
				"fieldhelp" => $this->helpsteps[3],
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 50)
				),
				"control" => array("TextField", "size" => 30, "maxlength" => 50),
				"helpstep" => 4
			),
			"fromemail" => array(
				"label" => _L('From Email'),
				"fieldhelp" => $this->helpsteps[4],
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 255),
					array("ValEmail", "domain" => $this->emailDomain)
				),
				"control" => array("TextField", "size" => 30, "maxlength" => 255),
				"helpstep" => 5
			),
			"subject" => array(
				"label" => _L('Subject'),
				"fieldhelp" => $this->helpsteps[5],
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 255)
				),
				"control" => array("TextField", "size" => 30, "maxlength" => 255),
				"helpstep" => 6
			),
			"messagebody" => array(
				"label" => _L("Message"),
				"fieldhelp" => $this->helpsteps[6],
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 256000),
					array("ValMessageBody")
				),
				"control" => array("TextArea"),
				"helpstep" => 7
			)
		);
	}

}

// Initialize PdfSendMail and render page
// ================================================================
executePage(new PdfSendMail($csApi));

?>