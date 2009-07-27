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
	initialize: function(formname, formitemname, language, minlength, maxlength, defaultphoneval, nophoneval) {
		this.formname = formname;
		this.formitemname = formitemname;
		this.language = language;
		this.minlength = minlength;
		this.maxlength = maxlength;
		this.specialtask = null;
		this.messageid = null;
		this.defaultphone = defaultphoneval;
		this.nophone = nophoneval;
		this.num = 0;
	},
	
	// Load initial form values
	load: function() {
		var messages = $(this.formitemname).value.evalJSON();
		
		if (typeof(messages[this.language]) !== "undefined") {
			this.messageid = messages[this.language];
			this.setupRecord();
			// remove input and button
			$(this.formitemname+"_"+this.language+"_callcontrol").remove();
			this.createFormItem();
		} else if (this.language == "Default") {
			this.setupRecord();
		}
	},
	
	// Starts an easycall
	record: function () {
		// get value of phone
		var phone = $(this.formitemname+"_"+this.language+"_phone").value;

		// remove input and button
		$(this.formitemname+"_"+this.language+"_callcontrol").remove();
		
		// load up a call progress div with new controls
		$(this.formitemname+"_"+this.language).insert(
			new Element("div",{id: this.formitemname+"_"+this.language+"_progress"}).insert(
				new Element("img",{id: this.formitemname+"_"+this.language+"_progress_img", src: "img/ajax-loader.gif", style: "float:left"})
			).insert(
				new Element("div",{id: this.formitemname+"_"+this.language+"_progress_text"})
			).insert(
				new Element("div", {"id": this.formitemname+"langlock"})
			).insert(
				new Element("div", {style: "padding-top: 3px; margin-bottom: 5px; border-bottom: 1px solid gray; clear: both"})
			)
		);

		if (!this.valPhone(phone,this.minlength,this.maxlength)) {
			this.handleEnd("badphone");
			return;
		}

		$(this.formitemname+"_"+this.language+"_progress_text").update("<?=escapehtml(_L('Starting up call... Please wait.'))?>");
		// do ajax to start the specialtask
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {
				"phone": phone, 
				"language": "Default",
				"name": "Call Me",
				"origin": "jobwizard"
			},
			// hand result off to handleRecord
			onSuccess: this.handleRecord.bindAsEventListener(this),
			onFailure: this.handleEnd.bindAsEventListener(this)
		});
	},
	
	handleRecord: function(transport) {
		// update progress
		$(this.formitemname+"_"+this.language+"_progress_text").update("<?=escapehtml(_L('Call started... Your phone should ring shortly.'))?>");
		var response = transport.responseJSON;
		if (response && !response.error) {
			// if successful start, hand off to update
			this.specialtask = response.id;
			this.update();
		} else {
			// error hand off to handleEnd
			this.handleEnd(response.error);
		}
	},
	
	// gets task status by getting the id
	update: function () {
		// start a periodical executor to check call status
		this.pe = new PeriodicalExecuter(function(pe) {
			new Ajax.Request('ajaxeasycall.php?id=' + this.specialtask, {
				method:'get',
				onSuccess: this.handleUpdate.bindAsEventListener(this),
				onFailure: function() {
					this.handleEnd("startupfail");
				}
			});
		}.bind(this), 2);
	},
	
	handleUpdate: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			// update progress
			$(this.formitemname+"_"+this.language+"_progress_text").update(response.progress);
			if (response.status == "done") {
				// if special task completes. Store the message and
				this.messageid = response.language["Default"];
				this.updateMessage();
				// hand off to handleEnd
				this.handleEnd("done");
			}
		} else {
			this.handleEnd(response.error);
		}
	},
	
	// update page based on how the task ended
	handleEnd: function(error) {
		if (this.pe)
			this.pe.stop();
		switch(error) {
			case "done":
				$(this.formitemname+"_"+this.language+"_progress").remove();
				// create the play and re-record buttons
				this.createFormItem();
				return;
			
			case "callended":
				$(this.formitemname+"_"+this.language+"_progress").update(icon_button("<?=addslashes(_L("Call ended early. Try again?"))?>", "exclamation", this.formitemname+"_retry")).insert(new Element("div", {style: "clear:both"}));
				break;
			
			case "badphone":
				$(this.formitemname+"_"+this.language+"_progress").update(icon_button("<?=addslashes(_L("Missing or invalid phone. Try again?"))?>", "exclamation", this.formitemname+"_retry")).insert(new Element("div", {style: "clear:both"}));
				break;
				
			case "notask":
				$(this.formitemname+"_"+this.language+"_progress").update(icon_button("<?=addslashes(_L("Status unavailable. Try again?"))?>", "exclamation", this.formitemname+"_retry")).insert(new Element("div", {style: "clear:both"}));
				break;
				
			case "startupfail":
				$(this.formitemname+"_"+this.language+"_progress").update(icon_button("<?=addslashes(_L("Session start failed. Try again?"))?>", "exclamation", this.formitemname+"_retry")).insert(new Element("div", {style: "clear:both"}));
				break;

			default:
				$(this.formitemname+"_"+this.language+"_progress").update(icon_button("<?=addslashes(_L("There was an error! Try again?"))?>", "exclamation", this.formitemname+"_retry")).insert(new Element("div", {style: "clear:both"}));
		}
		
		if ($(this.formitemname+"_"+this.language+"_progress")) {
			if (this.language == "Default") {
				$(this.formitemname+"_"+this.language+"_progress").observe("click", this.setupRecord.bind(this));
			} else {
				$(this.formitemname+"_"+this.language+"_progress").observe("click", this.removeMessage.bind(this));
			}
		}
	},
	
	createFormItem: function () {
		$(this.formitemname+"_"+this.language+"_lang").update(this.language);
		
		if ($(this.formitemname+"_"+this.language+"_delete")) {
			$(this.formitemname+"_"+this.language+"_delete").insert({ "before": action_link("<?=_L('Preview')?>", "control_play_blue", this.formitemname+"_"+this.language+"_play").setStyle({float: "left"}) });
			$(this.formitemname+"_"+this.language+"_delete").insert({ "before": action_link("<?=_L('Re-record')?>", "control_repeat_blue", this.formitemname+"_"+this.language+"_rerecord").setStyle({float: "left"}) });
		} else {
			$(this.formitemname+"_"+this.language+"_action").insert(action_link("<?=_L('Preview')?>", "control_play_blue", this.formitemname+"_"+this.language+"_play").setStyle({float: "left"}));
			$(this.formitemname+"_"+this.language+"_action").insert(action_link("<?=_L('Re-record')?>", "control_repeat_blue", this.formitemname+"_"+this.language+"_rerecord").setStyle({float: "left"}));
		}
		$(this.formitemname+"_"+this.language).insert(new Element("div", {style: "padding-top: 3px; margin-bottom: 5px; border-bottom: 1px solid gray; clear: both"}));

		$(this.formitemname+"_"+this.language+"_rerecord").observe("click", function (event) {
			if (confirm("<?=_L('This will delete the current recording. Are you sure you want to do this?')?>"))
				this.setupRecord();
		}.bind(this));
		$(this.formitemname+"_"+this.language+"_play").observe('click', function (event) {
			popup("previewmessage.php?close=1&id="+this.messageid, 400, 500);
		}.bind(this)); 
		
		if (!$(this.formitemname+"langlock"))
			$(this.formitemname+"_altlangs").show();
	},
	
	setupRecord: function () {
		if ($("'.$n.'_select")) {
			if ($(this.formitemname+'_select_'+this.language)) {
				$(this.formitemname+'_select_'+this.language).hide();
			}
			$(this.formitemname+'_select').value = 0;
		}
		if (!$(this.formitemname+"_"+this.language))
			$(this.formitemname+"_messages").insert(new Element("div",{id: this.formitemname+"_"+this.language}));
		
		$(this.formitemname+"_"+this.language).update().insert(
			new Element("div",{id: this.formitemname+"_"+this.language+"_lang", style: "font-size: large; float: left;"}).update(((easycallRecordings > 0)?this.language:''))
		).insert(
			new Element("div",{id: this.formitemname+"_"+this.language+"_action", style: "width: 80%; float: right;"})
		).insert(
			new Element("div",{style: "clear: both;"})
		);
		
		if (this.language !== "Default") {
			$(this.formitemname+"_"+this.language+"_action").insert(action_link("<?=addslashes(_L("Remove"))?>", "delete", this.formitemname+"_"+this.language+"_delete").setStyle({float:"left"}));
			$(this.formitemname+"_"+this.language+"_delete").observe("click", this.removeMessage.bind(this));
		}

		$(this.formitemname+"_"+this.language).insert(
			new Element("div", {"id": this.formitemname+"_"+this.language+"_callcontrol"}).insert(
				new Element("input", {"id": this.formitemname+"_"+this.language+"_phone", "class": "callmeinputphone", type: "text", style: "margin-bottom: 5px; border: 1px solid gray; "+((this.defaultphone == this.nophone)?"color: gray;":"")})
			).insert(
				new Element("div", {style: "clear:both"})
			).insert(
				icon_button("<?=_L("Call Me to Record")?>", "/diagona/16/151", this.formitemname+"_"+this.language+"_callme").setStyle({float: "left"})
			).insert(
				new Element("div", {style: "clear:both"})
			).insert(
				new Element("div", {"id": this.formitemname+"langlock"})
			)
		);
		
		$(this.formitemname+"_"+this.language+"_phone").value = this.defaultphone;
		blankFieldValue($(this.formitemname+"_"+this.language+"_phone"), this.nophone);
		
		$(this.formitemname+"_"+this.language+"_phone").observe("keyup", function (event) {
			var e = event.element();
			if (!this.valPhone(e.value, this.minlength, this.maxlength))
				e.setStyle({"background": "pink"});
			else
				e.setStyle({"background": "lightgreen"});
		}.bind(this));
		$(this.formitemname+"_"+this.language+"_callme").observe("click", this.record.bind(this));
		
		$(this.formitemname+"_altlangs").hide();
	},
	
	removeMessage: function (event) {
		var messages = $(this.formitemname).value.evalJSON();
		if (typeof(messages[this.language]) !== "undefined") {
			if (!confirm("<?=_L('This will delete the current recording. Are you sure you want to do this?')?>"))
				return false;
			delete messages[this.language];
			$(this.formitemname).value = Object.toJSON(messages);
			easycallRecordings--;
		}
		
		if ($("'.$n.'_select")) 
			$(this.formitemname+'_select_'+this.language).show();
		$(this.formitemname+"_"+this.language).remove();
		if (this.pe)
			this.pe.stop();
		if (!$(this.formitemname+"langlock"))
			$(this.formitemname+"_altlangs").show();
			
		form_do_validation(this.formname, this.formitemname);
	},
	
	updateMessage: function () {
		easycallRecordings++;
		//Save message information in hidden form field
		var messages = $(this.formitemname).value.evalJSON();
		messages[this.language] = this.messageid;
		$(this.formitemname).value = Object.toJSON(messages);
		try {
			form_do_validation(this.formname, this.formitemname);
		} catch (e) { alert(e); }
	},
	
	valPhone: function (pnumber,minlength,maxlength) {
		var phone = pnumber.replace(/[^0-9]/g,"");
		if (minlength == maxlength && maxlength == 10 && phone.length == 10) {
			var areacode = phone.substring(0, 3);
			var prefix = phone.substring(3, 6);
	
			// based on North American Numbering Plan
			// read more at en.wikipedia.org/wiki/List_of_NANP_area_codes

			if ((phone.charAt(0) == "0" || phone.charAt(0) == "1") || // areacode cannot start with 0 or 1
				(phone.charAt(3) == "0" || phone.charAt(3) == "1") || // prefix cannot start with 0 or 1
				(phone.charAt(1) == "1" && phone.charAt(2) == "1") || // areacode cannot be N11
				(phone.charAt(4) == "1" && phone.charAt(5) == "1") || // prefix cannot be N11
				("555" == areacode) || // areacode cannot be 555
				("555" == prefix)    // prefix cannot be 555
				) {
				// check special case N11 prefix with toll-free area codes
				// en.wikipedia.org/wiki/Toll-free_telephone_number
				if ((phone.charAt(4) == "1" && phone.charAt(5) == "1") && (
					("800" == areacode) ||
					("888" == areacode) ||
					("877" == areacode) ||
					("866" == areacode) ||
					("855" == areacode) ||
					("844" == areacode) ||
					("833" == areacode) ||
					("822" == areacode) ||
					("880" == areacode) ||
					("881" == areacode) ||
					("882" == areacode) ||
					("883" == areacode) ||
					("884" == areacode) ||
					("885" == areacode) ||
					("886" == areacode) ||
					("887" == areacode) ||
					("888" == areacode) ||
					("889" == areacode)
					)) {
					return true; // OK special case
				}
				return false;
			}
			return true;
		} else if (phone.length < minlength) {
			return false;
		} else if (phone.length > maxlength) {
			return false;
		}
		return true
	}
});
