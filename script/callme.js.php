<?
require_once("../inc/utils.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/locale.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>
var CallMe = Class.create({

	// Initialize with empty specialtask id
	initialize: function(formname,origin,name,minlength,maxlength) {
		this.specialtask = null;
		this.origin = origin;
		this.itemname = name;
		this.minlength = minlength;
		this.maxlength = maxlength;
		this.phone = "";
		this.formname = formname;
		this.pe = null;
		this.acceptedimg = "img/icons/accept.gif";
		this.loadingimg = "img/icons/loading.gif";
		this.exclamationimg = "img/icons/exclamation.gif";
		this.alertimg = "img/icons/error.gif";
	},
	
	// Starts a callme session
	start: function () {
		this.phone = $(this.itemname+"phone").value;
		var validcheck = this.valPhone(this.phone,this.minlength,this.maxlength);
		if (validcheck !== true) {
			this.handleEnd(validcheck);
			return;
		}
		$(this.itemname+"recordbutton").hide();
		$(this.itemname+"progress").innerHTML = "<img src=\""+this.loadingimg+"\" /><?=addslashes(_L("Starting session. Please wait."))?>";
		new Ajax.Request('ajaxeasycall.php', {
			method:'post',
			parameters: {
				"phone": this.phone, 
				"language": "Default",
				"name": "Call Me",
				"origin": this.origin
			},
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
			$(this.itemname+"progress").innerHTML = "<img src=\""+this.loadingimg+"\" />" + response.progress;
			if (response.status == "done") {
				$(this.itemname).value = response.language["Default"];
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
		form_do_validation($(this.formname), $(this.itemname))
		switch(error) {
			case "done":
				$(this.itemname+"progress").innerHTML = "<img src=\""+this.acceptedimg+"\" /><?=addslashes(_L("Completed!"))?>";
				return;
			
			case "callended":
				$(this.itemname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Call ended early."))?>";
				break;
			
			case "badphone":
				$(this.itemname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Missing or invalid phone."))?>";
				break;
				
			case "notask":
				$(this.itemname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("Status unavailable. Please try again."))?>";
				break;
				
			default:
				$(this.itemname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" /><?=addslashes(_L("There was an error! Please try again."))?>";
		}
		$(this.itemname+"recordbutton").show();
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
