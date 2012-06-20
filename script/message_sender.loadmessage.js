function loadMessage(mgid) {
	// Instead of using j we use j
	j = jQuery;

	var self = this;
	this.msgGroups = false;
	this.elements = {
		"messageTab": j('.msg_steps li:eq(1)'),
		"messageSection": j('#msg_section_2'),
		
		"phoneComplete": j('li.ophone'),
		"phoneType": j('#msgsndr_phonetype'),
		"phoneButtonCallMe": j('button.audioleft'),
		"phoneButtonText": j('button.audioright'),
		"phoneCallMeSection": j('#callme'),
		"phoneTextSection": j('#text'),
		"phoneRecording": j("#msgsndr_form_number"),
		"phoneText": j('#msgsndr_tts_message'),
		"phoneTranslatePrefix": "#tts_translated_",
		"phoneOverridePrefix": "#tts_override_",
		"phoneTranslateCheck": j("#msgsndr_form_phonetranslate"),
		"phoneLanguageCheckPrefix": "#tts_",
		
		"emailComplete": j('li.oemail'),
		"hasEmail": j('input[name=has_email'),
		"emailBody": j("#msgsndr_form_body"),
		"emailAttach" : j('#msgsndr_form_attachment'),
		"emailAttachControls": j('#uploadedfiles'),
		"emailSubject": j('#msgsndr_form_mailsubject'),
		"emailFromName": j('#msgsndr_form_name'),
		"emailFromEmail": j('#msgsndr_form_email'),
		"emailTranslatePrefix": "#email_translated_",
		"emailTranslateCheck": j("#msgsndr_form_emailtranslate"),
		"emailLanguageCheckPrefix": "#email_",

		"smsComplete": j('li.osms'),
		"hasSms": j('input[name=has_sms]'),
		"smsText": j('#msgsndr_form_sms'),

		"socialComplete": j('li.osocial'),
		
		"hasFacebook": j('#msgsndr_form_facebook'),
		"facebookSection": j('div.facebook'),
		"facebookText": j('#msgsndr_form_fbmsg'),
		
		"hasTwitter": j('#msgsndr_form_twitter'),
		"twitterSection": j('div.twitter'),
		"twitterText": j('#msgsndr_form_tmsg'),
		
		"hasFeed": j('#msgsndr_form_feed'),
		"feedSection": j('div.feed'),
		"feedSubject": j('#msgsndr_form_rsstitle'),
		"feedText": j('#msgsndr_form_rssmsg')
	};

	// This makes the whole table row clickable to select the message ready for loading into content
	j('#messages_list').on('click', 'tr', function(){
		// Unset all inputs
		j('input[name=msgsndr_msggroup]').removeAttr('checked');
		// Remove selected class from all tr's
		j('#messages_list tr').removeClass('selected');
	
		// Set radio button to checked for selected message
		j('td:first input:radio[name=msgsndr_msggroup]', this).attr('checked', 'checked');
		// Add class selected to tr
		j(this).addClass('selected');
	});

	// Load Saved Message button in saved message modal window
	j('#msgsndr_load_saved_msg').on('click', function(){
		var msgGroup = j('.msgsndr_msggroup > td > input:radio[name=msgsndr_msggroup]:checked'); //input:checkbox[name=msgsndr_msggroup]:checked'
		
		self.elements.messageTab.addClass("active complete");
		self.loadMessageGroup(msgGroup.attr('value'));
	});
	
	// load a specific message group
	this.loadMessageGroup = function(msgGrpId) {
		// get the selected message group data
		var selectedMsgGroup = false;
		if (self.msgGroups) {
			j.each(self.msgGroups, function(index, mg) {
				if (mg.id == msgGrpId)
					selectedMsgGroup = mg;
			});
		}
		// not found? look it up via API request
		if (!selectedMsgGroup)
			selectedMsgGroup = self.getMessageGroup(msgGrpId);

		// FIXME: still no message group? 
		
		// put the messageGroup id in the hidden input and display the message name
		j('#loaded_message_id').attr('value', selectedMsgGroup.id);
		j('#loaded_message_name').text(selectedMsgGroup.name);
		j('#msgsndr_loaded_message').fadeIn(300);
	
		// make sure the correct tab is shown
		j('#msgsndr_saved_message').modal('hide');
		if (self.elements.messageTab.hasClass("active")){
			self.elements.messageSection.show();
		} else {
			self.elements.messageTab.addClass('complete');
		}

		self.clearForm();
		self.prepareFormForLoad(selectedMsgGroup);
		self.getMessages(selectedMsgGroup);
	};
	
	// get message group data ...
	this.getMessageGroups = function() {
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups',
			type: "GET",
			dataType: "json",
			success: function(data) {
				self.msgGroups = data.messageGroups;

				j.each(self.msgGroups, function(index, msgGroup) {
					// format the date from the modifiedTimestamp value
					var msgDate = moment(msgGroup.modifiedTimestamp*1000).format('MM/DD/YYYY');
					
					var msgTypes = msgGroup.typeSummary;
					// loop through the typeSummary array to see what message parts are included
					var msgPhone = '';
					var msgEmail = '';
					var msgSms = '';
					var msgPost = '';
					var tickHtml = '<span class="icon">x</span>';
					j.each(msgTypes, function(index, msgType) {
						if (msgType.type == 'phone') {
							msgPhone = tickHtml;
						}
						if (msgType.type == 'email') {
							msgEmail = tickHtml;
						}
						if (msgType.type == 'sms') {
							msgSms = tickHtml;
						}
						if (msgType.type == 'post') {
							msgPost = tickHtml;
						}
					});

					j('#messages_list').append('<tr id="msgsndr_msggroup-'+msgGroup.id+'" class="msgsndr_msggroup"><td><input type="radio" data-audio="'+msgGroup.phoneIsAudioOnly+'" name="msgsndr_msggroup" value="'+msgGroup.id+'"/>'+msgGroup.name+'</td><td class="created">'+msgDate+'</td><td class="ico">'+msgPhone+'</td><td class="ico">'+msgEmail+'</td><td class="ico">'+msgSms+'</td><td class="ico">'+msgPost+'</td></tr>');
				});
			} 
		});	 
	};

	this.prepareFormForLoad = function(msgGroup) {
		// based on message group info, set appropriate form status
		j.each(msgGroup.typeSummary, function(index, msgType) {
			switch (msgType.type) {
				case 'phone':
					self.elements.phoneComplete.addClass('complete');
					if (msgGroup.phoneIsAudioOnly) {
						self.elements.phoneType.val('callme');
						self.elements.phoneButtonCallMe.addClass('active'); 
						self.elements.phoneButtonText.removeClass('active');
						self.elements.phoneCallMeSection.removeClass("hide");
						self.elements.phoneTextSection.addClass("hide");
						notVal.watchContent('callme');
					} else {
						self.elements.phoneType.val('text');
						self.elements.phoneButtonCallMe.removeClass('active'); 
						self.elements.phoneButtonText.addClass('active');
						self.elements.phoneCallMeSection.addClass("hide");
						self.elements.phoneTextSection.removeClass("hide");
						notVal.watchContent('text');
					}
					break;
				case "email":
					// ignore plain emails
					if (msgType.subType == "plain") {
						// Nothing
					} else {
						self.elements.emailComplete.addClass('complete');
						self.elements.hasEmail.attr('checked','checked');
						notVal.watchContent('msgsndr_tab_email');
					}
					break;
				case 'sms':
					self.elements.smsComplete.addClass('complete');
					self.elements.hasSms.attr('checked','checked');
					notVal.watchContent('msgsndr_tab_sms');
					break;
				case 'post':
					self.elements.socialComplete.addClass('complete');
					switch (msgType.subType) {
						case "facebook":
							self.elements.hasFacebook.attr('checked','checked');
							self.elements.hasFacebook.trigger("change");
							self.elements.facebookSection.show();
							break;
						case "twitter":
							self.elements.hasTwitter.attr('checked','checked');
							self.elements.twitterSection.show();
							break;
						case "feed":
							self.elements.hasFeed.attr('checked','checked');
							self.elements.feedSection.show();
							break;
					}
					notVal.watchSocial('msgsndr_tab_social');
					break;
			}
		});
	};

	// load messages from message group
	this.getMessages = function(msgGrp) {
		// load message group content into session server side.
		self.loadMessageGroupContentForPreview(msgGrp.id);
		// request all the messages for the selected message group
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrp.id+'/messages',
			type: "GET",
			dataType: "json",
			success: function(data) {
				// itterate all the messages and call methods to populate the data
				j.each(data.messages, function(mIndex, msg) {
					if(typeof(msg.type) != "undefined" && msg.type.length > 0) { 
						switch (msg.type) {
							case "phone":
								// ignore source phone messages
								if (msg.autoTranslate == "source") {
									// nothing
								} else {
									self.loadPhoneMessage(msgGrp.id, msg, msgGrp.phoneIsAudioOnly);
								}
								break;
							case "email":
								// ignore plain and source emails
								if (msg.subType == "plain" || msg.autoTranslate == "source") {
									// Nothing
								} else {
									self.loadEmailMessage(msgGrp.id, msg);
								}
								break;
							case "sms":
								self.loadSmsMessage(msgGrp.id, msg);
								break;
							case "post":
								self.loadPostMessage(msgGrp.id, msg);
								break;
						}
					}
				});
			}
		});
	};

	/*
		function used to empty all form data in section 2 - Message content, this is used when loading a
		previous message, as can not assume a user will only load one
	*/
	this.clearForm = function() {
		j('.msg_content_nav li').removeClass('complete active lighten');
		j('.tab_content .tab_panel').hide();
		j('.facebook, .twitter, .feed').hide();			
	
		var allDataInputs = j('#msg_section_2 input, textarea');
	
		j.each(allDataInputs, function(aIndex, aData) {
	
			if (j(aData).attr('type') == 'checkbox') {
				j(aData).removeAttr('checked');
			}
			// don't blank out easycall phone number field
			if (!j(aData).hasClass("easycallphoneinput"))
				j(aData).val('').removeClass('ok');
	
		});
	
		// Uncheck all translation check boxes and hide their controls
		j.each(j('input.translations'), function(aIndex, aData) {
			j(aData).removeAttr('checked');
			j(aData).parent().children(".controls").addClass("hide").children("textarea").attr("disabled","disabled");
		});
	
		var reviewTabs = j('.msg_complete li');
		j.each(reviewTabs, function(tIndex, tData) {
			j(tData).removeClass('complete');
		});
	
		// Phone 
		j("#msgsndr_form_number").empty();
	
		// Email Message Content resets
		clearHtmlEditorContent();
		self.elements.emailBody.val("");
		
		j('#uploadedfiles').empty();
	}

	// load phone message asynchronously from the server
	this.loadPhoneMessage = function(msgGrpId, msg, isRecording) {
		if (isRecording) {
			self.loadMessagePartsAudioFile(msgGrpId, msg, self.elements.phoneRecording);
		} else {
			if (msg.languageCode == "en") {
				self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.phoneText.addClass('ok'));
			} else {
				self.elements.phoneTranslateCheck.attr("checked","checked");
				j(self.elements.phoneLanguageCheckPrefix + msg.languageCode).attr('checked','checked');
				j(self.elements.phoneLanguageCheckPrefix + msg.languageCode).parent().children(".controls").first().removeClass("hide");
				self.loadMessagePartsFormatted(msgGrpId, msg, j(self.elements.phoneTranslatePrefix + msg.languageCode));
				// overridden messages should have their text area editable and override checked.
				if (msg.autoTranslate == "overridden") {
					j(self.elements.phoneTranslatePrefix + msg.languageCode).removeAttr("disabled");
					j(self.elements.phoneOverridePrefix + msg.languageCode).attr('checked','checked');
				}
			}
		}
	}
	
	// load email message
	this.loadEmailMessage = function(msgGrpId, msg) {
		if (msg.languageCode == "en") {
			self.elements.emailFromName.val(decodeURIComponent(msg.fromName).replace(/\+/g," ")).addClass('ok');
			self.elements.emailFromEmail.val(decodeURIComponent(msg.fromEmail).replace(/\+/g," ")).addClass('ok');
			self.elements.emailSubject.val(decodeURIComponent(msg.subject).replace(/\+/g," ")).addClass('ok');
			self.loadMessageAttachments(msgGrpId, msg, self.elements.emailAttach, self.elements.emailAttachControls);
			self.loadMessagePartsFormatted(msgGrpId, msg, "ckeditor");
		} else {
			self.elements.emailTranslateCheck.attr("checked","checked");
			j(self.elements.emailLanguageCheckPrefix + msg.languageCode).attr('checked','checked');
			j(self.elements.emailLanguageCheckPrefix + msg.languageCode).parent().children(".controls").first().removeClass("hide");
			self.loadMessagePartsFormatted(msgGrpId, msg, j(self.elements.emailTranslatePrefix + msg.languageCode));
		}
	}
	
	// load sms message
	this.loadSmsMessage = function(msgGrpId, msg) {
		self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.smsText.addClass('ok'));
	}

	// load post message
	this.loadPostMessage = function(msgGrpId, msg) {
		switch (msg.subType) {
			case "facebook":
				self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.facebookText.addClass('ok'));
				break;
			case "twitter":
				self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.twitterText.addClass('ok'));
				break;
			case "feed":
				self.elements.feedSubject.val(decodeURIComponent(msg.subject).replace(/\+/g," ")).addClass('ok');
				self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.feedText.addClass('ok'));
				break;
		}
	}

	// get audiofile based on message id from message group
	this.loadMessagePartsAudioFile = function(msgGrpId,msg,element){
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msg.id+'/messageparts',
			async: false,
			type: "GET",
			dataType: "json",
			success: function(data) {
				parts = data.messageParts;
				var code = msg.languageCode;
				var afid = parts[0].audioFile.id;

				// Used to store lang code and id = {"en":"987","es":"988","ca":"989"}
				var elementval = element.val();
				if (elementval == "")
					elementdata = {};
				else
					elementdata = j.secureEvalJSON(elementval);
				
				elementdata[code] = afid;
				element.val(j.toJSON(elementdata));
				
				// now, reattach the easycall
				self.elements.phoneRecording.attachEasyCall({
					"languages": easycallLangs,
					"defaultphone": userInfo.phoneFormatted});
			}
		});
	};

	// get formatted message body based on message id from message group
	this.loadMessagePartsFormatted = function(msgGrpId,msg,element){
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msg.id+'/messageparts/formatted',
			async: true,
			type: "GET",
			dataType: "json",
			success: function(data) {
				if (element == "ckeditor") {
					if (getHtmlEditorObject())
						getHtmlEditorObject().instance.setData(data.messageBody);
					else
						self.elements.emailBody.val(data.messageBody);
				} else if (element.is("div")) {
					element.empty().append(data.messageBody);
				} else {
					element.val(data.messageBody);
				}
			}
		});
	};

	// get message attachments based on message group id and message id
	this.loadMessageAttachments = function(msgGrpId,msg,element,controlElement){
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msg.id+'/messageattachments',
			async: true,
			type: "GET",
			dataType: "json",
			success: function(data) {
				attachments = data.messageAttachments;
				var files = new Object();
				if ( attachments.length != 0 ) {
					controlElement.show();
					j.each(attachments, function(eIndex,eData) {
						var filesize = Math.round(eData.size/1024);
						var attach = '<a href="emailattachment.php?maid=' + eData.id + '&name=' + eData.filename + '">' + eData.filename + '</a>' +
							'&nbsp;(Size: ' + filesize + 'k)&nbsp;<a href="#">Remove</a><br>';
						controlElement.append(attach);
						files[eData.id] = {"name":eData.filename,"size":eData.size}
					});
				}
				element.val(Object.toJSON(files));
			}
		});
	};
	
	// tell the server to load this messagegroups content into session data so it can be previed
	this.loadMessageGroupContentForPreview = function(msgGrpId) {
		j.post("ajax.php", {
			"type": "loadmessagegroupcontent",
			"id": msgGrpId}
		);
	};
	
	// get the requested message group
	this.getMessageGroup = function(msgGrpId) {
		var messagegroup = false;
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId,
			async: false,
			type: "GET",
			dataType: "json",
			success: function(data) {
				messagegroup = data;
			}
		});
		return messagegroup;
	};
}