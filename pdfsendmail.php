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

require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/PageForm.obj.php');

require_once('FirePHPCore-0.3.2/lib/FirePHPCore/FirePHP.class.php');

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
	private $pageTitle = 'Email PDF Document(s) to Specified Recipients';
	private $pageNav = 'notifications:pdfmanager';
	private $isAjaxRequest = false;

	public $formdata;
	public $userBroadcastTypes;
	public $defaultGeneralTypeId;
	public $emailDomain;

	private $jobName;
	private $jobType;
	private $fromName;
	private $fromEmail;
	private $subject;
	private $messageBody;

	private $burstId;
	private $burst;

	var $firephp;


	public function __construct($csApi) {
		$this->firephp = FirePHP::getInstance(true);
		$this->csApi = $csApi;
		parent::__construct();
	}

	// @override
	public function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return $USER->authorize('canpdfburst');
	}

	// @override
	public function initialize() {
		// override some options set in PageBase
		$this->options["formname"] = $this->formName;
		$this->options["page"]  = $this->pageNav;
	}

	// @override
	public function beforeLoad($get = array(), $post = array()) {
		if (isset($get['id']) && $get['id'] > 0) {
			$this->burstId = $get['id'];

			// fetch individual burstData for given burstId
			$this->burst = $this->getBurst($this->burstId);
			
			// set page title using burst->name data (also used for startWindow title)
			$this->pageTitle = 'Email PDFs from: &nbsp;' . $this->burst->name;
			$this->options["title"] = $this->pageTitle;
		}
	}

	// @override
	public function load() {
		// fetch user's broadcastTypes and email domain; used in formdata definition in setFormData()
		$this->userBroadcastTypes 	= $this->getUserBroadcastTypes();
		$this->emailDomain 			= $this->getUserEmailDomain();
	}

	// @override
	public function afterLoad() {
		$this->setFormData();
		$this->form = new Form($this->formName, $this->formdata, null, array( submit_button(_L(' Send Now'), 'send', 'pictos/p1/16/59')));
		$this->form->ajaxsubmit = true;

		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			// if user submits a search, update SESSION['sendmail'] with latest form data 
			$_SESSION['sendmail'] = $this->form->getData();
			 $this->firephp->log($_SESSION['sendmail'], '$_SESSION[sendmail]'); 

			 // TODO: additional handling logic? TBD

			// define AJAX response object
			$response = (object) array(
				'status' => 'success',
				'nexturl' => 'start.php' // TODO: redirect location TBD
			);

			 // return JSON response for AJAX form submit handler (form_handle_submit() in form.js.php)
			header('Content-Type: application/json');
			echo json_encode(!empty($response) ? $response : false);
			exit();
		}
	}

	// @override
	public function sendPageOutput() {
		global $TITLE;
		echo '<script type="text/javascript">';
			// load validator for Broadcast Name; echo's output, so wrap between opening/closing script tags
			Validator::load_validators(array("ValDuplicateNameCheck"));
		echo '</script>';
        
        echo '<link rel="stylesheet" type="text/css" href="css/pdfmanager.css">';
		startWindow($this->pageTitle);
		echo '<div id="sendmail-broadcast-instruction">Specify a broadcast name and select the desired broadcast type for this message.</div>';
		echo $this->form->render();
		endWindow(); 
		echo '<script type="text/javascript" src="script/pdfmanager.js"></script>';
	}

	/*=============== helper/wrapper methods below =================*/

	public function getUserBroadcastTypes() {
		return JobType::getUserJobTypes(false);
	}

	public function getUserEmailDomain() {
		return getSystemSetting('emaildomain');
	}

	public function getBurst($id) {
		// fetches individual burstData for a given burstId
		return $this->csApi->getBurstData($id);
	}

	public function setFormData() {
		$broadcastTypes = array();
		
		foreach ($this->userBroadcastTypes as $id => $jobtype) {
			$broadcastTypes[$id] = $jobtype->name;
		}

		// sort broadcastTypes ascending (A-Z) in dropdown for better usability
		asort($broadcastTypes);

		// check for a 'general' job type in $broadcastTypes and if found, use it as the 'value'
		// for the broadcasttype control in the formdata, which sets selected item 
		// in dropdown to 'General' as default selected job type
		foreach ($broadcastTypes as $id => $value) {
			if (strcasecmp($value, "general") == 0) {
				$this->defaultGeneralTypeId = $id;
				break;
			}
		}

		$this->formdata = array(
			"broadcastname" => array(
				"label" => _L('Broadcast Name'),
				"fieldhelp" => _L('Enter a unique name for your email broadcast'),
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValDuplicateNameCheck", "type" => "job"),
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			),
			"broadcasttype" => array(
				"label" => _L('Broadcast Type'),
				"fieldhelp" => _L('Select a Broadcast type for your email broadcast'),
				"validators" => array(
					array('ValRequired'),
					array("ValInArray", "values" => array_keys($broadcastTypes))),
				"value" => $this->defaultGeneralTypeId ? $this->defaultGeneralTypeId : $broadcastTypes[0],
				"control" => array('SelectMenu', 'values' => $broadcastTypes),
				"helpstep" => 1
			),
			"passwordprotected" => array(
				"label" => "Require Password",
				"fieldhelp" => _L('Select (check) the "Require Password" checkbox if you require your recipients to enter a password to view their PDF'),
				"value" => "",
				"validators" => array(),
				"control" => array("Checkbox"),
				"helpstep" => 1
			),

			// email form fields
			"fromname" => array(
				"label" => _L('From Name'),
				"fieldhelp" => _L('Enter the full name you want users to view when they receive your email'),
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			),
			"fromemail" => array(
				"label" => _L('From Email'),
				"fieldhelp" => _L('Enter the email address you want users to view when they receive your email. NOTE: make sure the email address used includes the following domain name: ' . $this->emailDomain),
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 255),
					array("ValEmail", "domain" => $this->emailDomain)
				),
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"subject" => array(
				"label" => _L('Subject'),
				"fieldhelp" => _L('Enter the subject for your email message'),
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 255)
				),
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"messagebody" => array(
				"label" => "Message",
				"fieldhelp" => _L('Enter the text for your email message'),
				"value" => "",
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 256000)
				),
				"control" => array("TextArea"),
				"helpstep" => 1
			)
		);
	}

}

// Initialize PdfSendMail and render page
// ================================================================
executePage(new PdfSendMail($csApi));

?>