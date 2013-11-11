var QuickTip = function() {

	var document 		= window.document,
		tipForm 		= document.getElementById('quicktip'),
		mask	 		= document.getElementById('mask'),
		orgListCoB 		= document.getElementById("orgId"),
		topicCoB 		= document.getElementById("topicId"),
		messageTA 		= document.getElementById("message"),
		messageTACont   = document.getElementById('tip-message-control-group'),
		errorMsgCont 	= document.getElementById("tip-error-message"),
		submitB 		= document.getElementById("tip-submit"),
		orgId			= null,
		topicId			= null,
		baseCustomerURL = "",
		formActionUrl	= "",
		isValid = false,
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

			if (!methods.isTipValid()) {
				event.preventDefault();
				methods.renderValidation();
			} else {
				methods.setFormActionURL();
				methods.removeClass(mask, 'hide');
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

		removeEventHandlers: function() {
			submitB.removeEventListener('click', methods.submitHandler, false);
			messageTA.removeEventListener('keyup', methods.messageTextHandler, false);
		},

		setErrorMessage: function(errorMsg) {
			errorMsgCont.innerHTML += errorMsg + '<br>';
			this.removeClass(errorMsgCont, 'hide');
		},

		isMessageTextValid: function() {
			return ((messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;
		},

		isSelectedOrgValid: function() {
			var id = this.getSelectedOrgId();
			return (typeof(id) !== 'undefined' && id > -1) ? true : false;
		},

		isSelectedTopicValid: function() {
			var id = this.getSelectedTopicId();
			return (typeof(id) !== 'undefined' && id > -1) ? true : false;
		},

		isTipValid: function() {
			return isValid = ((this.isMessageTextValid() && this.isSelectedOrgValid() && this.isSelectedTopicValid())) ? true : false;
		},

		renderValidation: function() {
			errorMsgCont.innerHTML = '';

			if (isValid) {
				this.addClass(errorMsgCont, 'hide');
				this.removeClass(messageTACont, 'has-error');
			} else {
				if (!this.isSelectedOrgValid()) {
					this.setErrorMessage('Please select a valid Organization.');
				}
				if (!this.isSelectedTopicValid()) {
					this.setErrorMessage('Please select a valid Category.');
				}
				if (!this.isMessageTextValid()) {
					this.addClass(messageTACont, 'has-error');
					this.setErrorMessage('Please enter a Tip Message.')
				} else if (this.isMessageTextValid()) {
					this.removeClass(messageTACont, 'has-error');
				}

				this.removeClass(errorMsgCont, 'hide');

			}
		},

		setFormActionURL: function() {
			this.formActionUrl = "/api/2/organizations/" + this.getSelectedOrgId() + "/topics/" + this.getSelectedTopicId() + "/quicktip";
			this.baseCustomerURL = tipForm.getAttribute('data-base-url');
			tipForm.setAttribute('action', this.baseCustomerURL + this.formActionUrl);
		},

		getFormActionURL: function() {
			return this.formActionUrl;
		},

		setSelectedOrgId: function() {
			this.orgId = (orgListCoB.selectedIndex > -1) ? orgListCoB.options[orgListCoB.selectedIndex].value : -1;
		},

		getSelectedOrgId: function() {
			return this.orgId;
		},

		setSelectedTopicId: function() {
			this.topicId = (topicCoB.selectedIndex > -1) ? topicCoB.options[topicCoB.selectedIndex].value : -1;
		},

		getSelectedTopicId: function() {
			return this.topicId;
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

	return methods;
};


