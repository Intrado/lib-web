describe("QuickTip", function() {

	var	tipForm,
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

	describe("validation.message.validate()", function() {
		it("returns true/false depending if Tip Message text is valid", function() {
			// empty string in TA is invalid
			messageTA[0].value = "";
			expect(qtip.validation.message.validate.call(qtip)).to.equal(false);
			
			// spaces only in TA is invalid
			messageTA[0].value = "   ";
			expect(qtip.validation.message.validate.call(qtip)).to.equal(false);

			messageTA[0].value = "finally some tip text...";
			expect(qtip.validation.message.validate.call(qtip)).to.equal(true);
		});
	});

	describe("validation.isValid()", function() {
		it("returns true/false depending if Tip message is && selected Orgnziation and Topic (ids) are valid (int > -1) and email & phone are valid", function() {
			
			// no message text && no topic options defined, so must be false/invalid
			expect(qtip.validation.validate.call(qtip)).to.equal(false);

			messageTA[0].value = "finally some tip text...";
			
			// message will be valid now but not the org and topic combos
			expect(qtip.validation.validate.call(qtip)).to.equal(false);

			// set selected topic option
			var orgOptions = [];
			// add some Org options
			for(var i = 0; i <= 3; i++) {
				var option = $("<option>").attr({"value": i}).html("Org_" + i);
				orgOptions.push(option);
			}
			orgListCoB.append(orgOptions);
			
			// set elem[0] (Org option) selected
			orgOptions[0].attr('selected', 'selected');
			qtip.setSelectedOrgId();
			expect(qtip.orgId).to.equal("0");

			// message text and org combos are valid, but not topic yet, so still invalid
			expect(qtip.validation.validate.call(qtip)).to.equal(false);

			// set selected topic option
			var topicOptions = [];
			// add some Org options
			for(var i = 0; i <= 3; i++) {
				var option = $("<option>").attr({"value": i}).html("Cat_" + i);
				topicOptions.push(option);
			}
			categoryCoB.append(topicOptions);
			
			// set elem[0] (Org option) selected
			topicOptions[0].attr('selected', 'selected');
			qtip.setSelectedTopicId();
			expect(qtip.topicId).to.equal("0");

			// finally, topic combo is set, so all 3 required fields are valid
			expect(qtip.validation.validate.call(qtip)).to.equal(true);
		});
	});

	describe("isSelectedIdValid()", function() {
		it("returns true/false depending if selected Orgnziation (id) is valid (int > -1)", function() {
			
			// no Org options defined, so must be false/invalid
			expect(qtip.isSelectedIdValid(qtip.orgId)).to.equal(false);

			var orgOptions = [];
			// add some Org options
			for(var i = 0; i <= 3; i++) {
				var option = $("<option>").attr({"value": i}).html("Org_" + i);
				orgOptions.push(option);
			}
			orgListCoB.append(orgOptions);
			
			// set elem[0] (Org option) selected
			orgOptions[0].attr('selected', 'selected');
			qtip.setSelectedOrgId();
			expect(qtip.orgId).to.equal("0");

			
			expect(qtip.isSelectedIdValid(qtip.orgId)).to.equal(true);
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

			// error container should NOT be hidden (org and topic combos don't have selected options yet) but textarea is now valid with no 'has-error' class
			expect(qtip.hasClass(errorCont, 'hide')).to.equal(false);
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

	describe("setSelectedOrgId()", function() {
		it("sets the orgId based on the selected Organization option (in the Org dropdown)", function() {

			// var url = "https://sandbox.testschoolmessenger.com/kbhigh/api/2/organizations/" + orgId + "/topics/" + topicId+ "/quicktip";
			// expect(tipForm.attr("action")).to.equal("");
			var orgOptions = [];
			// add some Org options
			for(var i = 0; i <= 3; i++) {
				var option = $("<option>").attr({"value": i}).html("Org_" + i);
				orgOptions.push(option);
			}
			orgListCoB.append(orgOptions);
			
			// set elem[0] (Org option) selected
			orgOptions[0].attr('selected', 'selected');
			qtip.setSelectedOrgId();
			expect(qtip.orgId).to.equal("0");

			orgOptions[0].removeAttr('selected');

			// set elem[3] (Org option) selected
			orgOptions[3].attr('selected', 'selected');
			qtip.setSelectedOrgId();
			expect(qtip.orgId).to.equal("3");
		});
	});

	describe("setSelectedTopicId()", function() {
		it("sets the topicId based on the selected Category (Topic) option (in the Category dropdown)", function() {

			var topicOptions = [];
			// add some category options
			for(var i = 0; i <= 10; i++) {
				var option = $("<option>").attr({"value": i}).html("Topic_" + i);
				topicOptions.push(option);
			}
			categoryCoB.append(topicOptions);
			
			// set elem[7] (Cat option) selected
			topicOptions[7].attr('selected', 'selected');
			qtip.setSelectedTopicId();
			expect(qtip.topicId).to.equal("7");

			topicOptions[7].removeAttr('selected');

			// set elem[10] (Cat option) selected
			topicOptions[10].attr('selected', 'selected');
			qtip.setSelectedTopicId();
			expect(qtip.topicId).to.equal("10");
		});
	});

	describe("setFormActionURL()", function() {
		it("sets the form's action url based on the currently selected orgId and topicId, if either is null, sets it to an empty string", function() {

			var orgOptions = [];
			// add some Org options
			for(var i = 0; i <= 3; i++) {
				var option = $("<option>").attr({"value": i}).html("Org_" + i);
				orgOptions.push(option);
			}
			orgListCoB.append(orgOptions);
			
			// set elem w/ orgId = 2 selected
			orgOptions[2].attr('selected', 'selected');
			qtip.setSelectedOrgId();
			expect(qtip.orgId).to.equal("2");

			var topicOptions = [];
			// add some category options
			for(var i = 0; i <= 5; i++) {
				var option = $("<option>").attr({"value": i}).html("Topic_" + i);
				topicOptions.push(option);
			}
			categoryCoB.append(topicOptions);
			
			// set elem w/ topicId = 7 selected
			topicOptions[5].attr('selected', 'selected');
			qtip.setSelectedTopicId();
			expect(qtip.topicId).to.equal("5");

			qtip.setFormActionURL();

			expect(qtip.formActionUrl).to.equal("/api/2/organizations/2/topics/5/quicktip");
		});
	});

});