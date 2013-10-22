
/* function to initialize the jquery.easycall.js widget
 * in non-MessageSender locations in Kona, ex. Messages > Select message > Edit > Record, Surveys (New or Edit), etc
 *
 * @param: formname - String representing name (and id) attr of EasyCall (ec) widgets parent form
 * @param: langcode - String representing language code, ex. 'en', 'af', 'm' ('af' & 'm' are non-language audiofile and messagegroup codes)
 * @param: language - String representing language, ex 'English'
 * @param: phone - String of phone number to initialize ec widget with
 */
function setupBasicVoiceRecorder(formname, langcode, language, phone) {
	var easyCallObj = jQuery("#" + formname),

		// get ec and its parent form DOM objects (not jQuery objs) for use in form validation (uses Prototype)
		easyCallDomEl   = easyCallObj[0],
		formDomEl = easyCallObj.closest("form")[0],
		languages = {};

	languages[langcode] = language;

	var	easyCallOptions = {
		"languages": languages,
		"defaultcode": langcode,
		"defaultphone": phone
	};

	// helper method to return object with code and id from ec event response
	var getAudioObj = function(event) {
		var audioFileObj = event && event.currentTarget && JSON.parse(event.currentTarget.value) || {};
		var code, id;
		for (var key in audioFileObj) {
			code = key;
			id = audioFileObj[key];
		}
		return {"code": code, "id": id};
	};

	easyCallObj.attachEasyCall(easyCallOptions);

	easyCallObj.on("easycall:update", function(event) {
		var audioObj = getAudioObj(event);

		easyCallDomEl.value = "{" + (audioObj.code === "m" ? "\"m\"" : "\"af\"") + ":" + audioObj.id + "}";
		form_do_validation(formDomEl, easyCallDomEl);
	});

	easyCallObj.on("easycall:preview", function(event) {
		var audioObj = getAudioObj(event);

		if (audioObj.code === "m") {
			messagePreviewModal(audioObj.id);
		} else {
			audioPreviewModal(audioObj.id);
		}
	});
}