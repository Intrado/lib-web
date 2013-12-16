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

require_once('obj/Burst.obj.php');


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


// -----------------------------------------------------------------------------
// CUSTOM PAGE FUNCTIONALITY
// -----------------------------------------------------------------------------

class PDFEditPage extends PageForm {

	const MAX_PDF_UPLOAD_BYTES = 209715200; // 200MB

	//var $burst = null;		// DBMO for the burst record we're working on
	var $burstData = null;		// Associative array for the burst record we're working on
	var $burstId = null;		// ID for the DBMO object to interface with
	var $burstTemplates = array();	// An array to collect all the available burst templates into

	var $customerName = '';		// For API access (customer name)
	var $customerURL = '';		// Base URL for the customer
	var $APIURL = '';		// API URL for the customer

	function PDFEditPage() {
		global $USER;

		// URL parts needed for API access
		// TODO : is the internal API HTTPS? does it need to be? it would reduce overhead if it was plain HTTP...
		if (isset($_SERVER['REQUEST_URI'])) {
			$uriParts = explode('/', $_SERVER['REQUEST_URI']); // ex /custname/...
			$this->customerName = $uriParts[1];
			$this->customerURL = 'http' . (isset($_SERVER['HTTPS']) ? 's' : '') . "://{$_SERVER['SERVER_NAME']}/{$this->customerName}/";
			$this->APIURL = "{$this->customerURL}api/2/users/{$USER->id}/bursts";
		}

		parent::PageForm(array());
	}

	function isAuthorized(&$get, &$post, &$request, &$session) {
		return(true); // open to the world, unconditionally!
	}

	function beforeLoad(&$get, &$post, &$request, &$session) {

		// The the query string/post data has a burst ID specified, then grab it
		$this->burstId = (isset($request['id']) && intval($request['id'])) ? intval($request['id']) : null;

		// Special case for handling deletions
		if (isset($get['deleteid'])) {
			// best practice: use transaction whenever modifying data
			// (around whole section or logical atomic block)
			//Query("BEGIN");
			//FooDBMO::delete($get['deleteid']);
			//Query("COMMIT");
			redirect();
		}
		// Any other special case. early-exit operations needed for our page?
	}

	function load(&$get, &$post, &$request, &$session) {

		// Make the burst DBMO
		//$this->burst = new Burst($this->burstId);
		if ($this->burstId) {
			$this->apiGetBurstData();
		}
		
		// Get a list of burst templates
		$this->loadBurstTemplates();

		// Make the edit FORM
		$this->form = $this->factoryFormPDFUpload();
	}

