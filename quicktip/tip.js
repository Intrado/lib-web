function QuickTip() {

	var $this = this;

	this.doc 			 = window.document,
	this.orgId			 = null,
	this.topicId		 = null,
	this.baseCustomerURL = "",
	this.formActionUrl	 = "",
	this.isValid 		 = false;

	this.validation = {
		isValid: function() {
			return $this.isValid = (this.org.validate() &&
									this.topic.validate() &&
									this.message.validate() &&
									this.email.validate() &&
									this.phone.validate());
		},
		org: {
			isValid: false,
			msg: 'Please select a valid Organization.',
			validate: function () {
				return this.isValid = $this.isSelectedIdValid($this.orgId);}
		},
		topic: {
			isValid: false,
			msg: 'Please select a valid Topic.',
			validate: function () {
				return this.isValid = $this.isSelectedIdValid($this.topicId);}
		},
		message: {
			isValid: false,
			msg: 'Please enter a Tip Message.',
			validate: function() {
				return this.isValid = (($this.messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;}
		},
		email: {
			isValid: true,
			msg: 'Please enter a valid email address.<div class="error-format-example">Ex. janedoe@example.com</div>',
			validate: function() {
				return this.isValid = (typeof($this.emailTF.checkValidity) === 'function') ? $this.emailTF.checkValidity() : true;}
		},
		phone: {
			isValid: true,
			msg: 'Please enter a valid phone number.<div class="error-format-example">Format: (888) 555-1234, 888-555-1234, or 888.555.1234</div>',
			validate: function() {
				return this.isValid = (typeof($this.phoneTF.checkValidity) === 'function') ? $this.phoneTF.checkValidity() : true;}
		}
	};

	this.ui = {
		'tipForm': 			'quicktip',
		'mask':	 			'mask',
		'orgListCoB': 		'orgId',
		'topicCoB': 		'topicId',
		'messageTA': 		'message',
		'messageTACont':	'tip-message-control-group',
		'errorMsgCont': 	'tip-error-message',
		'emailTF': 			'email',
		'phoneTF': 			'phone',
		'submitB': 			'tip-submit',
		'submittingTipSp':	'submitting-tip-span',
		'submitTipSp':		'submit-tip-span'
	};

	for(var key in this.ui) {
		this[key] = this.doc.getElementById(this.ui[key]);
	}

	this.submitFormHandler = function(event) {
		event.preventDefault();

		$this.setSelectedOrgId();
		$this.setSelectedTopicId();

		if (!$this.validation.isValid()) {
			$this.renderValidation();
		} else {
			$this.setFormActionURL();
			$this.removeClass($this.mask, 'hide');
			$this.removeClass($this.submittingTipSp, 'hide');
			$this.addClass($this.submitTipSp, 'hide');
			$this.tipForm.submit();
		}
	};

	this.valHandler = function(updateFieldVal) {
		if (!$this.isValid) {
			$this.validation.isValid();
			if (updateFieldVal()) {
				$this.renderValidation();
			}
		}
	};

	this.messageHandler = function() {
		$this.valHandler($this.validation.message.validate);
	};

	this.emailHandler = function() {
		$this.valHandler($this.validation.email.validate);
	};

	this.phoneHandler = function() {
		$this.valHandler($this.validation.phone.validate);
	};

	this.renderValidation = function() {
		this.errorMsgCont.innerHTML = '';

		if (this.isValid) {
			this.addClass(this.errorMsgCont, 'hide');
			this.removeClass(this.messageTACont, 'has-error');
		} else {
			if (!this.validation.org.isValid) {
				this.setErrorMessage(this.validation.org.msg);
			}
			if (!this.validation.topic.isValid) {
				this.setErrorMessage(this.validation.topic.msg);
			}
			if (!this.validation.message.isValid) {
				this.addClass(this.messageTACont, 'has-error');
				this.setErrorMessage(this.validation.message.msg);
			} else  {
				this.removeClass(this.messageTACont, 'has-error');
			}
			if (!this.validation.email.isValid) {
				this.setErrorMessage(this.validation.email.msg);
			}
			if (!this.validation.phone.isValid) {
				this.setErrorMessage(this.validation.phone.msg);
			}
			this.removeClass(this.errorMsgCont, 'hide');
		}
	};

	this.isSelectedIdValid = function(id) {
		return (typeof(id) !== 'undefined' && id > -1) ? true : false;
	};

	this.setErrorMessage = function(errorMsg) {
		this.errorMsgCont.innerHTML += errorMsg + '<br>';
		this.removeClass(this.errorMsgCont, 'hide');
	};

	this.setFormActionURL = function() {
		this.baseCustomerURL = this.tipForm.getAttribute('data-base-url');
		this.formActionUrl = "/api/2/organizations/" + this.orgId + "/topics/" + this.topicId + "/quicktip";
		this.tipForm.setAttribute('action', this.baseCustomerURL + this.formActionUrl);
	};

	this.setSelectedOrgId = function() {
		this.orgId = (this.orgListCoB.selectedIndex > -1) ? this.orgListCoB.options[this.orgListCoB.selectedIndex].value : -1;
	};

	this.setSelectedTopicId = function() {
		this.topicId = (this.topicCoB.selectedIndex > -1) ? this.topicCoB.options[this.topicCoB.selectedIndex].value : -1;
	};

	this.hasClass = function(elem, className) {
		return new RegExp(' ' + className).test(' ' + elem.className);
	};

	this.addClass = function(elem, className) {
		if (!this.hasClass(elem, className)) {
			elem.className += ' ' + className;
		}
	};

	this.removeClass = function(elem, className) {
		var newClass = ' ' + elem.className.replace( /[\t\r\n]/g, ' ') + ' ';
		if (this.hasClass(elem.className)) {
			while (newClass.indexOf(' ' + className + ' ') >= 0 ) {
				newClass = newClass.replace(' ' + className + ' ', ' ');
			}
			elem.className = newClass.replace(/^\s+|\s+$/g, '');
		}
	};

	// cross-browser addEvent handler 
	// Source: http://javascriptrules.com/2009/07/22/cross-browser-event-listener-with-design-patterns/
	// Copyright Marcel Duran, License N/A
	this.addEvent = (function () {
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

	this.removeEventHandlers = function() {
		this.submitB.removeEventListener('click', this.submitHandler, false);
		this.messageTA.removeEventListener('keyup', this.messageHandler, false);
		this.emailTF.removeEventListener('keyup', this.emailHandler, false);
		this.phoneTF.removeEventListener('keyup', this.phoneHandler, false);
	};

	this.addEvent(this.submitB,   'click', this.submitFormHandler);
	this.addEvent(this.messageTA, 'keyup', this.messageHandler);
	this.addEvent(this.emailTF,	  'keyup', this.emailHandler);
	this.addEvent(this.phoneTF,   'keyup', this.phoneHandler);

	this.setSelectedOrgId();
	this.setSelectedTopicId();

};