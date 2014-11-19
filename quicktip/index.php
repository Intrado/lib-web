<?

// required for escapeHtml()
setlocale(LC_ALL, 'en_US.UTF-8');
mb_internal_encoding('UTF-8');

function escapeHtml($string) {
	return htmlentities($string, ENT_COMPAT, 'UTF-8') ;
}

/**
 * class TipSubmissionHandler
 *
 * Description: class to help manage the Quick Tip form field validation, 
 * form submission to a target iframe (POST to quicktip API POST endpoint for tip submission),
 * fetching customer (root org or org specifed via GET 'i' attribute) data via quicktip API REST endpoint for population 
 * into Organization and Topic dropdowns. 
 * 
 * @author Justin Burns <jburns@schoolmessenger.com>
 * @date 11/08/2013
 */

class TipSubmissionHandler {
	private $productName;
	private $rootOrgId;
	private $customerName;
	private $customerDataURL;
	private $customerData;

	private $baseCustomerURL;
	private $actionURL;
	private $organizations;
	private $topics;
	
	private $orgId;
	private $topicId;
	private $orgName;
	private $orgFieldName;
	private $topicName;
	private $message;
	private $firstName;
	private $lastName;
	private $email;
	private $phone;
	private $file;


	public function TipSubmissionHandler($options = array()) {

		// sets required private member variables to values in the $options arg array
		foreach ($options as $key => $value) {
			$this->$key = $value;
		}

		// set the customer's REST API URL for fetching their data via GET
		$this->customerDataURL = $this->baseCustomerURL . '/api/2/organizations/' . $this->rootOrgId . '/quicktip/info';

		// fetch customer data via curl GET request to customer's quicktip API endpoint
		$this->customerData = json_decode($this->fetchCustomerData());
		
		// render blank page and exit if customer data is null due to 404 response from fetchCustomerData() call
		if (!$this->customerData) {
			$this->renderBlankPage();
			exit;
		}

		// set the product name, ex SchoolMessenger & org field name (ex. School, Organization)
		$this->productName 	= $this->customerData->productName;
		$this->orgFieldName = $this->customerData->organizationFieldName;
		
		// set the form's action attribute; only include query string param if not 'root' org
		$this->actionURL 	= $_SERVER["PHP_SELF"] . (is_numeric($this->rootOrgId) ? '?i='.$this->rootOrgId : '');

		if (!isset($this->message)) {
			// build up the Organization and Topic combos
			$this->setOrganizations($this->customerData->organizations);
			$this->setTopics($this->customerData->topics);
		} 
	}

	public function renderBlankPage() {
		echo '<!DOCTYPE html><html lang="en"><head></head><body></body></html>';
	}

	/** 
	 * Performs curl GET request for resouce at customer's 
	 * quicktip API endpoint; ie $this->customerDataURL
	 * ex GET URL: https://<host>/<custname>/api/2/organizations/<rootorgid>/quicktip/info';
	 * returns a json array of objects
	 */
	public function fetchCustomerData() {
		$curl = curl_init($this->customerDataURL);
		curl_setopt($curl, CURLOPT_RETURNTRANSFER, 1);
		curl_setopt($curl, CURLOPT_SSL_VERIFYPEER, 0);
		$response = curl_exec($curl);
		curl_close($curl);

		return $response;
 	}

 	public function setOrganizations($orgs) {
 		$this->organizations = $orgs;
 	}

 	public function setTopics($topics) {
 		$this->topics = $topics;
 	}

 	/**
 	 * Gets the name of an organization or category/topic for a given org/topicid and org/topic array
 	 * @param int id - id of an Org or Topic
 	 * @param array arr - array of either Org or Topic objects
     * @return the name of an org or topic for the given id; from the respective array (Org/Topic)
 	 */
 	public function getName($id, $arr) {
		$name = '';
		foreach ($arr as $k => $obj) {
			if ($obj->id == $id) {
				return escapeHtml($obj->name);
			}
		}
		return $name;
 	}

