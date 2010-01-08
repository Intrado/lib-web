<?
require_once("../inc/utils.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/locale.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>
var easycallRecordings = 0;

var Easycall = Class.create({

	// Initialize with empty specialtask id
	// variables used:
	//	formname: name of the parent form
	//	formitemname: name of the parent form item
	//	langcode: language code to store audiofiles id reference as
	//	minlength: minimum phone number length
	//	maxlength: maximum phone number length
	//	defaultphoneval: default phone number
	//	nophoneval: text to display when no phone number
	//	displaytitle: display lable on recording (used in wizard when there are multiple recordings)
	initialize: function(formname, formitemname, langcode, minlength, maxlength, defaultphoneval, nophoneval, displaytitle) {
		this.formname = formname;
		this.formitemname = formitemname;
		this.langcode = langcode;
		this.specialtask = null;
		this.audiofileid = null;
		this.defaultphone = defaultphoneval;
		this.nophone = nophoneval;
		this.num = 0;
		this.displaytitle = displaytitle;
		this.keytimer = null;
		this.validatorargs = {"min": this.minlength, "max": this.maxlength};
	},

	// Load initial form values
	load: function() {
		var audiofiles = $(this.formitemname).value.evalJSON();

		if (typeof(audiofiles[this.langcode]) !== "undefined") {
			easycallRecordings++;
			this.audiofileid = audiofiles[this.langcode];
			this.setupRecord();
			// remove input and button
			$(this.formitemname+"_"+this.langcode+"_callcontrol").remove();
			this.createFormItem();
		} else if (this.langcode == "Default") {
			this.setupRecord();
		}
	},

	// Starts an easycall
	record: function () {
		// get value of phone
		var phone = $(this.formitemname+"_"+this.langcode+"_phone").value;

		var val = this.easycallPhoneValidate(phone);
		if (val != true) {
			this.handleStatus("badphone", val);
			return;
		}

		// if there is a global variable for msgphone, set the current phone to it.
		if (typeof(msgphone) !== "undefined")
			msgphone = phone;

		// remove input and button
		$(this.formitemname+"_"+this.langcode+"_callcontrol").remove();

		// load up a call progress div with new controls
		$(this.formitemname+"_"+this.langcode).insert(
			new Element("div",{id: this.formitemname+"_"+this.langcode+"_progress"}).insert(
				new Element("img",{id: this.formitemname+"_"+this.langcode+"_progress_img", src: "img/ajax-loader.gif", style: "float:left"})
			).insert(
				new Element("div",{id: this.formitemname+"_"+this.langcode+"_progress_text"})
			).insert(
				new Element("div", {id: this.formitemname+"langlock"})
			).insert(
				new Element("div", {style: "padding-top: 3px; margin-bottom: 5px; border-bottom: 1px solid gray; clear: both"})
			)
		);

		$(this.formitemname+"_"+this.langcode+"_progress_text").update("<?=escapehtml(_L('Starting up call... Please wait.'))?>");
		// do ajax to start the specialtask
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {
				"phone": phone,
				"action": "new"
			},
			// hand result off to handleRecord
			onSuccess: this.handleRecord.bindAsEventListener(this),
			onFailure: function() {
				this.handleStatus("starterror", "");
			}
		});
	},

	handleRecord: function(transport) {
		// update progress
		$(this.formitemname+"_"+this.langcode+"_progress_text").update("<?=escapehtml(_L('Call started... Your phone should ring shortly.'))?>");
		var response = transport.responseJSON;
		if (response && !response.error) {
			// if successful start, hand off to update
			this.specialtask = response.id;
			this.update();
		} else {
			// error hand off to handleStatus
			this.handleStatus(response.error, "");
		}
	},

	// gets task status by getting the id
	update: function () {
		// start a periodical executor to check call status
		this.pe = new PeriodicalExecuter(function(pe) {
			new Ajax.Request('ajaxeasycall.php?action=status&id=' + this.specialtask, {
				method:'get',
				onSuccess: this.handleUpdate.bindAsEventListener(this),
				onFailure: function() {
					this.handleStatus("starterror", "");
				}
			});
		}.bind(this), 2);
	},

	handleUpdate: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			// update progress
			$(this.formitemname+"_"+this.langcode+"_progress_text").update(response.progress);
			if (response.status == "done") {
				// get the audiofile
				this.getAudioFile();
			}
		} else {
			this.handleStatus(response.error, "");
		}
	},

	// get an audiofile of the recording so we can store it
	getAudioFile: function() {
		$(this.formitemname+"_"+this.langcode+"_progress_text").update("<?=escapehtml(_L('Saving audio'))?>");
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {
				"id": this.specialtask,
				"action": "getaudiofile"
			},
			onSuccess: this.handleGetAudioFile.bindAsEventListener(this),
			onFailure: function() {
				this.handleStatus("saveerror", "");
			}
		});
	},

	// audiofileid should be returned
	handleGetAudioFile: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			// get the audiofileid and store the form data
			this.audiofileid = response.audiofileid;
			this.updateMessage();
			// all done! hand off to handleStatus
			this.handleStatus("done", "");
		} else {
			this.handleStatus(response.error, "");
		}
	},

	// update page based on ended tasks or validation errors
	handleStatus: function(type, msg) {
		var retrybuttontext = "";
		if (this.pe)
			this.pe.stop();
		switch(type) {
			case "done":
				// remove the progress div
				$(this.formitemname+"_"+this.langcode+"_progress").remove();
				if (!$(this.formitemname).fire('Easycall:RecordingDone', {'audiofileid':this.audiofileid}).stopped) {
					// create the play and re-record buttons
					this.createFormItem();
				} else {
					// If the event is stopped, then just setup another recording.
					this.setupRecord();
				}
				break;

			case "notask":
				retrybuttontext = "<?=escapehtml(_L('Clear and try again'))?>";
				form_validation_display(this.formitemname, "error", "<?=escapehtml(_L('No valid request was found'))?>");
				break;

			case "callended":
				retrybuttontext = "<?=escapehtml(_L('Clear and try again'))?>";
				form_validation_display(this.formitemname, "error", "<?=escapehtml(_L('Call ended early'))?>");
				break;

			case "starterror":
				retrybuttontext = "<?=escapehtml(_L('Clear and try again'))?>";
				form_validation_display(this.formitemname, "error", "<?=escapehtml(_L('Couldn\'t initiate request'))?>");
				break;

			case "saveerror":
				retrybuttontext = "<?=escapehtml(_L('Clear and try again'))?>";
				form_validation_display(this.formitemname, "error", "<?=escapehtml(_L('There was a problem saving your audio'))?>");
				break;

			case "badphone":
				form_validation_display(this.formitemname, "error", msg);
				break;

			default:
				retrybuttontext = "<?=escapehtml(_L('Clear and try again'))?>";
				form_validation_display(this.formitemname, "error", "Unknown end request:" + type + ", with message:" + msg);
		}
		if (retrybuttontext != "") {
			$(this.formitemname+"_"+this.langcode+"_progress").update(icon_button(retrybuttontext, "exclamation", this.formitemname+"_"+this.langcode+"_retry")).insert(new Element("div", {style: "clear:both"}));
			if (this.langcode == "Default") {
				$(this.formitemname+"_"+this.langcode+"_retry").observe("click", this.setupRecord.bind(this));
			} else {
				$(this.formitemname+"_"+this.langcode+"_retry").observe("click", this.removeMessage.bind(this));
			}
			$(this.formitemname+"_"+this.langcode+"_retry").observe("click", function() {form_validation_display(this.formitemname, "valid", "")}.bind(this));
		}
	},
	// create the form items after the message is recorded
	createFormItem: function () {
		// if this is a JobWizard callme session, display the language
		if (this.displaytitle)
			$(this.formitemname+"_"+this.langcode+"_lang").update((this.langcode == "Default")?"Default":languages[this.langcode]);

		if ($(this.formitemname+"_"+this.langcode+"_delete")) {
			$(this.formitemname+"_"+this.langcode+"_delete").insert({ "before": icon_button("<?=_L('Play')?>", "fugue/control", this.formitemname+"_"+this.langcode+"_play").setStyle({float: "left"}) });
			$(this.formitemname+"_"+this.langcode+"_delete").insert({ "before": icon_button("<?=_L('Re-record')?>", "diagona/16/118", this.formitemname+"_"+this.langcode+"_rerecord").setStyle({float: "left"}) });
		} else {
			$(this.formitemname+"_"+this.langcode+"_action").insert(icon_button("<?=_L('Play')?>", "fugue/control", this.formitemname+"_"+this.langcode+"_play").setStyle({float: "left"}));
			$(this.formitemname+"_"+this.langcode+"_action").insert(icon_button("<?=_L('Re-record')?>", "diagona/16/118", this.formitemname+"_"+this.langcode+"_rerecord").setStyle({float: "left"}));
		}

		// JobWizard call me gets a bottom border
		if (this.displaytitle)
			$(this.formitemname+"_"+this.langcode).insert(new Element("div", {style: "padding-top: 3px; margin-bottom: 5px; border-bottom: 1px solid gray; clear: both"}));

		$(this.formitemname+"_"+this.langcode+"_rerecord").observe("click", function (event) {
			if (confirm("<?=_L('This will delete the current recording. Are you sure you want to do this?')?>"))
				this.setupRecord();
		}.bind(this));
		$(this.formitemname+"_"+this.langcode+"_play").observe('click', function (event) {
			popup("previewmessage.php?close=1&id="+this.audiofileid, 400, 500);
		}.bind(this));

		if (!$(this.formitemname+"langlock"))
			$(this.formitemname+"_altlangs").show();
	},
	// set up the form for a new recording session
	setupRecord: function () {
		if ($(this.formitemname+'_select_'+this.langcode)) {
			if ($(this.formitemname+'_select_'+this.langcode)) {
				$(this.formitemname+'_select_'+this.langcode).hide();
			}
			$(this.formitemname+'_select').value = 0;
		}
		if (!$(this.formitemname+"_"+this.langcode))
			$(this.formitemname+"_content").insert(new Element("div",{id: this.formitemname+"_"+this.langcode}));

		// if this is a JobWizard callme session, display the language
		if (this.displaytitle) {
			$(this.formitemname+"_"+this.langcode).update().insert(
				new Element("div",{id: this.formitemname+"_"+this.langcode+"_lang", style: "font-size: large; float: left;"}).update(((easycallRecordings > 0)?((this.langcode == "Default")?"Default":languages[this.langcode]):''))
			).insert(
				new Element("div",{id: this.formitemname+"_"+this.langcode+"_action", style: "width: 80%; float: right; margin-bottom: 5px;"})
			).insert(
				new Element("div",{style: "clear: both;"})
			);
		} else {
			$(this.formitemname+"_"+this.langcode).update().insert(
				new Element("div",{id: this.formitemname+"_"+this.langcode+"_action", style: "margin-bottom: 5px;"})
			).insert(
				new Element("div",{style: "clear: both;"})
			);
		}

		if (this.langcode !== "Default") {
			$(this.formitemname+"_"+this.langcode+"_action").insert(icon_button("<?=_L("Remove")?>", "cross", this.formitemname+"_"+this.langcode+"_delete").setStyle({float:"left"}));
			$(this.formitemname+"_"+this.langcode+"_delete").observe("click", this.removeMessage.bind(this));
		}

		$(this.formitemname+"_"+this.langcode).insert(
			new Element("div", {"id": this.formitemname+"_"+this.langcode+"_callcontrol"}).insert(
				new Element("input", {"id": this.formitemname+"_"+this.langcode+"_phone", "class": "callmeinputphone", autocomplete:"off", type: "text", style: "margin-bottom: 5px; border: 1px solid gray; "+((this.defaultphone == this.nophone)?"color: gray;":"")})
			).insert(
				new Element("div", {style: "clear:both"})
			).insert(
				icon_button("<?=_L("Call Me to Record")?>", "/diagona/16/151", this.formitemname+"_"+this.langcode+"_callme").setStyle({float: "left"})
			).insert(
				new Element("div", {style: "clear:both"})
			).insert(
				new Element("div", {"id": this.formitemname+"langlock"})
			)
		);
		// if we saved a custom phone number entered earlier then use it as the default
		if (typeof(msgphone) !== "undefined" && msgphone !== null)
			this.defaultphone = msgphone;

		$(this.formitemname+"_"+this.langcode+"_phone").value = this.defaultphone;
		blankFieldValue($(this.formitemname+"_"+this.langcode+"_phone"), this.nophone);

		$(this.formitemname+"_"+this.langcode+"_phone").observe("keydown", function (event) {
			if (Event.KEY_RETURN == event.keyCode) {
				Event.stop(event);
				$(this.formitemname+"_"+this.langcode+"_callme").click();
			}
		}.bind(this));
		$(this.formitemname+"_"+this.langcode+"_phone").observe("keyup", function (event) {
			// Set a timer to do phone number validation
			if (this.keytimer)
				window.clearTimeout(this.keytimer);
			var e = event.element();
			this.keytimer = window.setTimeout(
				function () {
					var val = this.easycallPhoneValidate(e.value)
					if (val == true)
						form_validation_display(this.formitemname, "valid", "");
					else
						form_validation_display(this.formitemname, "error", val);
				}.bind(this),
				500
			);
		}.bind(this));
		$(this.formitemname+"_"+this.langcode+"_callme").observe("click", this.record.bind(this));

		$(this.formitemname+"_altlangs").hide();
	},

	removeMessage: function (event) {
		var audiofiles = $(this.formitemname).value.evalJSON();
		if (typeof(audiofiles[this.langcode]) !== "undefined") {
			if (!confirm("<?=_L('This will delete the current recording. Are you sure you want to do this?')?>"))
				return false;
			delete audiofiles[this.langcode];
			$(this.formitemname).value = Object.toJSON(audiofiles);
			easycallRecordings--;
		}

		if ($(this.formitemname+'_select_'+this.langcode))
			$(this.formitemname+'_select_'+this.langcode).show();
		$(this.formitemname+"_"+this.langcode).remove();
		if (this.pe)
			this.pe.stop();
		if (!$(this.formitemname+"langlock"))
			$(this.formitemname+"_altlangs").show();

		form_do_validation($(this.formname), $(this.formitemname));
	},

	updateMessage: function () {
		easycallRecordings++;
		//Save message information in hidden form field
		var audiofiles = $(this.formitemname).value.evalJSON();
		audiofiles[this.langcode] = this.audiofileid;
		$(this.formitemname).value = Object.toJSON(audiofiles);
		form_do_validation($(this.formname), $(this.formitemname));
	},

	easycallPhoneValidate: function (phone) {
		var validator = new document.validators["ValPhone"](this.formname, "Phone Number", this.validatorargs);
		return validator.validate(this.formname, "Phone Number", phone, this.validatorargs);
	}

});
