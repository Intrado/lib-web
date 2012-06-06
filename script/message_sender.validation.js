jQuery.noConflict();
(function($) { 
  $(function() {

    // Global Functions
    var notVal = new globalValidationFunctions();

    // Some Global variables

    orgPath     = window.location.pathname.split('/')[1]; // This gives us the the URL path needed for first part of AJAX call

    // Email Body Text
    emailData = "";


    $('.error').hide();

    document.formvars = {
      broadcast: {
        subject:    { }
      },
      phone: { },
      email: { },
      sms:   { },
      social:{ }
    };



    // Initial Call
    $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/roles',
        type: "GET",
        dataType: "json",
        success: function(data) {
          setUp(data.roles); // Send Data over to the function setUp();
       }
    });

    

    /* --
      The setUp function will do alot of the inital work, and call other functions based on users roles
    -- */



    function setUp(roleData) {
      if(!$.isArray(roleData)) {
        alert("error");
        return false;
      }


      userPermissions = {};
      userRoleId = false;
      $.each(roleData, function(rIndex, rItem) {
        //ideally match organization for rols here
        //if(rItem.organization.id == [orgVariable]) {
          userRoleId = rItem.id;
          $.each(rItem.accessProfile.permissions, function(pIndex, pItem) {
            userPermissions[pItem.name] = pItem.value;
          });
        //}
      });

      // console.log("userRoleID: " + userRoleId);
      // console.log("userPermissions: " + userPermissions);

      if(userRoleId == false) {
        alert("error: user doesn't have permissions for current organization");
        return false;
      }


      // Get Type for drop down on inital page
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/roles/'+userRoleId+'/settings/jobtypes',
        type: "GET",
        dataType: "json",
        success: function(data) {
          
          jobTypes = data.jobTypes;

          $.each(jobTypes, function(index, jobType) {  
            $('#msgsndr_form_type').append('<option value='+jobType.id+'>'+jobType.info+'</option');
          });

          step1();
        } // success
 
      });   

      /* -- 
        Depending on what options the users has will depend on what they can see
        Each message content Add part is seperated out into seperate functions
        * 1 = true - user has this option
      -- */

      if (userPermissions.sendphone == 1) {
        sendPhone();
      }

      if (userPermissions.sendemail == 1) {
        sendEmail();
      }

      if (userPermissions.sendsms == 1) {
        sendSMS();
      }

      if (userPermissions.facebookpost == 1 || userPermissions.twitterpost == 1 || userPermissions.feedpost == 1) {

        $('li.osocial').removeClass('notactive');

        if(userPermissions.facebookpost == 1) {
          socialFB();
        }

        if(userPermissions.twitterpost == 1) {
          socialTwitter();
        }

        if(userPermissions.feedpost == 1) {
          socialFeed();
        }

      }

    }; 

  

    function step1() {
      
      var formdata = document.formvars['broadcast'];

      document.formvars['broadcast']['subject'] = [
        new document.validators["ValRequired"]("broadcast_subject","Subject",{}), 
        new document.validators["ValLength"]("broadcast_subject","Subject",{min:7,max:30})
      ];

      notVal.watchFields('#msgsndr_form_subject');

    };

    function sendPhone() {

      $('li.ophone').removeClass('notactive');

      document.formvars['phone']['number'] = [
        new document.validators["ValRequired"]("content_phone","Number to Call",{}),
        new document.validators["ValPhone"]("content_phone","Number to Call",{})
      ];

      notVal.watchFields('#msgsndr_form_number');

      // Build up select box based on the maxjobdays user permission
      var daysToRun = userPermissions.maxjobdays;
      for(i=1;i<=daysToRun;i++) {
        $('#msgsndr_form_days').append('<option value="'+i+'">'+i+'</option>');
      };

      // Easy Call jQuery Plugin
      $("#msgsndr_form_number").attachEasyCall({"languages": {"en":"English","es":"Spanish","ca":"Catalan"}});   

    };

    function sendEmail() {

      $('li.oemail').removeClass('notactive');

      document.formvars['email'] = {
        name: [
          new document.validators["ValRequired"]("email_name","Name",{}), 
          new document.validators["ValLength"]("email_name","Name",{max:30})
        ],
        address: [
          new document.validators["ValRequired"]("email_address","Email Address",{}), 
          new document.validators["ValEmail"]("email_address","Email Address",{min:7,max:60})
        ],
        subject: [
          new document.validators["ValRequired"]("email_subject","Subject",{}), 
          new document.validators["ValLength"]("email_subject","Subject",{min:4})
        ],
        body: [
          new document.validators["ValRequired"]("email_body","Body",{}), 
          new document.validators["ValLength"]("email_body","Body",{min:4})
        ]
      }

      notVal.watchFields('#msgsndr_form_name, #msgsndr_form_email, #msgsndr_form_mailsubject, #msgsndr_form_body');

    };

    function sendSMS() {

      $('li.osms').removeClass('notactive');

      document.formvars['sms'] = {
        text: [
          new document.validators["ValRequired"]("sms_text","SMS",{}),
          new document.validators["ValLength"]("sms_text","SMS",{max:160}),
          new document.validators["ValSmsText"]("sms_text","sms_text")
        ]
      };

      notVal.watchFields('#msgsndr_form_sms');

      // Character Count
      $('#msgsndr_form_sms').on('keyup', function() {
        charCount(this, '160', '.sms.characters');
      });

    };

    function socialFB() {

      $('div[data-social=facebook]').removeClass('hidden');

      // Character Count
      $('#msgsndr_form_fbmsg').on('keyup', function() {
        charCount(this, '420', '.fb.characters');
      });

    };

    function socialTwitter() {

      $('div[data-social=twitter]').removeClass('hidden');

      var twitterCharCount = 140 - twitterReservedChars;

      $('.twit.characters').prepend(twitterCharCount);
      // Character Count
      $('#msgsndr_form_tmsg').on('keyup', function() {
        charCount(this, twitterCharCount, '.twit.characters');
      });

    };

    function socialFeed() {

      $('div[data-social=feed]').removeClass('hidden');

      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/roles/'+userRoleId+'/settings/feedcategories',
        type: "GET",
        dataType: "json",
        success: function(data) {
          
          feedCats = data.feedCategories;

          $.each(feedCats, function(index, feedCat) {  
            var name = feedCat.name.toLowerCase().replace(" ","_");
            $('#feed_categories').append('<div class="cf"><input type="checkbox" class="addme" name="" id="'+name+'" /><label class="addme" for="'+name+'">'+feedCat.name+'</label></div>');
          });

          step1();
        } // success
 
      });   


    };



    // Section 1 - Form Watch
    $('#msg_section_1').on('focusout', function() {
      notVal.watchSection('msg_section_1');
    });


    // Watch Email Form
    $('#msgsndr_tab_email .required').on('keyup', function() {
      notVal.watchContent('msgsndr_tab_email');
    });

    // Save Button for message content
    $('.btn_save').on('click', function(e) {
      e.preventDefault();

      notVal.saveBtn(this);
      notVal.checkContent();

    });




    // Social Input Buttons
    $('input.social').on('click', function() {

      var itemName = $(this).attr('id').split('_')[2];

       $('.'+itemName).slideToggle('slow', function() { 

         if (itemName == 'feed') { // if Post to Feeds set focus to the Post title input
           $('#msgsndr_form_rsstitle').focus();
         } else { // Set focus to the textarea
           $('.'+itemName+' textarea').focus();
         }
          
       });

       // This scrolls the page up to bring the element that has just opened into view
       offset = $(this).offset();
       $('html, body').animate({scrollTop: offset.top },2000);

      if ($(this).attr('checked')) {
        notVal.watchSocial(itemName);
      } else {
        notVal.unwatchSocial(itemName);
      }


    });








    // Send Message Button
    $('#send_new_broadcast').on('click', function(e) {
      e.preventDefault();

      //var formData = $('form[name=broadcast]').serializeArray();
      var formData = mapPostData();
/* // sample data from nickolas...
      var formData = {
        "name": "Test Notification Name",
        "jobtype" : "3",
        "addme": "true",
        "addmephone":  "4172140239",
        "addmeemail":  "nheckman@schoolmessenger.com",
        "addmesms":  "4172140239",
        "listids": "[41]",
        "hasphone":  "true",
        "phonemessagetype":  "callme",
        "phonemessagepost":  "true",
        "phonemessagecallme":  {"en":"346","es":"345"},
        "hasemail":  "true",
        "emailmessagefromname":  "Nickolas Heckman",
        "emailmessagefromemail": "nheckman@schoolmessenger.com",
        "emailmessagesubject": "Test Email Subject",
        "emailmessageattachment":  {"297":{"name":"188570.gif","size":"87307"}},
        "emailmessagetext":  '<p>   This is an <strong><em>html</em></strong> email message.</p> <p>  <img src="viewimage.php?id=296"></p> <p>  blah blah blah blah blah.</p> <p>   Thank you.</p>',
        "emailmessagetexttranslate": "true",
        "emailmessagetexttranslatecatext": {"enabled":true,"text":"\u8fd9\u662f\u4e00\u4e2a\u624b\u673a\u77ed\u4fe1\uff0c\u5c06\u4e0e\u6587\u672c\u5230\u8bed\u97f3\u5448\u73b0\u3002","override":false,"englishText":""},
        "emailmessagetexttranslatefitext": {"enabled":true,"text":"\u8fd9\u662f\u4e00\u4e2a\u624b\u673a\u77ed\u4fe1\uff0c\u5c06\u4e0e\u6587\u672c\u5230\u8bed\u97f3\u5448\u73b0\u3002","override":false,"englishText":""},
        "emailmessagetexttranslatefrtext": {"enabled":true,"text":"\u8fd9\u662f\u4e00\u4e2a\u624b\u673a\u77ed\u4fe1\uff0c\u5c06\u4e0e\u6587\u672c\u5230\u8bed\u97f3\u5448\u73b0\u3002","override":false,"englishText":""},
        "emailmessagetexttranslatedetext": {"enabled":true,"text":"\u8fd9\u662f\u4e00\u4e2a\u624b\u673a\u77ed\u4fe1\uff0c\u5c06\u4e0e\u6587\u672c\u5230\u8bed\u97f3\u5448\u73b0\u3002","override":false,"englishText":""},
        "emailmessagetexttranslateeltext": {"enabled":true,"text":"\u8fd9\u662f\u4e00\u4e2a\u624b\u673a\u77ed\u4fe1\uff0c\u5c06\u4e0e\u6587\u672c\u5230\u8bed\u97f3\u5448\u73b0\u3002","override":false,"englishText":""},
        "emailmessagetexttranslateestext": {"enabled":true,"text":"\u8fd9\u662f\u4e00\u4e2a\u624b\u673a\u77ed\u4fe1\uff0c\u5c06\u4e0e\u6587\u672c\u5230\u8bed\u97f3\u5448\u73b0\u3002","override":false,"englishText":""},
        "hassms":  "true",
        "smsmessagetext":  "This is the SMS message.",
        "hasfacebook": "true",
        "socialmediafacebookmessage":  "We have sent out a new message, you can preview it here. (Facebook)",
        "hastwitter":  "true",
        "socialmediatwittermessage": "We have sent out a new message, you can preview it here. (Twitter)",
        "hasfeed": "true",
        "socialmediafeedmessage": {"subject": "The feed title goes here", "message": "We have sent out a new message, you can preview it here. (RSS Feed)"},        
        "socialmediafacebookpage": ["119495234836474"],
        "socialmediafeedcategory":["7"],
        "optionmaxjobdays":  "1",
        "optionleavemessage":  "true",
        "optionmessageconfirmation": "true",
        "optionskipduplicate": "true",
        "optioncallerid":  "8316001090",
        "optionsavemessage": "true",
        "optionsavemessagename": "New Message Name",
        "scheduledate":  "06/05/2012",
        "schedulecallearly": "8:00 am",
        "schedulecalllate":  "9:00 pm",
        "submit" : "submit"
      };
*/
      $.ajax({
        type: 'POST',
        url: '_messagesender.php?form=msgsndr&ajax=true',
        data: formData,

        success: function(response) {
          console.log(response);
            var res = response;
            if (res.vres != true) {
              //console.log('false');
              console.log(res);
            } else {
              //console.log('true');
              console.log(res);
            }
        }
      });

    });





    // Postdata mapping
    /////////////////////

    function mapPostData(){
      //The store for our new POST data
      var sendData = {};

      //The mapping between our keys and the server keys
      var keyMap = {
        "broadcast_formsnum": "msgsndr-formsnum",
        "broadcast_subject":"msgsndr_name",
        "broadcast_type":"msgsndr_jobtype",
        //"":"msgsndr_addme",
        "me_phone":"msgsndr_addmephone",
        "me_email":"msgsndr_addmeemail",
        "me_sms":"msgsndr_addmesms",
        "broadcast_listids":"msgsndr_listids",
        "has_phone":"msgsndr_hasphone", // true/false
        "phone_type":"msgsndr_phonemessagetype", // callme or text
        //"":"msgsndr_phonemessagepost", // true/false -- CHECK IF THIS IS THE SAME AS 'ADD A LINK TO AUDIO MESSAGE' IN SOCIAL
        //"":"msgsndr_phonemessagecallme",
        "phone_text":"msgsndr_phonemessagetext",
        "phone_translate":"msgsndr_phonemessagetexttranslate", // true/false
        "phone_translation_es":"msgsndr_phonemessagetexttranslateestext",
        "has_email":"msgsndr_hasemail", // true/false
        "email_name":"msgsndr_emailmessagefromname",
        "email_address":"msgsndr_emailmessagefromemail",
        "email_subject":"msgsndr_emailmessagesubject",
        "email_attachment":"msgsndr_emailmessageattachment",
        //"":"msgsndr_emailmessagetext", // data from CK editor panel
        "email_translate":"msgsndr_emailmessagetexttranslate", // convert this from 1/0 to true/false
        "email_translation_es":"msgsndr_emailmessagetexttranslateestext", // translations...
        "has_sms":"msgsndr_hassms", // true/false
        "sms_text":"msgsndr_smsmessagetext",
        "has_facebook":"msgsndr_hasfacebook", // true/false
        "facebook_message":"msgsndr_socialmediafacebookmessage",
        "has_twitter":"msgsndr_hastwitter",
        "twitter_message":"msgsndr_socialmediatwittermessage",
        "has_feed":"msgsndr_hasfeed",
        "feed_message":"msgsndr_socialmediafeedmessage", // should be passed an object {"subject": "The feed title goes here", "message": "We have sent out a new message, you can preview it here. (RSS Feed)"}
        "feed_categories":"msgsndr_socialmediafeedcategory", // should be an array of feed category ids
        //"":"msgsndr_socialmediafacebookpage", // facebook id -- NEED TO GET THIS FROM ORGANZATIONS
        "broadcast_daystorun":"msgsndr_optionmaxjobdays",
        "options_voiceresponse":"msgsndr_optionleavemessage",
        "options_callconfirmation":"msgsndr_optionmessageconfirmation",
        "options_skipduplicates":"msgsndr_optionskipduplicate",
        "phone_callerid":"msgsndr_optioncallerid",
        "options_savemessage":"msgsndr_optionsavemessage",
        "options_savemessagename":"msgsndr_optionsavemessagename",
        "broadcast_scheduledate":"msgsndr_scheduledate",
        "broadcast_schedulecallearly":"msgsndr_schedulecallearly",
        "broadcast_schedulecalllate":"msgsndr_schedulecalllate"
      };

      //process all inputs into POST data
      $("input[name], textarea[name], select[name]").each(function() {
        var thisKey = $(this).attr("name");
        if(typeof(keyMap[thisKey]) != "undefined") {
          thisKey = keyMap[thisKey];
          if($(this).val() == 'on'){
            sendData[thisKey] = true;
          } else {
            sendData[thisKey] = $(this).val();
          }
        }
      });
      // add in the submit ...
      sendData['submit'] = 'submit';
      return(sendData);
    };

    


















    function emailVal(element,emailData) {

      var form  = element.split("_")[0];
      var field = element.split("_")[1];

      var value = emailData;

      var isValid = true;
      var validators = document.formvars[form][field];
      requiredvalues = [];


      // Loop validation
      for (var i = 0; i < validators.length; i++) {  
        var validator = validators[i];  
        if (value.length > 0 || validator.isrequired || validator.conditionalrequired || value.length == 0) {  
          res = validator.validate(validator.name,validator.label,value,validator.args,requiredvalues); 
          if (res != true) {  
            isValid = false;  

            $('#cke_msgsndr_form_body').removeClass('ok').addClass('er');
            $('#emailBodyError').text(res).show();

          } else {
            $('#cke_msgsndr_form_body').removeClass('er').addClass('ok');
            $('#emailBodyError').hide();
          }
            break; 
        } 

      } // for
        

    }; // form_val function



    function charCount(elem, limit, text) {
       
      var e = $(elem);
      var status = $(text);
      var remaining = limit - e.val().length;

      if (remaining < 0) {
        e.addClass('er');
        status.html('<span class="error">'+(0 - remaining) + " too many characters</span>");
      } else {
        status.text(remaining + " Characters left");
      }

    }


    

    // get caller id's ... WIP -- userRoleId not defined outside the setUp function
    /*
    function getCallerIds(){
      var callerIds = {};
       $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/roles/'+userRoleId+'/settings/callerids/',
        type: "GET",
        dataType: "json",
        success: function(data) {
          $.each(data.callerids, function(cIndex, cIds){
            callerIds[cIds.name] = cIds.value;
          });
        }
      });
      return callerIds;
    };  

    console.log(getCallerIds());
    */