	/**
	 * Returns a string containing all the <option> elements for a given array
	 * of objects, ex Org or Topics.
	 * @param array arrayOfObjects array of objects from either organizations or topics
	 * return string a string containing all the <option> elements for a given array of objects
	 * @return string
	 */
 	public function setSelectOptions($arrayOfObjects) {
		$html = '<option value="0">'.escapeHtml('-- Please select one of the following --').'</option>';
		foreach ($arrayOfObjects as $k => $obj) {
			$html .= '<option value="'.$obj->id.'">'.escapeHtml($obj->name).'</option>';
		}
		return $html;
 	}

 	/**
 	 * Provides the HTML for the starting Tip form page, using customer data fetched for their Orgs & Topics
 	 * return string representing the resulting HTML for the starting Tip form page
 	 */
 	public function renderTipForm() {
 		$html = '
 			<div class="alert"><strong>'.$this->productName.' Quick Tip allows you to submit an anonymous tip to school and district officials.</strong>
				Please select the appropriate '.$this->orgFieldName.' and Topic when submitting your tip.
				<div class="text-danger call911">If this is an emergency, please call 911.</div>
			</div>

			<form id="quicktip" name="quicktip" action="" method="POST" enctype="multipart/form-data" data-base-url="' . $this->baseCustomerURL .'"  target="thank-you-iframe">
			<fieldset>
					<label for="orgId">'.$this->orgFieldName.' <span class="sup" title="Required field">*</span></label>
					<select id="orgId" name="orgId" tabindex="1">';
			
			$html .= $this->setSelectOptions($this->organizations);
			
			$html .= '</select>
					<label for="topicId">Topic <span class="sup" title="Required field">*</span></label>
					<select id="topicId" name="topicId" tabindex="2">';
			
			$html .= $this->setSelectOptions($this->topics);
			
			$html .= '</select>
					<div id="tip-message-control-group" class="form-group">
						<label for="message" class="control-label">Message <span class="sup" title="Required field">*</span></label>
						<textarea id="message" class="form-control" name="message" rows="8" placeholder="Enter your tip here..." tabindex="3" maxlength="10000"></textarea>
					</div>
				</fieldset>
				<fieldset>
					<label for="file" title="Optional">Do you have a related image?</label>
					<div id="tip-attach-instruction">If so, you can attach it to your tip to help provide additional information.</div>
					<input id="file" name="file" type="file" tabindex="4">
					</fieldset>
				<div id="tip-contact" class="alert">
					<h4>Contact Information &nbsp;<span class="small">(Optional)</span></h4>
					<p>You have the option to leave your personal contact information. If provided, you may be contacted for more information if necessary.</p>
					<fieldset>
						<label for="firstname">First</label>
						<input id="firstname" name="firstname" type="text" placeholder="First name" value="" title="Enter your first name" tabindex="5" maxlength="50">
						<label for="lastname">Last</label>
						<input id="lastname" name="lastname" type="text" placeholder="Last name" value="" title="Enter your last name" tabindex="6" maxlength="50">
					</fieldset>
					<fieldset>
						<label for="email">Email</label>
						<input id="email" name="email" type="email" placeholder="Email address" value="" title="Enter your email address, ex. janedoe@example.com" tabindex="7" maxlength="255">
					</fieldset>
					<fieldset>
						<label for="phone">Phone</label>
						<input id="phone" name="phone" type="tel" pattern="^\(?\d{3}\)?[- \.]?\d{3}[- \.]?\d{4}$" placeholder="Phone number" value="" title="Enter your phone number. accepted formats: (555) 555-5555, 555-555-5555, 555.555.5555, or 555 555 5555" tabindex="8">
					</fieldset>
				</div>
				<div id="tip-error-message" class="alert alert-danger hide"></div>
				<fieldset>
					<button id="tip-submit" class="btn btn-lg btn-primary" type="submit" tabindex="9"><span id="submitting-tip-span" class="hide">Submitting Tip...</span><span id="submit-tip-span">Submit Tip</span></button>
				</fieldset>
			</form>
			';

		return $html;
 	}

