<?
require_once("inc/common.inc.php");
require_once("inc/form.inc.php");
require_once("inc/html.inc.php");
require_once("inc/table.inc.php");
require_once("inc/securityhelper.inc.php");
require_once("inc/formatters.inc.php");

require_once('ifc/Page.ifc.php');
require_once('obj/PageBase.obj.php');
require_once('obj/SddReport.obj.php');
require_once('obj/ReportInstance.obj.php');

/**
 * class ReportPdfDocument
 * 
 * @description: TODO
 * @author: Bill Karwin <bkarwin@schoolmessenger.com>
 * @date: 4/23/2015
 */
class ReportSdd extends PageBase {
	protected $reportGenerator = null;
	protected $reportOutput = null;
	protected $documentId;
	private $csApi;

	function __construct($csApi) {
		$this->csApi = $csApi;
		parent::__construct();
	}
	
	// @override
	function isAuthorized($get = array(), $post = array()) {
		global $USER;
		return getSystemSetting("_haspdfburst", false) && $USER->authorize("canpdfburst") && $USER->authorize("viewsystemreports");
	}

	// @override
	public function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		if (isset($session['report']) && isset($session['report']['options'])) {
			$this->options = $session['report']['options'];
		}
		$this->options['page']  = "reports:reports";
		$this->options['title'] = "Secure Document Delivery";
		$this->options['windowTitle'] = _L("SDD Document Results");
		$this->options['format'] = "html";
		if (isset($get['id'])) {
			$this->options['id'] = (int) $get['id'];
		}
		if (!$this->options['id']) {
			redirect('pdfmanager.php');
		}
		$this->documentId = $this->options['id'];
		$session['report']['options'] = $this->options;

		if (isset($get['csv']) && $get['csv']) {
			$this->options['format'] = 'csv';
		}
		$this->options['pagestart'] = isset($get['pagestart']) ? (int) $get['pagestart'] : 0;
	}

	// @override
	public function load() {
		$this->reportGenerator = new SddReport();
		$instance = new ReportInstance();
		$this->reportGenerator->reportinstance = $instance;
		$this->reportGenerator->set_format($this->options['format']);
		$instance->setParameters($this->options);
	}

	// @override
	public function beforeRender() {
		if ($this->reportGenerator) {
			switch ($this->reportGenerator->format) {
			case 'csv':
				$this->reportGenerator->generate();
				exit();
				break;
			case 'html':
			default:
				ob_start();
				$this->reportGenerator->generate();
				$this->reportOutput = ob_get_clean();
				break;
			}
		}
	}

	// @override
	public function render() {
		$html = parent::render() . $this->reportOutput;
		return ($html);
	}

	// @override
	public function sendPageOutput() {
		$fallbackUrl = "pdfmanager.php";
		$backButton = icon_button(_L("Back"), "fugue/arrow_180", "location.href='" . (isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : $fallbackUrl) . "'");
		$refreshButton = icon_button(_L('Refresh'),"arrow_refresh","window.location.reload()");
		$csvButton = icon_button("Download CSV", "page_white_excel", null, "reportsdd.php/report.csv?id={$this->documentId}&csv=true");
		buttons($backButton, $refreshButton, $csvButton);

		parent::sendPageOutput();

		buttons();
	}

}

// Initialize and render page
// ================================================================
executePage(new ReportSdd($csApi));

