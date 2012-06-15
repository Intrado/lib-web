jQuery.noConflict();
(function($) { 
  $(function() {

    // Global Functions
    notVal        = new globalValidationFunctions();
    loadMsg       = new loadMessage();

    // Some Global variables
    // userid, orgid, and twitterReservedChars are set in index.php

    orgPath     = window.location.pathname.split('/')[1]; // This gives us the the URL path needed for first part of AJAX call

    // Email Body Text
    emailData = "";


    $('.error').hide();
    // set the corresponding object here for any new validators
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
    orgid = false;

    $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/roles',
        type: "GET",
        dataType: "json",
        async: false,
        success: function(data) {
          // set the orgid from the very first set of role permissions
          orgid = data.roles[0].organization.id;

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
      
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/preferences',
        type: "GET",
        dataType: "json",
        success: function(data) {

          $.each(data.preferences, function(uIndex, uPrefs){
            userPrefs[uPrefs.name] = uPrefs.value;
          });
        }
      });  
    };

    // get user information ...
    function getUserInfo() {
      userInfo = false;
      
      $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid,
        type: "GET",
        dataType: "json",
        async: false,
        success: function(data) {
          userInfo = data;
           // format the phone number and add the new formatted version to the userInfo object
          userInfo.phoneFormatted = notVal.formatPhone(data.phone); 
        }
      });  
    };

    function formatPhone(number){ // must be a 10 digit number with no spaces passed in
      var phone = number;
      var phonePartOne = '(' + phone.substring(0,3) + ') ';
      var phonePartTwo = phone.substring(3,6) + '-';
      var phonePartThree = phone.substring(6,10);
      return phonePartOne + phonePartTwo + phonePartThree;
    };


      // call the functions
      getOptions();
      getLanguages();
      getUserPrefs();
      getUserInfo();

      // Load Saved message - this is in message_sender.loadmessage.js
      loadMsg.getMessageGroups();


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

      // set the addme values from the userInfo object...
      if(typeof(userInfo) != 'undefined' && userInfo != false){
        if(userInfo.phone != ''){
        $('#msgsndr_form_mephone').attr('value', userInfo.phoneFormatted);
        }
        if(userInfo.email != ''){
          $('#msgsndr_form_meemail').attr('value', userInfo.email);
        }
      };
      
      
      
    };

    function sendPhone() {

      $('li.ophone').removeClass('notactive');

      document.formvars['phone']['number'] = [
        new document.validators["ValRequired"]("content_phone","Number to Call",{}),
        new document.validators["ValPhone"]("content_phone","Number to Call",{})
      ];
      document.formvars['phone']['tts'] = [
        new document.validators["ValRequired"]("phone_tts","Phone Message",{}),
        new document.validators["ValLength"]("phone_tts","Phone Message",{length:10000}),
        new document.validators["ValTtsText"](),
        // new document.validators["ValTextAreaPhone"]()
      ];

      notVal.watchFields('#msgsndr_form_number, #msgsndr_tts_message');



      // Build up select box based on the maxjobdays user permission
      var daysToRun = userPermissions.maxjobdays;
      j('#msgsndr_form_daystorun').empty();
      for(i=1;i<=daysToRun;i++) {
        j('#msgsndr_form_days').append('<option value="'+i+'">'+i+'</option>');
      };



      // Hide / Show Translations
      $('#text').on('click', '.toggle-translations', function(event) {

        event.preventDefault();

        var text          = $(this).text().split(" ");

        $(this).text(text[0] == 'Show' ? 'Hide ' + text[1] + ' ' + text[2] : 'Show ' + text[1] + ' ' + text[2] );

        var etarget = $(this).attr('data-target');
        $(etarget).slideToggle();
        $(this).toggleClass('active');

        $('#tts_retranslate').remove();

        if (text[0] == 'Show') {

          ttsTranslate(this);

          $(this).parent().append(' <button id="tts_retranslate" data-target="#tts_translate">Re Translate</button>');
        }

      });


      $('#text').on('click', '#tts_retranslate', function() {
        ttsTranslate(this);
      });

        function ttsTranslate(elem) {

        var txtField      = $('#msgsndr_tts_message').val();
        var displayArea   = $(elem).attr('data-target');
        var msgType       = 'tts';

        var ttslangCodes  = '';

          var checkTranslations = $('input[name^=tts_override]');
          $.each(checkTranslations, function(tIndex, tData) {
            lCode = $(tData).attr('name').split('_')[2];
            if ($(tData).is(':checked')) {

            } else {
              if (ttslangCodes == '') {
                ttslangCodes = lCode
              } else {
                ttslangCodes = ttslangCodes + '|' + lCode;
              }
            }
          });

          $('#tts_translate fieldset > label[for^=tts_]').append(' <img src="img/ajax-loader.gif" class="loading" />');

          notVal.doTranslate(ttslangCodes,txtField,displayArea,msgType);

        }


      $('#tts_translate').on('click', '.show_hide_english', function(e) {
        e.preventDefault();

        var langCode        = $(this).attr('data-code');
        $('#retranslate_'+langCode).slideToggle('fast');

        if ($(this).text() == "Show In English") {
          reTranslate(this);
        }

        $(this).text($(this).text() == 'Show In English' ? 'Hide English' : 'Show In English');

      });


      $('#tts_translate').on('click', '.retranslate', function(e) {
        e.preventDefault();
        reTranslate(this);
      });

      // Override Translation 
      $('#tts_translate').on('click', 'input[name^=tts_override]', function() {

        var langCode      = $(this).attr('name').split('_')[2];
        var checkedState  = $(this).attr('checked');

        // Attached current translated text to the jQuery data object, so we can revert back to it

        if (typeof (checkedState) != "undefined" ) {
          $(this).data('translatedtext', $('#tts_translated_'+langCode).val());
          $('#tts_translated_'+langCode).removeAttr('disabled');
        } else {
          
          var revertTranslation = confirm('The translation will be put back to the previous translation');
          if (!revertTranslation) {
            $(this).attr('checked','checked');
            $('#tts_translated_'+langCode).removeAttr('disabled');
          } else {
            //$('#tts_translated_'+langCode)
            $('#tts_translated_'+langCode).attr('disabled','disbaled');
            $('#tts_translated_'+langCode).val($(this).data('translatedtext'));

          }
        }

      });

      function reTranslate(elem) {

        var langName        = $(elem).attr('data-text');
        var langCode        = $(elem).attr('data-code');
        var txt             = $('#tts_translated_'+langCode).val();
        var displayArea     = $('#tts_'+langName+'_to_english');
        var msgType         = 'tts';

        $('#retranslate_'+langCode).removeClass('hide');
        doreTranslate(langCode,txt,displayArea,msgType);
        
      }

      var splitlangCodes = ttslangCodes.split('|');
      var langCount = splitlangCodes.length;

      if (langCount == 1) {
        $('a[data-target=#tts_translate]').show().text('Show '+langCount+' translation');
      } else {
        $('a[data-target=#tts_translate]').show().text('Show '+langCount+' translations');
      }

      $.each(splitlangCodes, function(transIndex, transData) {

        var langCode = splitlangCodes[transIndex];

        var ttsTranslate = '<fieldset>';

        ttsTranslate += '<input type="checkbox" checked="checked" name="save_translation" class="translations" id="tts_'+langCode+'" />';
        ttsTranslate += '<label for="tts_'+langCode+'">'+nLangs[langCode]+'</label>';
        ttsTranslate += '<div class="controls">';
        ttsTranslate += '<textarea id="tts_translated_'+langCode+'" disabled="disabled"></textarea>';
        ttsTranslate += '<button class="playAudio" data-text="tts_'+nLangs[langCode]+'" data-code="'+langCode+'"><span class="icon play"></span> Play Audio</button>';
        ttsTranslate += '<button class="show_hide_english" data-text="'+nLangs[langCode]+'" data-code="'+langCode+'">Show In English</button>';
        ttsTranslate += '<input type="checkbox" name="tts_override_'+langCode+'" id="tts_override_'+langCode+'" /><label for="tts_override_'+langCode+'">Override Translation</label>';
        ttsTranslate += '</div>';
        ttsTranslate += '<div class="controls hide" id="retranslate_'+langCode+'">';
        ttsTranslate += '<button class="retranslate" data-text="'+nLangs[langCode]+'" data-code="'+langCode+'">Refresh '+nLangs[langCode]+' to English Translation</button>';
        ttsTranslate += '<textarea id="tts_'+nLangs[langCode]+'_to_english" disabled="disabled"></textarea>';
        ttsTranslate += '</fieldset>';

        $('#tts_translate').append(ttsTranslate);

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
          
          if (typeof(orgOptions.requireapprovedcallerid) != 'undefined' && orgOptions.requireapprovedcallerid == 1){ // if requireapprovedcallerid is defined...
            // get the users callerid's ...
            var userCallerIds = getUserCallerIds();

            // and append them as options to the select menu ...
            $.each(userCallerIds, function(cIndex, cItem){
              $('#msgsndr_form_callid').append('<option value="'+cItem+'" >'+cItem+'</option>');
            });

            // if the users setcallerid permission is defined, 
            // add the 'other' option and create a text input for them to add arbitrary value, and validate it.
            if (typeof(userPermissions.setcallerid) != 'undefined' && userPermissions.setcallerid == 1){ 
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
          // Commented out the following code, I believe there should be no callerid passed to postdata for users with 'hascallback'
          
          /* get the default caller id and append it as the selected option in the hidden callerid select menu
          * var callerIdnumber = getDefaultCallerId();
          * $('#msgsndr_form_callid').append('<option value="'+callerIdnumber+'" selected >'+callerIdnumber+'</option>'); */
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
              callerIds = [getDefaultCallerId()];
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
          new document.validators["ValLength"]("email_address", "Email Address", {max:255}),
          new document.validators["ValEmail"]("email_address","Email Address",{domain:orgOptions.emaildomain}),
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



      // Hide / Show Translations
      $('#msgsndr_tab_email').on('click', '.toggle-translations', function(event) {

        event.preventDefault();

        var text          = $(this).text().split(" ");

        $(this).text(text[0] == 'Show' ? 'Hide ' + text[1] + ' ' + text[2] : 'Show ' + text[1] + ' ' + text[2] );

        var etarget = $(this).attr('data-target');
        $(etarget).slideToggle();
        $(this).toggleClass('active');

        $('#email_retranslate').remove();

        if (text[0] == 'Show') {

          eTranslate(this);

          $(this).parent().append(' <button id="email_retranslate" data-target="#email_translate">Re Translate</button>');
        }

      });


      function eTranslate() {

          var txtField      = CKEDITOR.instances.reusableckeditor.getData();
          var displayArea   = $(this).attr('data-display');
          var msgType       = 'email';

          $(this).parent().append(' <button id="email_retranslate" data-target="#email_translate">Re Translate</button>');

          $('#email_translate fieldset > label[for^=email_]').append(' <img src="img/ajax-loader.gif" class="loading" />');

          notVal.doTranslate(elangCodes,txtField,displayArea,msgType);

      }

      $('#msgsndr_tab_email').on('click', '#email_retranslate', function() {
        eTranslate(this);
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

        $('#email_translate').append('<fieldset><input type="checkbox" checked="checked" id="email_'+langCode+'"  name="email_save_translation" class="translations" /><label for="email_'+nLangs[langCode]+'">'+nLangs[langCode]+'</label><div class="controls"><div class="html_translate" id="email_translated_'+langCode+'"></div></div></fieldset>');
      });


    };



    function sendSMS() {

      $('li.osms').removeClass('notactive');

      document.formvars['sms'] = {
        text: [
          new document.validators["ValRequired"]("sms_text","SMS",{}),
          new document.validators["ValLength"]("sms_text","SMS",{max:160}),
          new document.validators["ValSmsText"]("sms_text","SMS Text")
        ]
      };

      $("#msgsndr_form_sms").on({
          keyup: function() {
            charCount(this, '160', '.sms.characters');

            var elem  = $(this);
            notVal.formVal(elem);
            smsChar('set');

          },
          change: function() {
            
            charCount(this, '160', '.sms.characters');  

            var elem  = $(this);
            notVal.formVal(elem);
          
          }
      });

      $('#email_change').on('click', function() {
        $('#msgsndr_form_sms').val('Testing BOOOOMM');
      })

    };


      // setInterval to check sms text and update character count
      function smsChar(action) {

        function sInt() {
          //charCount($('#msgsndr_form_sms'), '160', '.sms.characters');
          $('#msgsndr_form_sms').trigger('change');
        }

        if (typeof (action) != 'undefined') {
          smsCharCount = setInterval(sInt,100);
        } else if (typeof (smsCharCount) != 'undefined') {
          clearInterval(smsCharCount);
        }
      };

    // social token ajax call
    var tokenCheck = true;
    function getTokens(){
      fbToken = false;
      twToken = false;
      tokenCheck = $.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/tokens',
        method: 'GET',
        dataType: 'json',
        async: true,
        success: function(data){
        if ($.isEmptyObject(data) != true) {
          if (typeof(data.facebook) != 'undefined' && data.facebook.accessToken){
            fbToken = data.facebook.accessToken;
            }
            if (typeof(data.twitter) != 'undefined' && data.twitter != ''){
              twToken = data.twitter;
              $('#msgsndr_twittername').append('Posting as <a class="" href="http://twitter.com/'+twToken.screenName+'">@'+twToken.screenName+'</a>');
            } else {
              $('#msgsndr_twittername').append('<a id="msgsndr_twitterauth" href="popuptwitterauth.php" class="btn"><img src="img/icons/custom/twitter.gif" alt="twitter"/> Add Twitter Account</a>');
              $('#msgsndr_twitterauth').on('click', function(){
                event.preventDefault();
                window.open($(this).prop('href'), '', 'height=300, width=600');
              });
            }
          }
        }
      });
    };

	getTokens();

	// facebook authorized destinations ajax call
	function getFbAuthorizedPages(){
		return $.get('/'+orgPath+'/api/2/organizations/'+orgid+'/settings/facebookpages',
			function(data) {
				if ($.isEmptyObject(data) != true && typeof(data.facebookPages) != 'undefined')
					facebookPages = data.facebookPages;
			}, "json");
	};
	




	function socialFB() {
		$('div[data-social=facebook]').removeClass('hidden');

		// set up the facebook api and any event listeners
		$.when(tokenCheck, getFbAuthorizedPages()).done(function() {
			// populate the authorized destinations hidden form item
			$("#msgsndr_fbpageauthpages").val($.toJSON({"pages":facebookPages,"wall":(orgOptions.fbauthorizewall?true:false)}));
			// intialize facebook with the current user's token
			initFacebook(fbToken);
			// listen for clicks to show facebook info
			$("#msgsndr_form_facebook").on('change', function(event) {
				if (fbToken && event.currentTarget.checked)
					updateFbPages(fbToken, "msgsndr_fbpage", "msgsndr_fbpagefbpages", false);
			});
		});

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



    // Watch form events to enable Save buttons

    // Section 1 - Watch
    $('#msg_section_1').on('focusout', function() {
      notVal.watchSection('msg_section_1');
    });


    // Watch Phone Content
    $('#callme .required').on('change', function() {
      notVal.watchContent('callme');
    });

    // Watch TTS Content
    $('#text .required').on('keyup', function() {
      notVal.watchContent('text');
    });


    // Watch Email Content
    $('#msgsndr_tab_email .required').on('keyup', function() {
      notVal.watchContent('msgsndr_tab_email');
    });



    // Save Button for message content
    $('.btn_save').on('click', function(e) {
      e.preventDefault();

      if ($(this).attr('data-nav') == '.osms' ) {
        smsChar();
      }

      notVal.saveBtn(this);
      notVal.checkContent();

    });


    // Cancel Button for message content areas
    $('.btn_cancel').on('click', function(e) {
      e.preventDefault();

      var navItem = $(this).attr('data-nav');

      if (navItem == '.osms' ) {
        smsChar();
      }

      $('.msg_content_nav li').removeClass('lighten');

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


      if ($(this).attr('checked')) {
        notVal.watchSocial(itemName);
      } else {
        notVal.unwatchSocial(itemName);
      }


    });



    /*****

      Translate 
    
    *****/



    function doreTranslate(langCode,txt,displayArea,msgType) {

      var transURL = 'translate.php?text='+txt+'&language='+langCode;

      // $('a[data-target='+displayArea+']').show().text('Fetching translations, please wait...');   
      // $(displayArea).empty();

      $.ajax({
        url: transURL,
        type: 'GET',
        dataType: 'json',
        success: function(data) {

          $('img.loading').remove();

          $(displayArea).val(data.responseData.translatedText);

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
        "lists":"msgsndr_listids", // check what this is in the new listpicker, currently passing in test data
        "has_phone":"msgsndr_hasphone", // true/false
        "phone_type":"msgsndr_phonemessagetype", // callme or text
        "phone_number":"msgsndr_phonemessagecallme", // callme messages object
        //"phone_voiceresponse":"", // true/false
        "phone_callconfirmation":"msgsndr_phonemessagepost",// true/false
        "phone_translate":"msgsndr_phonemessagetext", // english version
        "phone_tts_translate":"msgsndr_phonemessagetexttranslate", // true/false
        "phone_translate_es":"msgsndr_phonemessagetexttranslateestext",
        "phone_translate_af":"msgsndr_phonemessagetexttranslateaftext",
        "phone_translate_sq":"msgsndr_phonemessagetexttranslatesqtext",
        "phone_translate_ar":"msgsndr_phonemessagetexttranslateartext",
        "phone_translate_be":"msgsndr_phonemessagetexttranslatebetext",
        "phone_translate_bg":"msgsndr_phonemessagetexttranslatebgtext",
        "phone_translate_ca":"msgsndr_phonemessagetexttranslatecatext",
        "phone_translate_zh":"msgsndr_phonemessagetexttranslatezhtext",
        "phone_translate_hr":"msgsndr_phonemessagetexttranslateaftext",
        "phone_translate_cs":"msgsndr_phonemessagetexttranslatecstext",
        "phone_translate_da":"msgsndr_phonemessagetexttranslatedatext",
        "phone_translate_nl":"msgsndr_phonemessagetexttranslatenltext",
        "phone_translate_et":"msgsndr_phonemessagetexttranslateettext",
        "phone_translate_fil":"msgsndr_phonemessagetexttranslatefiltext",
        "phone_translate_fi":"msgsndr_phonemessagetexttranslatefitext",
        "phone_translate_fr":"msgsndr_phonemessagetexttranslatefrtext",
        "phone_translate_gl":"msgsndr_phonemessagetexttranslategltext",
        "phone_translate_de":"msgsndr_phonemessagetexttranslatedetext",
        "phone_translate_el":"msgsndr_phonemessagetexttranslateeltext",
        "phone_translate_ht":"msgsndr_phonemessagetexttranslatehttext",
        "phone_translate_iw":"msgsndr_phonemessagetexttranslateiwtext",
        "phone_translate_hi":"msgsndr_phonemessagetexttranslatehitext",
        "phone_translate_hu":"msgsndr_phonemessagetexttranslatehutext",
        "phone_translate_is":"msgsndr_phonemessagetexttranslateistext",
        "phone_translate_id":"msgsndr_phonemessagetexttranslateidtext",
        "phone_translate_ga":"msgsndr_phonemessagetexttranslategatext",
        "phone_translate_it":"msgsndr_phonemessagetexttranslateittext",
        "phone_translate_ja":"msgsndr_phonemessagetexttranslatejatext",
        "phone_translate_ko":"msgsndr_phonemessagetexttranslatekotext",
        "phone_translate_lv":"msgsndr_phonemessagetexttranslatelvtext",
        "phone_translate_lt":"msgsndr_phonemessagetexttranslatelttext",
        "phone_translate_mk":"msgsndr_phonemessagetexttranslatemktext",
        "phone_translate_ms":"msgsndr_phonemessagetexttranslatemstext",
        "phone_translate_mt":"msgsndr_phonemessagetexttranslatemttext",
        "phone_translate_no":"msgsndr_phonemessagetexttranslatenotext",
        "phone_translate_fa":"msgsndr_phonemessagetexttranslatefatext",
        "phone_translate_pt":"msgsndr_phonemessagetexttranslatepttext",
        "phone_translate_ro":"msgsndr_phonemessagetexttranslaterotext",
        "phone_translate_ru":"msgsndr_phonemessagetexttranslaterutext",
        "phone_translate_sr":"msgsndr_phonemessagetexttranslatesrtext",
        "phone_translate_sk":"msgsndr_phonemessagetexttranslatesktext",
        "phone_translate_sl":"msgsndr_phonemessagetexttranslatesltext",
        "phone_translate_sw":"msgsndr_phonemessagetexttranslateswtext",
        "phone_translate_sv":"msgsndr_phonemessagetexttranslatesvtext",
        "phone_translate_th":"msgsndr_phonemessagetexttranslatethtext",
        "phone_translate_tr":"msgsndr_phonemessagetexttranslatetrtext",
        "phone_translate_uk":"msgsndr_phonemessagetexttranslateuktext",
        "phone_translate_vi":"msgsndr_phonemessagetexttranslatevitext",
        "phone_translate_cy":"msgsndr_phonemessagetexttranslatecytext",
        "phone_translate_yi":"msgsndr_phonemessagetexttranslateyitext",
        "has_email":"msgsndr_hasemail", // true/false
        "email_name":"msgsndr_emailmessagefromname",
        "email_address":"msgsndr_emailmessagefromemail",
        "email_subject":"msgsndr_emailmessagesubject",
        "email_attachment":"msgsndr_emailmessageattachment",
        "email_message":"msgsndr_emailmessagetext", // data from CK editor panel
        "email_translate":"msgsndr_emailmessagetexttranslate", // convert this from 1/0 to true/false
        "email_translate_es":"msgsndr_emailmessagetexttranslateestext", // translates example
        "email_translate_es":"msgsndr_emailmessagetexttranslateestext",
        "email_translate_af":"msgsndr_emailmessagetexttranslateaftext",
        "email_translate_sq":"msgsndr_emailmessagetexttranslatesqtext",
        "email_translate_ar":"msgsndr_emailmessagetexttranslateartext",
        "email_translate_be":"msgsndr_emailmessagetexttranslatebetext",
        "email_translate_bg":"msgsndr_emailmessagetexttranslatebgtext",
        "email_translate_ca":"msgsndr_emailmessagetexttranslatecatext",
        "email_translate_zh":"msgsndr_emailmessagetexttranslatezhtext",
        "email_translate_hr":"msgsndr_emailmessagetexttranslateaftext",
        "email_translate_cs":"msgsndr_emailmessagetexttranslatecstext",
        "email_translate_da":"msgsndr_emailmessagetexttranslatedatext",
        "email_translate_nl":"msgsndr_emailmessagetexttranslatenltext",
        "email_translate_et":"msgsndr_emailmessagetexttranslateettext",
        "email_translate_fil":"msgsndr_emailmessagetexttranslatefiltext",
        "email_translate_fi":"msgsndr_emailmessagetexttranslatefitext",
        "email_translate_fr":"msgsndr_emailmessagetexttranslatefrtext",
        "email_translate_gl":"msgsndr_emailmessagetexttranslategltext",
        "email_translate_de":"msgsndr_emailmessagetexttranslatedetext",
        "email_translate_el":"msgsndr_emailmessagetexttranslateeltext",
        "email_translate_ht":"msgsndr_emailmessagetexttranslatehttext",
        "email_translate_iw":"msgsndr_emailmessagetexttranslateiwtext",
        "email_translate_hi":"msgsndr_emailmessagetexttranslatehitext",
        "email_translate_hu":"msgsndr_emailmessagetexttranslatehutext",
        "email_translate_is":"msgsndr_emailmessagetexttranslateistext",
        "email_translate_id":"msgsndr_emailmessagetexttranslateidtext",
        "email_translate_ga":"msgsndr_emailmessagetexttranslategatext",
        "email_translate_it":"msgsndr_emailmessagetexttranslateittext",
        "email_translate_ja":"msgsndr_emailmessagetexttranslatejatext",
        "email_translate_ko":"msgsndr_emailmessagetexttranslatekotext",
        "email_translate_lv":"msgsndr_emailmessagetexttranslatelvtext",
        "email_translate_lt":"msgsndr_emailmessagetexttranslatelttext",
        "email_translate_mk":"msgsndr_emailmessagetexttranslatemktext",
        "email_translate_ms":"msgsndr_emailmessagetexttranslatemstext",
        "email_translate_mt":"msgsndr_emailmessagetexttranslatemttext",
        "email_translate_no":"msgsndr_emailmessagetexttranslatenotext",
        "email_translate_fa":"msgsndr_emailmessagetexttranslatefatext",
        "email_translate_pt":"msgsndr_emailmessagetexttranslatepttext",
        "email_translate_ro":"msgsndr_emailmessagetexttranslaterotext",
        "email_translate_ru":"msgsndr_emailmessagetexttranslaterutext",
        "email_translate_sr":"msgsndr_emailmessagetexttranslatesrtext",
        "email_translate_sk":"msgsndr_emailmessagetexttranslatesktext",
        "email_translate_sl":"msgsndr_emailmessagetexttranslatesltext",
        "email_translate_sw":"msgsndr_emailmessagetexttranslateswtext",
        "email_translate_sv":"msgsndr_emailmessagetexttranslatesvtext",
        "email_translate_th":"msgsndr_emailmessagetexttranslatethtext",
        "email_translate_tr":"msgsndr_emailmessagetexttranslatetrtext",
        "email_translate_uk":"msgsndr_emailmessagetexttranslateuktext",
        "email_translate_vi":"msgsndr_emailmessagetexttranslatevitext",
        "email_translate_cy":"msgsndr_emailmessagetexttranslatecytext",
        "email_translate_yi":"msgsndr_emailmessagetexttranslateyitext",
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
        var thisType = $(this).attr("type");
        var thisKey = $(this).attr("name");

        if(typeof(keyMap[thisKey]) != "undefined") {
          thisKey = keyMap[thisKey];
          
          // make checkboxes true or false
          if(thisType == 'checkbox'){
            if($(this).attr('checked') == 'checked'){
              sendData[thisKey] = 'checked';
            } 
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
