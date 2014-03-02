<?

use messagelink\MessageLinkClient;
use messagelink\MessageLinkCodeNotFoundException;

class LinkController {

	private $view;
	private $request;
	private $model;

	public function __construct($request = array()) {
		if (is_array($request) && count($request) > 0) {
			$this->request = $request;
		}
	}

	public function initApp() {

		$defaultProductName = "SchoolMessenger";
		$footerView = new MessageLinkItemView("footer.tpl.php");

		list($protocol, $transport) = $this->initThriftMessageLinkApp();

		if ($protocol == null || $transport == null) {
			error_log("Cannot use AppServer");
			$this->view = $this->getErrorView(array(
				"productName" => $defaultProductName,
				"errorMessage" => "An error occurred while trying to retrieve your message. Please try again.",
				"footer" => $footerView
			));
		} else {
			$attempts = 0;
			while (true) {
				try {
					$client = $this->getMessageLinkClient($protocol);
					$this->openThriftTransport($transport);
					try {
						// fetch message info for the given code
						$this->model = $client->getInfo($this->request['s']);

						// stub response until API ready
						$this->model = (Object) array(
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
									"plainBody" => "Test email subject",
									"attachmentLookup" => array(
										"123" => (Object) array(
											"filename" => "Spring Grades 2014 - Soquel High School.pdf",
											"contentType" => "text/pdf",
											"size" => null,
											"isPasswordProtected" => false,
											"code" => ""
										)
									),
								),
							),
							"productName" => "SchoolMessenger"
						);

						if (isset($this->request['s'])) {
							// add fields required by either ML or SDD UI's
							$this->model->messageLinkCode = $this->request['s'];
							$this->model->footer = $footerView;

							// request is for messagelink, not SDD; create MessageLink UI
							if (!isset($this->request['mal'])) {
								$this->view = $this->createMessageLinkView();
								
							// request is for SDD; create SDD UI
							} else if (isset($this->request['mal'])) {
								$this->view = $this->createSDDView();
							}
						} else {
							$this->view = $this->getErrorView(array(
								"productName" => $defaultProductName,
								"customerdisplayname" => $this->model->customerdisplayname,
								"errorMessage" => "The requested information was not found. The message you are looking for does not exist or has expired.",
								"footer" => $footerView
							));
						}

					} catch (messagelink_MessageLinkCodeNotFoundException $e) {
						error_log("Unable to find the messagelinkcode: " . urlencode($this->request['s']));
					}
					$this->closeThriftTransport($transport);
					break;

				} catch (TException $tx) {

					$attempts++;
					error_log("getInfo: Exception Connection to AppServer (" . $tx->getMessage() . ")");
					$this->closeThriftTransport($transport);

					if ($attempts > 2) {
						error_log("getInfo: Failed 3 times to get content from appserver");
						$this->view = $this->getErrorView(array(
							"productName" => $defaultProductName,
							"errorMessage" => "An error occurred while trying to retrieve your message. Please try again.",
							"footer" => $footerView
						));
						break;
					}
				}
			}
		}
	}

	public function initThriftMessageLinkApp() {
		return initMessageLinkApp();
	}

	public function getMessageLinkClient($protocol) {
		return new MessageLinkClient($protocol);
	}

	public function openThriftTransport($transport) {
		$transport->open();
	}

	public function closeThriftTransport($transport) {
		$transport->close();
	}

	public function createMessageLinkView() {
		$this->model->pageTitle = "Voice Message Delivery from ". $this->model->customerdisplayname ." - Powered by " . $this->model->productName;
		return new MessageLinkItemView("messagelink.tpl.php", (array) $this->model);
	}
	
	public function createSDDView() {
		// add additional fields used to in SDD template
		$this->model->emailMessage = $this->model->messageInfo->selectedEmailMessage;
		$this->model->attachmentInfo = $this->model->emailMessage->attachmentLookup[$this->request['mal']];
		$this->model->pageTitle = "Secure Document Delivery - Powered by " . $this->model->productName;

		// create nested sub view for either the password or download view, depending on isPasswordProtected
		// used in main/parent SDD template; using data in $this->model response
		if ($this->model->attachmentInfo->isPasswordProtected) {
			$this->model->mainContentView = $this->getSDDPasswordSubView();
		} else {
			$this->model->mainContentView = $this->getSDDDownloadSubViews();
		}

		// create main/parent SDD view (sddmian.tpl.php) using data in $this->model response,
		// which contains the appropriate nested subview for either the password or download page
		return new MessageLinkItemView("sddmain.tpl.php", (array) $this->model);
	}

	public function getSDDPasswordSubView() {
		return new MessageLinkItemView("sddpassword.tpl.php", (array) $this->model);
	}

	public function getSDDDownloadSubViews() {
		$this->model->downloadTimerView = new MessageLinkItemView("sdddownloadtimer.tpl.php", (array) $this->model);
		return new MessageLinkItemView("sdddownload.tpl.php", (array) $this->model);
	}

	public function getErrorView($data) {
		return new MessageLinkItemView("error.tpl.php", $data);
	}

	public function renderView() {
		$this->view->render();
	}

}

?>