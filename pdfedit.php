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


/**
 * PDF Edit Page class
 */
class PdfEditPage extends PageForm {

	protected $burstData = null;		// Associative array for the burst record we're working on
	protected $burstId = null;		// ID for the DBMO object to interface with
	protected $burstTemplates = array();	// An array to collect all the available burst templates into

	protected $error = '';			// A place to capture an error string to control modal display

	protected $csApi = null;

	public $formName = 'pdfuploader';

	/**
	 * Constructor
	 *
	 * Use dependency injection to make those external things needed separately testable.
	 *
	 * @param object $csApi An instance of CommsuiteApiClient
	 */
	public function __construct($csApi) {
		$this->csApi = $csApi;
		parent::__construct();
	}

	public function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $USER;
		return($USER->authorize('canpdfburst'));
	}

	public function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

		// The burst ID will be in the form POST data or on the URL queryString, or unset...
		if (isset($post["{$this->formName}_id"]) && intval($post["{$this->formName}_id"])) {
			$this->burstId = intval($post["{$this->formName}_id"]);
		}
		else if (isset($request['id']) && intval($request['id'])) {
			$this->burstId = intval($request['id']);
		}
		else {
			$this->burstId = null;
		}
	}

	public function load(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

		// Get the data for this burst record
		$this->burstData = $this->getBurstData($this->burstId);

		// If there was a data reload issue
		if ($this->checkReload($this->burstId)) {

			// Add this error message to the page output, but still
			// allow the form to be displayed to take edits and resubmit
			$this->error = _L("This PDF Document's record was changed in another window or session. Please review the current data and reapply any additional changes needed, then resubmit.");
		}

		// Get a list of burst templates
		$this->loadBurstTemplatesInto($this->burstTemplates);

		// Make the edit FORM
		$this->form = $this->factoryFormPDFUpload();
	}

	public function afterLoad() {

		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();

		// If the form was submitted...
		if ($this->form->getSubmit()) {

			// Get the POSTed data from the form
			$postdata = $this->form->getData();
			$name = $postdata['name'];
			$bursttemplateid = intval($postdata['bursttemplateid']);

			// Check if the data has changed and display a notification if so...
			if (! is_null($this->burstId) && $this->form->checkForDataChange()) {

				// Flag the problem, then redirect back to ourselves to redisplay the form with now-current data
				$_SESSION['burstreload'] = true;
				redirect("?id={$this->burstId}");
			}

			// We're storing a new record if burstId is null, otherwise updating an existing record
			$action = (is_null($this->burstId)) ? 'stored' : 'updated';
			if ($this->csApi->setBurstData($this->burstId, $name, $bursttemplateid)) {

				// For success, we redirect back to the manager page with this notice to be shown on that page:
				notice(_L("The PDF Document was successfully {$action}"));
				redirect('pdfmanager.php');
			} else {

				// For errors, we fall through to render() and let this error message be shown:
				$this->error = _L("The PDF Document could not be {$action} - please try again later");
			}
		}
	}

	public function render() {

		// The page title depends on whether editing existing or uploading anew
		$this->options['title'] = ($this->burstId) ? _L('Edit PDF Document Properties') : _L('Upload New PDF Document');

		// URL hacking or what?
		if (! (is_null($this->burstId) || is_object($this->burstData))) {
			$html = _L('The requested PDF Document could not be found') . "<br/>\n";
		}
		else {
			$html = $this->form->render();
		}

		// If there was any error processing the submission...
		if (strlen($this->error)) {

			// Add an error dialog to the page (it'll be shown when the page renders)
			$html .= $this->modalHtml($this->error);
		}

		return($html);
	}

	/**
	 * Check if burst reload has been flagged and add an error to the output if so
	 *
	 * If the user submitted the form for editing an existing burst record, but the
	 * server data indicates that the data has changed (in another window/session,
	 * etc) since they originally displayed the form, then the submission handler
	 * will set the 'burstreload' flag in the session data which we will catch and
	 * unset here with a a true return value indicating that there was a problem.
	 * This allows the form to be redisplayed with now-current data from the server
	 * and the user will have to start over with edits, if any are still necessary.
	 *
	 * @param integer $burstId The ID of the burst record we're looking at; null if new
	 *
	 * @return boolean true if there was a reload issue, else false
	 */
	public function checkReload($burstId) {
		if (! is_null($burstId)) {
			// If we have been flagged as having reloaded on account of data having changed...
			if (isset($_SESSION['burstreload'])) {

				// Clear the flag and set the error message; this will allow the message to display, but
				// upon dismissal show the form repopulated with the freshly loaded burst data from above
				unset($_SESSION['burstreload']);
				return(true);
			}
		}

		return(false);
	}

	/**
	 * Get burst data for the specified ID
	 *
	 * If the burstId is null then we'll get a new burst record object out of
	 * this with values suitable for use as form defaults.
	 *
	 * @param integer $burstId The ID of the burst record we're interested in
	 *
	 * @return object A method-less, data-only onject with the burst data properties
	 */
	public function getBurstData($burstId) {
		// If we're editing an existing one, get its data
		if (! is_null($burstId)) {

			// Pull in the current data for this PDF Burst record
			$burstData = $this->csApi->getBurstData($burstId);

		}
		else {
			$burstData = (object) null;
			$burstData->name = '';
			$burstData->burstTemplateId = '';
			$burstData->filename = '';
		}

		return($burstData);
	}

	/**
	 * Get the list of burst templates directly from the database
	 *
	 * There is no API support for this at this time, so we have to go direct. The resulting array
	 * is stored in an instance property, $this->burstTemplates.
	 *
	 * @todo Put burst template data access into the commsuite API!
	 *
	 * @param array $target Associative array to save the discovered list of
	 * id/name pairs for available burst templates, indexed by id
	 */
	public function loadBurstTemplatesInto(&$target) {
		if (is_object($res = Query('SELECT `id`, `name` FROM `bursttemplate` WHERE NOT `deleted`;'))) {
			while ($row = DBGetRow($res, true)) {
				$target[$row['id']] = $row['name'];
			 }
		}
	}

	/**
	 * Factory method to spit out a form object for PDF uploads
	 *
	 * FIXME - Commsuite API supports no validation for things like duplicate names, invalid tempalte id's
	 *
	 * @return object Form
	 */
	public function factoryFormPDFUpload() {
		$formdata = array(
			"name" => array(
				"label" => _L('Name'),
				"value" => $this->burstData->name,
				"validators" => array(
					array('ValRequired'),
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50),
				"helpstep" => 1
			),
			"bursttemplateid" => array(
				"label" => _L('Template'),
				"value" => $this->burstData->burstTemplateId,
				"validators" => array(
					array('ValRequired')
				),
				"control" => array('SelectMenu', 'values' => (array('' => _L('Select PDF Template')) + $this->burstTemplates)),
				"helpstep" => 2
			)
		);

		// If we already have a burstId
		if (! is_null($this->burstId)) {
			// Then a file has already been uploaded

			// Hide the ID from sight!
			$formdata['id'] = array(
				'label' => 'thisshouldntneedalabel', // FIXME: Form.obj.php breaks without this
				'value' => $this->burstId,
				'control' => array('HiddenField'),
				'validators' => Array(
					array('ValRequired'),
					array('ValNumber', 'min' => 1)
				)
			);

			// we're just going to show a read-only representation
			$formdata['existingpdf'] = array(
				"label" => _L('PDF Document'),
				'value' => 'thisshouldntneedavalue', // FIXME: Form.obj.php breaks without this
				"control" => array('FormHtml', 'html' => $this->burstData->filename),
				"validators" => Array(),
				"helpstep" => 4
			);
		}
		else {
			// Otherwise we need to show the upload formitem to be able to select and upload a new PDF
			$formdata['thefile'] = array(
				"label" => _L('PDF Document'),
				"value" => '',
				"validators" => array(
					array('ValRequired')
				),
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
		$form = new Form($this->formName, $formdata, $helpsteps, $buttons, 'vertical');
		$form->multipart = (! $this->burstId);	// We only need multi-part encoding if we're uploading a new one
		$form->ajaxsubmit = false;		// We can't use AJAX form handling for multipart file uploads

		return($form);
	}

	/**
	 * Nifty bootstrap modal implementation lifted from tips.php/js
	 *
	 * @param string $content The content that is to appear within the modal body
	 * @param string $heading A brief string for the title/heading of the modal; optional, defaults to 'Error'
	 * @param boolean $autoshow A flag to automatically show the modal on page load; optional, defaults to true
	 *
	 * @return string Block of HTML code with requisite script tag needed to show automatically via jQuery
	 */
	// TODO - see about using defaultmodal in nav.inc.php instead of adding another one here; tips.php should match
	public function modalHtml($content, $heading='Error', $autoshow=true) {
		$html = <<<END
			<div id="pdfeditmodal" class="modal hide">
				<div class="modal-header">
					<h3 id="attachment-details">{$heading}</h3>
				</div>
				<div class="modal-body">
					<div id="tip-attachment-content">{$content}</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn" data-dismiss="modal">Close</button>
				</div>
			</div>
END;

		if ($autoshow) {
			$html .= <<<END
				<script language="JavaScript">
					(function($) {
						$('#pdfeditmodal').modal('show');
					}) (jQuery);
				</script>
END;
		}

		return($html);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

executePage(new PdfEditPage(isset($csApi) ? $csApi : null));

