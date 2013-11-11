var QuickTip = function() {

	var document 		= window.document,
		tipForm 		= document.getElementById('quicktip'),
		mask	 		= document.getElementById('mask'),
		orgListCoB 		= document.getElementById("orgId"),
		categoryCoB 	= document.getElementById("topicId"),
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
			if (!methods.validate()) {
				event.preventDefault();
			} else {
				methods.setFormActionURL();
				methods.removeClass(mask, 'hide');
				tipForm.submit();
			}
		},

		messageTextHandler: function(event) {
			if (!isValid) {
				methods.validate();
			}
		},

		removeEventHandlers: function() {
			submitB.removeEventListener('click', methods.submitHandler, false);
			messageTA.removeEventListener('keyup', methods.messageTextHandler, false);
		},

		showServerErrorMessage: function(errorMsg) {
			errorMsgCont.innerHTML = errorMsg;
			this.removeClass(errorMsgCont, 'hide');
		},

		validate: function() {
			isValid = ((messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;
			this.renderValidation();
			return isValid;
		},

		renderValidation: function() {
			if (isValid) {
				this.addClass(errorMsgCont, 'hide');
				this.removeClass(messageTACont, 'has-error');
			} else {
				this.removeClass(errorMsgCont, 'hide');
				this.addClass(messageTACont, 'has-error');
			}
		},

		setFormActionURL: function() {
			this.setSelectedOrgId();
			this.setSelectedTopicId();
			this.formActionUrl = "/api/2/organizations/" + this.getSelectedOrgId() + "/topics/" + this.getSelectedTopicId() + "/quicktip";
			this.baseCustomerURL = tipForm.getAttribute('data-base-url');
			tipForm.setAttribute('action', this.baseCustomerURL + this.formActionUrl);
		},

		getFormActionURL: function() {
			return this.formActionUrl;
		},

		setSelectedOrgId: function() {
			this.orgId = orgListCoB.options[orgListCoB.selectedIndex].value;
		},

		getSelectedOrgId: function() {
			return this.orgId;
		},

		setSelectedTopicId: function() {
			this.topicId = categoryCoB.options[categoryCoB.selectedIndex].value;
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


