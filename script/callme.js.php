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
	initialize: function(formname,origin,name) {
		this.specialtask = null;
		this.origin = origin;
		this.itemname = name;
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
	}
});
