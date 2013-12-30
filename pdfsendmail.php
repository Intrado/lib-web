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

require_once("obj/Job.obj.php");
require_once("obj/JobType.obj.php");

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
	private $formdata;
	private $pageTitle = 'Email PDF Document(s) to Specified Recipients';
	private $pageNav = 'notifications:pdfmanager';
	private $isAjaxRequest = false;

	private $jobName;
	private $jobType;
	private $fromName;
	private $fromEmail;
	private $subject;
	private $messageBody;


	public function __construct($csApi) {
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
		$this->options["title"] = $this->pageTitle;
		$this->options["page"]  = $this->pageNav;
	}

	// @override
	public function beforeLoad($get = array(), $post = array()) {

	}

	// @override
	public function load() {

	}

	// @override
	public function afterLoad() {

		$this->setFormData();
		$this->form = new Form($this->formName, $this->formdata, null, array( submit_button(_L(' Send'), 'send', 'pictos/p1/16/59')));
		$this->form->ajaxsubmit = false;

		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			// if user submits a search, update SESSION['tips'] with latest form data 
			$_SESSION['tips'] = $this->form->getData();
			// then reload (redirect to) self (with new data)
			redirect('pdfsendmail.php');
		}
	}

	// @override
	public function sendPageOutput() {
		global $TITLE;
		startWindow('Send Email');

		echo '<link rel="stylesheet" type="text/css" href="css/tips.css">
			  <div id="tip-icon"></div><div id="tip-search-instruction">Specify a broadcast name and select the desired broadcast type for this message.</div>';
		// render search form
		echo $this->form->render();
		endWindow(); 

		echo '<script src="script/pdfmanager.js"></script>';
	}

	/*=============== non-override helper methods below =================*/


	public function setFormData() {
		$userjobtypes = JobType::getUserJobTypes(false);
		$jobtypes = array();
		foreach ($userjobtypes as $id => $jobtype) {
			$jobtypes[$id] = $jobtype->name;
		}

		$this->formdata = array(
			"broadcastname" => array(
				"label" => _L('Broadcast Name'),
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			),
			"broadcasttype" => array(
				"label" => _L('Broadcast Type'),
				"validators" => array(
					array('ValRequired'),
					array("ValInArray", "values" => array_keys($jobtypes))),
				"value" => $jobtypes[0],
				"control" => array('SelectMenu', 'values' => $jobtypes),
				"helpstep" => 1
			),

			// email form fields
			"fromname" => array(
				"label" => _L('From Name'),
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
				"value" => '',
				"validators" => array(
					array('ValRequired'),
					array("ValLength","max" => 255),
					array("ValEmail", "domain" => getSystemSetting('emaildomain'))
				),
				"control" => array("TextField"),
				"helpstep" => 1
			),
			"subject" => array(
				"label" => _L('Subject'),
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
				"value" => "",
				"validators" => array(
					array('ValRequired'),
					// array("ValMessageBody"),
					array("ValLength","max" => 256000)
				),
				"control" => array("TextArea"),
				"helpstep" => 1
			),
		);
	}

	public function curlRequest() {
		// https://sandbox.testschoolmessenger.com/kbhigh/api/2/users/8629/roles/1/settings/jobtypes?limit=1000
	}

}

// Initialize PdfSendMail and render page
// ================================================================
executePage(new PdfSendMail($csApi));

?>