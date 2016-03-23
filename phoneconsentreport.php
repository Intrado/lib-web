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
require_once("obj/Job.obj.php");

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

// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY`
// -----------------------------------------------------------------------------

class ReportPhoneStatusPage extends PageForm {

	private $gdrIsUp;

	public $formName = "phonestatus";
	protected $reportGenerator = null;
	protected $reportOutput = null;

	function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $USER;
		return $USER->authorize("viewsystemreports");
	}

	function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $grapiClient;
		$this->gdrIsUp = $grapiClient->getStatus();

		if (isset($get["clear"])) {
			unset($session["phonestatus"]);
			redirect();
		}

		if (isset($session["phonestatus"]) && isset($session["phonestatus"]["mode"])) {

			$this->reportGenerator = new ConsentStatusReportGenerator();
			$instance = new ReportInstance();
			$this->reportGenerator->reportinstance = $instance;

			$this->options["reporttype"] = "html";
			$this->options["broadcast"] = $session["broadcast"];

			$this->reportGenerator->set_format("html");

			switch ($session["phonestatus"]["mode"]) {
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

			$this->options["phone"] = $session["phonestatus"]["phone"];

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

				$_SESSION["broadcast"] = $postdata["broadcast"];

				$this->form->sendTo("phoneconsentreport.php");
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
		$this->options["title"] = _L("Phone Consent Status");
		$this->options["windowTitle"] = _L("Display Options");

		if( ! $this->gdrIsUp ) {
			$html = $this->reportOutput = 'Service is temporarily unavailable.';
		}
		else {
			$html = parent::render() . $this->reportOutput;
		}

		return( $html );
	}

	function getBroadcasts() {
		global $USER;

		$jobs = DBFindMany("Job","from job where deleted = 0 and status in ('active','complete','cancelled','cancelling') and userid = $USER->id and questionnaireid is null order by id desc limit 500");

		if ( isset( $jobs ) ) {
			return $jobs;
		}

		return array();
	}

	function factoryTemplatePageForm() {
		$broadcasts = $this->getBroadcasts();

		// array has defauly -1 for job ID as it's impossible
		$broadcastSelectOptions = array( "-1" => " -- No broadcast selected -- ");

		foreach( $broadcasts as $broadcast ) {
			$broadcastSelectOptions[ $broadcast->id ] = $broadcast->name;
		}

		$defaultPhone = "";
		$defaultBroadcast = "-1";

		if( isset( $this->options["phone"] ) ) $defaultPhone = $this->options["phone"];
		if( isset( $this->options["broadcast"] ) )$defaultBroadcast = $this->options["broadcast"];

		$formdata = array(
			"phone" => array(
				"label" => _L("Search for Phone Number"),
				"value" => $defaultPhone,
				"validators" => array(
					array("ValPhone")
				),
				"control" => array("TextField","size" => 15, "maxlength" => 20),
				"helpstep" => 1
			),
			"broadcast" => array(
				"label" => "Filter by Broadcast",
				"value" => $defaultBroadcast,
				"validators" => array(),
				"control" => array( "SelectMenu", "values" => $broadcastSelectOptions ),
				"helpstep" => 2
			)
		);

		$helpsteps = array (
			_L("Enter a phone number if you wish to view the consent status for only that number."),
			_L("Selecting a broadcast will limit results to only that broadcast."),
		);

		$buttons = array(
			submit_button(_L("View Data"), "view", "table_multiple"),
			submit_button(_L("Summarize Count per Status"), "summary", "tick"),
			submit_button(_L("Download Data as CSV"), "csv", "arrow_down")
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
	"formname" => "phoneconsentstatus"
));

executePage($page);

