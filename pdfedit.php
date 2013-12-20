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

require_once('obj/APIClient.obj.php');
require_once('obj/BurstAPIClient.obj.php');


/**
 * PDF Edit Page class
 *
 */
class PDFEditPage extends PageForm {

	protected $burstData = null;		// Associative array for the burst record we're working on
	protected $burstId = null;		// ID for the DBMO object to interface with
	protected $burstTemplates = array();	// An array to collect all the available burst templates into

	protected $error = '';			// A place to capture an error string to control modal display

	protected $burstAPI = null;

	public function isAuthorized(&$get, &$post, &$request, &$session) {
		global $USER;
		return($USER->authorize('canpdfburst'));
	}

	public function beforeLoad(&$get, &$post, &$request, &$session) {
		global $USER;

		// Get our REST API Client connection together
		$apiCustomer = customerUrlComponent();
		$this->burstAPI = new BurstAPIClient(
			$_SERVER['SERVER_NAME'],
			$apiCustomer,
			$USER->id,
			$_COOKIE[strtolower($apiCustomer) . '_session']
		);

		// The the query string/post data has a burst ID specified, then grab it
		$this->burstId = (isset($request['id']) && intval($request['id'])) ? intval($request['id']) : null;
	}

	public function load(&$get, &$post, &$request, &$session) {

		// If we're editing an existing one, get its data
		if ($this->burstId) {

			// Pull in the current data for this PDF Burst record
			$this->burstData = $this->burstAPI->getBurstData($this->burstId);

			// If we have been flagged as having reloaded on account of data having changed...
			if ($_SESSION['burstreload']) {

				// Clear the flag and set the error message; this will allow the message to display, but
				// upon dismissal show the form repopulated with the freshly loaded burst data from above
				unset($_SESSION['burstreload']);
				$this->error = _L("This PDF Document's record was changed in another window or session; the current data has been reloaded so that your submission will be current. Please review and reapply any additional changes needed, then resubmit.");
			}
		}
		else {
			$this->burstData = (object) null;
			$this->burstData->name = '';
			$this->burstData->burstTemplateId = '';
			$this->burstData->filename = '';
		}

		// Get a list of burst templates
		$this->loadBurstTemplates();

		// Make the edit FORM
		$this->form = $this->factoryFormPDFUpload();
	}

	public function afterLoad() {

		// Normal form handling makes getData() work...
		$this->form->handleRequest();

		// If the form was submitted...
		if ($this->form->getSubmit()) {

			// Get the POSTed data from the form
			$postdata = $this->form->getData();
			$name = $postdata['name'];
			$bursttemplateid = intval($postdata['bursttemplateid']);

			// Are we saving edits or uploading anew?
			if ($this->burstId) {

				// check if the data hase changed and display a notification if so...
				if ($this->form->checkForDataChange()) {
					$_SESSION['burstreload'] = true;
					redirect("?id={$this->burstId}");
				}
				else {
					// Saving edits!
					if ($this->burstAPI->putBurstData($this->burstId, $name, $bursttemplateid)) {
						notice(_L('The PDF Document was successfully updated'));
					} else {
						$this->error = _L('There was a problem updating the PDF Document - please try again later');
					}
				}
			}
			else {
				// Uploading anew!
				if ($this->burstAPI->postBurst($name, $bursttemplateid)) {
					notice(_L('The PDF Document was successfully stored'));
				} else {
					$this->error = _L('There was a problem storing the new PDF Document - please try again later');
				}
			}

			// If there were no handling errors...
			if (! strlen($this->error)) {
				// return to the manager page
				redirect('pdfmanager.php');
			}
		}

		// The page title depends on whether editing existing or uploading anew
		$this->options['title'] = ($this->burstId) ? _L('Edit PDF Document Properties') : _L('Upload New PDF Document');
	}

	public function render() {

		// URL hacking or what?
		if ($this->burstId && ! is_object($this->burstData)) {
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
	 * Get the list of burst templates directly from the database
	 *
	 * There is no API support for this at this time, so we have to go direct. The resulting array
	 * is stored in an instance property, $this->burstTemplates.
	 */
	protected function loadBurstTemplates() {
		if (is_object($res = Query('SELECT `id`, `name` FROM `bursttemplate` WHERE NOT `deleted`;'))) {
			while ($row = DBGetRow($res, true)) {
				$this->burstTemplates[$row['id']] = $row['name'];
			 }
		}
	}

	/**
	 * Factory method to spit out a form object for PDF uploads
	 *
	 * @return object Form
	 */
	protected function factoryFormPDFUpload() {
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
		if ($this->burstId) {
			// Then a file has already been uploaded

			// Hide the ID from sight!
			$formdata['id'] = array(
				'value' => $this->burstId,
				'control' => array('HiddenField')
			);

			// we're just going to show a read-only representation
			$formdata[] = array(
				"label" => _L('PDF Document'),
				"control" => array('FormHtml', 'html' => $this->burstData->filename)
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
		$form = new Form('pdfuploader', $formdata, $helpsteps, $buttons, 'vertical');
		$form->multipart = true;
		$form->ajaxsubmit = false;

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
	protected function modalHtml($content, $heading='Error', $autoshow=true) {
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

executePage(new PDFEditPage());

