var QuickTip = function() {

	var document 		= window.document,
		orgListCoB 		= document.getElementById("tip-org-id"),
		categoryCoB 	= document.getElementById("tip-category-id"),
		messageTA 		= document.getElementById("tip-message"),
		messageTACont   = document.getElementById('tip-message-control-group'),
		errorMsgCont 	= document.getElementById("tip-error-message"),
		submitB 		= document.getElementById("tip-submit"),
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


	addEvent(submitB, 'click', function(event) {
		if (!methods.validate()) {
			event.preventDefault();
			setTimeout(function() {messageTA.focus();}, 500);
		}
	}, false);

	addEvent(messageTA, 'keyup', function(event) {
		if (!isValid) {			
			methods.validate();
		}
	}, false);

	methods = {
		
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

	return methods;
};


