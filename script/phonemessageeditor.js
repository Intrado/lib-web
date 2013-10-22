function startAudioUpload(itemname) {
	$(itemname + "uploaderror").update();
	$(itemname + "upload_process").show();
	return true;
}

function stopAudioUpload(audioid, audioname, errormessage, formname, itemname) {
	if (!formname || !itemname) {
		return;
	}
	// stopAudioUpload() is called automatically when the iframe is loaded, which may be before document.formvars is initialized by form_load().
	// In that case, just return.
	if (!document.formvars || !document.formvars[formname])
		return;

	setTimeout ("var uploadprocess = $(\'"+itemname+ "upload_process\'); if (uploadprocess) uploadprocess.hide();", 500 );

	$(itemname + "uploaderror").update(errormessage);

	// fire an event in the parent form item so we can further handle objects in this form
	if (audioid)
		$(itemname+"-audioupload").fire("AudioUpload:complete", {"audiofileid": audioid, "audiofilename": audioname});

	return true;
}

// setup the event handler for new audio file uploads
function setupAudioUpload(e, audiolibrarywidget) {
	e = $(e);
	var messagearea = e;
	var audioupload = $(e.id+"-audioupload");

	// listen for any new uploaded audio files so they can be inserted into the message and the library can be reloaded
	audioupload.observe("AudioUpload:complete", function(event) {
		var audiofilename = event.memo.audiofilename;
		var audiofileid = event.memo.audiofileid;

		// insert the audiofile into the message
		textInsert("{{" + audiofilename + ":#" + audiofileid +"}}", messagearea);

		// reload the audio library
		audiolibrarywidget.reload();
	});
}


/* Function to initialize the jquery.easycall.js widget in non-MessageSender locations
 * in Kona, ex. Messages > select message > Edit > select Language > Record, Surveys (New or Edit)
 *
 * @param: formname - String representing name (and id) attr of EasyCall (ec) widgets parent form
 * @param: langcode - String representing language code, ex. 'en', 'af', 'm' ('af' & 'm' are non-language audiofile and messagegroup codes)
 * @param: language - String representing language, ex 'English'
 * @param: phone - String of phone number to initialize ec widget with
 * @param: messagegroupid - integer
 * @param: audiolibrarywidget - reference to audiolibrarywidget
 */
function setupAdvancedVoiceRecorder(formname, langcode, language, phone, messagegroupid, audiolibrarywidget) {
	// need to reference jQuery as $ is used by prototype
	var easyCallObj = jQuery("#" + formname + "-easycall-widget");

	// get messageArea and its parent form DOM objects (not jQuery objs) for use in form validation (uses Prototype)
	var messageAreaDomEl = jQuery("#" + formname)[0];
	var formDomEl = easyCallObj.closest("form")[0];

	var languages = {};
	languages[langcode] = language;

	var easyCallOptions = {
		"languages": languages,
		"defaultcode": langcode,
		"defaultphone": phone
	};

	// attach EasyCall widget to easyCallObj (hidden input elem)
	easyCallObj.val("").attachEasyCall(easyCallOptions);

	// attach "easycall:update" event binding and callback handler fcn
	easyCallObj.on("easycall:update", function(event) {
		// event.currentTarget.value is a JSON string
		var easyCallDataObj = event && event.currentTarget && JSON.parse(event.currentTarget.value) || {};
		var audioFileId = easyCallDataObj[langcode];

		if (audioFileId && audioFileId.length > 0) {
			// insert the audiofile into the messageAreaDomEl
			textInsert("{{" + language + " - " + curDate() + ":#" + audioFileId +"}}", messageAreaDomEl);
			form_do_validation(formDomEl, messageAreaDomEl);

			// remove all remnants of previous ec recorder, in preparation for creating a new EasyCall
			easyCallObj.off("easycall:update");
			easyCallObj.detachEasyCall();
			easyCallObj.val("");

			// create a new EasyCall (renders the EasyCall widget in its default initial state again)
			// to allow users to continue to add more recordings if desired
			setupAdvancedVoiceRecorder(formname, langcode, language, phone, messagegroupid, audiolibrarywidget);

			if (messagegroupid && audiolibrarywidget) {
				// assign the audiofile to this message group
				new Ajax.Request("ajaxaudiolibrary.php", {
					"method": "post",
					"parameters": {
						"action": "assignaudiofile",
						"id": audioFileId,
						"messagegroupid": messagegroupid
					},
					"onSuccess": function() {
						// reload the audio library
						audiolibrarywidget.reload();
					},
					"onFailure": function() {
						alert("There was a problem assigning this audiofile to this message group");
					}
				});
			}

		}
	});
}


// set up the library of audio files for this message group, returns a reference to the widget
function setupAudioLibrary(e, mgid) {
	e = $(e);
	var library = $(e.id+"-library");
	var messagearea = e;

	// keep a reference to the audio library widget so we can pass it to other methods which will call reload() on it
	var audiolibrarywidget = new AudioLibraryWidget(library, mgid);

	// If insert is clicked in the library, insert that audio file into the message
	library.observe("AudioLibraryWidget:ClickInsert", function(event) {
		var audiofile = event.memo.audiofile;

		// insert the audiofile into the message
		textInsert("{{" + audiofile.name + ":#" + audiofile.id +"}}", messagearea);
	});

	return audiolibrarywidget;
}