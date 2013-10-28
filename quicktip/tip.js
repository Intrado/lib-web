(function() {

	// Only define variables, functions etc on the starting form page,
	// not the final Thank You landing page
	if (!document.getElementById("thank-you")) {
		document = window.document;

		var	orgNameL 		= document.getElementById("tip-orgname-label"),
			orgListCoB 		= document.getElementById("tip-org-id"),
			categoryCoB 	= document.getElementById("tip-category-id"),
			messageTA 		= document.getElementById("tip-message"),
			messageTACont   = document.getElementById('tip-message-control-group'),
			errorMsgCont 	= document.getElementById("tip-error-message"),
			submitB 		= document.getElementById("tip-submit"),
			selOrgName 		= document.getElementById("tip-org-name"),
			selCatName 		= document.getElementById("tip-category-name");

		// helper methods for validation rendering (ex add/remove 'hide' class)
		var hasClass = function(elem, className) {
			return new RegExp(' ' + className).test(' ' + elem.className);
		};

		var addClass = function(elem, className) {
			if (!hasClass(elem, className)) {
				elem.className += ' ' + className;
			}
		};

		var removeClass = function(elem, className) {
		    var newClass = ' ' + elem.className.replace( /[\t\r\n]/g, ' ') + ' ';
		    if (hasClass(elem.className)) {
		        while (newClass.indexOf(' ' + className + ' ') >= 0 ) {
		            newClass = newClass.replace(' ' + className + ' ', ' ');
		        }
		        elem.className = newClass.replace(/^\s+|\s+$/g, '');
		    }
		};

		var isTipMessageValid = function() {
			return ((messageTA.value).replace(/^\s+|\s+$/g, '')).length > 0 ? true : false;
		};

		var validate = function() {
			var isValid = isTipMessageValid();
			if (isValid) {
				addClass(errorMsgCont, 'hide');
				removeClass(messageTACont, 'has-error');
			} else {
				removeClass(errorMsgCont, 'hide');
				addClass(messageTACont, 'has-error');
			}
			return isValid;
		};
		var setHiddenElVal = function(hiddenEl, combo) {
			console.log(combo);
			hiddenEl.value = combo.options[combo.selectedIndex].text;
		};

		// initialize hidden org and category name values on page load;
		setHiddenElVal(selOrgName, orgListCoB);
		setHiddenElVal(selCatName, categoryCoB);

		// add change event listeners to the combos, to update their respective hidden input elements
		// with the selected Org/Category name (these form field values are used on the Thank You summary page)
		orgListCoB.addEventListener('change', function(event) {
			setHiddenElVal(selOrgName, orgListCoB);
		});

		categoryCoB.addEventListener('change', function(event) {
			setHiddenElVal(selCatName, categoryCoB);
		});

		submitB.addEventListener('click', function(event) {
			var isValid = validate();
			if (!isValid) {
				event.preventDefault();
				setTimeout(function() {messageTA.focus();}, 500);
			}
		}, false);

		messageTA.addEventListener('keyup', function(event) {
			if (!hasClass(errorMsgCont, 'hide')) {
				validate();
			}
		}, false);
	}

})();
