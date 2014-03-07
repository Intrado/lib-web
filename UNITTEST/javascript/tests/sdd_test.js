describe("SDD (Secure Document Delivery)", function() {

	var	messageLinkCode,
		attachmentLinkCode,
		errorMsgContainer,
		errorMsg,
		contentWrapper,
		sdd;

	beforeEach(function() {
		// create some dummy elements
		messageLinkCode	= $("<input>").attr({"id":"message-link-code", type:"hidden", value:"1234"});
		attachmentLinkCode	= $("<input>").attr({"id":"attachment-link-code", type:"hidden", value:"5678"});
		errorMsgContainer	= $("<div>").attr({"id":"download-error-message-container"}).css('display', 'none');
		errorMsg = $("<div>").attr({"id":"download-error-message"});
		contentWrapper = $("<div>").attr({id: "contentWrapper"});

		// add elements to dom
		contentWrapper.append(messageLinkCode)
					  .append(attachmentLinkCode)
					  .append(errorMsgContainer.append(errorMsg));

		$("body").append(contentWrapper);

		sdd = new SDD();


	});

	afterEach(function() {
		// remove elements from dom
		contentWrapper.remove();

		window.sdd = undefined;

	});

	describe("initialize", function() {

		it("should call addPasswordKeyupHandler() and addDownloadBtnClickHandler() if $('#password') elem in DOM", function() {

			var password = $("<input>").attr({"id":"password"});
			contentWrapper.append(password);

			var addPasswordStub = sinon.stub(sdd, "addPasswordKeyupHandler");
			var addDownloadStub = sinon.stub(sdd, "addDownloadBtnClickHandler");
			var addDirectLinkStub = sinon.stub(sdd, "addDirectLinkClickHandler");
			var addCountdownTimerStub = sinon.stub(sdd, "startCountdownTimer");

			sdd.initialize();

			expect(addPasswordStub).to.have.been.calledOnce;
			expect(addDownloadStub).to.have.been.calledOnce;

			expect(addDirectLinkStub).to.not.have.been.called;
			expect(addCountdownTimerStub).to.not.have.been.called;

			addPasswordStub.restore();
			addDownloadStub.restore();
			addDirectLinkStub.restore();
			addCountdownTimerStub.restore();


		});

		it("should call addDirectLinkClickHandler(), and startCountdownTimer() if $('#countElem') elem in DOM", function() {

			var countElem = $("<span>").attr({"id":"download-count"});
			contentWrapper.append(countElem);

			var addPasswordStub = sinon.stub(sdd, "addPasswordKeyupHandler");
			var addDownloadStub = sinon.stub(sdd, "addDownloadBtnClickHandler");
			var addDirectLinkStub = sinon.stub(sdd, "addDirectLinkClickHandler");
			var addCountdownTimerStub = sinon.stub(sdd, "startCountdownTimer");

			sdd.initialize();

			expect(addPasswordStub).to.not.have.been.called;
			expect(addDownloadStub).to.not.have.been.called;

			expect(sdd.count).to.equal(5);
			expect(addDirectLinkStub).to.have.been.calledOnce;
			expect(addCountdownTimerStub).to.have.been.calledOnce;

			addPasswordStub.restore();
			addDownloadStub.restore();
			addDirectLinkStub.restore();
			addCountdownTimerStub.restore();

		});
	});

	describe("requestDocument(password)", function() {

		it("if 'password' arg provided, should perform AJAX 'GET' request with messageLinkCode, attachmentLinkCode, and password params", function() {
			var ajaxStub = sinon.stub(jQuery, "ajax");

			// request with password (password-protected)
			sdd.requestDocument('secretpassword123');

			expect(ajaxStub).to.have.been.called;
			var args = ajaxStub.args[0][0];
			expect(args.url).to.equal("../messagelink/requestdocument.php");
			expect(args.type).to.equal("GET");
			expect(args.data.messageLinkCode).to.equal('1234');
			expect(args.data.attachmentLinkCode).to.equal('5678');
			expect(args.data.password).to.equal('secretpassword123');
			ajaxStub.restore();
		});

		it("if 'password' arg NOT provided, should perform AJAX 'GET' request with messageLinkCode, attachmentLinkCode, and NO password param", function() {
			var ajaxStub = sinon.stub(jQuery, "ajax");

			// request without password (non-password-protected)
			sdd.requestDocument();

			expect(ajaxStub).to.have.been.called;
			args = ajaxStub.args[0][0];
			expect(args.url).to.equal("../messagelink/requestdocument.php");
			expect(args.type).to.equal("GET");
			expect(args.data.messageLinkCode).to.equal('1234');
			expect(args.data.attachmentLinkCode).to.equal('5678');
			expect(args.data.password).to.equal(null);
			ajaxStub.restore();
		});

		it("should show the provided error message, from the error response, if error callback called", function() {
			var ajaxStub = sinon.stub(jQuery, "ajax").yieldsTo('error', {errorMessage: 'ERROR: server unavailable.'});

			expect(sdd.errorMsgContainer.is(":visible")).to.equal(false);

			// the request will result in the $.ajax error callback being invoked (from stub yieldsTo above)
			sdd.requestDocument();
			expect(sdd.errorMsgContainer.is(":visible")).to.equal(true);
			expect(sdd.errorMsg.text()).to.equal('ERROR: server unavailable.');
			ajaxStub.restore();
		});

		it("should show a generic default error message, if no error response message provided, if error callback called", function() {
			var ajaxStub = sinon.stub(jQuery, "ajax").yieldsTo('error', {});

			expect(sdd.errorMsgContainer.is(":visible")).to.equal(false);

			// the request will result in the $.ajax error callback being invoked (from stub yieldsTo above)
			sdd.requestDocument();
			expect(sdd.errorMsgContainer.is(":visible")).to.equal(true);
			expect(sdd.errorMsg.text()).to.equal('An error occurred while trying to retrieve your document. Please try again.');
			ajaxStub.restore();
		});

	});

	describe("getPassword()", function() {
		it("should return the 'trimmed' (leading & trailing whitespace removed) password in the $(#password) elem", function(){
			var password = $("<input>").attr({"id":"password"});
			contentWrapper.append(password);

			// set password field manually, since initialize() wasn't called
			sdd.password = password;

			var pwd = sdd.getPassword();
			// password elem's value not set yet,
			expect(pwd).to.equal('');

			password.val("secretpassword123");
			pwd = sdd.getPassword();
			expect(pwd).to.equal("secretpassword123");

			password.val("   asdf   ");
			pwd = sdd.getPassword();
			expect(pwd).to.equal("asdf");
		});
	});

	describe("disableElem(elem)", function() {
		it("should add 'disabled' attribute and class to provided elem arg", function() {
			var elem = $("<button>");
			contentWrapper.append(elem);
			expect(elem.hasClass('disabled')).to.equal(false);
			expect(elem.attr('disabled')).to.equal(undefined);

			sdd.disableElem(elem);
			expect(elem.hasClass('disabled')).to.equal(true);
			expect(elem.attr('disabled')).to.equal('disabled');

		});
	});

	describe("enableElem(elem)", function() {
		it("should remove 'disabled' attribute and class from provided elem arg", function() {
			var elem = $("<button>").attr('disabled', 'disabled').addClass('disabled');
			contentWrapper.append(elem);
			expect(elem.hasClass('disabled')).to.equal(true);
			expect(elem.attr('disabled')).to.equal('disabled');

			sdd.enableElem(elem);
			expect(elem.hasClass('disabled')).to.equal(false);
			expect(elem.attr('disabled')).to.equal(undefined);

		});
	});

	describe("countdownTimerFcn()", function() {
		it("should decrement count by 1", function() {
			var countElem = $("<span>").attr({"id":"download-count"}).html(5);
			contentWrapper.append(countElem);

			// manually set countElem since initialize() isn't called
			sdd.countElem = countElem;

			var stopCountdownTimerStub = sinon.stub(sdd, "stopCountdownTimer");
			var requestDocumentStub = sinon.stub(sdd, "requestDocument");

			sdd.count = 5;

			sdd.countdownTimerFcn();
			expect(sdd.count).to.equal(4);
			sdd.countdownTimerFcn();
			expect(sdd.count).to.equal(3);
			sdd.countdownTimerFcn();
			expect(sdd.count).to.equal(2);
			sdd.countdownTimerFcn();
			expect(sdd.count).to.equal(1);
			sdd.countdownTimerFcn();
			expect(sdd.count).to.equal(0);
			sdd.countdownTimerFcn();
			// count should not become < 0
			expect(sdd.count).to.equal(0);

		});

		it("should call stopCountdownTimer() and requestDocument(messageLinkCode, attachmentLinkCode, null) if count == 0", function() {
			var countElem = $("<span>").attr({"id":"download-count"}).html(5);
			contentWrapper.append(countElem);

			// manually set countElem since initialize() isn't called
			sdd.countElem = countElem;

			var stopCountdownTimerStub = sinon.stub(sdd, "stopCountdownTimer");
			var requestDocumentStub = sinon.stub(sdd, "requestDocument");

			sdd.count = 0;

			sdd.countdownTimerFcn();
			expect(stopCountdownTimerStub).to.have.been.calledOnce;
			expect(requestDocumentStub).to.have.been.calledWith('1234', '5678', null);

			stopCountdownTimerStub.restore();
			requestDocumentStub.restore();
		});

		it("should show count in $('#download-count') elem", function() {
			var countElem = $("<span>").attr({"id":"download-count"}).html(5);
			contentWrapper.append(countElem);

			// manually set countElem since initialize() isn't called
			sdd.countElem = countElem;

			sdd.count = 4;
			sdd.countdownTimerFcn();
			expect(countElem.html()).to.equal('3');

		});
	});

	describe("addPasswordKeyupHandler()", function() {
		it("should enable the Download button upon keyup events in the $('#password') input if a valid password with 'trimmed' string length > 0 is present", function() {
			var password = $("<input>").attr({"id":"password"});
			var downloadB = $("<button>").attr({"id":"downloadB", "disabled":"disabled"}).addClass('disabled');
			contentWrapper.append(password)
						  .append(downloadB);

			// manually set vars since initialize() isn't called
			sdd.password = password;
			sdd.downloadB = downloadB;

			sdd.addPasswordKeyupHandler();

			expect(sdd.downloadB.hasClass('disabled')).to.equal(true);
			// now enter password and trigger keyup event
			password.val('secretpassword123').trigger('keyup');

			// download button should be enabled now
			expect(sdd.downloadB.attr('disabled')).to.equal(undefined);
			expect(sdd.downloadB.hasClass('disabled')).to.equal(false);

		});

		it("should disable the Download button upon keyup events in the $('#password') input if an invalid/empty password is present", function() {
			var password = $("<input>").attr({"id":"password"});
			var downloadB = $("<button>").attr({"id":"downloadB", "disabled":"disabled"}).addClass('disabled');
			contentWrapper.append(password)
				.append(downloadB);

			// manually set vars since initialize() isn't called
			sdd.password = password;
			sdd.downloadB = downloadB;

			sdd.addPasswordKeyupHandler();

			// now empty password field and trigger keyup to simulate user clearing password field
			password.trigger('keyup');

			// download button should be disabled now
			expect(sdd.downloadB.attr('disabled')).to.equal('disabled');
			expect(sdd.downloadB.hasClass('disabled')).to.equal(true);

			// now enter password and trigger keyup event
			password.val('secretpassword123').trigger('keyup');

			// download button should be enabled now
			expect(sdd.downloadB.attr('disabled')).to.equal(undefined);
			expect(sdd.downloadB.hasClass('disabled')).to.equal(false);

			// for good measure, clear password field and trigger keyup
			password.val('').trigger('keyup');

			// download button should be disabled now
			expect(sdd.downloadB.attr('disabled')).to.equal('disabled');
			expect(sdd.downloadB.hasClass('disabled')).to.equal(true)

		});
	});

	describe("addDownloadBtnClickHandler()", function() {
		it("should call requestDocument(messageLinkCode, attachmentLinkCode, password) on download button click events", function() {
			var requestDocumentStub = sinon.stub(sdd, "requestDocument");
			var password = $("<input>").attr({"id":"password"});
			var downloadB = $("<button>").attr({"id":"downloadB"});
			contentWrapper.append(password)
				.append(downloadB);

			// manually set since initialize() isn't called
			sdd.password = password;
			sdd.downloadB = downloadB;

			password.val('secretpassword123')

			sdd.addDownloadBtnClickHandler();

			expect(requestDocumentStub).to.not.have.been.called;
			downloadB.trigger('click');
			expect(requestDocumentStub).to.have.been.calledWith('1234', '5678', 'secretpassword123');
			requestDocumentStub.restore();
		});
	});

	describe("addDirectLinkClickHandler()", function() {
		it("should call requestDocument(messageLinkCode, attachmentLinkCode, null) on (download page) direct 'link' click events", function() {
			var requestDocumentStub = sinon.stub(sdd, "requestDocument");
			var directlink = $("<a>").addClass('directlink');
			contentWrapper.append(directlink);

			sdd.addDirectLinkClickHandler();

			expect(requestDocumentStub).to.not.have.been.called;
			directlink.trigger('click');
			expect(requestDocumentStub).to.have.been.calledWith('1234', '5678', null);
			requestDocumentStub.restore();
		});
	});

	describe("startCountdownTimer()", function() {
		it("should call setInterval(countdownTimerFcn, 1000)", function() {
			var countdownTimerFcnStub = sinon.stub(sdd, "countdownTimerFcn");
			var setIntervalStub = sinon.stub(window, "setInterval");

			expect(sdd.counter).to.equal(undefined);

			sdd.startCountdownTimer();
			expect(setIntervalStub).to.have.been.calledWith(countdownTimerFcnStub, 1000);

			countdownTimerFcnStub.restore()
			setIntervalStub.restore();
		});
		it("should set this.counter to the return value of setInterval(countdownTimerFcn, 1000), a unique ID used to pass to clearInterval() via stopCountodwnTimer()", function() {
			var countdownTimerFcnStub = sinon.stub(sdd, "countdownTimerFcn");
			var setIntervalStub = sinon.stub(window, "setInterval", function(){ return 'unique ID'});

			expect(sdd.counter).to.equal(undefined);

			sdd.startCountdownTimer();
			expect(sdd.counter).to.equal('unique ID');

			countdownTimerFcnStub.restore();
			setIntervalStub.restore();
		});
	});

	describe("stopCountdownTimer()", function() {
		it("should call setInterval(countdownTimerFcn, 1000)", function() {
			var clearIntervalStub = sinon.stub(window, "clearInterval");

			// dummy return result from setInterval(...)
			sdd.counter = 'unique ID';

			sdd.stopCountdownTimer();
			expect(clearIntervalStub).to.have.been.calledWith(sdd.counter);

			clearIntervalStub.restore();
		});
	});


});
