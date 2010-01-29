<?
require_once("../inc/subdircommon.inc.php");
require_once("../inc/html.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>

var EasyCall = Class.create({

	// Initialize with empty specialtask id
	// variables used:
	//	formitemname: name of the parent form item
	//	containerid: parent form container id
	//	defaultphone: default phone number
	//	audiofilename: text to use as audiofile name
	initialize: function(formitemname, containerid, defaultphone, audiofilename) {
		this.form = $(formitemname).up("form");
		this.formitemname = $(formitemname).id;
		this.containerid = $(containerid).id;
		this.validatorargs = {
			"min": "<?=getSystemSetting('easycallmin',10)?>",
			"max": "<?=getSystemSetting('easycallmax',10)?>"
		};
		this.defaultphone = defaultphone;
		this.audiofilename = audiofilename;
		this.specialtaskid = null;
		this.audiofileid = null;
		this.nophonemessage = "<?=addslashes(_L('Phone Number'))?>";
		this.keytimer = null;

		// setup the record controls
		this.setupRecord();
	},

	// Starts an easycall specialtask
	record: function () {
		// get value of phone input
		var phone = $(this.containerid+"_callcontrol").down("input").value;

		// validate the phone, if it's invalid change the status and bail out on recording
		var val = this.easycallPhoneValidate(phone);
		if (val != true) {
			this.handleStatus("badphone", val);
			return;
		}

		// remove input and button
		$(this.containerid+"_callcontrol").remove();

		// load up a call progress div with new controls
		$(this.containerid).insert(
			new Element("div",{id: this.containerid+"_progress"}).insert(
				new Element("img",{src: "img/ajax-loader.gif", class: "easycallcallprogress"})
			).insert(
				new Element("div",{id: this.containerid+"_progress_text"}).update(
					"<?=addslashes(_L('Starting up call... Please wait.'))?>"
				)
			).insert(
				new Element("div", {class: "easycallunderline"})
			)
		);

		// do ajax to start the specialtask
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {
				"phone": phone,
				"action": "new"
			},
			// hand result off to handleRecord
			onSuccess: this.handleRecord.bindAsEventListener(this),
			// if it fails, change the status so the user has a vague idea what went wrong
			onFailure: function() {
				this.handleStatus("starterror", "");
			}.bindAsEventListener(this)
		});
	},

	// handles successful return of recording ajax call
	handleRecord: function(transport) {
		// update progress
		$(this.containerid+"_progress_text").update("<?=addslashes(_L('Call started... Your phone should ring shortly.'))?>");
		var response = transport.responseJSON;
		if (response && !response.error) {
			// if successful start, hand off to update
			this.specialtaskid = response.id;
			this.update();
		} else {
			// error hand off to handleStatus
			this.handleStatus(response.error, "");
		}
	},

	// gets task status by getting the specialtask id with a periodical executor
	update: function () {
		// start a periodical executor to check call status every 2 seconds
		this.pe = new PeriodicalExecuter(function(pe) {
			// request the status using the specialtask id
			new Ajax.Request('ajaxeasycall.php?action=status&id=' + this.specialtaskid, {
				method:'get',
				// hand result off to handleUpdate
				onSuccess: this.handleUpdate.bindAsEventListener(this),
				// failures update the status to let the user know there was a problem
				onFailure: function() {
					this.handleStatus("notask", "");
				}.bindAsEventListener(this)
			});
		}.bind(this), 2);
	},

	// handles successful return of status ajax call
	handleUpdate: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			// update progress
			$(this.containerid+"_progress_text").update(response.progress);
			// if the result indicates that the task is complete
			if (response.status == "done") {
				// stop the periodical executor and get the audiofile
				if (this.pe)
					this.pe.stop();
				// ajax request the audio file
				this.getAudioFile();
			}
		} else {
			this.handleStatus(response.error, "");
		}
	},

	// get an audiofile of the recording so we can store it
	getAudioFile: function() {
		$(this.containerid+"_progress_text").update("<?=addslashes(_L('Saving audio'))?>");
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {
				"id": this.specialtaskid,
				"name": this.audiofilename,
				"action": "getaudiofile"
			},
			onSuccess: this.handleGetAudioFile.bindAsEventListener(this),
			onFailure: function() {
				this.handleStatus("saveerror", "");
			}.bindAsEventListener(this)
		});
	},

	// audiofileid should be returned
	handleGetAudioFile: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			// all done! get audiofileid and hand off to handleStatus
			this.audiofileid = response.audiofileid;
			this.handleStatus("done", "");
		} else {
			this.handleStatus(response.error, "");
		}
	},

	// update page based on ended tasks or validation errors
	handleStatus: function(type, msg) {
		var needsretry = false;
		// make sure the periodical executor is stopped
		if (this.pe)
			this.pe.stop();

		switch(type) {
			case "done":
				// complete the session
				this.completeSession();
				return;

			// clears form validation
			case "noerror":
				form_validation_display(this.formitemname, "valid", "");
				break;

			case "notask":
				needsretry = true;
				form_validation_display(this.formitemname, "error", "<?=addslashes(_L('No valid request was found'))?>");
				break;

			case "callended":
				needsretry = true;
				form_validation_display(this.formitemname, "error", "<?=addslashes(_L('Call ended early'))?>");
				break;

			case "starterror":
				needsretry = true;
				form_validation_display(this.formitemname, "error", "<?=addslashes(_L('Couldn\'t initiate request'))?>");
				break;

			case "saveerror":
				needsretry = true;
				form_validation_display(this.formitemname, "error", "<?=addslashes(_L('There was a problem saving your audio'))?>");
				break;

			case "badphone":
				form_validation_display(this.formitemname, "error", msg);
				break;

			default:
				form_validation_display(this.formitemname, "error", "Unknown end request:" + type + ", with message:" + msg);
		}
		// if we need a retry button
		if (needsretry)
			$(this.containerid).update(this.getRetryButton());
	},

	// set up the form for a new recording session
	setupRecord: function () {
		$(this.containerid).update().insert(
			new Element("div", {"id": this.containerid+"_callcontrol"}).insert(
				this.getPhoneInput()
			).insert(
				new Element("div", { style: "clear: both;" })
			).insert(
				this.getCallMeButton()
			).insert(
				new Element("div", { style: "clear: both;" })
			)
		);
	},

	// return phone input
	getPhoneInput: function() {
		var phoneinput = new Element("input", {autocomplete:"off", type: "text", class: "easycallphoneinput"});

		// set the initial value
		phoneinput.value = this.defaultphone;

		// set up the value to display when blanked out
		blankFieldValue(phoneinput, this.nophonemessage);

		// observe keydown event listening for the return key
		phoneinput.observe("keydown", function (event) {
			if (Event.KEY_RETURN == event.keyCode) {
				// clear any form validation errors and stop keytimer
				this.handleStatus("noerror")
				window.clearTimeout(this.keytimer);
				// intercept and stop the event so we don't submit the form
				Event.stop(event);
				this.record();
			}
		}.bind(this));

		// observe keyup event to do number validation
		phoneinput.observe("keyup", function (event) {
			// Set a timer to do validation after 500 ms
			if (this.keytimer)
				window.clearTimeout(this.keytimer);
			var e = event.element();
			this.keytimer = window.setTimeout(
				function () {
					var val = this.easycallPhoneValidate(e.value)
					if (val === true)
						this.handleStatus("noerror");
					else
						this.handleStatus("badphone", val);
				}.bind(this),
				500
			);
		}.bind(this));

		return phoneinput;
	},

	// return a callme button
	getCallMeButton: function() {
		var callmebutton = icon_button("<?=addslashes(_L("Call Me to Record"))?>", "/diagona/16/151");

		// call me button starts the recording process
		callmebutton.observe("click", this.record.bind(this));

		return callmebutton;
	},

	// return a retry button
	getRetryButton: function() {
		var retrybutton = icon_button("<?=addslashes(_L('Clear and try again'))?>", "exclamation");

		// listen for clicks on the retry button
		retrybutton.observe(
			"click", this.setupRecord.bind(this)
		).observe(
			"click", function() {
				this.handleStatus("noerror");
			}.bind(this)
		);

		return retrybutton;
	},

	// fire the event that signifies the session is complete
	completeSession: function () {
		$(this.containerid).fire("EasyCall:update", {"audiofileid": this.audiofileid, "audiofilename": this.audiofilename});
	},

	easycallPhoneValidate: function (phone) {
		var validator = new document.validators["ValPhone"](this.form, "Phone Number", this.validatorargs);
		return validator.validate(this.form, "Phone Number", phone, this.validatorargs);
	}

});
