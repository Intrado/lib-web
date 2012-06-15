  function loadMessage() {


    // Instead of using j we use j
    j = jQuery;

    var self = this;

    // this.watchSection = function(section) {

	   // get message group data ...
    this.getMessageGroups = function() {
      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups',
        type: "GET",
        dataType: "json",
        success: function(data) {
          var msgGroups = data.messageGroups;

          j.each(msgGroups, function(index, msgGroup) {
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


    // load the messages into the modal dialog.
    //getMessageGroups();



    // get messages based on message id from message group
    this.getMessages = function(msgGrpId, callback, audioOnly) {

      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages',
        type: "GET",
        dataType: "json",
        success: function(data) {

          allMessages = data.messages;
          messages = { 'phoneAudio' : audioOnly};

          j.each(allMessages, function(mIndex, mData) {

            var msgParts = self.getMessageParts(msgGrpId,mData.id);
            
            mData['msgParts'] = msgParts;

          });

          j.each(allMessages, function(mIndex, mData) {

            var msgParts = self.getMessagePartsFormatted(msgGrpId,mData.id);
            mData['msgFormatted'] = msgParts;

          });

          j.each(allMessages, function(mIndex, mData) {

            var msgParts = self.getMessageAttachments(msgGrpId,mData.id);
            mData['msgAttachments'] = msgParts;

          });      


          j.each(allMessages, function(mIndex, mData) {
            if(typeof(mData.type) != "undefined" && mData.type.length > 0) { 


              if(mData.type == "post") {
                if(typeof(messages[mData.type]) == "undefined") {
                  messages[mData.type] = {};
                }
                if(typeof(messages[mData.type][mData.subType]) == "undefined") {
                  messages[mData.type][mData.subType] = mData;
                }
              } else if (mData.type == "email") {
              	if(typeof(messages[mData.type]) == "undefined") {
              		messages[mData.type] = {};
              	}
              	if(typeof(messages[mData.type][mData.languageCode]) == "undefined") {
              		messages[mData.type][mData.languageCode] = mData;
              	}
              } else if(typeof(messages[mData.type]) == "undefined" && (mData.type != "email" || mData.subType != "plain")) {
                messages[mData.type] = mData;
              }

            }
          });

          // console.log(messages);

          callback();
        }
      });

      //return messages;
    };

    
    // get message parts based on message id from message group
    this.getMessageParts = function(msgGrpId,msgId){

      var parts = {};

      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msgId+'/messageparts',
        async: false,
        type: "GET",
        dataType: "json",
        success: function(data) {
          
          parts = data.messageParts;

        }
      });

      return parts;

    };


    // get formatted message parts based on message id from message group
    this.getMessagePartsFormatted = function(msgGrpId,msgId){

      var parts = {};

      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msgId+'/messageparts/formatted',
        async: false,
        type: "GET",
        dataType: "json",
        success: function(data) {

          // console.log(data.messageBody);
          parts = data.messageBody;

          // j.each(data.messageparts, function(mIndex, mData){
          //   partsFormatted[mData.name] = mData.value;
          // });
        }
      });

      return parts;
    };


    // get message attachments based on message group id and message id
    this.getMessageAttachments = function(msgGrpId, msgId){
      var attachments = {};
      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msgId+'/messageattachments',
        async: false,
        type: "GET",
        dataType: "json",
        success: function(data) {

        	attachments = data.messageAttachments;
          // j.each(data.messageAttachments, function(mIndex, mData){
          //   attachments[mData.name] = mData.value;
          // });
        }
      });

      return attachments;
    };



    // load the message ...

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
      var audioOnly = msgGroup.attr('data-audio');
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
      self.getMessages(grpId, self.doLoadMessage, audioOnly);

    });
    

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


    // This will contain the logic to populate message content from a loaded message
    this.doLoadMessage = function() {

    	// Phone
    	if (typeof(messages.phone) != "undefined") {
	  		if(messages.phoneAudio == 'true') { // True is Call Me To Record

	  			j('li.ophone').addClass('complete');

	  			j('#msgsndr_phonetype').val('callme');
	  			j('button.audioleft').addClass('active'); 
	  			j('button.audioright').removeClass('active');
	  			j('#callme').show();
	  			j('#text').hide();

	  			notVal.watchContent('callme');

	  			ctrIds = {};   // Used to store lang code and id = {"en":"987","es":"988","ca":"989"}
	  			var ctrLangs = {}; // Used to store lang code and lang name = {"en":"Default","es":"Spanish"},"defaultphone":"4172140239"}

					j(messages.phone).each(function(k, v) {
				    if(typeof(v.languageCode) != "undefined" && j.isArray(v.msgParts) && v.msgParts.length > 0) {
				    	ctrIds[v.languageCode] = v.msgParts[0].id;
				    }
					});

					ctrLang = {};
					j(languages).each(function(k, v) {
						ctrLang[v.code] = v.name;
					});

					j("#msgsndr_form_number").val(j.toJSON(ctrIds));
					// now, reattach the easycall
					j("#msgsndr_form_number").attachEasyCall({
						"languages": ctrLang,
						"defaultphone": notVal.formatPhone(orgOptions.callerid)});

	  		} else {

	  			j('li.ophone').addClass('complete');
	  			
	  			j('#msgsndr_phonetype').val('text');
	  			j('button.audioleft').removeClass('active'); 
	  			j('button.audioright').addClass('active');
	  			j('#callme').hide();
	  			j('#text').show();

	  			j('#msgsndr_tts_message').val(messages.phone.msgFormatted).addClass('ok');

	  			notVal.watchContent('text');

	  		}
	  	}


    	// Email 

      if (typeof(messages.email) != "undefined") {
        j('li.oemail').addClass('complete');

        emailSubject = messages.email.subject.replace("+"," ");

        j('input[name=has_email').attr('checked','checked');
        j('#msgsndr_form_name').val(messages.email.fromName.replace(/\+/g," ")).addClass('ok');
        j('#msgsndr_form_email').val(messages.email.fromEmail.replace("%40","@")).addClass('ok');
        j('#msgsndr_form_mailsubject').val(emailSubject.replace(/\+/g," ")).addClass('ok');

        // Email Attachements 
        var eAttachments = messages.email.msgAttachments;
        	
        	if ( eAttachments.length != 0 ) {
        		j('#uploadedfiles').show();
        		
        		j.each(eAttachments, function(eIndex,eData) {

        			var filesize = Math.round(eData.size/1024);

        			// https://sandbox/jwhigh/emailattachment.php?id=9&name=staff.csv

        			var attach = '<a href="emailattachment.php?id='+eData.id+'&name='+eData.filename+'">'+eData.filename+'</a> ';
        			attach 		+= '(Size: '+filesize+'k) ';
        			attach 		+= '<a href="#">Remove</a>';

							j('#uploadedfiles').append(attach);
        		});
        			
        	
        	}

        CKEDITOR.instances.reusableckeditor.setData(unescape(messages.email.msgFormatted));

        notVal.watchContent('msgsndr_tab_email');

      }


      // SMS

      if (typeof(messages.sms) != "undefined") {
        j('li.osms').addClass('complete');

        j('input[name=has_sms]').attr('checked','checked');
        j('#msgsndr_form_sms').val(messages.sms.msgParts[0].txt).addClass('ok');

        notVal.watchContent('msgsndr_tab_sms');
      }


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



  }