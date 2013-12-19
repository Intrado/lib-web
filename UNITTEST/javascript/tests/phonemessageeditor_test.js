describe("setupAdvancedVoiceRecorder(form, formItem, easyCallFormItem, easyCallOptions, messagegroupid, audiolibrarywidget)", function() {

	var formname = 'formname';
	var easyCallFormItem = $('<input>').attr({
			'type':'hidden',
			'id': formname + '-easycall-widget',
			'value': '{}'
		});

	var form = $('<form>').attr({
			'id': formname + "-form"
		});

	var formItem = $('<textarea>').attr({
			'id': formname
		});

	var curDate = '01/02/2013 12:00p';

	var easyCallOptions = {
		languages: {en: "English"},
		defaultcode: 'en',
		defaultphone: '888-123-4567',
		phonemindigits: 10,
		phonemaxdigits: 10
	};

	beforeEach(function() {
		window.textInsert = function() {
			console.log('textInsert called');
		};

		window.form_do_validation = function() {
			console.log('form_do_validation called');
		};

		window.curDate = function() {
			return this.curDate;
		};

		this.attachSpy = sinon.spy($.fn, 'attachEasyCall');
		this.detachSpy = sinon.spy($.fn, 'detachEasyCall');
		this.textInsertStub = sinon.stub(window, 'textInsert');
		this.validationStub = sinon.stub(window, 'form_do_validation');

		// stick ec elem inside form
		$("body").append(form.append(easyCallFormItem));

		var messagegroupid = null;
		var audiolibrarywidget = null;
		setupAdvancedVoiceRecorder(form[0], formItem[0], easyCallFormItem[0], easyCallOptions, messagegroupid, audiolibrarywidget);
	});


	afterEach(function() {
		// remove ec event bindings
		easyCallFormItem.off("easycall:update");
		easyCallFormItem.off("easycall:preview");
		easyCallFormItem.detachEasyCall();
		easyCallFormItem.val("");
		form.remove();
		easyCallFormItem.remove();
		$(".easycallmaincontainer.easycall-widget.easycall").remove();
		this.attachSpy.restore();
		this.detachSpy.restore();
		this.validationStub.restore();
		this.textInsertStub.restore();

		window.textInsert = undefined;
		window.form_do_validation = undefined;
		window.curDate = undefined;
	});

	it("calls attachEasyCall(options) on the easyCall hidden input element ", function() {
		expect(this.attachSpy).to.have.been.calledWith(easyCallOptions);
	});

	describe('"easycall:update" event handler', function() {

		describe("if event handler receives response with audioFileId > 0", function() {

		});
		it('inserts audio recording text string into message textarea; calls textInsert()', function() {
			var res = { recordings: [ { recordingId: 123, languageCode: 'en', language: 'English' } ] };
			easyCallFormItem.trigger('easycall:update', res);
			expect(this.textInsertStub).to.have.been.called;
		});

		it('validates the message textarea after inserting the audio text string; calls form_do_validation()', function() {
			var res = { recordings: [ { recordingId: 123, languageCode: 'en', language: 'English' } ] };
			easyCallFormItem.trigger('easycall:update', res);
			expect(this.validationStub).to.have.been.called;
		});

		it('detaches old easyCall widget (removes "easycall:update" event listener, detaches easycall, clears value attr); calls detachEasyCall()', function() {
			var offStub = sinon.stub($.fn, 'off');
			var valSpy = sinon.spy($.fn, "val");
			var res = { recordings: [ { recordingId: 123, languageCode: 'en', language: 'English' } ] };
			easyCallFormItem.trigger('easycall:update', res);

			expect(this.detachSpy).to.have.been.called;
			expect(valSpy).to.have.been.called;

			offStub.restore();
			valSpy.restore();
		});

		it('re-initializes new EasyCall widget (after detaching old EC) by calling resetEasyCall()', function() {
			var res = { recordings: [ { recordingId: 123, languageCode: 'en', language: 'English' } ] };
			easyCallFormItem.trigger('easycall:update', res);

			expect(this.detachSpy).to.have.been.called;
			expect(this.attachSpy).to.have.been.called;
		});
	});
});