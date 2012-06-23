(function ($) {
$.loadMessage = function loadMessage() {
	var self = this;
	this.msgGroups = false;
	this.elements = {
		"messageTab": $('.msg_steps li:eq(1)'),
		"messageSection": $('#msg_section_2'),
		"messageGroupTable": $('#messages_list'),
		
		"phoneComplete": $('li.ophone'),
		"phoneType": $('#msgsndr_phonetype'),
		"phoneButtonCallMe": $('button.audioleft'),
		"phoneButtonText": $('button.audioright'),
		"phoneCallMeSection": $('#callme'),
		"phoneTextSection": $('#text'),
		"phoneRecording": $("#msgsndr_form_number"),
		"phoneText": $('#msgsndr_tts_message'),
		"phoneTranslatePrefix": "#tts_translated_",
		"phoneOverridePrefix": "#tts_override_",
		"phoneTranslateCheck": $("#msgsndr_form_phonetranslate"),
		"phoneLanguageCheckPrefix": "#tts_",
		"phoneAdvancedOptions": $(".phone_advanced_options"),
		"phoneCallMeOptions": $("#callme_advanced_options"),
		"phoneTextOptions": $("#text_advanced_options"),
		
		"emailComplete": $('li.oemail'),
		"hasEmail": $('input[name=has_email'),
		"emailBody": $("#msgsndr_form_body"),
		"emailAttach" : $('#msgsndr_form_attachment'),
		"emailAttachControls": $('#uploadedfiles'),
		"emailSubject": $('#msgsndr_form_mailsubject'),
		"emailFromName": $('#msgsndr_form_name'),
		"emailFromEmail": $('#msgsndr_form_email'),
		"emailTranslatePrefix": "#email_translated_",
		"emailTranslateCheck": $("#msgsndr_form_emailtranslate"),
		"emailLanguageCheckPrefix": "#email_",

		"smsComplete": $('li.osms'),
		"hasSms": $('input[name=has_sms]'),
		"smsText": $('#msgsndr_form_sms'),

		"socialComplete": $('li.osocial'),
		
		"hasFacebook": $('#msgsndr_form_facebook'),
		"facebookSection": $('div.facebook'),
		"facebookText": $('#msgsndr_form_fbmsg'),
		
		"hasTwitter": $('#msgsndr_form_twitter'),
		"twitterSection": $('div.twitter'),
		"twitterText": $('#msgsndr_form_tmsg'),
		
		"hasFeed": $('#msgsndr_form_feed'),
		"feedSection": $('div.feed'),
		"feedSubject": $('#msgsndr_form_rsstitle'),
		"feedText": $('#msgsndr_form_rssmsg')
	};
	
	this.init = function() {
		// load a saved message is clicked, get the messages
		$('#load_saved_message').on("click", function(){
			self.getMessageGroups();
		});

		// This makes the whole table row clickable to select the message ready for loading into content
		$('#messages_list').on('click', 'tr', function(){
			// Unset all inputs
			$('input[name=msgsndr_msggroup]').removeAttr('checked');
			// Remove selected class from all tr's
			$('#messages_list tr').removeClass('selected');
		
			// Set radio button to checked for selected message
			$('td:first input:radio[name=msgsndr_msggroup]', this).attr('checked', 'checked');
			// Add class selected to tr
			$(this).addClass('selected');
			
			if($('#msgsndr_load_saved_msg').is(':disabled')){                
				$('#msgsndr_load_saved_msg').removeAttr('disabled');           
			}
		});

		// Load Saved Message button in saved message modal window
		$('#msgsndr_load_saved_msg').on('click', function(){
			//var msgGroup = $('.msgsndr_msggroup > td > input:radio[name=msgsndr_msggroup]:checked');
			var msgGroup = $(".msgsndr_msggroup.selected");
			if(msgGroup.size() > 0) {
				var getId = msgGroup.attr("id").match(/msgsndr_msggroup-([0-9]*)/i)[1];
				
				self.loadMessageGroup(getId);
				
				$('#msgsndr_load_saved_msg').attr('disabled', 'disabled');
			}
		});
	};
	
	// load a specific message group
	this.loadMessageGroup = function(msgGrpId) {
		// get the selected message group data
		var selectedMsgGroup = false;
		if (self.msgGroups) {
			$.each(self.msgGroups, function(index, mg) {
				if (mg.id == msgGrpId) {
					selectedMsgGroup = mg;
					return false;
				}
			});
		}
		// not found? look it up via API request
		if (!selectedMsgGroup)
			selectedMsgGroup = self.getMessageGroup(msgGrpId);

		// still no message group? 
		if (selectedMsgGroup == 'undefined' || !selectedMsgGroup) {
			return; // TODO: error message?
		}
		
		// put the messageGroup id in the hidden input and display the message name
		$('#loaded_message_id').attr('value', selectedMsgGroup.id);
		$('#loaded_message_name').text(selectedMsgGroup.name);
		$('#msgsndr_loaded_message').fadeIn(300);
	
		// make sure the correct tab is shown
		$('#msgsndr_saved_message').modal('hide');
		if (self.elements.messageTab.hasClass("active"))
			self.elements.messageSection.show();
		
		self.elements.messageTab.addClass('complete');

		self.clearForm();
		self.prepareFormForLoad(selectedMsgGroup);
		self.getMessages(selectedMsgGroup);
	};
	
	// get message group data ...
	this.getMessageGroups = function(start) {
		// if no start set, this must be a new request to get message groups
		if (!start) {
			// if we have already loaded them, return
			if (self.msgGroups)
				return;
			else
				start = 0;
		}
		var limit = 25;
		$.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups',
			type: "GET",
			data: {"start": start, "limit": limit},
			dataType: "json",
			success: function(data) {
				if (!self.msgGroups)
					self.msgGroups = [];
				// sort by name
				$.each(data.messageGroups, function(i,mg) {
					// only show notification type message groups
					if (mg.type != "notification")
						return;
					self.msgGroups.push(mg);
					// format the date from the modifiedTimestamp value
					var msgDate = moment(mg.modifiedTimestamp*1000).format('MM/DD/YYYY');
					// loop through the typeSummary array to see what message parts are included
					var msgPhone = msgEmail = msgSms =  msgPost = '';
					var tickHtml = '<span class="icon">x</span>';
					$.each(mg.typeSummary, function(index, msgType) {
						switch (msgType.type) {
						case 'phone':
							msgPhone = tickHtml;
							break;
						case 'email':
							msgEmail = tickHtml;
							break;
						case 'sms':
							msgSms = tickHtml;
							break;
						case 'post':
							msgPost = tickHtml;
						}
					});
					var mgtablerecord = 
						$('<tr id="msgsndr_msggroup-'+mg.id+'" class="msgsndr_msggroup">'+
							'<td>'+
								'<input type="radio" name="msgsndr_msggroup" value="'+mg.id+'"/>'+
							'</td>'+
							'<td class="created">'+
								msgDate+
							'</td>'+
							'<td class="ico">'+
								msgPhone+
							'</td>'+
							'<td class="ico">'+
								msgEmail+
							'</td>'+
							'<td class="ico">'+
								msgSms+
							'</td>'+
							'<td class="ico">'+
								msgPost+
							'</td>'+
						'</tr>');
					// escape html in message group names
					mgtablerecord.children("td").first().append($("<span>", {"class":"msgsndr_msggroup_name"}).text(mg.name));
					var isInserted = false;
					var tablerecords = self.elements.messageGroupTable.children("tr");
					tablerecords.each(function(index) {
						var name = mg.name.toLowerCase();
						var td = $(this).find(".msgsndr_msggroup_name").first();
						var sortedname = td.text().toLowerCase();
						if (name < sortedname) {
							$(this).before(mgtablerecord);
							isInserted = true;
							return false;
						}
					});
					if (!isInserted)
						self.elements.messageGroupTable.append(mgtablerecord);
				});
				// if there are more pages
				if (data.paging.total > start + limit)
					self.getMessageGroups(start + limit);
			}
		});
	};

	this.prepareFormForLoad = function(msgGroup) {
		// based on message group info, set appropriate form status
		$.each(msgGroup.typeSummary, function(index, msgType) {
			switch (msgType.type) {
				case 'phone':
					self.elements.phoneComplete.addClass('complete');
					if (msgGroup.phoneIsAudioOnly) {
						self.elements.phoneType.val('callme');
						self.elements.phoneButtonCallMe.addClass('active'); 
						self.elements.phoneButtonText.removeClass('active');
						self.elements.phoneCallMeSection.show();
						self.elements.phoneTextSection.hide();
						self.elements.phoneCallMeOptions.append(self.elements.phoneAdvancedOptions);
						//global.watchContent('callme');
						obj_stepManager.updateStepStatus();
					} else {
						self.elements.phoneType.val('text');
						self.elements.phoneButtonCallMe.removeClass('active'); 
						self.elements.phoneButtonText.addClass('active');
						self.elements.phoneCallMeSection.hide();
						self.elements.phoneTextSection.show();
						self.elements.phoneTextOptions.append(self.elements.phoneAdvancedOptions);
						//global.watchContent('text');
						obj_stepManager.updateStepStatus();
					}
					break;
				case "email":
					// ignore plain emails
					if (msgType.subType == "plain") {
						// Nothing
					} else {
						self.elements.emailComplete.addClass('complete');
						self.elements.hasEmail.attr('checked','checked');
						//global.watchContent('msgsndr_tab_email');
						obj_stepManager.updateStepStatus();
					}
					break;
				case 'sms':
					self.elements.smsComplete.addClass('complete');
					self.elements.hasSms.attr('checked','checked');
					//global.watchContent('msgsndr_tab_sms');
					obj_stepManager.updateStepStatus();
					break;
				case 'post':
					self.elements.socialComplete.addClass('complete');
					switch (msgType.subType) {
						case "facebook":
							self.elements.hasFacebook.attr('checked','checked');
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
					//global.watchSocial('msgsndr_tab_social');
					obj_stepManager.updateStepStatus();
					break;
			}
		});
	};

	// check that user is authorized for the specified message (type/language)
	this.checkUserAuth = function (msg) {
		if (!userPermissions.sendmulti && msg.languageCode != "en")
			return false;
		switch (msg.type) {
			case "phone":
			case "email":
			case "sms":
				if (!userPermissions["send"+msg.type])
					return false;
				break;
			case "post":
				if (!userPermissions[msg.subType+"post"])
					return false;
				break;
			default:
				return false;
		}
		return true;
	};
	
	// load messages from message group
	this.getMessages = function(msgGrp) {
		// load message group content into session server side.
		self.loadMessageGroupContentForPreview(msgGrp.id);
		// request all the messages for the selected message group
		$.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrp.id+'/messages',
			type: "GET",
			dataType: "json",
			success: function(data) {
				// itterate all the messages and call methods to populate the data
				$.each(data.messages, function(mIndex, msg) {
					if (!self.checkUserAuth(msg))
						return true;
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
		$('.msg_content_nav li').removeClass('complete active lighten');
		$('.tab_content .tab_panel').hide();
		$('.facebook, .twitter, .feed').hide();			
	
		var allDataInputs = $('#msg_section_2 input, textarea');
	
		$.each(allDataInputs, function(aIndex, aData) {
	
			if ($(aData).attr('type') == 'checkbox') {
				$(aData).removeAttr('checked');
			}
			// don't blank out easycall phone number field
			if (!$(aData).hasClass("easycallphoneinput"))
				$(aData).val('').removeClass('ok');
	
		});
	
		// Uncheck all translation check boxes and hide their controls
		$.each($('input.translations'), function(aIndex, aData) {
			$(aData).removeAttr('checked');
			$(aData).parent().children(".controls").addClass("hide").children("textarea").attr("disabled","disabled");
		});
	
		var reviewTabs = $('.msg_complete li');
		$.each(reviewTabs, function(tIndex, tData) {
			$(tData).removeClass('complete');
		});
	
		// Phone 
		$("#msgsndr_form_number").empty();
	
		// Email Message Content resets
		clearHtmlEditorContent();
		self.elements.emailBody.val("");
		
		$('#uploadedfiles').empty();
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
				$(self.elements.phoneLanguageCheckPrefix + msg.languageCode).attr('checked','checked');
				$(self.elements.phoneLanguageCheckPrefix + msg.languageCode).parent().children(".controls").first().removeClass("hide");
				self.loadMessagePartsFormatted(msgGrpId, msg, $(self.elements.phoneTranslatePrefix + msg.languageCode));
				// overridden messages should have their text area editable and override checked.
				if (msg.autoTranslate == "overridden") {
					$(self.elements.phoneTranslatePrefix + msg.languageCode).removeAttr("disabled");
					$(self.elements.phoneOverridePrefix + msg.languageCode).attr('checked','checked');
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
			self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.emailBody, true);
		} else {
			self.elements.emailTranslateCheck.attr("checked","checked");
			$(self.elements.emailLanguageCheckPrefix + msg.languageCode).attr('checked','checked');
			$(self.elements.emailLanguageCheckPrefix + msg.languageCode).parent().children(".controls").first().removeClass("hide");
			self.loadMessagePartsFormatted(msgGrpId, msg, $(self.elements.emailTranslatePrefix + msg.languageCode));
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
		$.ajax({
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
					elementdata = $.secureEvalJSON(elementval);
				
				elementdata[code] = afid;
				element.val($.toJSON(elementdata));
				
				// now, reattach the easycall
				self.elements.phoneRecording.attachEasyCall({
					"languages": easycallLangs,
					"defaultphone": userInfo.phoneFormatted});
			}
		});
	};

	// get formatted message body based on message id from message group
	this.loadMessagePartsFormatted = function(msgGrpId,msg,element,ckeditor){
		// first hide the element and show a loading message
		var loadingMessage = "<div class='loadingmessage'><img src='img/ajax-loader.gif'/>&nbsp;Loading content, please wait...</div>";
		if (ckeditor)
			hideHtmlEditor();
		
		element.hide();
		element.after(loadingMessage);
		
		$.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msg.id+'/messageparts/formatted',
			async: true,
			type: "GET",
			dataType: "json",
			success: function(data) {
				element.parent().children(".loadingmessage").remove();
				element.show();
				if (element.is("div"))
					element.empty().append(data.messageBody);
				else
					element.val(data.messageBody);
				
				if (ckeditor) {
					var textarea = element.attr("id");
					applyHtmlEditor(textarea, true);
				}
			}
		});
	};

	// get message attachments based on message group id and message id
	this.loadMessageAttachments = function(msgGrpId,msg,element,controlElement){
		$.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msg.id+'/messageattachments',
			async: true,
			type: "GET",
			dataType: "json",
			success: function(data) {
				attachments = data.messageAttachments;
				var files = new Object();
				if ( attachments.length != 0 ) {
					controlElement.show();
					$.each(attachments, function(eIndex,eData) {
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
		$.post("ajax.php", {
			"type": "loadmessagegroupcontent",
			"id": msgGrpId}
		);
	};
	
	// get the requested message group
	this.getMessageGroup = function(msgGrpId) {
		var messagegroup = false;
		$.ajax({
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
})(jQuery);