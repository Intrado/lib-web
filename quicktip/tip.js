var QuickTip = function() {

	var document 		= window.document,
		orgListCoB 		= document.getElementById("tip-org-id"),
		categoryCoB 	= document.getElementById("tip-category-id"),
		messageTA 		= document.getElementById("tip-message"),
		messageTACont   = document.getElementById('tip-message-control-group'),
		errorMsgCont 	= document.getElementById("tip-error-message"),
		submitB 		= document.getElementById("tip-submit"),
		methods;

	submitB && submitB.addEventListener('click', function(event) {
		var isValid = methods.validate(messageTA, errorMsgCont, messageTACont);
		if (!isValid) {
			event.preventDefault();
			setTimeout(function() {messageTA.focus();}, 500);
		}
	}, false);

	messageTA && messageTA.addEventListener('keyup', function(event) {
		if (!methods.hasClass(errorMsgCont, 'hide')) {
			methods.validate(messageTA, errorMsgCont, messageTACont);
		}
	}, false);

	methods = {
		
		validate: function() {
			var isValid = ((messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;
			this.renderValidation(isValid);
			return isValid;
		},

		renderValidation: function(isValid) {
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


