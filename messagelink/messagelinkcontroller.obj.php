<?

/**
 * Simple controller class to handle message/attachment link request param(s), fetch (model) data via
 * a Thrift-based MessageLinkClient, and instantiate/render the appropriate view to the user.
 *
 * This controller is part of an MVC-ish design pattern, where the "model" is considered the MessageLinkClient (since it handles
 * all the business logic on the server/java side, provides the data to be rendered in the view.  The controller has a reference
 * to a single view it controls; it instantiates appropriate view based on the request params, and renders view to user.
 *
 * Currently, their are 3 different main view types that can be rendered to the user depending on
 * the request params (s, mal):
 *
 * 1) If only 's' is provided, this implies a request to a "message link", thus
 * a "MessageLink" view is shown to the user.
 *
 * 2) If both 's' and 'mal' are provided, this implies a request for an "attachment link" and thus a
 * "Secure Document Delivery" view is showing to the user.
 * 		There are currently only 2 possible SDD views a user will see:
 * 		1) a password-protected view; which the user will need to enter their specific password, ex pkey, to download
 * 		2) a download view (non-protected); after a 5s timer completes, the user's download will begin automatically
 *
 * If there are errors during the initialization of the MessageLinkClient, or the fetching of date from the client, or
 * the user doesn't enter the correct password, etc., then appropriate error views/messages are shown to the user.
 *
 * @author: Justin Burns <jburns@schoolmessenger.com
 * @date: Feb 28, 2014
 *
 */
class MessageLinkController {

	public $request;
	public $model;
	public $view;
	public $customerErrorMessage = "The requested information was not found. The content you are looking for does not exist or has expired.";

	/**
	 * @param array $request params ('s', 'mal') from initial entry/page request
	 * @param object $protocol TProtocol thrift protocol object
	 * @param object $transport TTransport thrift transport object
	 */
	public function __construct($request = array(), $protocol, $transport) {
		if (is_array($request) && count($request) > 0) {
			$this->request = $request;
		}

		if (isset($protocol)) {
			$this->protocol = $protocol;
		}

		if (isset($transport)) {
			$this->transport = $transport;
		}
	}