// messages, message parts and message attachments...
//////////////////////////////////////////////////////

    // get message group data ...
    function getMessageGroups() {
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups',
        type: "GET",
        dataType: "json",
        success: function(data) {
          var msgGroups = data.messageGroups;

          $.each(msgGroups, function(index, msgGroup) {
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
            $.each(msgTypes, function(index, msgType) {
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

            $('#messages_list').append('<tr id="msgsndr_msggroup-'+msgGroup.id+'" class="msgsndr_msggroup"><td><input type="radio" name="msgsndr_msggroup" value="'+msgGroup.id+'"/>'+msgGroup.name+'</td><td>'+msgDate+'</td><td>'+msgPhone+'</td><td>'+msgEmail+'</td><td>'+msgSms+'</td><td>'+msgPost+'</td></tr>');
          });
        } 
      });   
    };


    // load the messages into the modal dialog.
    getMessageGroups();



    // get messages based on message id from message group
    function getMessages(msgGrpId){
      var messages = {};
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages',
        type: "GET",
        dataType: "json",
        success: function(data) {
          $.each(data.messages, function(mIndex, mData){
            messages[mData.name] = mData.value;
          });
        }
      });
      return messages;
    };

    
    // get message parts based on message id from message group
    function getMessageParts(msgGrpId){
      var parts = {};
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messageparts',
        type: "GET",
        dataType: "json",
        success: function(data) {
          $.each(data.messageparts, function(mIndex, mData){
            parts[mData.name] = mData.value;
          });
        }
      });
      return parts;
    };


    // get formatted message parts based on message id from message group
    function getMessagePartsFormatted(msgGrpId){
      var partsFormatted = false;
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messageparts/formatted',
        type: "GET",
        dataType: "json",
        success: function(data) {
          $.each(data.messageparts, function(mIndex, mData){
            partsFormatted[mData.name] = mData.value;
          });
        }
      });
      return partsFormatted;
    };


    // get message attachments based on message group id and message id
    function getMessageAttachment(msgGrpId, msgId){
      var attachments = {};
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/message/'+msgId+'/messageattachments',
        type: "GET",
        dataType: "json",
        success: function(data) {
          $.each(data.messageAttachments, function(mIndex, mData){
            attachments[mData.name] = mData.value;
          });
        }
      });
      return attachments;
    };



    // load the message ... (WIP: currently this only displays the messagegroup name on the page and closes the modal)
    $('#messages_list').on('click', 'tr', function(){
      $('td:first input:radio[name=msgsndr_msggroup]', this).attr('checked', 'checked');
    });

    $('#msgsndr_load_saved_msg').on('click', function(){
      var msgGroup = $('.msgsndr_msggroup > td > input:radio[name=msgsndr_msggroup]:checked'); //input:checkbox[name=msgsndr_msggroup]:checked'
      var grpId = msgGroup.attr('value');
      var msgName = msgGroup.parent().text();

      // put the messageGroup id in the hidden input and display the message name
      $('#loaded_message_id').attr('value', grpId);
      $('#loaded_message_name').text(msgName);
      $('#msgsndr_loaded_message').fadeIn(300);

      // make sure the correct tab is shown
      $('#msgsndr_saved_message').modal('hide');
      $('.msg_steps').find('li:eq(1)').addClass('active');
      $('#msg_section_2').show();  
    });
    



