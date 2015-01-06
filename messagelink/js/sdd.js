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

	this.requestDocumentUrl = "requestdocument.php";

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
			$this.addPasswordInputHandler();
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
		var requestParams = {
			"s": $this.messageLinkCode,
			"mal": $this.attachmentLinkCode,
			"p": password ? password : null
		};

		// if password provided, include v ("verify") param to verify password server-side,
		if (password) {
			requestParams['v'] = true;
		}

		var requestDocumentUrl = this.requestDocumentUrl;
		return $.ajax({
			url: requestDocumentUrl,
			type: "POST",
			data: requestParams,
			success: function(res) {
				// ensure the verify 'v' param is removed from requestParams, i.e. was successful
				delete requestParams['v'];

				// now download the document, i.e. redirect user to direct URL,
				// which should invoke the browser's download/save as dialog
				$this.postToUrl(requestDocumentUrl, requestParams);
			},
			error: function(res) {
				if (res && res.responseJSON && res.responseJSON.errorMessage) {
					$this.errorMsg.html(res.responseJSON.errorMessage);
				} else {
					$this.errorMsg.html("An error occurred while trying to retrieve your document. Please try again.").show();
				}
				$this.errorMsgContainer.show();
			}
		});
	};

	/**
	 *
	 * Builds and submits form dynamically via jQuery. Upon submit, removes form from DOM.
	 * For use in submitting a non-AJAX POST request to ../messagelink/requestDocument.php to download document (ex. pdf)
	 *
	 * borrowed/modified from http://stackoverflow.com/questions/133925/javascript-post-request-like-a-form-submit
	 *
	 * @param path
	 * @param params
	 */
	this.postToUrl = function(path, params) {
		var form = $this.getPostForm(path, params);

		// the form must be in the document to submit
		$("body").append(form);
		form.submit();

		// clean up/remove the form from the DOM now that we've submitted
		form.remove();
	}

	/**
	 *
	 * @param string path - url to post to
	 * @param object params - object containing all the required post params, ex. s, mal, p, v.
	 * @return jQuery object representing the form, contains child hidden input elements
	 */
	this.getPostForm = function(path, params) {
		var form = $("<form>").attr({method: "post", action: path});

		// append hidden elements to the form, based on params object
		for(var key in params) {
			if(params.hasOwnProperty(key)) {
				var hiddenField = $("<input>");
				hiddenField.attr({type: "hidden", name: key, value: params[key]});
				form.append(hiddenField);
			}
		}

		return form;
	}

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
			clearInterval($this.counter);
			$this.requestDocument();
		}
		$this.countElem.html($this.count);
	};

	/**
	 *
	 */
	this.addPasswordInputHandler = function() {
		if ($this.password) {
			$this.password.on("keyup", function(e) {
				var pwdVal = $this.getPassword();

				if (pwdVal.length > 0) {
					$this.enableElem($this.downloadB);
				} else {
					$this.disableElem($this.downloadB);
				}

				if (e.which !== 13) {
					$this.errorMsgContainer.hide();
				}
			});

			$this.password.on("keydown", function(e) {
				var pwdVal = $this.getPassword();

				if (pwdVal.length > 0) {
					if (e.which === 13) {
						$this.requestDocument($this.getPassword());
						return false;
					}
				} else {
					if (e.which === 13) {
						return false;
					}
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
				$this.requestDocument($this.getPassword());
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

}
