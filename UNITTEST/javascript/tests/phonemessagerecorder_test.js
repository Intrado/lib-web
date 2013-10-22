describe("setupBasicVoiceRecorder(formname, langcode, language, phone)", function() {

		var formname = 'formname';
		var ecEl = $('<input>').attr({
				'type':'hidden',
				'id': formname,
				'value': '{}'
			});

		var formEl = $('<form>').attr({
				'id': formname + "-form",
			});

		var formDomEl = formEl[0],
			ecDomEl = ecEl[0],
			langs = {};
			langcode = 'en';
			language = 'English'
			langs['en'] = 'English';
			phone = '888-123-4567'

	beforeEach(function() {
		window.form_do_validation = function() {
			console.log('form_do_validation called');
		};

		window.messagePreviewModal = function() {
			console.log('messagePreviewModal called');
		};

		window.audioPreviewModal = function() {
			console.log('audioPreviewModal called');
		};

		this.attachSpy = sinon.spy($.fn, 'attachEasyCall');
		this.validationStub = sinon.stub(window, 'form_do_validation');
		this.messagePreviewStub = sinon.stub(window, 'messagePreviewModal');
		this.audioPreviewStub = sinon.stub(window, 'audioPreviewModal');

		// stick ec elem inside form
		$("body").append(formEl.append(ecEl));
		setupBasicVoiceRecorder(formname, langcode, language, phone);

	});


	afterEach(function() {
		// remove ec event bindings
		ecEl.off("easycall:update");
		ecEl.off("easycall:preview");
		ecEl.detachEasyCall();
		ecEl.val("");
		formEl.remove();
		ecEl.remove();
		$(".easycallmaincontainer.easycall-widget.easycall").remove();
		this.attachSpy.restore();
		this.validationStub.restore();
		this.messagePreviewStub.restore();
		this.audioPreviewStub.restore();

		window.form_do_validation = undefined;
		window.messagePreviewModal = undefined;
		window.audioPreviewModal = undefined;
	});

	it("calls attachEasyCall() on the easyCall hidden input element ", function() {
		expect(this.attachSpy).to.have.been.called;
	});

	it('listens to "easycall:update" event and calls form_do_validation', function() {
		var res = "{\"af\": 123}";
		ecEl.val(res);
		ecEl.trigger('easycall:update', res);
		expect(this.validationStub).to.have.been.called;

	});

	it('listens to "easycall:preview" event and calls messagePreviewModal(id) if code = "m"', function() {
		// support Kona audiofile 'm' codes
		var res = "{\"m\": 123}";
		ecEl.val(res);
		ecEl.trigger('easycall:preview', res);
		expect(this.messagePreviewStub).to.have.been.calledWith(123);
	});

	it('listens to "easycall:preview" event and calls audioPreviewModal(id) if code is NOT "m", ex. "af", "en", "es"', function() {
		// support Kona audiofile 'af' codes
		var res = "{\"af\": 456}";
		ecEl.val(res);
		ecEl.trigger('easycall:preview', res);
		expect(this.audioPreviewStub).to.have.been.calledWith(456);

		// support language codes (non-af, non-m)
		res = "{\"en\": 987}";
		ecEl.val(res);
		ecEl.trigger('easycall:preview', res);
		expect(this.audioPreviewStub).to.have.been.calledWith(987);

		res = "{\"es\": 34}";
		ecEl.val(res);
		ecEl.trigger('easycall:preview', res);
		expect(this.audioPreviewStub).to.have.been.calledWith(34);
	});


});