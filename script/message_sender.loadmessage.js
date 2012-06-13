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

            j('#messages_list').append('<tr id="msgsndr_msggroup-'+msgGroup.id+'" class="msgsndr_msggroup"><td><input type="radio" name="msgsndr_msggroup" value="'+msgGroup.id+'"/>'+msgGroup.name+'</td><td class="created">'+msgDate+'</td><td class="ico">'+msgPhone+'</td><td class="ico">'+msgEmail+'</td><td class="ico">'+msgSms+'</td><td class="ico">'+msgPost+'</td></tr>');
          });
        } 
      });   
    };


    // load the messages into the modal dialog.
    //getMessageGroups();



    // get messages based on message id from message group
    this.getMessages = function(msgGrpId, callback){

      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages',
        type: "GET",
        dataType: "json",
        success: function(data) {

          allMessages = data.messages;
          messages = {};

          j.each(allMessages, function(mIndex, mData) {

            var msgParts = self.getMessageParts(msgGrpId,mData.id);
            
            mData['msgparts'] = msgParts;

          });

          j.each(allMessages, function(mIndex, mData) {

            var msgParts = self.getMessagePartsFormatted(msgGrpId,mData.id);
            mData['msgFormatted'] = msgParts;

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
    this.getMessageAttachment = function(msgGrpId, msgId){
      var attachments = {};
      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/message/'+msgId+'/messageattachments',
        type: "GET",
        dataType: "json",
        success: function(data) {
          j.each(data.messageAttachments, function(mIndex, mData){
            attachments[mData.name] = mData.value;
          });
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
      self.getMessages(grpId, self.doLoadMessage);

    });
    

    /*
      function used to empty all form data in section 2 - Message content, this is used when loading a
      previous message, as can not assume a user will only load one
    */


    this.clearForm = function() {

      j('.msg_content_nav li').removeClass('complete');


      var allDataInputs = j('#msg_section_2 input, textarea');

      CKEDITOR.instances.reusableckeditor.setData('');

      j('.facebook, .twitter, .feed').hide();

      j.each(allDataInputs, function(aIndex, aData) {

        if (j(aData).attr('type') == 'checkbox') {
          j(aData).removeAttr('checked');
        }
        j(aData).val('');

      });

      var reviewTabs = j('.msg_complete li');
      j.each(reviewTabs, function(tIndex, tData) {
        j(tData).removeClass('complete');
      });

    }


    // This will contain the logic to populate message content from a loaded message
    this.doLoadMessage = function() {

      if (typeof(messages.sms) != "undefined") {
        j('li.osms').addClass('complete');

        j('input[name=has_sms]').val('1');
        j('#msgsndr_form_sms').val(messages.sms.msgparts[0].txt);
      }

      if (typeof(messages.email) != "undefined") {
        j('li.oemail').addClass('complete');

        emailSubject = messages.email.subject;

        j('input[name=has_email').val('1');
        j('#msgsndr_form_name').val(messages.email.fromName.replace("+"," "));
        j('#msgsndr_form_email').val(messages.email.fromEmail.replace("%40","@"));
        j('#msgsndr_form_mailsubject').val(emailSubject);

        CKEDITOR.instances.reusableckeditor.setData(unescape(messages.email.msgFormatted));
        // CKEDITOR.instances.reusableckeditor.document.j.body.innerText;

        // j('iframe[name^=Ric]').contents().find('body').append(unescape(messages.email.msgFormatted));
        // Message HTML content == messages.email.msgparts[0].txt
      }

      if (typeof(messages.post) != "undefined") {
        j('li.osocial').addClass('complete');


        if (typeof(messages.post.facebook)  != "undefined") {
          j('#msgsndr_form_facebook').attr('checked','checked');
          j('div.facebook').show();

          j('#msgsndr_form_fbmsg').val(messages.post.facebook.msgparts[0].txt);
        }

        if (typeof(messages.post.twitter) != "undefined") {
          j('#msgsndr_form_twitter').attr('checked','checked');
          j('div.twitter').show();

          j('#msgsndr_form_tmsg').val(messages.post.twitter.msgparts[0].txt);
        }

        if (typeof(messages.post.feed) != "undefined") {
          j('#msgsndr_form_feed').attr('checked','checked');
          j('div.feed').show();

          j('#msgsndr_form_rsstitle').val(messages.post.feed.subject.replace("+"," "));
          j('#msgsndr_form_rssmsg').val(messages.post.feed.msgparts[0].txt);
        }

      }

      notVal.checkContent();

    }



  }