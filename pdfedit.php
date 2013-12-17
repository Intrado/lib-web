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

//require_once('obj/Burst.obj.php');

require_once('obj/APIClient.obj.php');
require_once('obj/BurstAPIClient.obj.php');

// -----------------------------------------------------------------------------
// CUSTOM FORM FOR THIS PAGE
// -----------------------------------------------------------------------------

class TemplateForm extends Form {

	function handleSubmit($button, $data) {
		//Query('BEGIN');
		// TODO: Save form data
		//Query('COMMIT');

		// Where do we want the client to be sent after submission?
		return('start.php');
	}
}

class JSONObject {};


// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------

class PDFEditPage extends PageForm {

	const MAX_PDF_UPLOAD_BYTES = 209715200; // 200MB

	//var $burst = null;		// DBMO for the burst record we're working on
	var $burstData = null;		// Associative array for the burst record we're working on
	var $burstId = null;		// ID for the DBMO object to interface with
	var $burstTemplates = array();	// An array to collect all the available burst templates into

	private $burstAPI = null;

	public function __construct($args) {
		$this->burstAPI = new BurstAPIClient($args);
		parent::__construct(array());
	}

	public function isAuthorized(&$get, &$post, &$request, &$session) {
		global $USER;
		//return($USER->authorize('canpdfburst'));
		return(true); // FIXME - white screen of death on use of authorize()
	}

	public function beforeLoad(&$get, &$post, &$request, &$session) {

		// The the query string/post data has a burst ID specified, then grab it
		$this->burstId = (isset($request['id']) && intval($request['id'])) ? intval($request['id']) : null;
	}

	public function load(&$get, &$post, &$request, &$session) {

		// If we're editing an existing one, get its data
		if ($this->burstId) {
			$this->burstData = $this->burstAPI->getBurstData($this->burstId);
		}
		else {
			$this->burstData = new JSONObject();
			$this->burstData->name = '';
			$this->burstData->bursttemplateid = '';
			$this->burstData->filename = '';
		}

		// Get a list of burst templates
		$this->loadBurstTemplates();

		// Make the edit FORM
		$this->form = $this->factoryFormPDFUpload();
	}

	public function loadBurstTemplates() {
		$res = Query("
			SELECT
				`id`,
				`name`
			FROM
				`bursttemplate`
			WHERE
				NOT `deleted`;
		");

		if (is_object($res)) {
			while ($row = DBGetRow($res, true)) {
				$this->burstTemplates[$row['id']] = $row['name'];
			 }
		}
	}

	public function factoryFormPDFUpload() {
		$formdata = array(
			"name" => array(
				"label" => _L('Name'),
				"value" => $this->burstData->name,
				"validators" => array(
					array('ValRequired'),
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50, "autocomplete" => "test"),
				"helpstep" => 1
			),
			"bursttemplateid" => array(
				"label" => _L('Template'),
				"value" => $this->burstData->bursttemplateid,
				"validators" => array(),
				"control" => array('SelectMenu', 'values' => (array('' => _L('Select PDF Template')) + $this->burstTemplates)),
				"helpstep" => 2
			)
		);

		// If we already have a burstId
		if ($this->burstId) {
			// Then a file has already been uploaded

			// Hide the ID from sight!
			$formdata['id'] = array(
				'value' => $this->burstId,
				'control' => array('HiddenField')
			);

			// we're just going to show a read-only representation
			$formdata[] = array(
				"label" => _L('Uploaded PDF'),
				"control" => array('FormHtml', 'html' => $this->burstData->filename)
			);
		}
		else {
			// Otherwise we need to show the upload formitem to be able to select and upload a new PDF
			$formdata[] = array(
				"label" => _L('Upload PDF'),
				"value" => '',
				"validators" => array(),
				//"validators" => array(
				//	array('ValRequired')
				//),
				"control" => array('FileUpload'),
				"helpstep" => 3
			);
		}

                $helpsteps = array (
			_L('Templatehelpstep 1'),
			_L('Templatehelpstep 2'),
			_L('Templatehelpstep 3')
		);

		$buttons = array(
			submit_button(_L($this->burstId ? 'Save' : 'Upload'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'pdfmanager.php')
		);

		// A new form with some defaults overridden...
		$form = new Form('pdfuploader', $formdata, $helpsteps, $buttons, 'vertical');
		$form->multipart = true;
		$form->ajaxsubmit = false;

		return($form);
	}

	public function afterLoad() {
		$this->form->handleRequest();
		if ($button = $this->form->getSubmit()) {
			$postdata = $this->form->getData();
			$name = $postdata['name'];
			$bursttemplateid = intval($postdata['bursttemplateid']);

			// Are we saving edits or uploading anew?
			if ($this->burstId) {
				// Saving edits!
				if ($this->burstAPI->putBurstData($this->burstId, $name, $bursttemplateid)) {
					print "saved!";
				}
				else print "failed 1";
			}
			else {
				// Uploading anew!
				if ($this->burstAPI->postBurst($name, $bursttemplateid)) {
					print "uploaded!";
				}
				else print "failed 2";
			}
			redirect('pdfmanager.php');
		}
		$this->options['title'] = ($this->burstId) ? _L('Edit PDF Properties') : _L('Upload New PDF');
	}

	public function beforeRender() {
	}

	public function render() {
		if ($this->burstId && ! is_object($this->burstData)) {
			$html = _L('The requested PDF Document could not be found') . "<br/>\n";
		}
		else {
			$html = $this->form->render();
		}

		return($html);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

// Fun with globals and super globals...
$uriParts = explode('/', $_SERVER['REQUEST_URI']); // ex /custname/...
$apiCustomer = $uriParts[1];

$args = Array(
	'apiHostname' => $_SERVER['SERVER_NAME'],
	'apiCustomer' => $apiCustomer,
	'apiUser' => $USER->id,
	'apiAuth' => $_COOKIE[$apiCustomer . '_session']
);

executePage(new PDFEditPage($args));