	/**
	 *
	 * @param object $model MessageLinkClient
	 */
	public function initView($model = null) {
		if (isset($model)) {
			$this->model = $model;
		}

		// verify MessageLink thrift protocol and transport objects exist,
		// if not, create error message view
		if (!isset($this->model) || empty($this->model) || !isset($this->protocol) || !isset($this->transport)) {
			$this->logError("Cannot use AppServer; MessageLinkClient failed to be initialized");
			$this->view = $this->getErrorMessageView();
			return;
		}

		// fetch response data (model->attributes) for the given messagelink ('s') code;
		$this->model->fetchRequestCodeData($this->request['s']);

		// if messagelink code ('s') is invalid, model->attributes will be undefined; create error message view
		if (!$this->model->getAttributes()) {
			// MessageLinkClient threw an exception due to invalid s code, show error message view
			$this->view = $this->getErrorMessageView($this->customerErrorMessage);
			return;
		}

		// if we make it this far, it means we've successfully fetched model data for the given 's' code
		// now inspect s and mal to determine which view to create (ML, SDD, or Error view)
		if (isset($this->request['s']) && !empty($this->request['s']) ) {
			// add field to model->attributes required by both ML or SDD UI's
			$this->model->setAttributes(array("messageLinkCode" => $this->request['s']));

			if (!isset($this->request['mal'])) {
				// request is for messagelink, not SDD; create MessageLink UI.
				$this->view = $this->getAppView('ML');

			} else if (isset($this->request['mal']) && !empty($this->request['mal'])) {
				// add field to model->attributes required by SDD UI
				$this->model->setAttributes(array("attachmentLinkCode" => $this->request['mal']));

				// request is for SDD; create SDD UI;
				$this->view = $this->getAppView('SDD');

			} else {
				// show error if mal code set but empty, ex. mal=
				$this->logError("Unable to find the attachmentlinkcode: " . urlencode($this->request['mal']));
				$this->view = $this->getErrorMessageView($this->customerErrorMessage);
			}

		} else {
			// show error if s code is not set, ie no s param at all in query string,
			// or if s is set and empty, ex s=
			$this->logError("Unable to find the messagelinkcode: " . urlencode($this->request['s']));
			$this->view = $this->getErrorMessageView($this->customerErrorMessage);
		}
	}

	
	/**
	 * @param string $app 'SDD' or 'ML'
	 * @return MessageLinkItemView
	 */
	public function getAppView($app) {

		$pageTitleAppendage = $this->model->get("customerdisplayname") ." - Powered by " . $this->model->get("productName");
		$messageInfo = $this->model->getAttributes()->messageInfo;

		if ($app == 'SDD' &&
			isset($messageInfo->selectedEmailMessage) &&
			isset($messageInfo->selectedEmailMessage->attachmentLookup)) {
			// ensure selectedEmailMessage object and its child property
			// attachmentLookup exist before creating the SDD view

			// add additional attr(s) to model->attributes, required in SDD UI
			$this->model->setAttributes(array(
				"pageTitle" => "Secure Document Delivery from ". $pageTitleAppendage,
				"emailMessage" => $emailMessage = $messageInfo->selectedEmailMessage,
				"attachmentInfo" => $attachmentInfo = isset($emailMessage->attachmentLookup[$this->request['mal']]) ?
					$emailMessage->attachmentLookup[$this->request['mal']] : null
			));

			if (!is_object($attachmentInfo)) {
				return $this->getErrorMessageView($this->customerErrorMessage);
			}

		} else if ($app == 'ML' &&
			isset($messageInfo->selectedPhoneMessage) &&
			isset($messageInfo->selectedPhoneMessage->nummessageparts)) {
			// ensure selectedPhoneMessage object and its child property nummessageparts exist
			// before creating the MessageLink view

			// add pageTitle to model->attributes, required in MessageLink UI
			$this->model->setAttributes(array("pageTitle" => "Voice Message Delivery from ". $pageTitleAppendage));
		} else {
			return $this->getErrorMessageView($this->customerErrorMessage);
		}

		return $this->getItemView("sddmessagelink.tpl.php");
	}

	/**
	 * @param string $errorMessage
	 * @return MessageLinkItemView
	 */
	public function getErrorMessageView($errorMessage = null) {
		$errorAttributes = array(
			"pageTitle" => "SchoolMessenger",
			"productName" => "SchoolMessenger",
			"customerdisplayname" => "",
			"errorMessage" => $errorMessage ? $errorMessage : "An error occurred while trying to retrieve your message. Please try again."
		);

		if (isset($this->model->attributes)) {
			$errorAttributes["customerdisplayname"] = $this->model->get("customerdisplayname");
		}

		// handles view rendering if no model passed into initView(model)
		if (!isset($this->model)) {
			return $this->getItemView("error.tpl.php", $this->model = $errorAttributes);
		}

		$this->model->setAttributes($errorAttributes);
		return $this->getItemView("error.tpl.php");
	}

	/**
	 * @param string $errorMessage error message to display to users
	 */
	public function logError($errorMessage) {
		error_log($errorMessage);
	}

	/**
	 * @param string $template filename of template to use for view
	 * @param null $modelAttrs optional model attributes that can be passed in which override this->model->attributes
	 * @return MessageLinkItemView
	 */
	public function getItemView($template, $modelAttrs = null) {
		$modelAttributes = (is_array($modelAttrs)) ? $modelAttrs : (array) $this->model->getAttributes();
		return new MessageLinkItemView($template, $modelAttributes);
	}

	/**
	 * Renders the main controller's view (the complete/final page
	 * for MessageLink, SDD (password or download), or an Error view
	 */
	public function renderView() {
		if (isset($this->view)) {
			$this->view->render();
		}
	}

}



?>