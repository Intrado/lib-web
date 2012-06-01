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

    // Inital Call
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
        alert("error: user doesnt have permissions for current organization");
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

    }


    function step1() {
      
      var formdata = document.formvars['broadcast'];

      document.formvars['broadcast']['subject'] = [
        new document.validators["ValRequired"]("broadcast_subject","Subject",{}), 
        new document.validators["ValLength"]("broadcast_subject","Subject",{min:7,max:30})
      ];

      watchFields('#msgsndr_form_subject');

    }

    function sendPhone() {

      $('li.ophone').removeClass('notactive');

      document.formvars['phone']['number'] = [
        new document.validators["ValRequired"]("content_phone","Number to Call",{}),
        new document.validators["ValPhone"]("content_phone","Number to Call",{})
      ];

      watchFields('#msgsndr_form_number');

      // Easy Call jQuery Plugin
      $("#msgsndr_form_number").attachEasyCall({"languages": {"en":"English","es":"Spanish","ca":"Catalan"}});

    }

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

      watchFields('#msgsndr_form_name, #msgsndr_form_email, #msgsndr_form_mailsubject, #msgsndr_form_body');

    }

    function sendSMS() {

      $('li.osms').removeClass('notactive');

      document.formvars['sms'] = {
        text: [
          new document.validators["ValRequired"]("sms_text","SMS",{}),
          new document.validators["ValLength"]("sms_text","SMS",{max:160}),
          new document.validators["ValSmsText"]("sms_text","sms_text")
        ]
      };

      watchFields('#msgsndr_form_sms');

      // Character Count
      $('#msgsndr_form_sms').on('keyup', function() {
        charCount(this, '160', '.sms.characters');
      });

    }

    function socialFB() {

      $('div[data-social=facebook]').removeClass('hidden');

      // Character Count
      $('#msgsndr_form_fbmsg').on('keyup', function() {
        charCount(this, '420', '.fb.characters');
      });

    }

    function socialTwitter() {

      $('div[data-social=twitter]').removeClass('hidden');

      var twitterCharCount = 140 - twitterReservedChars;

      $('.twit.characters').prepend(twitterCharCount);
      // Character Count
      $('#msgsndr_form_tmsg').on('keyup', function() {
        charCount(this, twitterCharCount, '.twit.characters');
      });

    }

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


    }



    // Section 1 - Form Watch
    $('#msg_section_1').on('focusout', function() {
      notVal.watchSection('msg_section_1');
    });


    // Wathc Email Form
    $('#msgsndr_tab_email .required').on('keyup', function() {
      notVal.watchContent('msgsndr_tab_email');
    });

    // Save SMS Message
    $('.btn_save').on('click', function(e) {
      e.preventDefault();

      notVal.saveBtn(this);
      notVal.checkContent();

    });


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

      console.log($('form[name=broadcast]').serializeArray());

    })






    // Set keyup event to the fields that need validating - These fields passed through from functions above
    function watchFields(fieldId) {

      var watch = watch + ', ' + fieldId;

      $(watch).on('keyup', function() {
        var elem  = $(this);
        formVal(elem);
      });

    }

    function formVal(element) {

      var name  = element.attr('name');
      var form  = name.split("_")[0];
      var field = name.split("_")[1];

      var value = element.val();

      var ajax  = element.attr('data-ajax');


      var isValid = true;
      var validators = document.formvars[form][field];
      requiredvalues = [];

      if (ajax == 'true') {

        var postData = {
          value: value,
          requiredvalues: ""
        }

        var ajaxurl = "message_sender.php?form=broadcast&ajaxvalidator=true&formitem=" + name;

        $.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {json: $.toJSON(postData) },

          success: function(response) {
              var res = response;
              if (res.vres != true) {
                element.removeClass('ok').addClass('er').next('.error').show().text(res.vmsg);
              } else {
                element.removeClass('er').addClass('ok').next('.error').hide();
              }
          }
        });

      } else { // None AJAX validation

        // Loop validation
        for (var i = 0; i < validators.length; i++) {  
          var validator = validators[i];  
          if (value.length > 0 || validator.isrequired || validator.conditionalrequired || value.length == 0) {  
            res = validator.validate(validator.name,validator.label,value,validator.args,requiredvalues); 
            if (res != true) {  
              isValid = false;  
              // If SMS - add class er to textarea and disable save button
              if (name == 'sms_text') {
                element.removeClass('ok').addClass('er'); 
                $('#msgsndr_tab_sms .btn_save').attr('disabled','disabled');
              } else {
                element.removeClass('ok').addClass('er').next('.error').show().text(res);
              }
              break; 
            } else {
              // If SMS - add class ok to textarea and remove disabled from save button
              if (name == 'sms_text') {
                element.removeClass('er').addClass('ok'); 
                $('#msgsndr_tab_sms .btn_save').removeAttr('disabled');
              } else {
                element.removeClass('er').addClass('ok').next('.error').hide();
              }
            }
          } 

        } // for

          if (res == true && field == "number") {
            $('#ctrecord').show();
          } 
          

      } // if ajax

    } // form_val function


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
        

    } // form_val function



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


  /*
   Message Groups, messages and message parts
  \******************************************/

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

    // get messages based on message id from message group (WIP)
    function getMessages(msgid){
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgid+'/messages',
        type: "GET",
        dataType: "json",
        success: function(data) {
          var messages = (data.messages);
        }
      });
    };

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