 	/**
 	 * Provides the HTML for the final Thank You page, using data submitted by user in
 	 * return string representing the resulting HTML for the 'Thank You for your tip' landing page
 	 */
 	public function renderThankYou() {
		// get the topic and org names (based on their id) to show on Thank You page
		if (isset($this->customerData)) {
			$this->topicName = $this->getName($this->topicId, $this->customerData->topics);
			$this->orgName 	 = $this->getName($this->orgId, $this->customerData->organizations);
		}

		$html = '
			<div id="thank-you" class="alert">
				<h1>Thank You for the Tip!</h3>
				<div class="text-danger call911">If this is an emergency, please call 911.</div>
			</div>
			<div class="summary-info">
				<div class="summary-heading">Summary of the tip information you submitted:</div>
				<div><span class="summary-label">'.$this->orgFieldName.':</span> &nbsp;<div class="summary-value">'. $this->orgName. '</div></div>
				<div><span class="summary-label">Topic:</span> &nbsp;<div class="summary-value">'. escapeHtml($this->topicName).'</div></div>
				<div><span class="summary-label">Message:</span> &nbsp;<div class="summary-value message-text">'.escapeHtml($this->message).'</div></div>';
		if ($this->file) {
			$html .= '<div id="summary-attachment-container"><span class="summary-label">Attachment:</span> &nbsp;<div class="summary-value">'.escapeHtml($this->file).'</div></div>';
		}

		// show the user's contact info, if provided
		if ($this->firstname != null || $this->lastname != null || $this->email != null || $this->phone != null) {
				$html .= '<div class="alert contact-info">
						<div class="summary-heading">Contact info you provided with your tip:</div>';
				if ($this->firstname != null && $this->lastname != null) {
					$html .= '<div><span class="summary-label">Name:</span> &nbsp;<div class="summary-value">'.escapeHtml($this->firstname).' '.escapeHtml($this->lastname).'</div></div>';
				}
				if ($this->firstname != null && $this->lastname == null) {
					$html .= '<div><span class="summary-label">First Name:</span> &nbsp;<div class="summary-value">'.escapeHtml($this->firstname).'</div></div>';
				}
				if ($this->firstname == null && $this->lastname != null) {
					$html .= '<div><span class="summary-label">Last Name:</span> &nbsp;<div class="summary-value">'.escapeHtml($this->lastname).'</div></div>';
				}								
				if ($this->email != null) {
					$html .= '<div><span class="summary-label">Email:</span> &nbsp;<div class="summary-value">'.escapeHtml($this->email).'</div></div>';
				}
				if ($this->phone != null) {
					$html .= '<div><span class="summary-label">Phone:</span> &nbsp;<div class="summary-value">'.escapeHtml($this->phone).'</div></div>';
				}
				$html .= '</div>';
		}

		$html .= '</div>
			<form id="newquicktip" name="newquicktip" action="' . $this->actionURL . '" method="POST">
				<fieldset>
					<button id="new-tip" class="btn btn-lg btn-primary" type="submit">Done</button>
				</fieldset>
			</form>';

		return $html;
 	}

 	/**
 	 * Tip-specific JS; handles 'final' form submission (after initial form POST to API endpoint
 	 * with the POST results sent to a hidden target iframe)
 	 * @return string custom javascript for tip form handling
  	 */
 	public function renderTipJavascript() {
		$html = '
			<script type="text/javascript" src="tip.js"></script>
			<script type="text/javascript">
				var qtip,
					form 			= window.document.getElementById("quicktip"),
					targetIframe 	= window.document.getElementById("thank-you-iframe"),
					mask 			= window.document.getElementById("mask");
				
				window.onload = function() {
					qtip = new QuickTip();
					qtip.iframeHandler = qtip.bind(function() {
						var iframeContent = targetIframe.contentWindow.document.body.innerHTML;

						// if iframe content contains HTML response with "Thank You", it means the tip submission
						// was successfully received, else there was an error
						if (iframeContent && iframeContent.indexOf("Thank you") > -1) {
							// remove hidden target iframe (no longer needed)
							targetIframe.parentNode.removeChild(targetIframe);

							// remove the previous POST API URL (so we don\'t accidentally re-post to POST API URL)
							// and set action attribute to originating script with the rootorgid query param,
							// i.e. /<custname>/quicktip/tip.php?i=<rootorgid>
							form.setAttribute("action", "' . $this->actionURL . '");

							// remove the target attribute to make sure we submit (post) to ourself, not the target iframe;
							form.removeAttribute("target");

							// submit form (posts back to ourself; i.e. /<custname>/quicktip/tip.php?i=<rootorgid>)
							form.submit();
						} else {
							// there was an error; show the error message
							qtip.setErrorMessage("Sorry, there was an error.  Please try again.");
							qtip.addClass(mask, "hide");
						}
					});

					// listen to "onload" event (fired when iframe gets loaded with initial API POST response) 
					// from targetIframe and call iframeHandler callback
					qtip.addEvent(targetIframe, "load", qtip.iframeHandler);
				};
			</script>';

		return $html;
 	}

