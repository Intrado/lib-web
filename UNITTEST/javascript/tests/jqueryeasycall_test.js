describe("jQueryEasyCall", function() {

	var ecEl = $('<input>').attr({
				'type':'hidden',
				'id': 'easycall-widget',
				'value': '{}'
			});

	var	languages = {};
		langcode = 'en';
		language = 'English'
		languages[langcode] = language,
		phone = '888-123-4567';


	describe("attachEasyCall(options) - Without recording ID", function() {

		var easyCallOptions = {
			"languages": languages,
			"defaultcode": langcode,
			"defaultphone": phone
		};

		beforeEach(function() {
			// stick ec elem in DOM
			$("body").append(ecEl);
			ecEl.attachEasyCall(easyCallOptions);
		});

		afterEach(function() {
			// cleanup ec
			ecEl.detachEasyCall();
			ecEl.remove();
		});

		it("creates easycall phone and extension input fields", function() {
			expect($(".easycallmaincontainer.easycall-widget").length).to.equal(1);
			expect($(".easycallmaincontainer .easycallphoneinput").val()).to.equal('888-123-4567');
			expect($(".easycallmaincontainer .easycallextensioninput").val()).to.equal('Extension');
		});

		it("Initializes phone field value to value provided in options, if any", function() {
			expect($(".easycallmaincontainer .easycallphoneinput").val()).to.equal('888-123-4567');
		});

		it("Initializes extension field with 'Extension' val (used as pseudo-placeholder)", function() {
			expect($(".easycallmaincontainer .easycallextensioninput").val()).to.equal('Extension');
		});
	});


	describe("attachEasyCall(options) - With Recording ID", function() {
		var options = {
			"languages": languages,
			"defaultcode": langcode,
			"defaultphone": phone
		};

		before(function() {
			// stick ec elem in DOM
			ecEl.val("{\"en\": 123}");
			$("body").append(ecEl);
			ecEl.attachEasyCall(options);
		});

		after(function() {
			// cleanup ec
			ecEl.detachEasyCall();
			ecEl.remove();
		});


		it("creates easycall langauge label, Play and re-record buttons for the provided language recording data", function() {
			expect($(".easycallmaincontainer.easycall-widget").length).to.equal(1);

			// no text fields should be present
			expect($(".easycallmaincontainer .easycallphoneinput").length).to.equal(0);
			expect($(".easycallmaincontainer .easycallextensioninput").length).to.equal(0);

			expect($.trim($(".easycallpreviewcontainer .easycalllanguagetitle").text())).to.equal('English');
			expect($(".easycallpreviewcontainer .easycallpreviewbutton.btn").length).to.equal(1);
			expect($(".easycallpreviewcontainer .easycallrerecordbutton.btn").length).to.equal(1);
		});

	});

});