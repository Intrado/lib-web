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
		$this->form = new Form($this->formName, $this->formdata, null, array( submit_button(_L(' Send Now'), 'send', 'pictos/p1/16/59')));
		$this->form->ajaxsubmit = false;

		$this->form->handleRequest();
		if ($this->form->getSubmit()) {
			// if user submits a search, update SESSION['sendmail'] with latest form data 
			$_SESSION['sendmail'] = $this->form->getData();
			// then reload (redirect to) self (with new data)
			redirect('pdfsendmail.php');
		}
	}

	// @override
	public function sendPageOutput() {
		global $TITLE;
        echo '<link rel="stylesheet" type="text/css" href="css/pdfmanager.css">';
		startWindow('Email PDF Reports');

		echo '<div id="sendmail-broadcast-instruction">Specify a broadcast name and select the desired broadcast type for this message.</div>';
		// render search form
		echo $this->form->render();
		endWindow(); 

		echo '<script src="script/pdfmanager.js"></script>';
	}

	/*=============== non-override helper methods below =================*/

	public function getUserBroadcastTypes() {
		return JobType::getUserJobTypes(false);
	}

	public function setFormData() {
		$userBroadcastTypes = $this->getUserBroadcastTypes();
		$broadcastTypes = array();
		
		foreach ($userBroadcastTypes as $id => $jobtype) {
			$broadcastTypes[$id] = $jobtype->name;
		}

		// sort broadcastTypes ascending (A-Z) in dropdown for better usability
		asort($broadcastTypes);

		// check for a 'general' job type in $broadcastTypes and if found, use it as the 'value'
		// for the broadcasttype control in the formdata, which sets selected item 
		// in dropdown to 'General' as default selected job type
		foreach ($broadcastTypes as $id => $value) {
			if (strcasecmp($value, "general") == 0) {
				$defaultGeneralTypeId = $id;
				break;
			}
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
					array("ValInArray", "values" => array_keys($broadcastTypes))),
				"value" => $defaultGeneralTypeId ? $defaultGeneralTypeId : $broadcastTypes[0],
				"control" => array('SelectMenu', 'values' => $broadcastTypes),
				"helpstep" => 1
			),
			"passwordprotected" => array(
				"label" => "Require Password",
				"value" => "",
				"validators" => array(),
				"control" => array("Checkbox"),
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

			// PDF splitting/template info
			// "skipstart" => array(
			// 	"label" => _L('Skip @ start'),
			// 	"value" => '0',
			// 	"validators" => array(),
			// 	"control" => array("TextField"),
			// 	"helpstep" => 1
			// ),
			// "skipend" => array(
			// 	"label" => _L('Skip @ end'),
			// 	"value" => '0',
			// 	"validators" => array(),
			// 	"control" => array("TextField"),
			// 	"helpstep" => 1
			// ),
			// "pagesperreport" => array(
			// 	"label" => _L('Pages / Report'),
			// 	"value" => '1',
			// 	"validators" => array(),
			// 	"control" => array("TextField"),
			// 	"helpstep" => 1
			// ),
			// "template" => array(
			// 	"label" => _L('Template'),
			// 	"value" => 'Test Template',
			// 	"validators" => array(),
			// 	"control" => array("TextField"),
			// 	"helpstep" => 1
			// )
		);
	}

}

// Initialize PdfSendMail and render page
// ================================================================
executePage(new PdfSendMail($csApi));

?>