var QuickTip = function() {

	var document 		= window.document,
		tipForm 		= document.getElementById('quicktip'),
		mask	 		= document.getElementById('mask'),
		orgListCoB 		= document.getElementById("orgId"),
		topicCoB 		= document.getElementById("topicId"),
		messageTA 		= document.getElementById("message"),
		messageTACont   = document.getElementById('tip-message-control-group'),
		errorMsgCont 	= document.getElementById("tip-error-message"),
		emailTF 		= document.getElementById("email"),
		phoneTF 		= document.getElementById("phone"),
		submitB 		= document.getElementById("tip-submit"),
		submittingTipSp	= document.getElementById("submitting-tip-span"),
		submitTipSp		= document.getElementById("submit-tip-span"),
		orgId			= null,
		topicId			= null,
		baseCustomerURL = "",
		formActionUrl	= "",
		isValid = false,
		isEmailValid = true,
		isPhoneValid = true,
		methods;

	// cross-browser addEvent handler
	var addEvent = (function () {
		if (window.addEventListener) {
			return function (el, ev, fn) {
				el.addEventListener(ev, fn, false);
			};
		} else if (window.attachEvent) {
			return function (el, ev, fn) {
				el.attachEvent('on' + ev, fn);
			};
		} else {
			return function (el, ev, fn) {
				el['on' + ev] =  fn;
			};
		}
	}());

	// public QuickTip API
	methods = {
		submitFormHandler: function(event) {
			methods.setSelectedOrgId();
			methods.setSelectedTopicId();
			
			// check if required fields are valid
			methods.isTipValid()

			// check if optional email/phone fields are valid;
			// valid = either empty or if non-empty, they have the correct format (email / phone pattern formats)
			methods.isEmailValid();
			methods.isPhoneValid();

			// if invalid, stop form submission and render validation error message
			if (!isValid || !isEmailValid || !isPhoneValid) {
				event.preventDefault();
				methods.renderValidation();
			} else {

				// form fields are valid, now set form's action URL, display mask overlay, and submit form
				// to QuickTip API (response gets sent to hidden target iframe)
				methods.setFormActionURL();
				methods.removeClass(mask, 'hide');
				methods.removeClass(submittingTipSp, 'hide');
				methods.addClass(submitTipSp, 'hide');
				tipForm.submit();
			}
		},

		messageTextHandler: function(event) {
			if (!isValid) {
				methods.isTipValid();
				if (methods.isMessageTextValid()) {
					methods.renderValidation();
				}
			}
		},

		emailHandler: function(event) {
			if (!isValid) {
				methods.isTipValid();
				if (methods.isEmailValid()) {
					methods.renderValidation();
				}
			}
		},

		phoneHandler: function(event) {
			if (!isValid) {
				methods.isTipValid();
				if (methods.isPhoneValid()) {
					methods.renderValidation();
				}
			}
		},

		removeEventHandlers: function() {
			submitB.removeEventListener('click', methods.submitHandler, false);
			messageTA.removeEventListener('keyup', methods.messageTextHandler, false);
			emailTF.removeEventListener('keyup', methods.emailHandler, false);
			phoneTF.removeEventListener('keyup', methods.phoneHandler, false);
		},

		setErrorMessage: function(errorMsg) {
			errorMsgCont.innerHTML += errorMsg + '<br>';
			this.removeClass(errorMsgCont, 'hide');
		},

		isMessageTextValid: function() {
			return ((messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;
		},

		isSelectedIdValid: function(id) {
			return (typeof(id) !== 'undefined' && id > -1) ? true : false;
		},

		isTipValid: function() {
			return isValid = (this.isMessageTextValid() && 
							  this.isSelectedIdValid(this.orgId) && 
							  this.isSelectedIdValid(this.topicId) &&
							  this.isEmailValid() &&
							  this.isPhoneValid()
							 );
		},

		isEmailValid: function() {
			return isEmailValid = (typeof(emailTF.checkValidity) === 'function') ? emailTF.checkValidity() : true;
		},

		isPhoneValid: function() {
			return isPhoneValid = (typeof(phoneTF.checkValidity) === 'function') ? phoneTF.checkValidity() : true;
		},

		renderValidation: function() {
			errorMsgCont.innerHTML = '';

			if (isValid) {
				this.addClass(errorMsgCont, 'hide');
				this.removeClass(messageTACont, 'has-error');
			} else {
				if (!this.isSelectedIdValid(this.orgId)) {
					this.setErrorMessage('Please select a valid Organization.');
				}
				if (!this.isSelectedIdValid(this.topicId)) {
					this.setErrorMessage('Please select a valid Topic.');
				}
				if (!this.isMessageTextValid()) {
					this.addClass(messageTACont, 'has-error');
					this.setErrorMessage('Please enter a Tip Message.');
				} else if (this.isMessageTextValid()) {
					this.removeClass(messageTACont, 'has-error');
				}
				if (!isEmailValid) {
					this.setErrorMessage('Please enter a valid email address.<div class="error-format-example">Ex. janedoe@example.com</div>');
				}
				if (!isPhoneValid) {
					this.setErrorMessage('Please enter a valid phone number.<div class="error-format-example">Format: (888) 555-1234, 888-555-1234, or 888.555.1234</div>');
				}
				this.removeClass(errorMsgCont, 'hide');

			}
		},

		setFormActionURL: function() {
			this.formActionUrl = "/api/2/organizations/" + this.orgId + "/topics/" + this.topicId + "/quicktip";
			this.baseCustomerURL = tipForm.getAttribute('data-base-url');
			tipForm.setAttribute('action', this.baseCustomerURL + this.formActionUrl);
		},

		setSelectedOrgId: function() {
			this.orgId = (orgListCoB.selectedIndex > -1) ? orgListCoB.options[orgListCoB.selectedIndex].value : -1;
		},

		setSelectedTopicId: function() {
			this.topicId = (topicCoB.selectedIndex > -1) ? topicCoB.options[topicCoB.selectedIndex].value : -1;
		},

		// helper methods for validation rendering (ex add/remove classes)
		hasClass: function(elem, className) {
			return new RegExp(' ' + className).test(' ' + elem.className);
		},

		addClass: function(elem, className) {
			if (!this.hasClass(elem, className)) {
				elem.className += ' ' + className;
			}
		},

		removeClass: function(elem, className) {
		    var newClass = ' ' + elem.className.replace( /[\t\r\n]/g, ' ') + ' ';
		    if (this.hasClass(elem.className)) {
		        while (newClass.indexOf(' ' + className + ' ') >= 0 ) {
		            newClass = newClass.replace(' ' + className + ' ', ' ');
		        }
		        elem.className = newClass.replace(/^\s+|\s+$/g, '');
		    }
		}
	};

	// add event listeners
	addEvent(submitB,   'click', methods.submitFormHandler);
	addEvent(messageTA, 'keyup', methods.messageTextHandler);
	addEvent(emailTF, 'keyup', methods.emailHandler);
	addEvent(phoneTF, 'keyup', methods.phoneHandler);

	// initialize selected Org and Topic Ids
	methods.setSelectedOrgId();
	methods.setSelectedTopicId();

	return methods;
};


