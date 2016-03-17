<?

////////////////////////////////////////////////////////////////////////////////
// Includes
////////////////////////////////////////////////////////////////////////////////
require_once("inc/common.inc.php");
include_once("inc/securityhelper.inc.php");
require_once("inc/table.inc.php");
require_once("inc/html.inc.php");
require_once("inc/utils.inc.php");
require_once("obj/Validator.obj.php");
require_once("obj/Form.obj.php");
require_once("obj/FormItem.obj.php");
require_once("ifc/Page.ifc.php");
require_once("obj/PageBase.obj.php");
require_once("obj/PageForm.obj.php");
require_once("inc/formatters.inc.php");
require_once("obj/ReportInstance.obj.php");
require_once("obj/ReportGenerator.obj.php");
require_once("obj/Phone.obj.php");

require_once("obj/ConsentStatusReportGenerator.php");

// -----------------------------------------------------------------------------
// CUSTOM VALIDATORS
// -----------------------------------------------------------------------------

// No custom Validators are used in this form.

// -----------------------------------------------------------------------------
// CUSTOM FORM ITEMS
// -----------------------------------------------------------------------------

// No custom FormItems are used in this form.

// -----------------------------------------------------------------------------
// CUSTOM FORMATTERS
// -----------------------------------------------------------------------------

function fmt_lastupdate_date($row, $index) {
	if( $row[$index] )
		return date( "M j, Y g:i a", $row[$index] / 1000 );
	else {
		return '';
	}
}

function fmt_modifiedby($row, $index) {
	if ($row[$index] === "global")
		return "System";
	else
		return $row[$index];
}

function fmt_smsstatus($row, $index) {
	switch ($row[$index]) {
		case "new":
		case "pendingoptin":
			return "Pending Opt-In";
		case "block":
			return "Blocked";
		case "optin":
			return "Opted In";
	}
}

// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY`
// -----------------------------------------------------------------------------

class ReportPhoneStatusPage extends PageForm {

	public $formName = "phonestatus";
	protected $reportGenerator = null;
	protected $reportOutput = null;

	function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $USER;
		return $USER->authorize("viewsystemreports");
	}

	function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

		if (isset($get["clear"])) {
			unset($session["phonestatus"]);
			redirect();
		}

		if (isset($session["phonestatus"]) && isset($session["phonestatus"]["mode"])) {

			$this->reportGenerator = new ConsentStatusReportGenerator();
			$instance = new ReportInstance();
			$this->reportGenerator->reportinstance = $instance;

			$this->options["reporttype"] = "html";
			$this->reportGenerator->set_format("html");

			switch ($session["phonestatus"]["mode"]) {
			case "singlePhone":
				$this->options["reporttype"] = "singlePhone";
				$this->options["phone"] = $session["phonestatus"]["phone"];
				break;
			case "csv":
				$this->reportGenerator->set_format("csv");
				$this->options["reporttype"] = "csv";
				break;
			case "summary":
				$this->options["reporttype"] = "summary";
				break;
			case "view":
				$this->options["reporttype"] = "view";
				$this->options["pagestart"] = isset($get["pagestart"]) ? $get["pagestart"] : 0;
				break;
			}

			$instance->setParameters($this->options);
		}
	}

	function load(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		$this->form = $this->factoryTemplatePageForm();
	}

	function afterLoad() {

		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();

		// If the form was submitted...
		if ($button = $this->form->getSubmit()) {

			// Check for validation errors
			if (($errors = $this->form->validate()) === false) {
				$postdata = $this->form->getData();
				$_SESSION["phonestatus"] = array(
					"mode" => $button,
					"phone" => Phone::parse($postdata["phone"])
				);
				$this->form->sendTo("csreport.php");
			}
		}
	}

	function beforeRender() {
		if ($this->reportGenerator) {
			if ($this->options["reporttype"] == "csv") {
				$this->reportGenerator->generate();
				exit();
			}
			ob_start();
			$this->reportGenerator->generate();
			$this->reportOutput = ob_get_clean();
		}
	}

	function render() {
		$this->options["page"] = "reports:reports";
		$this->options["title"] = _L("Phone Status");
		$this->options["windowTitle"] = _L("Display Options");

		$html	= parent::render()
			. $this->reportOutput;
		return($html);
	}

	function factoryTemplatePageForm() {
		$formdata = array(
			"phone" => array(
				"label" => _L("Phone number"),
				"value" => "",
				"validators" => array(
					array("ValPhone")
				),
				"control" => array("TextField","size" => 15, "maxlength" => 20),
				// "helpstep" => 1
			),
		);

		$helpsteps = array (
			// _L("Templatehelpstep 1"),
		);

		$buttons = array(
			submit_button(_L("Search for Single Phone"), "singlePhone", "application_form_magnify"),
			submit_button(_L("Summarize Count per Status"), "summary", "tick"),
			submit_button(_L("Download All Data in CSV"), "csv", "arrow_down"),
			submit_button(_L("View All Data"), "view", "table_multiple")
		);

		$form = new Form($this->formName, $formdata, $helpsteps, $buttons, "vertical");
		$form->ajaxsubmit = true; // Set to false if your form can't do AJAX submission

		return($form);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

$page = new ReportPhoneStatusPage(array(
	"formname" => "phonestatus"
));

executePage($page);

