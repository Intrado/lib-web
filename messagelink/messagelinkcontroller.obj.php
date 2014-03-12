<?
require_once("inc/appserver.inc.php");

// load the thrift api requirements.
$thriftdir = '../Thrift';
require_once("{$thriftdir}/Base/TBase.php");
require_once("{$thriftdir}/Protocol/TProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocol.php");
require_once("{$thriftdir}/Protocol/TBinaryProtocolAccelerated.php");
require_once("{$thriftdir}/Transport/TTransport.php");
require_once("{$thriftdir}/Transport/TSocket.php");
require_once("{$thriftdir}/Transport/TBufferedTransport.php");
require_once("{$thriftdir}/Transport/TFramedTransport.php");
require_once("{$thriftdir}/Exception/TException.php");
require_once("{$thriftdir}/Exception/TTransportException.php");
require_once("{$thriftdir}/Exception/TProtocolException.php");
require_once("{$thriftdir}/Exception/TApplicationException.php");
require_once("{$thriftdir}/Type/TType.php");
require_once("{$thriftdir}/Type/TMessageType.php");
require_once("{$thriftdir}/StringFunc/TStringFunc.php");
require_once("{$thriftdir}/Factory/TStringFuncFactory.php");
require_once("{$thriftdir}/StringFunc/Core.php");
require_once("{$thriftdir}/packages/messagelink/Types.php");
require_once("{$thriftdir}/packages/messagelink/MessageLink.php");

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

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
	public $view;
	public $modelData;
	public $errorVars = array("productName" => "SchoolMessenger");

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

		// calls appserver.inc.php > initMessageLinkApp() to initialize MessageLink thrift protocol/transport objects
		list($protocol, $transport) = $this->getMessageLinkProtocolTransport();

		// verify MessageLink thrift protocol and transport objects exist; if not, create error message view
		if ($protocol == null || $transport == null) {
			$this->logError("Cannot use AppServer; MessageLinkClient failed to be initialized");
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
						// get server-side response data (modelData) for the given messagelink ('s') code;
						// NOTE: if messagelink code ('s') is invalid, the client will throw an exception here
						// which we catch and create an error message view
						$this->modelData = $model->getInfo($this->request['s']);

						if (isset($this->request['s']) && !empty($this->request['s']) ) {
							// add field to modelData required by both ML or SDD UI's
							$this->modelData->messageLinkCode = $this->request['s'];

							if (!isset($this->request['mal'])) {
								// request is for messagelink, not SDD; create MessageLink UI.
								$this->view = $this->getAppView('ML', $this->modelData);

							} else if (isset($this->request['mal']) && !empty($this->request['mal'])) {
								// add field to modelData required by SDD UI
								$this->modelData->attachmentLinkCode = $this->request['mal'];

								// request is for SDD; create SDD UI;
								$this->view = $this->getAppView('SDD', $this->modelData);

							} else {
								// show error if mal code set but empty, ex. mal=
								$this->view = $this->getCustomerErrorMessageView();
							}

						} else {
							// show error is s code is not set, ie no s param at all in query string,
							// or if s is set and empty, ex s=
							$this->view = $this->getCustomerErrorMessageView();
						}

					} catch (MessageLinkCodeNotFoundException $e) {
						// MessageLinkClient threw an exception due to invalid s code, show error message
						$this->logError("Unable to find the messagelinkcode: " . urlencode($this->request['s']));
						$this->view = $this->getCustomerErrorMessageView();
					}
					$this->closeThriftTransport($transport);
					break;

				} catch (TException $tx) {
					// MessageLinkClient instantiation was unsuccessfull

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
	 * @param string $template filename of template to use for view
	 * @param array $modelData data containing required template variables
	 * @return MessageLinkItemView
	 */
	public function getItemView($template, $modelData = array()) {
		return new MessageLinkItemView($template, $modelData);
	}

	
	/**
	 * @param string $app 'SDD' or 'ML'
	 * @param object $modelData containing data from MessageLinkClient->getInfo() call
	 * @return MessageLinkItemView
	 */
	public function getAppView($app, $modelData) {

		$showErrorMsg = true;
		
		// ensure selectedEmailMessage object and its child property
		// attachmentLookup exist before creating the SDD view
		if ($app == 'SDD' &&
			isset($modelData->messageInfo->selectedEmailMessage) &&
			isset($modelData->messageInfo->selectedEmailMessage->attachmentLookup)) {

			// add additional field(s) to modelData, required in SDD UI
			$this->modelData->pageTitle = "Secure Document Delivery from ". $this->modelData->customerdisplayname ." - Powered by " . $this->modelData->productName;
			$this->modelData->emailMessage = $this->modelData->messageInfo->selectedEmailMessage;
			$this->modelData->attachmentInfo = $this->modelData->emailMessage->attachmentLookup[$this->request['mal']];

			if (!is_object($this->modelData->attachmentInfo)) {
				return $this->getCustomerErrorMessageView();
			}
			$showErrorMsg = false;

			// ensure selectedPhoneMessage object and its child property nummessageparts exist
			// before creating the MessageLink view
		} else if ($app == 'ML' &&
			isset($modelData->messageInfo->selectedPhoneMessage) &&
			isset($modelData->messageInfo->selectedPhoneMessage->nummessageparts)) {

			// add additional field(s) to modelData, required in MessageLink UI
			$this->modelData->pageTitle = "Voice Message Delivery from ". $this->modelData->customerdisplayname ." - Powered by " . $this->modelData->productName;
			$showErrorMsg = false;
		}

		return !$showErrorMsg ? $this->getItemView("sddmessagelink.tpl.php", (array) $this->modelData) : $this->getCustomerErrorMessageView();
	}
	
	/**
	 * @param $data
	 * @return MessageLinkItemView
	 */
	public function getErrorView($modelData = array()) {
		return $this->getItemView("error.tpl.php", $modelData);
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
			"errorMessage" => "The requested information was not found. The content you are looking for does not exist or has expired."
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