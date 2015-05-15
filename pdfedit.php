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

	/**
	 * Constructor
	 *
	 * Use dependency injection to make those external things needed separately testable.
	 *
	 * @param object $csApi An instance of CommsuiteApiClient
	 */
	public function __construct($csApi) {
		$this->csApi = $csApi;
		$this->options['validators'] = array('ValClientOnlyRequired');
		parent::__construct($this->options);
	}

	public function isAuthorized(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {
		global $USER;
		return(getSystemSetting("_haspdfburst", false) && $USER->authorize('canpdfburst'));
	}

	public function beforeLoad(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

		if (isset($request['id']) && intval($request['id'])) {

			// Peel the burst ID off the URL, stash it in the session...
			$session['burstid'] = intval($request['id']);

			// .. then redirect back to ourselves to clean up the URL
			redirect(); 
		}
		else if (isset($session['burstid'])) {
			$this->burstId = $session['burstid'];
		}
		else {
			$this->burstId = null;
		}

		$this->options['windowTitle'] = ($this->burstId) ? _L('Edit Existing Document') : _L('Create New Document'); 
	}

	public function load(&$get=array(), &$post=array(), &$request=array(), &$session=array()) {

		// If we're editing an existing one, get its data
		if (! is_null($this->burstId)) {

			// Pull in the current data for this PDF Burst record
			$this->burstData = $this->csApi->getBurstData($this->burstId);
		}
		else {
			$this->burstData = (object) null;
			$this->burstData->name = '';
			$this->burstData->status = 'new';
			$this->burstData->burstTemplateId = '';
			$this->burstData->filename = '';
		}

		// If there was a data reload issue
		if (! is_null($this->burstId)) {
			// If we have been flagged as having reloaded on account of data having changed...
			if (isset($session['burstreload'])) {

				// Clear the flag and set the error message; this will allow the message to display, but
				// upon dismissal show the form repopulated with the freshly loaded burst data from above
				unset($session['burstreload']);
	
				// Add this error message to the page output, but still
				// allow the form to be displayed to take edits and resubmit
				$this->error = _L("This PDF document was changed in another window or session during the time you've had it open. Please review the current data. You may need to redo your changes and resubmit.");
			}
		}

		//in case of sent burst, just get the current template
		$query = (!is_null($this->burstId) && $this->burstData->status != 'new') ? "SELECT id, name from bursttemplate where id=".$this->burstData->burstTemplateId : 'SELECT `id`, `name` FROM `bursttemplate` WHERE NOT `deleted`;';
		// Get a list of burst templates
		if (is_object($res = Query($query))) {
			while ($row = DBGetRow($res, true)) {
				$this->burstTemplates[$row['id']] = $row['name'];
			 }
		}

		// Make the edit FORM
		$this->form = $this->factoryFormPDFUpload();
	}

	public function afterLoad() {

		// Normal form handling makes form->getData() work...
		$this->form->handleRequest();

		// If the form was submitted...
		if ($this->form->getSubmit()) {

			// Check if the data has changed and display a notification if so...
			if (! is_null($this->burstId) && $this->form->checkForDataChange()) {

				// Flag the problem, then redirect back to ourselves to redisplay the form with now-current data
				$_SESSION['burstreload'] = true;
				redirect("?id={$this->burstId}");
			}

			// Check for validation errors
			$action = (is_null($this->burstId)) ? 'created' : 'updated';
			if (($errors = $this->form->validate()) === false) {

				// Get the POSTed data from the form
				$postdata = $this->form->getData();
				$name = $postdata['name'];
				$bursttemplateid = intval($postdata['bursttemplateid']);

				// We're storing a new record if burstId is null, otherwise updating an existing record
				if ($this->csApi->setBurstData($this->burstId, $name, $bursttemplateid)) {

					// For success, we redirect back to the manager page with this notice to be shown on that page:
					unset($_SESSION['burstid']);
					notice(_L("The PDF Document was successfully {$action}."));
					redirect('pdfmanager.php');
				}
			}

			// For errors, we fall through to render() and let this error message be shown:
			$this->error = _L("The PDF Document could not be {$action}. Please try again later.");
		}
	}

	public function render() {
		// define main:subnav tab settings
		$this->options["page"]	= 'notifications:pdfmanager';
		
		//Note: This is the title of the page. It should not also be the header of the form "window".
		// This 'title' is set to global $TITLE in the base class.
		$this->options['title'] = _L('Document Editor');

		//If you are NOT creating a new Document or the burst data we're working on is NOT a valid object in the db
		//OR you don't have permission to view it because you don't own it, you get this error.
		// The user is probably trying to bypass the page by editing the ID in the URL.
		if (! (is_null($this->burstId) || is_object($this->burstData))) {
			$html = _L('The Document you have requested could not be found. It may not exist or your account does not have permission to view it.') . "<br/>\n";
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
	 * Method to create form object content for this page, including guide and mouseover help.
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
			)
		);

		if ($this->burstData->status === 'new') {
			$formdata["bursttemplateid"] = array(
				"label" => _L('Template'),
				"value" => $this->burstData->burstTemplateId,
				"validators" => array(
					array('ValRequired'),
					array('ValInArray', 'values' => array_keys($this->burstTemplates))
				),
				"control" => array('SelectMenu', 'values' => (array('' => _L('Select PDF Template')) + $this->burstTemplates)),
				"helpstep" => 2
			);
		} else {
			$formdata['bursttemplateid'] = array(
				"label" => _L('Template'),
				"fieldhelp" => _L('This template was for processing this Document for delivery'),
				'value' => $this->burstData->burstTemplateId,
				"control" => array('FormHtml', 'html' => $this->burstTemplates[$this->burstData->burstTemplateId]),
				"validators" => Array(),
				"helpstep" => 2
			);
		}


		// If we already have a burstId
		if (! is_null($this->burstId)) {

			// Then a file has already been uploaded; we're 
			// just going to show a read-only representation
			$formdata['existingpdf'] = array(
				"label" => _L('File'),
				"fieldhelp" => _L('This is the PDF file which will be processed for delivery.'),
				'value' => 'thisshouldntneedavalue', // FIXME: Form.obj.php breaks without this
				"control" => array('FormHtml', 'html' => $this->burstData->filename),
				"validators" => Array(),
				"helpstep" => 3
			);
		}
		else {
			// Otherwise we need to show the upload formitem to be able to select and upload a new PDF
			$formdata['thefile'] = array(
				"label" => _L('File'),
				"fieldhelp" => _L('Click the Choose File button and navigate to the location of the PDF file on your computer.'),
				"value" => '',
				"validators" => array(
					array('ValClientOnlyRequired')
				),
				"control" => array('FileUpload'),
				"helpstep" => 3
			);
		}

		//Now we build the help. This creates the Guide help and also inserts the
		//field help for each form item depending on context (uploading or editing).
		if (is_null($this->burstId)) {
			//The page is in "upload file" form.
			$formdata['name']['fieldhelp'] = _L('Enter a descriptive name for the Document.');
			$formdata['bursttemplateid']['fieldhelp'] = _L('Select the template which matches the layout of the PDF file. See Guide for more information.');
			$helpsteps = array(
				_L('Enter a descriptive name for the Document you wish to process and deliver.'),
				_L('Select template which should be used when processing the PDF file for delivery. Templates are designed ' .
					'to be compatible with specific PDF file layouts. If you do not already have a template for the PDF file you' .
					' wish to upload, please contact support. A representative will work with you to create a template within 24 hours.'),
				_L('Click the Choose File button to upload a PDF file from your computer.')
			) ;
		} else {
			//The page is in "edit document" form.
			$formdata['name']['fieldhelp'] = _L('The name of the Document.');
			$formdata['bursttemplateid']['fieldhelp'] = _L('This is the template which is used when processing the PDF file for delivery. See Guide for more information.');
			$helpsteps = array(
				_L('This is the name associated with this Document. You may edit this field if you would prefer a different name.'),
				_L('This is the template which will be used when processing this Document for delivery. Templates are designed to be compatible with specific PDF file layouts. If this is not the correct template for the layout of this PDF file, you may select another. If there is no template for processing this PDF file, please contact support. A representative will work with you to create a template within 24 hours.'),
				_L('This is the PDF file associated with this delivery Document.')
			);
		}

		$buttons = array(
			submit_button(_L($this->burstId ? 'Save' : 'Upload'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'pdfmanager.php')
		);

		// A new form with some defaults overridden...
		$form = new Form($this->formName, $formdata, $helpsteps, $buttons);
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

/**
 * This whacky custom validator is made necessary by the fact that PHP cannot
 * see the file field in the POST data since it goes into the multi-part
 * section. Therefore we'll fake out the server-side part of the validator by
 * always making it pass, but on the client side still show that the field is
 * required since JavaScript CAN see the form field's "value" which would
 * normally have the filename of the selected file in it.
 *
 * Note that if the user touches the field, but makes no file selection, the
 * server will mark the field as valid, and then the vlient side will not re-
 * check because it trusts the server as the source of truth. So it will be
 * incorrectly marked as valid even though it is not.
 */
class ValClientOnlyRequired extends Validator {
	var $isrequired = true;

	function validate ($value, $args) {
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (value.length == 0)
					return label + " is required";
				return true;
			}';
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

executePage(new PdfEditPage(isset($csApi) ? $csApi : null));

