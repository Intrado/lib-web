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
 * @param: form - The form
 * @param: formItem - The form input (or text area) which holds the message data
 * @param: easyCallFormItem - Hidden input to attach easyCall to
 * @param: easyCallOptions - Initialization options for easyCall
 * @param: messagegroupid - integer
 * @param: audiolibrarywidget - reference to audiolibrarywidget
 */
function setupAdvancedVoiceRecorder(form, formItem, easyCallFormItem, easyCallOptions, messagegroupid, audiolibrarywidget) {
	// need to reference jQuery as $ is used by prototype
	var formEl = jQuery(form)[0];
	var formItemEl = jQuery(formItem)[0];
	var easyCallObj = jQuery(easyCallFormItem);

	// attach EasyCall widget to easyCallObj (hidden input elem)
	easyCallObj.val("").attachEasyCall(easyCallOptions);

	// attach "easycall:update" event binding and callback handler fcn
	easyCallObj.on("easycall:update", function(event, data) {
		// there is only ever one recording going on at a time, so just get the first recording from the event
		var audiofileId = null;
		var language = null;
		if (data.recordings) {
			var firstRecording = data.recordings[0];
			audiofileId = firstRecording.recordingId;
			language = firstRecording.language;
		}

		if (audiofileId) {
			// insert the audiofile into the messageAreaDomEl
			textInsert("{{" + language + " - " + curDate() + ":#" + audiofileId +"}}", formItemEl);
			form_do_validation(formEl, formItemEl);

			// remove all remnants of previous easycall recorder and re-initialize it
			easyCallObj.val("").resetEasyCall();

			if (messagegroupid && audiolibrarywidget) {
				// assign the audiofile to this message group
				new Ajax.Request("ajaxaudiolibrary.php", {
					"method": "post",
					"parameters": {
						"action": "assignaudiofile",
						"id": audiofileId,
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
function setupAudioLibrary(formItem, libraryItem, mgid) {
	var library = $(libraryItem);

	// keep a reference to the audio library widget so we can pass it to other methods which will call reload() on it
	var audiolibrarywidget = new AudioLibraryWidget(library, mgid);

	// If insert is clicked in the library, insert that audio file into the message
	library.observe("AudioLibraryWidget:ClickInsert", function(event) {
		var audiofile = event.memo.audiofile;

		// insert the audiofile into the message
		textInsert("{{" + audiofile.name + ":#" + audiofile.id +"}}", formItem);
	});

	return audiolibrarywidget;
}