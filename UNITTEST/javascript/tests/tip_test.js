describe("QuickTip", function() {

	var	tipForm,
		orgOptions,
		topicOptions,
		orgListCoB,
		categoryCoB,
		messageTA,
		messageTACont,
		errorMsgCont,
		submitB,
		selOrgName,
		qtip;

	beforeEach(function() {
		// create some dummy elements
		tipForm			= $("<form>").attr({"id":"quicktip", "action":"", "method":"POST"}),
		orgListCoB 		= $("<select>").attr("id","orgId"),
		categoryCoB 	= $("<select>").attr("id","topicId"),
		messageTA 		= $("<textarea>").attr("id","message"),
		messageTACont   = $("<div>").attr("id",'tip-message-control-group'),
		errorMsgCont 	= $("<div>").attr("id","tip-error-message"),
		emailTF			= $("<input>").attr("id", "email"),
		phoneTF			= $("<input>").attr("id", "phone"),
		submitB 		= $("<button>").attr("id","tip-submit"),
		
		// add elements to dom
		tipForm.append(orgListCoB);
		tipForm.append(categoryCoB);
		tipForm.append(messageTA);
		tipForm.append(messageTACont);
		tipForm.append(emailTF);
		tipForm.append(phoneTF);
		tipForm.append(errorMsgCont.addClass('hide')); // default/initial state = hidden
		tipForm.append(submitB);
		$('body').append(tipForm);

		orgOptions = [];
		// add some Org options
		for(var i = 0; i <= 3; i++) {
			var option = $("<option>").attr({"value": i}).html("Org_" + i);
			orgOptions.push(option);
		}
		orgListCoB.append(orgOptions);

		topicOptions = [];
		// add some category options
		for(var i = 0; i <= 5; i++) {
			var option = $("<option>").attr({"value": i}).html("Topic_" + i);
			topicOptions.push(option);
		}
		categoryCoB.append(topicOptions);

		// init QuickTip object/api
		qtip = new QuickTip();
					
	});

	afterEach(function() {	
		// remove elements from dom	
		tipForm.remove();
		orgListCoB.remove();
		categoryCoB.remove();
		messageTA.remove();
		messageTACont.remove();
		errorMsgCont.remove();
		emailTF.remove();
		phoneTF.remove();
		submitB.remove();

		window.qtip = undefined;
	});

	describe("this.valMessage()", function() {
		it("returns true/false depending if Tip Message text is valid", function() {
			// empty string in TA is invalid
			messageTA[0].value = "";
			expect(qtip.valMessage()).to.equal(false);
			
			// spaces only in TA is invalid
			messageTA[0].value = "   ";
			expect(qtip.valMessage()).to.equal(false);

			messageTA[0].value = "finally some tip text...";
			expect(qtip.valMessage()).to.equal(true);
		});
	});

	describe("validation.isValid()", function() {
		it("returns true/false depending if Tip message length (trimmed) > 0", function() {
			
			// no message text && no topic options defined, so must be false/invalid
			expect(qtip.validate()).to.equal(false);

			// set elem w/ orgId = 2 selected
			orgOptions[2].attr('selected', 'selected');
			// set elem w/ topicId = 7 selected
			topicOptions[5].attr('selected', 'selected');
			messageTA[0].value = "finally some tip text...";
			expect(qtip.validate()).to.equal(true);
		});
	});

	describe("isSelectedIdValid()", function() {
		it("returns true/false depending if selected Orgnziation (id) is valid (int > -1)", function() {
			
			// no Org options defined, so must be false/invalid
			expect(qtip.isSelectedIdValid(0)).to.equal(false);
			
			expect(qtip.isSelectedIdValid(1)).to.equal(true);
		});
	});

	describe("renderValidation()", function() {
		it("if valid, hides error message container and removes error styling on textarea", function() {
			var errorCont = errorMsgCont[0];
			var msgTACont = messageTACont[0];
			var event = document.createEvent('CustomEvent');

			event.initCustomEvent('keyup', false, false, null);

			// no error yet, so error msg container is hidden and textarea has normal styling
			expect(qtip.hasClass(errorCont, 'hide')).to.equal(true);
			expect(qtip.hasClass(msgTACont, 'has-error')).to.equal(false);

			// set elem w/ orgId = 2 selected
			orgOptions[2].attr('selected', 'selected');
			// set elem w/ topicId = 7 selected
			topicOptions[5].attr('selected', 'selected');

			qtip.validate();
			expect(qtip.renderValidation());

			// check if error container is visible (no 'hide' class) and textarea has 'has-error' class
			expect(qtip.hasClass(errorCont, 'hide')).to.equal(false);
			expect(qtip.hasClass(msgTACont, 'has-error')).to.equal(true);

			messageTA[0].value = "some tip text";
			messageTA[0].dispatchEvent(event); // simulate keyup with some text in the textarea
			// messageTA[0].dispatchEvent(new Event('keyup')); // simulate keyup with some text in the textarea

			expect(qtip.renderValidation());

			// error container should NOT be hidden (org and topic combos don't have selected options yet) but textarea is now valid with no 'has-error' class
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

	describe("setFormActionURL()", function() {
		it("sets the form's action url based on the currently selected orgId and topicId, if either is null, sets it to an empty string", function() {

			// set elem w/ orgId = 2 selected
			orgOptions[2].attr('selected', 'selected');
			// set elem w/ topicId = 7 selected
			topicOptions[5].attr('selected', 'selected');

			qtip.setFormActionURL();

			expect(qtip.formActionUrl).to.equal("/api/2/organizations/2/topics/5/quicktip");
		});
	});

});