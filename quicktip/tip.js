function QuickTip() {

	this.doc 			 = window.document;
	this.baseCustomerURL = "";
	this.formActionUrl	 = "";
	this.isValid 		 = false;

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

	this.valMessage = function() {
		return this.validation.message.isValid = ((this.messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;
	};

	this.valEmail = function() {
		return this.validation.email.isValid = (typeof(this.emailTF.checkValidity) === 'function') ? this.emailTF.checkValidity() : true;
	};

	this.valPhone = function() {
		return this.validation.phone.isValid = (typeof(this.phoneTF.checkValidity) === 'function') ? this.phoneTF.checkValidity() : true;
	};

	this.valSchool = function() {
		return this.validation.school.isValid = (this.orgListCoB.value > 0);
	};

	this.valTopic = function() {
		return this.validation.topic.isValid = (this.topicCoB.value > 0);
	};

	this.validate = function() {
		return this.isValid = (this.valSchool() && this.valTopic() && this.valMessage() && this.valEmail() && this.valPhone());
	};

	this.validation = {
		school: {
			isValid: true,
			msg: 'Please select a School.'
		},
		topic: {
			isValid: true,
			msg: 'Please select a Topic.'
		},
		message: {
			isValid: true,
			msg: 'Please enter a Tip Message.'
		},
		email: {
			isValid: true,
			msg: 'Please enter a valid email address.<div class="error-format-example">Ex. janedoe@example.com</div>'
		},
		phone: {
			isValid: true,
			msg: 'Please enter a valid phone number.<div class="error-format-example">Format: (888) 555-1234, 888-555-1234, or 888.555.1234</div>'
		}
	};

	this.renderValidation = function() {
		this.errorMsgCont.innerHTML = '';

		this.addClass(this.errorMsgCont, 'hide');
		this.removeClass(this.messageTACont, 'has-error');
		this.removeClass(this.topicCoB, 'has-error');
		this.removeClass(this.orgListCoB, 'has-error');

		if (!this.validation.school.isValid) {
			this.addClass(this.orgListCoB, 'has-error');
			this.setErrorMessage(this.validation.school.msg);
		}
		if (!this.validation.topic.isValid) {
			this.addClass(this.topicCoB, 'has-error');
			this.setErrorMessage(this.validation.topic.msg);
		}
		if (!this.validation.message.isValid) {
			this.addClass(this.messageTACont, 'has-error');
			this.setErrorMessage(this.validation.message.msg);
		}
		if (!this.validation.email.isValid) {
			this.setErrorMessage(this.validation.email.msg);
		}
		if (!this.validation.phone.isValid) {
			this.setErrorMessage(this.validation.phone.msg);
		}
		if (!this.isValid) {
			this.removeClass(this.errorMsgCont, 'hide');
		}
	};

	this.isSelectedIdValid = function(id) {
		return (typeof(id) !== 'undefined' && id > 0) ? true : false;
	};

	this.setErrorMessage = function(errorMsg) {
		this.errorMsgCont.innerHTML += errorMsg + '<br>';
		this.removeClass(this.errorMsgCont, 'hide');
	};

	this.setFormActionURL = function() {
		this.baseCustomerURL = this.tipForm.getAttribute('data-base-url');
		this.formActionUrl = "/api/2/organizations/" + this.orgListCoB.value + "/topics/" + this.topicCoB.value + "/quicktip";
		this.tipForm.setAttribute('action', this.baseCustomerURL + this.formActionUrl);
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

	this.bind = function (fn){ 
	  var ctx = this;
	  return function(){ 
	    return fn.apply(ctx, arguments); 
	  }; 
	};

	this.submitHandler = this.bind(function(event) {
		event.preventDefault ? event.preventDefault() : event.returnValue = false;

		if (!this.validate()) {
			this.renderValidation();
		} else {
			this.setFormActionURL();
			this.removeClass(this.mask, 'hide');
			this.removeClass(this.submittingTipSp, 'hide');
			this.addClass(this.submitTipSp, 'hide');
			this.tipForm.submit();
		}
	});

	this.valHandler = function(updateFieldVal) {
		if (!this.isValid) {
			this.validate();
			if (updateFieldVal.apply(this)) {
				this.renderValidation();
			}
		}
	};

	this.schoolHandler = this.bind(function() {
		this.valHandler(this.valSchool);
	});

	this.topicHandler = this.bind(function() {
		this.valHandler(this.valTopic);
	});

	this.messageHandler = this.bind(function() {
		this.valHandler(this.valMessage);
	});

	this.emailHandler = this.bind(function() {
		this.valHandler(this.valEmail);
	});

	this.phoneHandler = this.bind(function() {
		this.valHandler(this.valPhone);
	});

	this.addEvent(this.submitB,   'click', this.submitHandler);
	this.addEvent(this.orgListCoB, 'change', this.schoolHandler);
	this.addEvent(this.topicCoB,  'change', this.topicHandler);
	this.addEvent(this.messageTA, 'keyup', this.messageHandler);
	this.addEvent(this.emailTF,   'keyup', this.emailHandler);
	this.addEvent(this.phoneTF,   'keyup', this.phoneHandler);
}
