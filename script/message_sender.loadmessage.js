  function loadMessage() {
    // Instead of using j we use j
    j = jQuery;

    var self = this;
	this.msgGroups = [];
	this.elements = {
		"phoneComplete": j('li.ophone'),
		"phoneType": j('#msgsndr_phonetype'),
		"phoneButtonCallMe": j('button.audioleft'),
		"phoneButtonText": j('button.audioright'),
		"phoneCallMeSection": j('#callme'),
		"phoneTextSection": j('#text'),
		"phoneRecording": j("#msgsndr_form_number"),
		"phoneText": j('#msgsndr_tts_message'),
		"phoneTranslatePrefix": "#tts_translated_",
		
		"emailComplete": j('li.oemail'),
		"hasEmail": j('input[name=has_email'),
		"emailAttach": j('#uploadedfiles'),
		"emailSubject": j('#msgsndr_form_mailsubject'),
		"emailFromName": j('#msgsndr_form_name'),
		"emailFromEmail": j('#msgsndr_form_email'),
		"emailTranslatePrefix": "#email_translated_",

		"smsComplete": j('li.osms'),
		"hasSms": j('input[name=has_sms]'),
		"smsText": j('#msgsndr_form_sms'),

		"socialComplete": j('li.osocial'),
	}

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
      var grpId = msgGroup.attr('value');
      var msgName = msgGroup.parent().text();

      // put the messageGroup id in the hidden input and display the message name
      j('#loaded_message_id').attr('value', grpId);
      j('#loaded_message_name').text(msgName);
      j('#msgsndr_loaded_message').fadeIn(300);

      // make sure the correct tab is shown
      j('#msgsndr_saved_message').modal('hide');
      j('.msg_steps li:eq(1)').addClass('active');
      j('#msg_section_2').show();  


      self.clearForm(); 
      self.getMessages(grpId);

    });

    
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
            var fullDate = new Date(msgGroup.modifiedTimestamp*1000);
            var DD = fullDate.getDate();
            var MM = fullDate.getMonth();
            var YY = fullDate.getFullYear();
            var msgDate = MM+1 + '/' + DD + '/' + YY;
            
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
					notVal.watchSocial('msgsndr_tab_social');
					break;
			}
		});
	};

	// load messages from message group
	this.getMessages = function(msgGrpId) {
		// get the selected message group data
		var selectedMsgGroup = {};
		j.each(self.msgGroups, function(index, msgGroup) {
			if (msgGroup.id == msgGrpId)
				selectedMsgGroup = msgGroup;
		});
		
		self.prepareFormForLoad(selectedMsgGroup);
		
		// request all the messages for the selected message group
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+selectedMsgGroup.id+'/messages',
			type: "GET",
			dataType: "json",
			success: function(data) {
				// itterate all the messages and call methods to populate the data
				j.each(data.messages, function(mIndex, msg) {
					if(typeof(msg.type) != "undefined" && msg.type.length > 0) { 
						switch (msg.type) {
							case "phone":
								self.loadPhoneMessage(msgGrpId, msg, selectedMsgGroup.phoneIsAudioOnly);
								break;
							case "email":
								// ignore plain emails
								if (msg.subType == "plain") {
									// Nothing
								} else {
									self.loadEmailMessage(msgGrpId, msg);
								}
								break;
							case "sms":
								self.loadSmsMessage(msgGrpId, msg);
								break;
							case "post":
								self.loadPostMessage(msgGrpId, msg);
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
			j(aData).val('').removeClass('ok');
	
		});
	
		// Recheck all translations checkboxes 
		var translationsChecks = j('input.translations');
	
		j.each(translationsChecks, function(aIndex, aData) {
			j(aData).attr('checked','checked');
		});
	
		var reviewTabs = j('.msg_complete li');
		j.each(reviewTabs, function(tIndex, tData) {
			j(tData).removeClass('complete');
		});
	
		// Phone 
		j("#msgsndr_form_number").empty();
	
		// Email Message Content resets
		CKEDITOR.instances.reusableckeditor.setData('');
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
				self.loadMessagePartsFormatted(msgGrpId, msg, j(self.elements.phoneTranslatePrefix + msg.languageCode));
			}
		}
	}
	
	// load email message
	this.loadEmailMessage = function(msgGrpId, msg) {
		if (msg.languageCode == "en") {
			self.elements.emailFromName.val(decodeURIComponent(msg.fromName).replace(/\+/g," ")).addClass('ok');
			self.elements.emailFromEmail.val(decodeURIComponent(msg.fromEmail).replace(/\+/g," ")).addClass('ok');
			self.elements.emailSubject.val(decodeURIComponent(msg.subject).replace(/\+/g," ")).addClass('ok');
			self.loadMessageAttachments(msgGrpId, msg, self.elements.emailAttach);
			self.loadMessagePartsFormatted(msgGrpId, msg, "ckeditor");
		} else {
			self.loadMessagePartsFormatted(msgGrpId, msg, j(self.elements.emailTranslatePrefix + msg.languageCode));
		}
	}
	
	// load sms message
	this.loadSmsMessage = function(msgGrpId, msg) {
		self.loadMessagePartsFormatted(msgGrpId, msg, self.elements.smsText.addClass('ok'));
	}

	// load post message
	this.loadPostMessage = function(msgGrpId, msg) {
		// TODO: not implemented yet
	}
	
    // This will contain the logic to populate message content from a loaded message
    this.doLoadMessageBroken = function() {

      // Social 

      if (typeof(messages.post) != "undefined") {
        j('li.osocial').addClass('complete');


        if (typeof(messages.post.facebook)  != "undefined") {
          j('#msgsndr_form_facebook').attr('checked','checked');
          j('div.facebook').show();

          j('#msgsndr_form_fbmsg').val(messages.post.facebook.msgParts[0].txt).addClass('ok');
        }

        if (typeof(messages.post.twitter) != "undefined") {
          j('#msgsndr_form_twitter').attr('checked','checked');
          j('div.twitter').show();

          j('#msgsndr_form_tmsg').val(messages.post.twitter.msgParts[0].txt).addClass('ok');
        }

        if (typeof(messages.post.feed) != "undefined") {
          j('#msgsndr_form_feed').attr('checked','checked');
          j('div.feed').show();

         	var postTitle = unescape(messages.post.feed.subject);
         	var postTitle = postTitle.replace(/\+/g," ");

          j('#msgsndr_form_rsstitle').val(postTitle).addClass('ok');
          j('#msgsndr_form_rssmsg').val(messages.post.feed.msgParts[0].txt).addClass('ok');
        }

        notVal.watchSocial('msgsndr_tab_social');

      }

      notVal.checkContent();

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
				var ctrIds = j.extend({code: afid},j.fromJSON(self.elements.phoneRecording));
				self.elements.phoneRecording.val(j.toJSON(ctrIds));
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
					CKEDITOR.instances.reusableckeditor.setData(unescape(data.messageBody));
				} else if (element.is("div")) {
					element.empty().append(data.messageBody);
				} else {
					element.val(data.messageBody);
				}
			}
		});
	};

	// get message attachments based on message group id and message id
	this.loadMessageAttachments = function(msgGrpId,msg,element){
		j.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msg.id+'/messageattachments',
			async: true,
			type: "GET",
			dataType: "json",
			success: function(data) {
				attachments = data.messageAttachments;
				if ( attachments.length != 0 ) {
					element.show();
					j.each(attachments, function(eIndex,eData) {
						var filesize = Math.round(eData.size/1024);
						var attach = '<a href="emailattachment.php?id=' + eData.id + '&name=' + eData.filename + '">' + eData.filename + '</a> ' +
							'(Size: ' + filesize + 'k) <a href="#">Remove</a>';
						element.append(attach);
					});
				}
			}
		});
	};
}