var MessageSelect = Class.create({
	
	// Initialize with formname and message type
	initialize: function(formname, type) {
		this.formname = formname;
		this.type = type;
		this.messageid = "";
		this.loadingimg = "img/icons/loading.gif";
		this.playimg = "img/icons/play.gif";
		this.fields = {};
		this.multisearch = {};
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
		if (this.type == "email" || this.type == "sms")
			$(this.formname+"body").value = "";
		if (this.type == "phone")
			$(this.formname+"preview").update('<img src="'+this.loadingimg+'" />');
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
				$(this.formname+"body").value = response.body;
			}
			if (this.type == "sms")
				$(this.formname+"body").value = response.body;
			if (this.type == "phone")
				this.getFields();
		} else {
			$(this.formname+"lastused").update();
			$(this.formname+"description").update();
			if (this.type == "email") {
				$(this.formname+"from").update();
				$(this.formname+"subject").update();
				$(this.formname+"attachment").update();
			}
			if (this.type == "email" || this.type == "sms")
				$(this.formname+"body").value = "";
			if (this.type == "phone")
				$(this.formname+"preview").update();
		}
	},
	
	// get the fieldmaps for fields in the message
	getFields: function() {
		this.fields = {};
		if (parseInt(this.messageid) <= 0)
			return false;
		new Ajax.Request('ajax.php?ajax&type=messagefields&id='+this.messageid, {
			method:'get',
			onSuccess: function(transport) {
				this.fields = transport.responseJSON;
				this.getFieldValues();
			}.bindAsEventListener(this)
		});
	},
	
	// generate preview area
	generatePhonePreview: function() {
		var htmlstr = "<table>";
		$H(this.fields).each(function(field) {
			htmlstr += "<tr><td>"+field.value.name+"</td>";
			if ($A(field.value.optionsarray).indexOf("text") !== -1)
				htmlstr += "<td><input id="+this.formname+"field_"+field.key+" type=text value=\"\"</td>";
			if ($A(field.value.optionsarray).indexOf("multisearch") !== -1) {
				htmlstr += "<td><select id="+this.formname+"field_"+field.key+" >";
				htmlstr += "<option value=0>--- Please Choose an Item ---</option>";
				$A(this.multisearch[field.key]).each(function(value) {
					htmlstr += "<option value="+value+">"+value+"</option>";
				});
				htmlstr += "</select></td>";
			}
			htmlstr += "</tr>";
		}.bind(this));
		htmlstr += "</table>";
		htmlstr += "<input type=\"button\" onclick=\""+this.formname+"messageselect.play();\" value=\"Play\">";
		$(this.formname+"preview").innerHTML = htmlstr;
	},
	
	getFieldValues: function() {
		if (this.fields) { // There are field inserts in the message.
			var requestfields = [];
			$H(this.fields).each(function(field) {
				if (this.multisearch[field.key]) // field is already cached
					return true;
				if ($A(field.value.optionsarray).indexOf("multisearch") !== -1)
					requestfields[requestfields.length] = field.key;
			}.bind(this));
			if (requestfields.length) {
				new Ajax.Request('ajax.php', {
					method:'post',
					parameters: {"type": "fieldvalues",
						"fields": Object.toJSON(requestfields)},
					onSuccess: function(transport) {
						var response = transport.responseJSON;
						$H(response).each(function(field) {
							this.multisearch[field.key] = field.value;
						}.bind(this));
						this.generatePhonePreview();
					}.bindAsEventListener(this)
				});
			} else {
				this.generatePhonePreview();
			}
		} else { // No field inserts
			this.generatePhonePreview();
		}
	},
	
	// play preview
	play: function() {
		var previewdata = "";
		$H(this.fields).each(function(field) {
			previewdata += "&"+field.key+"="+escape($(this.formname+"field_"+field.key).value);
		}.bind(this));
		previewdata += "&qt="+escape(" ");
		
		var htmlstr = "<PARAM NAME=\"FileName\" VALUE=\"preview.wav.php/mediaplayer_preview.wav?id="+this.messageid+previewdata+"\">";
		htmlstr += "<param name=\"controller\" value=\"true\">";
		htmlstr += "<EMBED SRC=\"preview.wav.php/embed_preview.wav?id="+this.messageid+previewdata+"\" AUTOSTART=\"TRUE\"></EMBED>";
		htmlstr += "</OBJECT>";
		
		$(this.formname+"play").innerHTML = htmlstr;
	}
});