 	/**
 	 * Provides the final HTML for either the starting Tip form or Thank You page, 
 	 * depending on existance of post data (or not)
 	 * echoes string representing the final HTML for the Tip submission form or the 'Thank You for your tip' landing page
 	 */
	public function render() {
		$html = '
		<!DOCTYPE html>
		<html lang="en">
			<head>
				<meta charset="utf-8">
				<title>Quick Tip - '. ((isset($this->message)) ? 'Thank You for the Tip!' : 'Submit an Anonymous Tip') .' - Powered by '.$this->productName.'</title>
				<link rel="stylesheet" type="text/css" href="tip.css">
			</head>
			<body>		
				<div id="tip-container">
					<div id="mask" class="hide"></div>
					<div class="tip-chat"></div>
					<h1>'.$this->productName.' Quick Tip</h1>
					<div id="tip-orgname-label">'. $this->customerData->customerName .' </div>';

					if (isset($this->message)) {
						$html .= $this->renderThankYou();
					} 

					else {
						$html .= $this->renderTipForm();
					}

		$html .= '</div>';

		// only add hidden target iframe & init QuickTip (JS) if we're on the starting page (not the Thank You landing page)
		if (!isset($this->message)) {
			$html .= '<iframe id="thank-you-iframe" name="thank-you-iframe" style="display:none;"></iframe>';
			$html .= $this->renderTipJavascript();
		}	

		$html .= '</body></html>';

		echo $html;
	}
}
///////////////// end of Tip class ///////////////////////

// scrape customer 'name' out of the URL (for use in 'baseCustomerURL')
$uriParts 	= explode('/', $_SERVER['REQUEST_URI']); // ex /custname/quicktip/tip.php
$custName 	= $uriParts[1];

// get customer's root org id (used in API URL) from $_GET param
$rootOrgId 	= isset($_GET['i']) ? $_GET['i'] : 'root';

// define options hash to pass to Tip constructor for proper initialization of Tip instance
$options = array(
	"rootOrgId"			=> $rootOrgId, 
	"baseCustomerURL"	=> (isset($_SERVER['HTTPS']) ? 'https://' : 'http://') . $_SERVER['SERVER_NAME'] . '/' . $custName,
	"orgId" 	 		=> isset($_POST['orgId']) ? $_POST['orgId'] : null,
	"topicId" 			=> isset($_POST['topicId']) ? $_POST['topicId'] : null,
	"message" 			=> isset($_POST['message']) ? $_POST['message'] : null,
	"file"				=> isset($_FILES['file']['name']) ? $_FILES['file']['name'] : null,
	"firstname"  		=> isset($_POST['firstname']) ? $_POST['firstname'] : null,
	"lastname"   		=> isset($_POST['lastname'])  ? $_POST['lastname'] : null,
	"email" 			=> isset($_POST['email'])  	? $_POST['email'] : null,
	"phone"  			=> isset($_POST['phone'])  ? $_POST['phone'] : null
);

// initialize new TipSubmissionHandler instance with $options arg
$tipSubmissionHandler = new TipSubmissionHandler($options);

// render final HTML markup for Tip Submission form or
// resulting Thank You landing page, depending on $options
$tipSubmissionHandler->render();

?>