// CK Editor ...
/////////////////

    // ckeditor for the email message body ...
    chooseCkButtons = function(type){ // pass in 'basic' or 'advanced'

      var toolbarBasic = [
          ['Bold', 'Italic','NumberedList','BulletedList','Link']
        ];

      var toolbarAdvanced = [
          ['Print','Source'],
          ['Undo','Redo','-','PasteFromWord', 'SpellCheck'],
          '/',
          ['Styles','Format'],
          ['NumberedList','BulletedList','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Outdent','Indent'],
          '/',
          ['Font','FontSize','Bold', 'Italic', 'Underline','Strike','TextColor','BGColor', 'RemoveFormat'],
          ['Link', 'Image','Table','HorizontalRule']
        ];

      var choice = toolbarBasic;
        if ( typeof(type) != undefined ){
          if (type == 'basic'){
          choice = toolbarBasic;
          } else if (type == 'advanced'){
            choice = toolbarAdvanced;
          }
        } else {
          // default to basic toolbar ...
          choice = toolbarBasic;
        }
      return choice;
    };
    
    // set the toolbar choice variable before calling the ckeditor function
    toolbarChoice = chooseCkButtons('basic');

    CKEDITOR.replace('msgsndr_form_body', {
      'customConfig': '', // Prevent ckeditor from trying to load an external configuration file, should improve startup time.
      'disableNativeSpellChecker': false,
      'browserContextMenuOnCtrl': true,
      'extraPlugins': 'aspell', //enable aspell port
      'removePlugins': 'wsc,scayt,smiley,showblocks,flash,elementspath,save',
      'toolbar': toolbarChoice,
      'disableObjectResizing': true,
      'resize_enabled': false,
      'width': '100%',
      'filebrowserImageUploadUrl' : 'uploadimage.php'
    });

    

    CKEDITOR.on('instanceCreated', function(e) {  
      e.editor.on('contentDom', function() {
        $('#cke_msgsndr_form_body').addClass('required');
        e.editor.document.on('keyup', function(event) {

          emailData = e.editor.document.$.body.innerText;

          emailVal('email_body', emailData );
          notVal.watchContent('msgsndr_tab_email');

          if (emailData != "" ) {
            $('#paste_from_email').removeClass('hidden');
          } else {
            $('#paste_from_email').addClass('hidden');
          }
          
        });
      });
    }); 



  });
}) (jQuery);