	function loadBurstTemplates() {
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

	function factoryFormPDFUpload() {
		$formdata = array(
			"pdfname" => array(
				"label" => _L('Name'),
				"value" => "",
				"validators" => array(
					array('ValRequired'),
					array("ValLength","min" => 3,"max" => 50)
				),
				"control" => array("TextField","size" => 30, "maxlength" => 50, "autocomplete" => "test"),
				"helpstep" => 1
			),
			"bursttemplate" => array(
				"label" => _L('Template'),
				"value" => '0',
				"validators" => array(),
				"control" => array('SelectMenu', 'values' => (array('' => _L('Select PDF Template')) + $this->burstTemplates)),
				"helpstep" => 2
			)
		);

		// If we already have a burstId
		if ($this->burstId) {
			// Then a file has already been uploaded, so we're just going to show a read-only representation
			$formdata[] = array(
				"label" => _L('Uploaded PDF'),
				//"control" => array('FormHtml', 'html' => $this->burst->filename)
				"control" => array('FormHtml', 'html' => $this->burstData['filename'])
			);
		}
		else {
			// Otherwise we need to show the upload formitem to be able to select and upload a new PDF
			$formdata[] = array(
				"label" => _L('Upload PDF'),
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
			submit_button(_L('Upload'), 'submit', 'tick'),
			icon_button(_L('Cancel'), 'cross', null, 'pdfmanager.php')
		);

		return(new Form('pdfuploader', $formdata, $helpsteps, $buttons, 'vertical'));
	}

	function afterLoad() {
		$this->form->handleRequest();
		$this->options['title'] = ($this->burstId) ? _L('Edit PDF Properties') : _L('Upload New PDF');
	}

	function beforeRender() {
	}

	function render() {
		if ($this->burstId && ! $this->burstData) {
			$html = _L('There is no PDF on file with the requested ID.') . "<br/>\n";
		}
		else {
			$html = $this->form->render();
		}

		return($html);
	}

	function apiGetBurstData() {
		$creq = curl_init("{$this->APIURL}/{$this->burstId}");
		curl_setopt($creq, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($creq, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($creq, CURLOPT_HTTPHEADER, array(
			"Accept: application/json",
			"X-Auth-SessionId: " . $_COOKIE[$this->customerName . '_session'])
		);

		$response = curl_exec($creq);

		$hdrsize = curl_getinfo($creq, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $hdrsize);
		$body = substr($response, $hdrsize);
		$code = curl_getinfo($creq, CURLINFO_HTTP_CODE);

		curl_close($creq);

		// If we got anything other than 200OK, then return false, else return just the response body without headers
		$this->burstData = ($code == 200) ? json_decode($body) : null;
	}

	function apiPutBurstData() {

		// We don't support PUTting ALL fields, only some...
		// TODO - is the API doc correct on this? it seems to show all fields updatable...
		$postData = array(
			'name' => $this->burstData['name'],
			'bursttemplateid' => $this->burstData['bursttemplateid']
		);

		$creq = curl_init("{$this->APIURL}/{$this->burstId}");
		curl_setopt($creq, CURLOPT_PUT, 1);
		curl_setopt($creq, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($creq, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($creq, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($creq, CURLOPT_HTTPHEADER, array(
			"Accept: application/json",
			"X-Auth-SessionId: " . $_COOKIE[$this->customerName . '_session'])
		);
		
		if (! ($response = curl_exec($creq))) return(false);

		$hdrsize = curl_getinfo($creq, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $hdrsize);
		$body = substr($response, $hdrsize);
		$code = curl_getinfo($creq, CURLINFO_HTTP_CODE);

		curl_close($creq);

		return($code == 200);
	}

	function apiPostBurstData() {

		// get all the uploaded files names (there should be one)
		$uploadedNames = array_keys($_FILES);
		// get the first one
		$uploadedName = $uploadedNames[0];
		// get the details for this one
		$uploadedFile = $_FILES[$uploadedName];

		// ref: http://stackoverflow.com/questions/15223191/php-curl-file-upload-multipart-boundary
		$postData = array(
			'name' => $this->burstData['name'],
			'templateid' => $this->burstData['bursttemplateid'], // TODO - can we change this? it is inconsistent with the GET and response
			$uploadedName => "@{$uploadedFile['tmp_name']};type=application/pdf"
		);

		$creq = curl_init("{$this->APIURL}/{$this->burstId}");
		curl_setopt($creq, CURLOPT_POST, 1);
		curl_setopt($creq, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($creq, CURLOPT_SSL_VERIFYPEER, 0);
		curl_setopt($creq, CURLOPT_POSTFIELDS, http_build_query($postData));
		curl_setopt($creq, CURLOPT_HTTPHEADER, array(
			"Content-type: multipart/form-data",
			"Accept: application/json",
			"X-Auth-SessionId: " . $_COOKIE[$this->customerName . '_session'])
		);
		
		if (! ($response = curl_exec($creq))) return(false);

		$hdrsize = curl_getinfo($creq, CURLINFO_HEADER_SIZE);
		$headers = substr($response, 0, $hdrsize);
		$body = substr($response, $hdrsize);
		$code = curl_getinfo($creq, CURLINFO_HTTP_CODE);

		curl_close($creq);

		return($code == 200);
	}
}


// -----------------------------------------------------------------------------
// PAGE INSTANTIATION AND DISPLAY
// -----------------------------------------------------------------------------

executePage(new PDFEditPage());

