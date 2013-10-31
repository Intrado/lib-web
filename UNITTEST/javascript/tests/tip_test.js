describe("QuickTip", function() {

	var	orgListCoB,
		categoryCoB,
		messageTA,
		messageTACont,
		errorMsgCont,
		submitB,
		selOrgName,
		qtip;

	beforeEach(function() {
		// create some dummy elements
		orgListCoB 		= $("<select>").attr("id","tip-org-id"),
		categoryCoB 	= $("<select>").attr("id","tip-category-id"),
		messageTA 		= $("<textarea>").attr("id","tip-message"),
		messageTACont   = $("<div>").attr("id",'tip-message-control-group'),
		errorMsgCont 	= $("<div>").attr("id","tip-error-message"),
		submitB 		= $("<button>").attr("id","tip-submit"),
		
		// add elements to dom
		$('body').append(orgListCoB);
		$('body').append(categoryCoB);
		$('body').append(messageTA);
		$('body').append(messageTACont);
		$('body').append(errorMsgCont.addClass('hide')); // default/initial state = hidden
		$('body').append(submitB);
		
		// init QuickTip object/api
		qtip = new QuickTip();
					
	});

	afterEach(function() {	
		// remove elements from dom	
		orgListCoB.remove();
		categoryCoB.remove();
		messageTA.remove();
		messageTACont.remove();
		errorMsgCont.remove();
		submitB.remove();

		window.qtip = undefined;
	});

	describe("validate()", function() {
		it("returns true/false depending if Tip Message text is valid, and calls renderValidation() to update validation error messaging", function() {
			// empty string in TA is invalid
			messageTA[0].value = "";
			expect(qtip.validate()).to.equal(false);
			
			// spaces only in TA is invalid
			messageTA[0].value = "   ";
			expect(qtip.validate()).to.equal(false);

			messageTA[0].value = "finally some tip text...";
			expect(qtip.validate()).to.equal(true);
		});
	});

	describe("renderValidation()", function() {
		it("if valid, hides error message container and removes error styling on textarea", function() {
			var errorCont = errorMsgCont[0];
			var msgTACont = messageTACont[0];

			// no error yet, so error msg container is hidden and textarea has normal styling
			expect(qtip.hasClass(errorCont, 'hide')).to.equal(true);
			expect(qtip.hasClass(msgTACont, 'has-error')).to.equal(false);

			expect(qtip.renderValidation());

			// check if error container is visible (no 'hide' class) and textarea has 'has-error' class
			expect(qtip.hasClass(errorCont, 'hide')).to.equal(false);
			expect(qtip.hasClass(msgTACont, 'has-error')).to.equal(true);

			messageTA[0].value = "some tip text";
			messageTA[0].dispatchEvent(new Event('keyup')); // simulate keyup with some text in the textarea

			expect(qtip.renderValidation());

			// error container should be hidden and textarea with no 'has-error' class
			expect(qtip.hasClass(errorCont, 'hide')).to.equal(true);
			expect(qtip.hasClass(msgTACont, 'has-error')).to.equal(false);

		});
	});

	describe("hasClass(elem, className)", function() {
		it("returns true if elem has ClassName, else returns false", function() {
			var elem = errorMsgCont[0];
			expect(qtip.hasClass(elem, 'hide')).to.equal(true);

			// remove 'hide' class
			elem.className = '';
			expect(qtip.hasClass(elem, 'hide')).to.equal(false);
		});
	});

	describe("addClass(elem, className)", function() {
		it("adds className to elem", function() {
			var elem = errorMsgCont[0];
			expect(qtip.hasClass(elem, 'test-class')).to.equal(false);

			// add test class
			qtip.addClass(elem, ' test-class');
			expect(qtip.hasClass(elem, 'test-class')).to.equal(true);
		});
	});

	describe("removeClass(elem, className)", function() {
		it("removes className from elem", function() {
			var elem = errorMsgCont[0];		
			expect(qtip.hasClass(elem, 'test-class')).to.equal(false);

			// add test class
			qtip.addClass(elem, ' test-class');
			expect(qtip.hasClass(elem, 'test-class')).to.equal(true);

			// remove test class
			qtip.removeClass(elem, 'test-class');
			expect(qtip.hasClass(elem, 'test-class')).to.equal(false);
		});
	});	

});