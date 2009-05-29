var MessageSelect = Class.create({
	
	// Initialize with formname and message type
	initialize: function(formname, type) {
		this.formname = formname;
		this.type = type;
		this.messageid = "";
		this.loadingimg = "img/icons/loading.gif";
		this.playimg = "img/icons/play.gif";
		// on init, load any message that might be set
		this.getMessage();
	},
	
	// ajax request the message
	getMessage: function() {
		this.messageid = $(this.formname).value;
		if (parseInt(this.messageid) > 0) {
			$(this.formname+"details").show();
		} else {
			$(this.formname+"details").hide();
			return false;
		}
		$(this.formname+"lastused").update('<img src="'+this.loadingimg+'" />');
		$(this.formname+"description").update('<img src="'+this.loadingimg+'" />');
		if (this.type == "email") {
			$(this.formname+"from").update('<img src="'+this.loadingimg+'" />');
			$(this.formname+"subject").update('<img src="'+this.loadingimg+'" />');
			$(this.formname+"attachment").update('<img src="'+this.loadingimg+'" />');
		}
		$(this.formname+"body").value = "";
		new Ajax.Request('ajax.php?ajax&type=previewmessage&id='+this.messageid, {
			method:'get',
			onSuccess: this.handleMessage.bindAsEventListener(this)
		});
	},
	
	// handle the return data and populate it on the form
	handleMessage: function(transport){
		var response = transport.responseJSON;
		var text = "";
		if (response) {
			$(this.formname+"lastused").innerHTML = response.lastused || 'Never';
			$(this.formname+"description").innerHTML = response.description;
			if (this.type == "email") {
				$(this.formname+"from").innerHTML = response.fromemail;
				$(this.formname+"subject").innerHTML = response.subject;
				if (response.attachment) {
					$H(response.attachment).each(function(file) {
						text = text + '<a href="messageattachmentdownload.php?id=' + file.key + '">' + file.value.filename + '&nbsp;&nbsp;(' + parseInt(parseInt(file.value.size) / 1024) + 'K)</a><br>';
					});
					$(this.formname+"attachment").update(text);
					text = "";
				} else {
					$(this.formname+"attachment").update("None");
				}
			}
			if (this.type == "phone" && response.simple)
				$(this.formname+"body").value = "Simple Recording";
			else
				$(this.formname+"body").value = response.body;
		} else {
			$(this.formname+"lastused").update();
			$(this.formname+"description").update();
			if (this.type == "email") {
				$(this.formname+"from").update();
				$(this.formname+"subject").update();
				$(this.formname+"attachment").update();
			}
			$(this.formname+"body").value = "";
		}
	}
});
