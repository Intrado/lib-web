<?
require_once("../inc/utils.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/locale.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>
var Easycall = Class.create({

	// Initialize with empty specialtask id
	initialize: function(formname,reqlang,origin,minlength,maxlength) {
		this.specialtask = null;
		this.origin = origin;
		this.minlength = minlength;
		this.maxlength = maxlength;
		this.language = "";
		this.messageid = "";
		this.phone = "";
		this.formname = formname;
		this.required = reqlang;
		this.pe = null;
		this.acceptedimg = "img/icons/accept.gif";
		this.loadingimg = "img/icons/loading.gif";
		this.exclamationimg = "img/icons/exclamation.gif";
		this.deleteimg = "img/icons/delete.gif";
		this.playimg = "img/icons/play.gif";
		this.alertimg = "img/icons/error.gif";
	},
	
	// Load initial form values
	load: function() {
		var messages = $(this.formname).value.evalJSON();
		$H(messages).each(function (message) {
			this.language = message.key;
			this.messageid = message.value;
			this.displayMessage();
			this.updateMessage();
			if (this.language !== this.required)
				$(this.formname+this.language+"_remove").stopObserving().observe('click', function(event) {new Easycall(this.formname,this.required).del(this.language)}.bind(this));
		}.bind(this));
	},
	
	// Starts an easycall
	start: function () {
		if (!this.add())
			return;
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {"phone": this.phone, "language": this.language, "origin": this.origin},
			onSuccess: this.handleStart.bindAsEventListener(this),
			onFailure: this.handleEnd.bindAsEventListener(this)
		});
		return true;
	},
	handleStart: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			this.specialtask = response.id;
			this.update();
		} else {
			this.handleEnd(response.error);
		}
	},
	
	// gets task status by getting the id
	update: function () {
		try {
			this.pe = new PeriodicalExecuter(function(pe) {
				new Ajax.Request('ajaxeasycall.php?id=' + this.specialtask, {
					method:'get',
					onSuccess: this.handleUpdate.bindAsEventListener(this),
					onFailure: function() {
						this.handleEnd("startupfail");
					}
				});
			}.bind(this), 2);
		} catch (e) { alert(e); }
	},
	handleUpdate: function(transport) {
		var response = transport.responseJSON;
		if (response && !response.error) {
			// update image and progress text for call status
			$(this.formname+this.language+"_img").src = this.loadingimg;
			$(this.formname+"progress").innerHTML = "<img src=\""+this.loadingimg+"\" />" + response.progress;
			if (response.status == "done") {
				this.messageid = response.language[this.language];
				this.handleEnd("done");
			}
		} else {
			this.handleEnd(response.error);
		}
	},
	
	// update page based on how the task ended
	handleEnd: function(error) {
		if (this.pe) this.pe.stop();
		switch(error) {
			case "done":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.acceptedimg+"\" /><?=addslashes(_L("Completed!"))?>";
				break;
			
			case "callended":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Call ended early."))?>";
				break;
			
			case "badphone":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Missing or invalid phone."))?>";
				break;
			
			case "badlanguage":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Missing or invalid language."))?>";
				break;
			
			case "notask":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Status unavailable. Please try again."))?>";
				break;
			
			default:
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("There was an error! Please try again."))?>";
		}
		
		if ($(this.formname+this.language+"_row") !== null) {
			this.updateMessage();
			if (this.language !== this.required)
				$(this.formname+this.language+"_remove").stopObserving().observe('click', function(event) {new Easycall(this.formname,this.required).del(this.language)}.bind(this));
		}
		$(this.formname+"recordbutton").show();
	},
	
	add: function () {
		this.language = $(this.formname+"select").value;
		this.phone = $(this.formname+"phone").value;
		
		var validcheck = this.valPhone(this.phone,this.minlength,this.maxlength);
		if (validcheck !== true) {
			this.handleEnd(validcheck);
			return false;
		}
		
		if (!this.language) {
			this.handleEnd("badlanguage");
			return false;
		}
		
		var messages = $(this.formname).value.evalJSON();
		
		if (messages[this.language]) {
			if (!confirm("<?=addslashes(_L("There is already a message recorded for this language. Do you want to over-write it?"))?>"))
				return false;
			$(this.formname+this.language+"_img").stopObserving();
		}
		
		$(this.formname+"recordbutton").hide();
		$(this.formname+"progress").innerHTML = "<img src=\""+this.loadingimg+"\" /><?=addslashes(_L("Starting session. Please wait."))?>";
		if (typeof(messages[this.language]) == "undefined") {
			this.displayMessage();
			this.updateMessage();
		}
		return true;
	},
	
	del: function (language) {
		var messages = $(this.formname).value.evalJSON();
		delete messages[language];
		$(this.formname).value = Object.toJSON(messages);
		$(this.formname+language+"_row").remove();
	},
	
	displayMessage: function() {
		var remove =new Element("div",{id: this.formname+this.language+"_remove", style: "float: left;"});
		
		var newtbody = new Element("tbody",{})
		var newlang = new Element("tr", {id: this.formname+this.language+"_row"});
		newlang.insert(new Element("td",{style: "width: 18px"}).insert(new Element("img",{id: this.formname+this.language+"_img", src: "img/icons/time_add.gif"})));
		newlang.insert(new Element("td",{}).insert(new Element("div",{style: "margin-right: 5px"}).update(this.language)));
		
		var actions = new Element("div",{});
		actions.insert(remove);
		
		newlang.insert(new Element("td",{}).insert(actions));
		newtbody.insert(newlang);
		
		$(this.formname + "messages").insert(newtbody);
	},
	
	updateMessage: function () {
		var messages = $(this.formname).value.evalJSON();
		messages[this.language] = this.messageid;
		$(this.formname).value = Object.toJSON(messages);

		if (this.messageid) {
			$(this.formname+this.language+"_img").src = this.playimg;
			$(this.formname+this.language+"_img").observe('click', function(event) {popup("previewmessage.php?close=1&id="+this.messageid, 400, 500)}.bind(this));
		} else {
			$(this.formname+this.language+"_img").src = this.exclamationimg;
		}
		
		if (this.language !== this.required) {
			$(this.formname+this.language+"_remove").update("<img src=\""+this.deleteimg+"\" style=\"float: left; margin-right: 1px\" ><div style=\"font-size: 90%; text-decoration: underline; float: left; margin-right: 5px\"><?=addslashes(_L("Remove"))?></div>");
			$(this.formname+this.language+"_remove").observe('click', function(event) {alert("<?=addslashes(_L("Cannot remove a message while record session in progress."))?>")});
		} else {
			$(this.formname+this.language+"_remove").update("<img src=\""+this.alertimg+"\" style=\"float: left; margin-right: 1px\" ><div style=\"font-size: 90%; float: left; margin-right: 5px\"><?=addslashes(_L("Required"))?></div>");
		}
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
				return "badphone";
			}
			return true;
		} else if (phone.length < minlength) {
			return "badphone";
		} else if (phone.length > maxlength) {
			return "badphone";
		}
		return true
	}
});
