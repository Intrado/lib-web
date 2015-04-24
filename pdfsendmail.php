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
require_once("obj/MessageAttachment.obj.php");
require_once("obj/BurstAttachment.obj.php");
require_once("obj/Person.obj.php");
require_once("obj/Email.obj.php");
require_once("obj/Phone.obj.php");
require_once("obj/FieldMap.obj.php");
require_once("obj/PeopleList.obj.php");
require_once("obj/ListEntry.obj.php");
require_once("obj/Voice.obj.php");

require_once("obj/HtmlTextArea.fi.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

require_once("obj/PeopleList.obj.php");
require_once("obj/RestrictedValues.fi.php");
require_once("obj/ListGuardianCategory.obj.php");
require_once("obj/ListRecipientMode.obj.php");

/**
 * class PdfSendMail
 * 
 * @description: TODO
 * @author: Justin Burns <jburns@schoolmessenger.com>
 * @author: Sean Kelly <skelly@schoolmessenger.com>
 */
class PdfSendMail extends PageForm {
	private $csApi;

	public $userBroadcastTypes;
	public $defaultJobTypeId = '';
	public $emailDomain;

	private $burstId;
	private $burst;
	private $custname;
	private $listRecipientMode;


	/**
	 * @param CommsuiteApiClient $csApi
	 */
	public function __construct($csApi) {
		$this->csApi = $csApi;
		$maxguardians = getSystemSetting("maxguardians", 0);

		$this->listRecipientMode = new ListRecipientMode ($csApi, 3, $maxguardians, null, null);
		parent::__construct();
	}

	// @override
	public function isAuthorized(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		global $USER;
		if (getSystemSetting("_haspdfburst", false) && $USER->authorize('canpdfburst') &&
				(isset($get['id']) || isset($session['pdfsendmail_burstid']))) {
			return true;
		}
		return false;
	}

	// @override
	public function initialize() {
		// override some options set in PageBase
		$this->options["page"]  = 'notifications:pdfmanager';
		$this->options['validators'] = array("ValDuplicateNameCheck", "ValMessageBody");
	}

	// @override
	public function beforeLoad(&$get = array(), &$post = array(), &$request = array(), &$session = array()) {
		if (isset($get['id']) && $get['id']) {
			$session['pdfsendmail_burstid'] = intval($get['id']);
			redirect();
		}
		$this->burstId = $session['pdfsendmail_burstid'];
		if (isset($session['custname'])) {
			$this->custname = $session['custname'];
		}
	}

	// @override
	public function load() {
		// fetch individual burstData for given burstId
		$this->burst = $this->csApi->getBurstData($this->burstId);
		// TODO: Does this make sense here? Should we have another type of authorization check to be sure the loaded objects are any good?
		if (!$this->burst)
			redirect('unauthorized.php');

		// set page title
		$this->options['title'] = _L('Secure Document Delivery');
		//set window title
		$this->options['windowTitle'] = _L('Create Delivery Email: ') . $this->burst->name;

		// fetch user's broadcastTypes and email domain; used in formdata definition in setFormData()
		$this->userBroadcastTypes = $this->getUserBroadcastTypes();
		$this->emailDomain = $this->getUserEmailDomain();

		// Make the edit FORM
		$this->form = $this->factoryPdfSendMailForm();
	}

	// @override
	public function afterLoad() {
		global $USER;
		global $ACCESS;

		$this->form->handleRequest();

		if ($this->form->getSubmit()) {

			// run server-side validation...
			if (($errors = $this->form->validate()) !== false) {

				// not good: there was a server-side validation error if we got here...
				return;
			}

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

			// Create a message group which has the email message created by the user in this form.
			// NOTE: No additional messaging types are supported right now
			$messageGroup = new MessageGroup();
			$messageGroup->userid = $USER->id;
			$messageGroup->name = $job->name;
			$messageGroup->description = $job->description;
			$messageGroup->modified = $job->modifydate;
			$messageGroup->deleted = 1;
			$messageGroup->create();
			$job->messagegroupid = $messageGroup->id;

			// Create the email message and attach it to the message group
			$message = new Message();
			$message->userid = $USER->id;
			$message->messagegroupid = $messageGroup->id;
			$message->name = $job->name;
			$message->description = $job->description;
			$message->type = 'email';
			$message->subtype = 'html';
			$message->autotranslate = 'none';
			$message->modifydate = $job->modifydate;
			$message->languagecode = 'en';
			$message->subject = $postData['subject'];
			$message->fromname = $postData['fromname'];
			$message->fromemail = $postData['fromemail'];
			$message->stuffHeaders();
			$message->create();

			// create attachment from burst
			$burstAttachment = new BurstAttachment();
			$burstAttachment->burstid = $this->burst->id;
			// TODO: allow a different name for the attachment
			$burstAttachment->filename = "attachment.pdf";
			// TODO: allow the secret field to be selected
			$burstAttachment->secretfield = ($doPasswordProtect) ? 'pkey' : '';
			$burstAttachment->create();

			$attachment = new MessageAttachment();
			$attachment->messageid = $message->id;
			$attachment->type = 'burst';
			$attachment->burstattachmentid = $burstAttachment->id;
			$attachment->create();

			// So Message->parse() can see it, swap HTML's placeholder link with "mal" field insert with the ID in it
			$htmlBody = str_replace('#MESSAGEATTACHMENTLINKPLACEHOLDER', '<{burst:#' . $attachment->id . '}>', $postData['messagebody']);

			$customerName = escapehtml($this->custname);
			$htmlBody = str_replace('#CUSTOMERNAMEPLACEHOLDER', $customerName, $htmlBody);

			// Parse the message parts out of the HTML body (for field inserts, etc)
			$messageParts = Message::parse($htmlBody);

			// And create a MessagePart for each one
			$sequence = 0;
			foreach ($messageParts as $messagePart) {
				$messagePart->messageid = $message->id;
				$messagePart->sequence = $sequence++;
				$messagePart->create();
			}

			// build the list of people for this job
			$list = new PeopleList();
			$list->userid = $USER->id;
			$list->name = $job->name;
			$list->description = $job->description;
			$list->modifydate = $job->modifydate;
			$list->deleted = 1;
			// customer flat or guardian data, default to 'selfAndGuardian' for ease of use when/if migrate customer from flat to guardian
			$list->recipientmode = $this->listRecipientMode->getRecipientModeFromPostData($postData);
			$list->create();
			$this->listRecipientMode->resetListCategories($postData, $list->id);

			$this->fillListFromBurst($list, $this->burst->id);

			$job->create();

			$jobList = new JobList();
			$jobList->jobid = $job->id;
			$jobList->listid = $list->id;
			$jobList->create();

			// run the job
			$job->runNow();

			//update burst to be "sent"
			$this->burst->jobId = $job->id;
			$this->burst->status = "sent";
			$this->csApi->updateBurstData($this->burst);

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
		$html .= '<script type="text/javascript">' . $this->listRecipientMode->addJavaScript("pdfsendmail") . '</script>';
		return $html;
	}

	/*=============== helper/wrapper methods below =================*/
	/**
	 * @param PeopleList $list to fill with the pkeys found
	 * @param int $burstId the id which identifies the burst
	 */
	public function fillListFromBurst($list, $burstId) {
		$pkeys = array();
		$portionList = $this->csApi->getBurstPortionList($burstId);
		if ($portionList) {
			foreach ($portionList->portions as $portion) {
				$pkeys[] = $portion->identifierText;
			}
		}

		$res = $list->updateManualAddByPkeys($pkeys, false);
	}

	public function getUserBroadcastTypes() {
		return JobType::getUserJobTypes(false);
	}

	public function getUserEmailDomain() {
		return getSystemSetting('emaildomain');
	}

	public function factoryPdfSendMailForm() {
		global $USER;

		// TODO: preselect a valid and applicable job type
		$broadcastTypeNames = array();
		foreach ($this->userBroadcastTypes as $id => $jobType)
			$broadcastTypeNames[$id] = $jobType->name;

		// define help steps used in form 
		$helpsteps = array(
			_L('Enter a unique informative name for your Delivery email. You will see this name on reports.'),
			_L('Select a Broadcast type. Broadcast types determine which destinations will be used when delivering this Document. Make sure you select the most appropriate type.'),
			_L('If you would like to require recipients to enter a password to be able to view this Document, select Require Password. The password will the recipient\'s individual ID number.'),
			_L('Enter the sender name which recipients should see when receiving this Delivery email.'),
			_L('Enter the email address this Delivery email should appear to come from. Keep in mind that recipients may reply to this address.'),
			_L('Enter the subject for this Delivery email.'),
			_L('Enter the message body for this Delivery email. The portion of the Document which should be delivered to each recipient will be attached to this email message.')
		);

		$this->listRecipientMode->addHelpText($helpsteps);

		// Pull the static message layout from disk
		$messageBody = file_get_contents(dirname(__FILE__) . '/layouts/SDDBurstEmailLayout.html');

		// Replace the automagical customer name of it is present
		$customerName = escapehtml($this->custname);
		$messageBody = str_replace('#CUSTOMERNAMEPLACEHOLDER', $customerName, $messageBody);
		reset($broadcastTypeNames);
		$selectedBroadcastType = key($broadcastTypeNames);
		$formdata = array(
			_L("Broadcast Settings"),
			"broadcastname" => array(
				"label" => _L('Broadcast Name'),
				"fieldhelp" => _L('Enter a name for your email.'),
				"value" => _L('Secure Document Delivery: ') . $this->burst->name,
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
				"fieldhelp" => _L('Select the type for this Broadcast.'),
				"value" => $selectedBroadcastType,
				"validators" => array(
					array('ValRequired'),
					array("ValInArray", "values" => array_keys($broadcastTypeNames))),
				"control" => array('RadioButton', 'values' => $broadcastTypeNames),
				"helpstep" => 2
			)
		);

		$this->listRecipientMode->addToForm($formdata);

		$formdata[] = _L("Secure Documents");
		$formdata["passwordhelp"] = array(
			'label' => '',
			'control' => array(
				"FormHtml",
				'html' => '<div class="password-protect-wrapper"><span class="secure-lock"></span>' .
					_L('To require recipients to enter a password when viewing this Document, you must select Require Password.') . '</div>'
			),
			'helpstep' => 3 + $this->listRecipientMode->isEnabled()
		);
		$formdata["dopasswordprotect"] = array(
			"label" => _L("Require Password"),
			"fieldhelp" => _L('Select this option if recipients must enter a password to view this Document.'),
			"value" => '',
			"validators" => array(),
			"control" => array("Checkbox"),
			"helpstep" => 3 + $this->listRecipientMode->isEnabled()
		);

		$formdata[] = _L("Email Details");
		$formdata["fromname"] = array(
			"label" => _L('From Name'),
			"fieldhelp" => _L('Enter the name of the Document sender.'),
			"value" => '',
			"validators" => array(
				array('ValRequired'),
				array("ValLength", "max" => 50)
			),
			"control" => array("TextField", "size" => 30, "maxlength" => 50),
			"helpstep" => 4 + $this->listRecipientMode->isEnabled()
		);
		$formdata["fromemail"] = array(
			"label" => _L('From Email'),
			"fieldhelp" => _L('Enter the email address this message should appear to come from.'),
			"value" => '',
			"validators" => array(
				array('ValRequired'),
				array("ValLength", "max" => 255),
				array("ValEmail", "domain" => $this->emailDomain)
			),
			"control" => array("TextField", "size" => 30, "maxlength" => 255),
			"helpstep" => 5 + $this->listRecipientMode->isEnabled()
		);
		$formdata["subject"] = array(
			"label" => _L('Subject'),
			"fieldhelp" => _L('Enter a subject for this Delivery email.'),
			"value" => '',
			"validators" => array(
				array('ValRequired'),
				array("ValLength", "max" => 255)
			),
			"control" => array("TextField", "size" => 30, "maxlength" => 255),
			"helpstep" => 6 + $this->listRecipientMode->isEnabled()
		);
		$formdata["messagebody"] = array(
			"label" => _L('Message'),
			"fieldhelp" => _L('Enter an email message to accompany the Document.'),
			"value" => $messageBody,
			"validators" => array(
				array("ValRequired"),
				array("ValMessageBody"),
				array("ValLength", "max" => 256000)
			),
			"control" => array('HtmlTextArea', 'subtype' => 'html', 'rows' => 20, 'editor_mode' => 'inline'),
			"helpstep" => 7 + $this->listRecipientMode->isEnabled()
		);
		$formdata[] = _L("Information");
		$formdata["infohelp"] = array(
			'label' => '',
			'control' => array(
				"FormHtml",
				'html' => '<div class="password-protect-wrapper"><span class="secure-lock"></span>' .
					_L('Note: Larger files may take up to a minute to process. Thank you for your patience.') . '</div>'
			),
			'helpstep' => 7 + $this->listRecipientMode->isEnabled()
		);

		$form = new Form("pdfsendmail", $formdata, $helpsteps, array( submit_button(_L(' Send Now'), 'send', 'tick')));
		$form->ajaxsubmit = true;

		return($form);
	}
}

// Initialize PdfSendMail and render page
// ================================================================
executePage(new PdfSendMail($csApi));

?>
