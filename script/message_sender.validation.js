jQuery.noConflict();
(function($) { 
  $(function() {

    // Global Functions
    var notVal = new globalValidationFunctions();

    // Some Global variables
    // userid, orgid, and twitterReservedChars are set in index.php

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
      social:{ },
      addme: { }
    };



    // Initial Call
    $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/roles',
        type: "GET",
        dataType: "json",
        async: false,
        success: function(data) {
          setUp(data.roles); // Send Data over to the function setUp();
       }
    });

 
    /* --
      The setUp function will do a lot of the inital work, and call other functions based on users roles
    -- */

    function setUp(roleData) {
      if(!$.isArray(roleData)) {
        alert("error");
        return false;
      }


      // get the organization settings options
      function getOptions(){
        orgOptions = {};
        $.ajax({
          url: '/'+orgPath+'/api/2/organizations/'+orgid+'/settings/options',
          type: "GET",
          dataType: "json",
          async: false,
          success: function(data){
            var options = data.options;
            $.each(options, function(oIndex, oItem) {
              orgOptions[oItem.name] = oItem.value;
            });
          }
        });
      };


      function getLanguages(){

        ttslangCodes   = "";
        elangCodes    = "";
        nLangs        = {};

        $.ajax({
            url: '/'+orgPath+'/api/2/organizations/'+orgid+'/languages',
            type: "GET",
            dataType: "json",
            async: false,
            success: function(data) {
             languages = data.languages;

              $.each(languages, function(lIndex, lData) {

                var lCodes = lData.code;
                nLangs[lCodes] = lData.name;
                var voices = lData.voices;

                /*
                  If languages has voices then add code to ttlangcodes as well as elangcodes
                  if voices is undefined only add code to elangcodes
                */

                if (lCodes != "en") {
                  if (typeof (voices) != "undefined") {

                    if (ttslangCodes == "") {
                      ttslangCodes = lCodes;
                      if (elangCodes == "") {
                        elangCodes = lCodes;
                      }
                    } else {
                      ttslangCodes = ttslangCodes + '|' + lCodes;
                      elangCodes   = elangCodes + '|' + lCodes;
                    }

                  } else {

                    if (elangCodes == "") {
                      elangCodes = lCodes;
                    } else {
                      elangCodes = elangCodes + '|' + lCodes;
                    }

                  }
                }

              });

           }
        });

      };

    // get user preferences ...
    function getUserPrefs() {

      userPrefs = {};
      
      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/preferences',
        type: "GET",
        dataType: "json",
        success: function(data) {

          j.each(data.preferences, function(uIndex, uPrefs){
            userPrefs[uPrefs.name] = uPrefs.value;
          });

          // callback();
        }
      });
  
      //return userPrefs;
  
    };


      // call the functions
      getOptions();
      getLanguages();
      getUserPrefs();


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
            $('#msgsndr_form_type').append('<option value='+jobType.id+'>'+jobType.name+'</option');
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

      
      document.formvars['addme']['phone'] = [new document.validators["ValPhone"]("addme_phone","My phone",{})];
      document.formvars['addme']['email'] = [new document.validators["ValEmail"]("addme_email","My email",{})];
      document.formvars['addme']['sms']   = [new document.validators["ValPhone"]("addme_sms","My SMS",{})];

      notVal.watchFields('#msgsndr_form_mephone');
      notVal.watchFields('#msgsndr_form_meemail');
      notVal.watchFields('#msgsndr_form_mesms');
      

    };

    function sendPhone() {

      $('li.ophone').removeClass('notactive');

      document.formvars['phone']['number'] = [
        new document.validators["ValRequired"]("content_phone","Number to Call",{}),
        new document.validators["ValPhone"]("content_phone","Number to Call",{})
      ];
      document.formvars['phone']['tts'] = [
        new document.validators["ValLength"]("phone_tts","Phone Message",{length:10000}),
        new document.validators["ValTtsText"](),
        // new document.validators["ValTextAreaPhone"]()
      ];

      notVal.watchFields('#msgsndr_form_number, #msgsndr_tts_message');

      // $('#msgsndr_tts_message').on('keyup', function() {
      //   charCount(this, '10000', '.tts.characters');
      // });

      // Build up select box based on the maxjobdays user permission
      var daysToRun = userPermissions.maxjobdays;
      j('#msgsndr_form_daystorun').empty();
      for(i=1;i<=daysToRun;i++) {
        j('#msgsndr_form_days').append('<option value="'+i+'">'+i+'</option>');
      };



      // Translate Checkbox event
      $('#msgsndr_form_phonetranslate').on('click', function() {
        // Checked if checked then do translate, as do not want to hit API when deselected translate
        if ($(this).is(':checked')) {

          var txtField      = $('#msgsndr_tts_message').val();
          var displayArea   = $(this).attr('data-display');
          var msgType       = 'tts'

          doTranslate(ttslangCodes,txtField,displayArea,msgType);

        }
      });


      var splitlangCodes = ttslangCodes.split('|');
      var langCount = splitlangCodes.length;

      if (langCount == 1) {
        $('a[data-target=#tts_translate]').show().text('Show '+langCount+' translation');
      } else {
        $('a[data-target=#tts_translate]').show().text('Show '+langCount+' translations');
      }

      $.each(splitlangCodes, function(transIndex, transData) {

        var langCode = splitlangCodes[transIndex];

        $('#tts_translate').append('<fieldset><input type="checkbox" checked="checked" /><label for="tts_'+nLangs[langCode]+'">'+nLangs[langCode]+'</label><div class="controls"><textarea id="tts_'+nLangs[langCode]+'"></textarea><button class="playAudio" data-text="tts_'+nLangs[langCode]+'" data-code="'+langCode+'"><span class="icon play"></span> Play Audio</button><button class="retranslate" data-code="'+langCode+'">Show In English</button><input type="checkbox" name="tts_override_'+langCode+'" id="tts_override_'+langCode+'" /><label for="tts_override_'+langCode+'">Override Translation</label></div></fieldset>');
      });




      // Caller ids
      ////////////////////////////////////

      // determine whether we show or hide the callerId
      function callerIdDisplay(){
      
        var callerIdnumber = false;

        // If hascallback isn't enabled, 
        // check for orgOptions.requiredapprovedcallerid, 
        // then subsequently for userPermissions.setcallerid
        if (orgOptions._hascallback == 0){
          
          if (typeof(orgOptions.requireapprovedcallerid) != 'undefined'){ // if requireapprovedcallerid is defined...
            // get the users callerid's ...
            var userCallerIds = getUserCallerIds();

            // and append them as options to the select menu ...
            $.each(userCallerIds, function(cIndex, cItem){
              $('#msgsndr_form_callid').append('<option value="'+cItem+'" >'+cItem+'</option>');
            });

            // if the users setcallerid permission is defined, 
            // add the 'other' option and create a text input for them to add arbitrary value, and validate it.
            if (typeof(userPermissions.setcallerid) != 'undefined'){ 
              $('#msgsndr_form_callid').append('<option value="other" >Other</option>');
              $('#msgsndr_form_callid').closest('div.controls').append('<span id="callerid_other_wrapper" class="hidden"><input type="text" id="callerid_other" name="phone_callerid"  /><span class="error"></span></span>');
              // set up the validation on the 'other' input
              notVal.watchFields('#callerid_other');
              document.formvars['phone']['callerid'] = [new document.validators["ValPhone"]("phone_callerid","Caller ID",{})];
              // watch for the 'other' option being selected and act accordingly
              $('#msgsndr_form_callid').on('change', function(){
                if ($(this, 'option:selected').val() == 'other') {
                  $('#callerid_other_wrapper').removeClass('hidden');
                } else {
                  $('#callerid_other_wrapper').addClass('hidden');
                }
              });
            }
            
          } else { // not sure here, set the default callerid and display the select with that as the option?
            var callerIdnumber = getDefaultCallerId();
            $('#msgsndr_form_callid').append('<option value="'+callerIdnumber+'" selected >'+callerIdnumber+'</option>'); 
          }

        } else { // the user hascallback so we hide caller id select fieldset from view
          $('#msgsndr_form_callid').closest('fieldset').addClass('hidden');
          // get the default caller id and append it as the selected option in the hidden callerid select menu
          var callerIdnumber = getDefaultCallerId();
          $('#msgsndr_form_callid').append('<option value="'+callerIdnumber+'" selected >'+callerIdnumber+'</option>');
        }
      };

      // call the callerIdDisplay function...
      callerIdDisplay();
      
      // get the default caller id depending on settings, check the user role permissions first,
      // if that isn't set, then get the callerid from system options.
      function getDefaultCallerId(){
        var userCallerId = userPermissions.callerid;
        var orgCallerId = orgOptions.callerid;

        if (typeof(userCallerId) == 'undefined'){
          return orgCallerId;
        } else {
          return userCallerId;
        }
      };

      // get the users list of caller ids, if the list is empty, return the default caller id...
      function getUserCallerIds(){
        var callerIds = false;
         $.ajax({
          url: '/'+orgPath+'/api/2/users/'+userid+'/roles/'+userRoleId+'/settings/callerids/',
          type: "GET",
          dataType: "json",
          async: false,
          success: function(data) {
            callerIds = data.callerids;
            // if the ajax call returns no numbers or nothing, get the default callerid...
            if (callerIds == false || callerIds.length == 0) {
              callerIds = getDefaultCallerId();
            }
          }
        });

        return callerIds;
        //return ["8316001090","8316001091","8043810293"]; // some test data...
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


      // Translate Checkbox event
      $('#msgsndr_form_emailtranslate').on('click', function() {
        // Checked if checked then do translate, as do not want to hit API when deselected translate
        if ($(this).is(':checked')) {

          var txtField      = CKEDITOR.instances.reusableckeditor.document.$.body.innerHTML;
          // $('#cke_reusableckeditor iframe').contents().find('body').text();
          // CKEDITOR.instances.reusableckeditor.document.$.body.innerText;
          var displayArea   = $(this).attr('data-display');
          var msgType       = 'email';


          $(this).next('a').append(' <img src="img/ajax-loader.gif" class="loading" />');

          // $(this).attr('disabled','disabled');
          doTranslate(elangCodes,txtField,displayArea,msgType);

        }
      });

      var splitlangCodes = elangCodes.split('|');
      var langCount = splitlangCodes.length;

      if (langCount == 1) {
        $('a[data-target=#email_translate]').show().text('Show '+langCount+' translation');
      } else {
        $('a[data-target=#email_translate]').show().text('Show '+langCount+' translations');
      }

      $.each(splitlangCodes, function(transIndex, transData) {

        var langCode = splitlangCodes[transIndex];

        $('#email_translate').append('<fieldset><input type="checkbox" checked="checked" /><label for="email_'+nLangs[langCode]+'">'+nLangs[langCode]+'</label><div class="controls"><div class="html_translate" id="email_'+nLangs[langCode]+'"></div></div></fieldset>');
      });


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

    $('.btn_cancel').on('click', function(e) {
      e.preventDefault();

      notVal.cancelBtn(this);
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



    /*****

      Translate 
    
    *****/




    function doTranslate(langCodes,txtField,displayArea,msgType) {

      var transTxt = makeTranslatableString(txtField);

      function makeTranslatableString(str) {
        return str.replace(/(<<.*?>>)/g, '<input value="$1"/>').replace(/({{.*?}})/g, '<input value="$1"/>').replace(/(\[\[.*?\]\])/g, '<input value="$1"/>');
      }

      var transURL = 'translate.php?english='+transTxt+'&languages='+langCodes;

      var splitlangCodes = langCodes.split('|');
      var langCount = splitlangCodes.length;

      // $('a[data-target='+displayArea+']').show().text('Fetching translations, please wait...');   
      // $(displayArea).empty();

      $.ajax({
        url: transURL,
        type: 'GET',
        dataType: 'json',
        success: function(data) {

          $('img.loading').remove();

          $.each(data.responseData, function(transIndex, transData) {

            var langCode = splitlangCodes[transIndex];
            var textareaId = '#'+msgType+'_'+nLangs[langCode];

            transText = transData.translatedText;
            if ( msgType == "email" ) {
              $(textareaId).html(transText);
            } else {
              $(textareaId).text(transText);
            }

          });

        }

      });

    };














    // Send Message Button
    $('.submit_broadcast').on('click', function(e) {
      e.preventDefault();

      //var formData = $('form[name=broadcast]').serializeArray();
      var formData = mapPostData();
      $.ajax({
        type: 'POST',
        url: '_messagesender.php?form=msgsndr&ajax=true',
        data: formData,
        dataType: 'json',
        success: function(response) {
          console.log(response);
            var res = response;
            if (res.vres != true) {
              console.log(res);
            } else {
              console.log(res);
            }
        }
      });
      //window.location = 'start.php';
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
        "addme_check":"msgsndr_addme",
        "addme_phone":"msgsndr_addmephone",
        "addme_email":"msgsndr_addmeemail",
        "addme_sms":"msgsndr_addmesms",
        "":"msgsndr_listids", // check what this is in the new listpicker
        "has_phone":"msgsndr_hasphone", // true/false
        "phone_type":"msgsndr_phonemessagetype", // callme or text
        "phone_voiceresponse":"msgsndr_phonemessagepost", // true/false
        "phone_callconfirmation":"msgsndr_phonemessagecallme",
        "phone_text":"msgsndr_phonemessagetext",
        "phone_translate":"msgsndr_phonemessagetexttranslate", // true/false
        "phone_translation_es":"msgsndr_phonemessagetexttranslateestext",
        // need to account for at least all tts languages, if not all of them
        "has_email":"msgsndr_hasemail", // true/false
        "email_name":"msgsndr_emailmessagefromname",
        "email_address":"msgsndr_emailmessagefromemail",
        "email_subject":"msgsndr_emailmessagesubject",
        "email_attachment":"msgsndr_emailmessageattachment",
        "email_message":"msgsndr_emailmessagetext", // data from CK editor panel
        "email_translate":"msgsndr_emailmessagetexttranslate", // convert this from 1/0 to true/false
        "email_translation_es":"msgsndr_emailmessagetexttranslateestext", // translations...
        // translations could get big, there's 50 languages!
        "has_sms":"msgsndr_hassms", // true/false
        "sms_text":"msgsndr_smsmessagetext",
        "has_facebook":"msgsndr_hasfacebook", // true/false
        "facebook_message":"msgsndr_socialmediafacebookmessage",
        "has_twitter":"msgsndr_hastwitter",
        "twitter_message":"msgsndr_socialmediatwittermessage",
        "has_feed":"msgsndr_hasfeed",
        "feed_message":"msgsndr_socialmediafeedmessage", // should be passed an object {"subject": "The feed title goes here", "message": "We have sent out a new message, you can preview it here. (RSS Feed)"}
        "feed_categories":"msgsndr_socialmediafeedcategory", // should be an array of feed category ids
        //"":"msgsndr_socialmediafacebookpage", // facebook id
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
    function getMessages(msgGrpId, callback){

      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages',
        type: "GET",
        dataType: "json",
        success: function(data) {

          allMessages = data.messages;
          messages = {};

          $.each(allMessages, function(mIndex, mData) {

            var msgParts = getMessageParts(msgGrpId,mData.id);
            
            mData['msgparts'] = msgParts;

          });

          $.each(allMessages, function(mIndex, mData) {

            var msgParts = getMessagePartsFormatted(msgGrpId,mData.id);
            mData['msgFormatted'] = msgParts;

          });


          $.each(allMessages, function(mIndex, mData) {
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
    function getMessageParts(msgGrpId,msgId){

      var parts = {};

      $.ajax({
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
    function getMessagePartsFormatted(msgGrpId,msgId){

      var parts = {};

      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/messagegroups/'+msgGrpId+'/messages/'+msgId+'/messageparts/formatted',
        async: false,
        type: "GET",
        dataType: "json",
        success: function(data) {

          // console.log(data.messageBody);
          parts = data.messageBody;

          // $.each(data.messageparts, function(mIndex, mData){
          //   partsFormatted[mData.name] = mData.value;
          // });
        }
      });

      return parts;
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



    // load the message ...

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
    });

    

    // Load Saved Message button in saved message modal window
    $('#msgsndr_load_saved_msg').on('click', function(){
      var msgGroup = $('.msgsndr_msggroup > td > input:radio[name=msgsndr_msggroup]:checked'); //input:checkbox[name=msgsndr_msggroup]:checked'
      var grpId = msgGroup.attr('value');
      var msgName = msgGroup.parent().text();

      // put the messageGroup id in the hidden input and display the message name
      $('#loaded_message_id').attr('value', grpId);
      $('#loaded_message_name').text(msgName);
      $('#msgsndr_loaded_message').fadeIn(300).fadeOut(7000);

      // make sure the correct tab is shown
      $('#msgsndr_saved_message').modal('hide');
      $('.msg_steps li:eq(1)').addClass('active');
      $('#msg_section_2').show();  


      clearForm(); 
      getMessages(grpId, doLoadMessage);

    });
    
    /*
      function used to empty all form data in section 2 - Message content, this is used when loading a
      previous message, as can not assume a user will only load one
    */


    function clearForm() {

      $('.msg_content_nav li').removeClass('complete');


      var allDataInputs = $('#msg_section_2 input, textarea');

      $('#cke_reusableckeditor iframe').contents().find('body').empty();
      // CKEDITOR.instances.reusableckeditor.document.$.body.innerText;

      $('.facebook, .twitter, .feed').hide();

      $.each(allDataInputs, function(aIndex, aData) {

        if ($(aData).attr('type') == 'checkbox') {
          $(aData).removeAttr('checked');
        }
        $(aData).val('');

      });

      var reviewTabs = $('.msg_complete li');
      $.each(reviewTabs, function(tIndex, tData) {
        $(tData).removeClass('complete');
      });

    }


    // This will contain the logic to populate message content from a loaded message
    function doLoadMessage() {

      if (typeof(messages.sms) != "undefined") {
        $('li.osms').addClass('complete');

        $('input[name=has_sms]').val('1');
        $('#msgsndr_form_sms').val(messages.sms.msgparts[0].txt);
      }

      if (typeof(messages.email) != "undefined") {
        $('li.oemail').addClass('complete');

        emailSubject = messages.email.subject;

        $('input[name=has_email').val('1');
        $('#msgsndr_form_name').val(messages.email.fromName.replace("+"," "));
        $('#msgsndr_form_email').val(messages.email.fromEmail.replace("%40","@"));
        $('#msgsndr_form_mailsubject').val(emailSubject);

        $('#cke_reusableckeditor iframe').contents().find('body').append(unescape(messages.email.msgFormatted));
        // CKEDITOR.instances.reusableckeditor.document.$.body.innerText;

        // $('iframe[name^=Ric]').contents().find('body').append(unescape(messages.email.msgFormatted));
        // Message HTML content == messages.email.msgparts[0].txt
      }

      if (typeof(messages.post) != "undefined") {
        $('li.osocial').addClass('complete');


        if (typeof(messages.post.facebook)  != "undefined") {
          $('#msgsndr_form_facebook').attr('checked','checked');
          $('div.facebook').show();

          $('#msgsndr_form_fbmsg').val(messages.post.facebook.msgparts[0].txt);
        }

        if (typeof(messages.post.twitter) != "undefined") {
          $('#msgsndr_form_twitter').attr('checked','checked');
          $('div.twitter').show();

          $('#msgsndr_form_tmsg').val(messages.post.twitter.msgparts[0].txt);
        }

        if (typeof(messages.post.feed) != "undefined") {
          $('#msgsndr_form_feed').attr('checked','checked');
          $('div.feed').show();

          $('#msgsndr_form_rsstitle').val(messages.post.feed.subject.replace("+"," "));
          $('#msgsndr_form_rssmsg').val(messages.post.feed.msgparts[0].txt);
        }

      }

      notVal.checkContent();

    }













    // WIP: function to choose ckeditor toolbars for the email message body
    // the idea is to pass this choice to the applyHtmlEditor() function in htmleditor.js
    
    chooseCkButtons = function(type){ // pass in 'basic' or 'advanced'

      var toolbarBasic = [
          ['Bold', 'Italic','NumberedList','BulletedList','Link']
        ];
        
      var toolbarAdvanced = [
          ['Print','Source','-','Undo','Redo','PasteFromWord','SpellCheck','-','NumberedList','BulletedList','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Outdent','Indent','Styles'],
          ['Bold', 'Italic', 'Underline','Strike','TextColor','BGColor', 'RemoveFormat','Link', 'Image','Table','HorizontalRule','Font','FontSize','Format']
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
    // toolbarChoice = chooseCkButtons('advanced');


  });
}) (jQuery);
