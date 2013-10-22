describe("setupAdvancedVoiceRecorder(formname, langcode, language, phone, messagegroupid, audiolibrarywidget)", function() {

		var formname = 'formname';
		var ecEl = $('<input>').attr({
				'type':'hidden',
				'id': formname + '-easycall-widget',
				'value': '{}'
			});

		var formEl = $('<form>').attr({
				'id': formname + "-form"
			});

		var messageAreaEl = $('<textarea>').attr({
				'id': formname
			});

		var formDomEl = formEl[0],
			messageAreaDomEl = messageAreaEl[0],
			langs = {};
			langcode = 'en';
			language = 'English'
			langs['en'] = 'English',
			phone = '888-123-4567',
			curDate = '01/02/2013 12:00p';

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
		this.detachSpy = sinon.spy($.fn, 'detachEasyCall')
		this.textInsertStub = sinon.stub(window, 'textInsert');
		this.validationStub = sinon.stub(window, 'form_do_validation');

		// stick ec elem inside form
		$("body").append(formEl.append(ecEl));
		setupAdvancedVoiceRecorder(formname, langcode, language, phone, false, null);

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
		this.detachSpy.restore();
		this.validationStub.restore();
		this.textInsertStub.restore();

		window.textInsert = undefined;
		window.form_do_validation = undefined;
		window.curDate = undefined;
	});

	it("calls attachEasyCall(options) on the easyCall hidden input element ", function() {
		var options = {
			"languages": langs,
			"defaultcode": langcode,
			"defaultphone": phone
		};

		expect(this.attachSpy).to.have.been.calledWith(options);
	});

	describe('"easycall:update" event handler', function() {

		describe("if event handler receives response with audioFileId > 0", function() {

		});
		it('inserts audio recording text string into message textarea; calls textInsert()', function() {
			var res = "{\"en\": \"123\"}";
			ecEl.val(res);
			ecEl.trigger('easycall:update', res);
			expect(this.textInsertStub).to.have.been.called;
		});

		it('validates the message textarea after inserting the audio text string; calls form_do_validation()', function() {
			var res = "{\"en\": \"123\"}";
			ecEl.val(res);
			ecEl.trigger('easycall:update', res);
			expect(this.validationStub).to.have.been.called;
		});

		it('detaches old easyCall widget (removes "easycall:update" event listener, detaches easycall, clears value attr); calls detachEasyCall()', function() {
			var offStub = sinon.stub($.fn, 'off');
			var valSpy = sinon.spy($.fn, "val");
			var res = "{\"en\": \"876\"}";
			ecEl.val(res);
			ecEl.trigger('easycall:update', res);

			expect(offStub).to.have.been.calledWith('easycall:update');
			expect(this.detachSpy).to.have.been.called;
			expect(valSpy).to.have.been.called;

			offStub.restore();
			valSpy.restore();
		});

		it('re-initializes new EasyCall widget (after detaching old EC) by calling setupAdvancedVoiceRecorder()', function() {
			var recorderStub = sinon.stub(window, 'setupAdvancedVoiceRecorder');

			var res = "{\"en\": \"876\"}";
			ecEl.val(res);
			ecEl.trigger('easycall:update', res);

			expect(recorderStub).to.have.been.called;
			recorderStub.restore()
		});
	});
});