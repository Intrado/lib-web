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
	//	formname: name of the parent form
	//	formitemname: name of the parent form item
	//	container: parent form container
	//	defaultphoneval: default phone number
	//	audiofilename: text to use as audiofile name
	initialize: function(formname, formitemname, container, defaultphoneval, audiofilename) {
		this.formname = formname;
		this.formitemname = formitemname;
		this.container = container;
		this.validatorargs = {
			"min": "<?=getSystemSetting('easycallmin',10)?>",
			"max": "<?=getSystemSetting('easycallmax',10)?>"
		};
		this.defaultphone = defaultphoneval;
		this.audiofilename = audiofilename;
		this.specialtaskid = null;
		this.audiofileid = null;
		this.nophone = "<?=addslashes(_L('Phone Number'))?>";
		this.keytimer = null;

		// setup up the record controls
		this.setupRecord();
	},

	// Starts an easycall
	record: function () {
		// get value of phone
		var phone = $(this.container+"_phone").value;

		// validate the phone, if it's invalid change the status and bail out on recording
		var val = this.easycallPhoneValidate(phone);
		if (val != true) {
			this.handleStatus("badphone", val);
			return;
		}

		// remove input and button
		$(this.container+"_callcontrol").remove();

		// load up a call progress div with new controls
		$(this.container).insert(
			new Element("div",{id: this.container+"_progress"}).insert(
				new Element("img",{id: this.container+"_progress_img", src: "img/ajax-loader.gif", style: "float:left"})
			).insert(
				new Element("div",{id: this.container+"_progress_text"})
			).insert(
				new Element("div", {style: "padding-top: 3px; margin-bottom: 5px; border-bottom: 1px solid gray; clear: both"})
			)
		);

		// set the progress text so the user knows something is happening in the background
		$(this.container+"_progress_text").update("<?=addslashes(_L('Starting up call... Please wait.'))?>");

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
		$(this.container+"_progress_text").update("<?=addslashes(_L('Call started... Your phone should ring shortly.'))?>");
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
			$(this.container+"_progress_text").update(response.progress);
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
		$(this.container+"_progress_text").update("<?=addslashes(_L('Saving audio'))?>");
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
		if (needsretry) {
			$(this.container).update(icon_button("<?=addslashes(_L('Clear and try again'))?>", "exclamation", this.container+"_retry")).insert(new Element("div", {style: "clear:both"}));
			// listen for clicks on the re-record button
			$(this.container+"_retry").observe(
				"click", this.setupRecord.bind(this)
			).observe(
				"click", function() {
					this.handleStatus("noerror");
				}.bind(this)
			);
		}
	},

	// set up the form for a new recording session
	setupRecord: function () {

		$(this.container).update().insert(
			new Element("div", {"id": this.container+"_callcontrol"}).insert(
				new Element("input", {"id": this.container+"_phone", autocomplete:"off", type: "text", style: "margin-bottom: 5px; border: 1px solid gray; "+((this.defaultphone == this.nophone)?"color: gray;":"")})
			).insert(
				new Element("div", {style: "clear:both"})
			).insert(
				icon_button("<?=addslashes(_L("Call Me to Record"))?>", "/diagona/16/151", this.container+"_callme").setStyle({float: "left"})
			).insert(
				new Element("div", {style: "clear:both"})
			)
		);

		$(this.container+"_phone").value = this.defaultphone;
		blankFieldValue($(this.container+"_phone"), this.nophone);

		$(this.container+"_phone").observe("keydown", function (event) {
			if (Event.KEY_RETURN == event.keyCode) {
				// clear any form validation errors and stop keytimer
				this.handleStatus("noerror")
				window.clearTimeout(this.keytimer);
				// intercept and stop the event so we don't submit the form
				Event.stop(event);
				this.record();
			}
		}.bind(this));
		$(this.container+"_phone").observe("keyup", function (event) {
			// Set a timer to do phone number validation
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

		// call me button starts the recording process
		$(this.container+"_callme").observe("click", this.record.bind(this));
	},

	// fire the event that signifies the session is complete
	completeSession: function () {
		$(this.container).fire("EasyCall:update", {"audiofileid": this.audiofileid, "audiofilename": this.audiofilename});
	},

	easycallPhoneValidate: function (phone) {
		var validator = new document.validators["ValPhone"](this.formname, "Phone Number", this.validatorargs);
		return validator.validate(this.formname, "Phone Number", phone, this.validatorargs);
	}

});
