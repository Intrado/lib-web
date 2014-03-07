/**
 *
 * Secure Document Delivery (SDD) "class"
 *
 * Requires specific DOM element(s) to be present prior to instantiation, i.e. calling 'new SDD()',
 * should be performed when the document is "ready"
 *
 * Upon instantiation, appropriate event handler(s) will be initialized to manage necessary view elements.
 *
 * Supports two (2) different SDD views: SDD Password and SDD Download
 *
 * @author Justin Burns <jburns@schoolmessenger.com>
 * @date Mar 1, 2013
 *
 * @constructor
 */
function SDD() {

	var $this = this;

	this.messageLinkCode = $("#message-link-code").val();
	this.attachmentLinkCode = $("#attachment-link-code").val();

	this.errorMsgContainer = $("#download-error-message-container");
	this.errorMsg = $("#download-error-message");

	/**
	 *	Initializes appropriate event handler(s) depending on the resulting SDD page (Password or Download).
	 *
	 * If password elem exists, implies SDD Password page, therefore init SDD Password page-specifc event handlers,
	 * otherwise if countElem exists, implies SDD (automatic) download page, therefore start (5s) countdown timer and
	 * init SDD Download page-specific event handler
	 */
	this.initialize = function() {
		this.password = $("#password");
		this.countElem = $("#download-count");

		if ($this.password.length) {
			$this.downloadB  = $("#downloadB");
			$this.addPasswordKeyupHandler();
			$this.addDownloadBtnClickHandler();
		} else if ($this.countElem.length) {
			$this.count = 5;
			$this.addDirectLinkClickHandler();
			$this.startCountdownTimer();
		}
	}

	/**
	 *
	 * @param string password user-entered password value from input[type=text] elem
	 * @return {*}
	 */
	this.requestDocument = function(password) {
		return $.ajax({
			url: "../messagelink/requestdocument.php",
			type: "GET",
			data: {
				"messageLinkCode": $this.messageLinkCode,
				"attachmentLinkCode": $this.attachmentLinkCode,
				"password": password ? password : null
			},
			success: function(res) {
				// download is invoked via target script "../messagelink/requestdocument.php",
				// nothing to do here except ensure error message is hidden

				// ensure error message is hidden
				$this.errorMsgContainer.hide();
			},
			error: function(res) {
				if (res && res.errorMessage) {
					$this.errorMsg.html(res.errorMessage);
				} else {
					$this.errorMsg.html("An error occurred while trying to retrieve your document. Please try again.").show();
				}
				$this.errorMsgContainer.show();
			}
		});
	};

	/**
	 *
	 * @return {*}
	 */
	this.getPassword = function() {
		return $this.password ? $.trim($this.password.val()) : null;
	};

	/**
	 *
	 * @param elem
	 */
	this.disableElem = function(elem) {
		if (elem) {
			elem.attr('disabled', 'disabled').addClass('disabled');
		}
	};

	/**
	 *
	 * @param elem
	 */
	this.enableElem = function(elem) {
		if (elem) {
			elem.removeAttr('disabled').removeClass('disabled');
		}
	};

	/**
	 *
	 */
	this.countdownTimerFcn = function() {
		// only decrement if count > 0
		if ($this.count > 0) {
			$this.count -= 1;
		}

		if ($this.count == 0) {
			$this.stopCountdownTimer();
			$this.requestDocument();
		}
		$this.countElem.html($this.count);
	};

	/**
	 *
	 */
	this.addPasswordKeyupHandler = function() {
		if ($this.password) {
			$this.password.on("keyup", function(e) {
				var pwdVal = $this.getPassword();

				if (pwdVal.length > 0) {
					$this.enableElem($this.downloadB);
				} else {
					$this.disableElem($this.downloadB);
				}
			});
		}
	};

	/**
	 *
	 */
	this.addDownloadBtnClickHandler = function() {
		if ($this.downloadB) {
			$this.downloadB.on('click', function(e) {
				e.preventDefault();
				$this.requestDocument($this.password.val());
			});
		}
	}

	/**
	 *
	 */
	this.addDirectLinkClickHandler = function() {
		var directLink = $(".directlink");
		if (directLink) {
			directLink.on('click', function(e) {
				e.preventDefault();
				$this.requestDocument();
			});
		}
	};

	/**
	 *
	 */
	this.startCountdownTimer = function() {
		if ($this.countdownTimerFcn) {
			$this.counter = setInterval($this.countdownTimerFcn, 1000);
		}
	};

	/**
	 *
	 */
	this.stopCountdownTimer = function() {
		clearInterval($this.counter);
	};

}