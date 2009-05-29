var Easycall = Class.create({

	// Initialize with empty specialtask id
	initialize: function(formname,reqlang) {
		this.specialtask = null;
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
		try {
			new Ajax.Request('ajaxeasycall.php', {
				method:'post',
				parameters: {"phone": this.phone,
					"language": this.language},
				onSuccess: this.handleStart.bindAsEventListener(this),
				onFailure: function() {
					alert("onFailure");
				}
			});
		} catch (e) { alert(e); }
		return true;
	},
	handleStart: function(transport) {
		var response = transport.responseJSON;
		if (response) {
			this.specialtask = response.id;
			try {
				this.update();
			} catch (e) { alert(e) }
		} else {
			this.handleError();
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
			// TODO: update icon and progress text
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
	
	handleEnd: function(error) {
		if (this.pe)
			this.pe.stop();
		this.updateMessage();
		switch(error) {
			case "done":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.acceptedimg+"\" />Completed! "
				$(this.formname+this.language+"_img").src = this.playimg;
				break;
			
			case "messageexists":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" />This message is already recorded "
				$(this.formname+this.language+"_img").src = this.playimg;
				break;
			
			case "callended":
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" />The call ended early!";
				$(this.formname+this.language+"_img").src = this.exclamationimg;
				break;
			
			default:
				$(this.formname+"progress").innerHTML = "<img src=\""+this.exclamationimg+"\" />There was an error! ("+error+")";
				$(this.formname+this.language+"_img").src = this.exclamationimg;
		}
		if (this.language !== this.required)
			$(this.formname+this.language+"_remove").stopObserving().observe('click', function(event) {new Easycall(this.formname,this.required).del(this.language)}.bind(this));
		
		$(this.formname+"recordbutton").show();
	},
	
	add: function () {
		this.language = $(this.formname+"select").value;
		this.phone = $(this.formname+"phone").value;
		var messages = $(this.formname).value.evalJSON();
		
		if (messages[this.language]) {
			this.handleEnd("messageexists");
			return false;
		}
		
		$(this.formname+"recordbutton").hide();
		$(this.formname+"progress").innerHTML = "<img src=\""+this.loadingimg+"\" />Starting session. Please wait...";
		
		if ($H(messages).keys().every(function(lang) { return (lang !== this.language); }.bind(this))) {
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
		if (this.messageid) {
			var messages = $(this.formname).value.evalJSON();
			messages[this.language] = this.messageid;
			$(this.formname).value = Object.toJSON(messages);
			$(this.formname+this.language+"_img").src = this.playimg;
			$(this.formname+this.language+"_img").observe('click', function(event) {popup("previewmessage.php?close=1&id="+this.messageid, 400, 500)}.bind(this)); // TODO: preview message popup
		} else {
			$(this.formname+this.language+"_img").src = this.exclamationimg;
		}
		
		if (this.language !== this.required) {
			$(this.formname+this.language+"_remove").update("<img src=\""+this.deleteimg+"\" style=\"float: left; margin-right: 1px\" ><div style=\"font-size: 90%; text-decoration: underline; float: left; margin-right: 5px\">Remove</div>");
			$(this.formname+this.language+"_remove").observe('click', function(event) {alert("Cannot remove a message while record session in progress.")});
		} else {
			$(this.formname+this.language+"_remove").update("<img src=\""+this.alertimg+"\" style=\"float: left; margin-right: 1px\" ><div style=\"font-size: 90%; float: left; margin-right: 5px\">Required</div>");
		}
	}
});
