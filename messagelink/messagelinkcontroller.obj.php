<?

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

/**
 * Simple controller class to handle message/attachment link request param(s), fetch (model) data via
 * a Thrift-based MessageLinkClient, and instantiate/render the appropriate view to the user.
 *
 * This controller is part of an MVC-ish design pattern, where the "model" is considered the MessageLinkClient (since it handles
 * all the business logic on the server/java side, provides data on request, etc.  The controller has a reference
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

	private $request;
	private $view;
	private $modelData;
	private $errorVars = array("productName" => "SchoolMessenger");

	/**
	 * @param array $request params ('s', 'mal') from initial entry/page request
	 */
	public function __construct($request = array()) {
		if (is_array($request) && count($request) > 0) {
			$this->request = $request;
		}
	}

	/**
	 * Initializes MessageLink thrift app protocol/transport layers and creates views
	 * (MessageLink, SDD Password, or SDD Download) depending on request params.
	 * Displays appropriate error message view if request params or thrift protocol/transport objects are invalid/null.
	 */
	public function initApp() {

		$footerView = $this->getMessageLinkView("footer.tpl.php");
		$this->errorVars["footer"] = $footerView;

		// calls appserver.inc.php > initMessageLinkApp() to initialize MessageLink thrift protocol/transport objects
		list($protocol, $transport) = $this->getMessageLinkProtocolTransport();

		// verify MessageLink thrift protocol and transport objects exist; if not, create error message view
		if ($protocol == null || $transport == null) {
			$this->logError("Cannot use AppServer");
			$this->view = $this->getGeneralErrorMessageView();

		} else {
			// MessageLink thrift app was initialized successfully;
			$attempts = 0;
			while (true) {
				try {
					// now get a new MessageLinkClient, considered the "model" in this case
					// and open its transport connection
					$model = $this->getMessageLinkClient($protocol);
					$this->openThriftTransport($transport);

					try {
						// get server-side response (model) data for the given messagelink ('s') code;
						// NOTE: if messagelink code ('s') is invalid, the client will throw an exception here
						// which we catch and create an error message view
//						$this->modelData = $model->getInfo($this->request['s']);

						// stub response until API ready
						$this->modelData = (Object) array(
							"customerdisplayname" => "Burns Academy",
							"urlcomponent" => "burns",
							"timezone" => "GMT-0700",
							"jobname" => "Job Name Test",
							"jobdescription" => "Job Description Test",
							"jobstarttime" => date(time()),
							"recipient" => (Object) array(
								"firstName" => "Justin",
								"lastName" => "Burns"
							),
							"messageInfo" => (Object) array(
								"selectedPhoneMessage" => (Object) array(
									"languageCode" => "en",
									"nummessageparts" => 3
								),
								"selectedEmailMessage" => (Object) array(
									"fromName" => "Admin Burns",
									"fromEmail" => "adminBurns@asdf.com",
									"subject" => "Test email subject",
									"plainBody" => "Lorem ipsum dolor sit amet, consectetur adipiscing elit. Maecenas in dui pharetra, vestibulum nibh fringilla, convallis ligula. Aenean accumsan lectus sed turpis iaculis, non viverra nisi dignissim. Donec luctus neque dapibus lorem ultrices, in fringilla nisi tempus. Aenean hendrerit luctus porta. Nulla pretium metus porttitor ipsum vehicula, ac venenatis ipsum vehicula. Phasellus hendrerit, nulla vel dictum varius, tellus nisl scelerisque sem, sed ultricies nisi urna vel dolor. Vivamus pretium tincidunt porta.",
									"attachmentLookup" => array(
										"5ab32aade03b0b150d06ded6dd78bdb066ba4d9cf06112cdcc96e56991a8d2fd" => (Object) array(
											"filename" => "Spring Grades 2014 - Soquel High School.pdf",
											"contentType" => "application/pdf",
											"size" => null,
											"isPasswordProtected" => true,
											"code" => "5ab32aade03b0b150d06ded6dd78bdb066ba4d9cf06112cdcc96e56991a8d2fd"
										)
									),
								), //http://sandbox/m/messagelink/?s=Mv0ZyFhMzSM&mal=5ab32aade03b0b150d06ded6dd78bdb066ba4d9cf06112cdcc96e56991a8d2fd
							),
							"productName" => "SchoolMessenger"
						);

						if (isset($this->request['s']) && !empty($this->request['s']) ) {
							// add fields required by both ML or SDD UI's
							$this->modelData->messageLinkCode = $this->request['s'];
							$this->modelData->footer = $footerView;

							// request is for messagelink, not SDD; create MessageLink UI
							if (!isset($this->request['mal'])) {

								// ensure selectedPhoneMessage object and its child property nummessageparts exist
								// before creating the MessageLink view
								if (isset($this->modelData->messageInfo->selectedPhoneMessage) &&
									isset($this->modelData->messageInfo->selectedPhoneMessage->nummessageparts)) {

									$this->view = $this->createMessageLinkView();
								} else {
									$this->view = $this->getCustomerErrorMessageView();
								}


							// request is for SDD; create SDD UI
							} else if (isset($this->request['mal']) && !empty($this->request['mal'])) {

								// ensure selectedEmailMessage object and its child property attachmentLookup exist
								// before creating the SDD view
								if (isset($this->modelData->messageInfo->selectedEmailMessage) &&
									isset($this->modelData->messageInfo->selectedEmailMessage->attachmentLookup)) {

									$this->modelData->attachmentLinkCode = $this->request['mal'];
									$this->view = $this->createSDDView();
								} else {
									$this->view = $this->getCustomerErrorMessageView();
								}
							} else {
								$this->view = $this->getCustomerErrorMessageView();
							}

						// no 's' code, show error message
						} else {
							$this->view = $this->getCustomerErrorMessageView();
						}

					// MessageLinkClient threw an exception due to invalid code, show error message
					} catch (MessageLinkCodeNotFoundException $e) {
						$this->logError("Unable to find the messagelinkcode: " . urlencode($this->request['s']));
						$this->view = $this->getCustomerErrorMessageView();
					}
					$this->closeThriftTransport($transport);
					break;

				// MessageLinkClient instantiation was unsuccessfull
				} catch (TException $tx) {

					$attempts++;
					$this->logError("getInfo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
					$this->closeThriftTransport($transport);

					// if $attempts > 2, show error message, else instantiate MessageLinkClient again
					if ($attempts > 2) {
						$this->logError("getInfo: Failed 3 times to get content from appserver");
						$this->view = $this->getGeneralErrorMessageView();
						break;
					}
				}
			}
		}
	}

	/**
	 * @return array ex. list($protocol, $transport)
	 */
	public function getMessageLinkProtocolTransport() {
		return initMessageLinkApp();
	}

	/**
	 * @param $protocol MessageLink thrift protocol
	 * @return messagelink\MessageLinkClient
	 */
	public function getMessageLinkClient($protocol) {
		return new MessageLinkClient($protocol);
	}

	/**
	 * @param $transport MessageLink thrift transport
	 */
	public function openThriftTransport($transport) {
		if (is_object($transport)) {
			$transport->open();
		}
	}

	/**
	 * @param $transport MessageLink thrift transport
	 */
	public function closeThriftTransport($transport) {
		if (is_object($transport)) {
			$transport->close();
		}
	}

	/**
	 * @return MessageLinkItemView
	 */
	public function createMessageLinkView() {
		$this->modelData->pageTitle = "Voice Message Delivery from ". $this->modelData->customerdisplayname ." - Powered by " . $this->modelData->productName;
		return $this->getMessageLinkView("messagelink.tpl.php", (array) $this->modelData);
	}

	/**
	 * @return MessageLinkItemView
	 */
	public function createSDDView() {
		// add additional fields to modelData, required to in SDD template
		$this->modelData->pageTitle = "Secure Document Delivery from ". $this->modelData->customerdisplayname ." - Powered by " . $this->modelData->productName;
		$this->modelData->emailMessage = $this->modelData->messageInfo->selectedEmailMessage;
		$this->modelData->attachmentInfo = $this->modelData->emailMessage->attachmentLookup[$this->request['mal']];

		if (is_object($this->modelData->attachmentInfo)) {
			// create nested sub view for either the password or download view, depending on isPasswordProtected = T/F
			// used in main/parent SDD template; using data in $this->modelData response
			if ($this->modelData->attachmentInfo->isPasswordProtected) {
				$this->modelData->mainContentView = $this->getMessageLinkView("sddpassword.tpl.php", (array) $this->modelData);
			} else {
				$this->modelData->downloadTimerView = $this->getMessageLinkView("sdddownloadtimer.tpl.php", (array) $this->modelData);
				$this->modelData->mainContentView = $this->getMessageLinkView("sdddownload.tpl.php", (array) $this->modelData);
			}
		} else {
			// attachmentInfo was not found for the provided 'mal' code, show error message
			return $this->getCustomerErrorMessageView();
		}

		// create main/parent SDD view (sddmian.tpl.php) using data in $this->modelData response,
		// which contains the appropriate nested subview for either the password or download page
		return $this->getMessageLinkView("sddmain.tpl.php", (array) $this->modelData);
	}

	/**
	 * @param string $template filename of template to use for view
	 * @param array $modelData data containing required template variables
	 * @return MessageLinkItemView
	 */
	public function getMessageLinkView($template, $modelData = array()) {
		return new MessageLinkItemView($template, $modelData);
	}

	/**
	 * @param $data
	 * @return MessageLinkItemView
	 */
	public function getErrorView($modelData = array()) {
		return $this->getMessageLinkView("error.tpl.php", $modelData);
	}

	/**
	 * Generic Error message view
	 *
	 * @return MessageLinkItemView
	 */
	public function getGeneralErrorMessageView() {
		return $this->getErrorView(array_merge($this->errorVars, array(
			"errorMessage" => "An error occurred while trying to retrieve your message. Please try again."
		)));
	}

	/**
	 * Generic Error message view that contains the customer's display name
	 *
	 * @return MessageLinkItemView
	 */
	public function getCustomerErrorMessageView() {
		return $this->getErrorView(array_merge($this->errorVars, array(
			"customerdisplayname" => $this->modelData->customerdisplayname,
			"errorMessage" => "The requested information was not found. The message you are looking for does not exist or has expired."
		)));
	}

	/**
	 * @param string $errorMessage error message to display to users
	 */
	public function logError($errorMessage) {
		error_log($errorMessage);
	}

	/**
	 * Renders the main controller's view (the complete/final page for MessageLink, SDD (password or download), or an Error view
	 */
	public function renderView() {
		$this->view->render();
	}

}

?>