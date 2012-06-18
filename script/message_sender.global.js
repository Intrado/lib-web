  function globalValidationFunctions() {

    // Instead of using $ we use j
    j = jQuery;

    var self = this;

    this.watchSection = function(section) {

      var reqFields   = j('#'+section+' .required');

      var reqCount    = 0;
      var reqAmount   = parseInt(reqFields.length);

      j.each(reqFields, function(index, ele) {
        if (j(ele).hasClass('ok')) {
          reqCount++;
        } else if (reqCount != 0) {
          reqCount--;
        }

      });

      if (reqCount == reqAmount) {
        j('#'+section+' button.btn_confirm').removeAttr('disabled');
      } else {
        j('#'+section+' button.btn_confirm').attr('disabled','disabled');
      }

    } // this.watchSection


    this.watchContent = function(section) {

      var reqFields   = j('#'+section+' .required');

      var reqCount    = 0;
      var reqAmount   = parseInt(reqFields.length);

      j.each(reqFields, function(index, ele) {
        if (j(ele).hasClass('ok')) {
          reqCount++;
        } else if (reqCount != 0) {
          reqCount--;
        }

      });

      if (reqCount == reqAmount) {
        j('#'+section+' button.btn_save').removeAttr('disabled');
      } else {
        j('#'+section+' button.btn_save').attr('disabled','disabled');
      }

    } // this.watchContent


    this.watchSocial = function(section) {

        checkSocial(section);

      j('div[data-social='+section+'] input, div[data-social='+section+'] textarea').on('keyup', function() {
        checkSocial(section);
      });

    }

    
    this.unwatchSocial = function(section) {

      var reqFields   = j('div[data-social='+section+'] .required');

      j.each(reqFields, function(index, ele) {
        j(ele).val('');
      });

      checkSocial(section);

    }

    
    checkSocial = function(section) {

      // for each input.social thats checked run test
      var socialInputs  = j('input.social:checked');

      var reqCount    = 0;
      var reqAmount   = 0;

      j.each(socialInputs, function(sIndex, sEle) {

        var itemName = j(sEle).attr('id').split('_')[2];

        var reqFields   = j('div[data-social='+itemName+'] .required');

        reqAmount = reqAmount + reqFields.length;


        j.each(reqFields, function(index, ele) {
          if (j(ele).val() != "") {
            reqCount++;
          } else if (reqCount != 0) {
            reqCount--;
          }

        });

      });


      if (reqCount > 0 && reqAmount > 0 && reqCount == reqAmount) {
        j('#msgsndr_tab_social button.btn_save').removeAttr('disabled');
      } else {
        j('#msgsndr_tab_social button.btn_save').attr('disabled','disabled');
      }

    }



    /* 
      checkContent() Function to run once Save Message button is clicked, this is check if any Add buttons 
      have class of complete if so then enable the button continue to allow user to move to step 3
    
    */
    this.checkContent = function() {

      var items = j('.msg_content_nav li');

      j.each(items, function(index, item) {
        if ( j(item).hasClass('complete') ) {
          j('#msg_section_2 .msg_confirm').removeClass('hide');
          j('#msg_section_2 .btn_confirm').removeAttr('disabled');
        }
      });

    } // checkContent


    this.saveBtn = function(ele) {

      var nav = j(ele).attr('data-nav');
      var el  = nav.substr(2);
      var tts = j(ele).attr('data-tts');

      if (tts == 'true') {
        
        j(ele).next('img').removeClass('hide');
        self.ttsSave();

      } else if (el == 'email') {

        j(ele).next('img').removeClass('hide');
        self.emailSave();
      
      } else {
      
        j('#msgsndr_tab_'+el).hide();

        j('.msg_content_nav li').removeClass('lighten');
        j('.msg_content_nav '+nav).removeClass('active').addClass('complete');

        j('input[name=has_'+el+']').attr('checked', 'checked');

        // Set Message tabs on review tab
        j('#msgsndr_review_'+el).parent().addClass('complete');
      }

      //j('#msg_section_2 .msg_confirm').removeClass('hide');
      self.checkContent();

    } // saveBtn

    this.cancelBtn = function(ele) {

      var nav = j(ele).attr('data-nav');
      var el  = nav.substr(2);

      j('.msg_content_nav '+nav).removeClass('active').removeClass('complete');
      j('#msgsndr_tab_'+el).hide();

      j('input[name=has_'+el+']').empty();


      // Set Message tabs on review tab
      j('#msgsndr_review_'+el).parent().removeClass('complete');

    } // saveBtn




    /* Section Three - Review and Send */
    this.reviewSend = function() {

      j('.review_subject p').text(j('#msgsndr_form_subject').val());
      j('.review_type p').text(j('#msgsndr_form_type option:selected').text());
      j('.review_count p').text('2,134');


        document.formvars['broadcast']['schedulecallearly'] = [
          new document.validators["ValTimeCheck"]("schedulecallearly","Early",{min:userPrefs.callearly,max:userPrefs.calllate}), 
          new document.validators["ValTimeWindowCallEarly"]("schedulecallearly")
        ];
        document.formvars['broadcast']['schedulecalllate'] = [
          new document.validators["ValTimeCheck"]("schedulecalllate","Late",{min:userPrefs.callearly,max:userPrefs.calllate}), 
          new document.validators["ValTimeWindowCallEarly"]("schedulecalllate"),
          new document.validators["ValTimePassed"]("scheduledate")
        ];
        document.formvars['broadcast']['requires'] = { 
          schedulecallearly: [
            "schedulecalllate", "scheduledate"
          ],
          schedulecalllate: [
            "schedulecallearly", "scheduledate"
          ]
        };

        var watchFields = '#schedulecallearly, #schedulecalllate';

        j(watchFields).on('change', function() {
          var elem  = j(this);
          self.formVal(elem);
        });


        // Callearly 
        var earlyProfile  = userPermissions.callearly;
        var earlyPref     = userPrefs.callearly;

        if ( typeof(earlyProfile) == "undefined" && typeof(earlyPref) == "undefined") {
          var callearly = "8:00 am";
        } else if (typeof(earlyPref) != "undefined" && typeof(earlyProfile) != "undefined") {
          if (Date(earlyPref) < Date(earlyProfile)) {
            var callearly = earlyProfile;
          } else {
            var callearly = earlyPref;
          }
        } else if (typeof(earlyPref) != "undefined") {
          var callearly = earlyPref;
        } else {
          var callearly = earlyProfile;
        }


        // Calllate
        var lateProfile   = userPermissions.calllate;
        var latePref      = userPrefs.calllate;

        if ( typeof(lateProfile) == "undefined" && typeof(latePref) == "undefined") {
          var calllate = "5:00 pm";
        } else if (typeof latePref != "undefined" && typeof(lateProfile) != "undefined") {
          if (Date(latePref) > Date(lateProfile)) {
            var calllate = lateProfile;
          } else {
            var calllate = latePref;
          }
        } else if (typeof(latePref) != "undefined") {
          var calllate = latePref;
        } else {
          var calllate = lateProfile;
        }

        j('#schedulecallearly option[value="'+callearly+'"]').attr('selected','selected').prevAll().remove();
        j('#schedulecallearly option[value="'+calllate+'"]').nextAll().remove();
        j('#schedulecalllate option[value="'+callearly+'"]').prevAll().remove();
        j('#schedulecalllate option[value="'+calllate+'"]').attr('selected','selected').nextAll().remove();

        // Populate the max attempts select box
        var maxAttempts = userPrefs.callmax;
        j('#msgsndr_form_maxattempts').empty();
        for(i=1;i<=maxAttempts;i++) {
          j('#msgsndr_form_maxattempts').append('<option value="'+i+'">'+i+'</option>');
        };

    }



    // Set keyup event to the fields that need validating - These fields passed through from functions above
    this.watchFields = function(fieldId) {

      var watch = watch + ', ' + fieldId;

      j(watch).on('blur', function() {
        var elem  = j(this);
        self.formVal(elem);
      });


    } // WatchFields


    this.formVal = function(element) {

      var name  = element.attr('name');
      var form  = name.split("_")[0];
      var field = name.split("_")[1];

      var value = element.val();

      var ajax  = element.attr('data-ajax');


      var isValid = true;
      var validators = document.formvars[form][field];

      if(typeof document.formvars[form]['requires'] != "undefined") {
        var requiredFields = document.formvars[form]['requires'][field];
      } else {
        requiredFields = false;
      }

      requiredValues = {};
      if (requiredFields) {
        for ( var i = 0; i < requiredFields.length; i++) {
          var requiredName = requiredFields[i];
          requiredValues[requiredName] = self.getValue(requiredName);
        }
      }

      if (ajax == 'true') {

        var postData = {
          value: value,
          requiredvalues: requiredValues
        }

        var ajaxurl = "message_sender.php?form=broadcast&ajaxvalidator=true&formitem=" + name;

        j.ajax({
          type: 'POST',
          url: ajaxurl,
          data: {json: j.toJSON(postData) },

          success: function(response) {
              var res = response;
              if (res.vres != true) {
                element.removeClass('ok').addClass('er').next('.error').fadeIn(300).text(res.vmsg);
              } else {
                element.removeClass('er').addClass('ok').next('.error').fadeOut(300);
              }
          }
        });

      } else { // None AJAX validation

        requiredvalues = [];

        // Loop validation
        for (var i = 0; i < validators.length; i++) {  
          var validator = validators[i];  
          if (value.length > 0 || validator.isrequired || validator.conditionalrequired || value.length == 0) {  
            res = validator.validate(validator.name,validator.label,value,validator.args,requiredvalues);
            if (res != true) {  
              isValid = false;  
              // If SMS - add class er to textarea and disable save button
              if (name == 'sms_text') {
                j('.sms.characters').addClass('error').text(res);
                element.removeClass('ok').addClass('er'); 
                j('#msgsndr_tab_sms .btn_save').attr('disabled','disabled');
              } else if (element.hasClass('required') == false && element.val() == '') {
                // check if the element isn't required, and if it's empty. If so, remove the validation message.
                element.removeClass('er').addClass('ok').next('.error').fadeOut(300);
              } else {
                element.removeClass('ok').addClass('er').next('.error').fadeIn(300).text(res);
              }
            } else {
              // If SMS - add class ok to textarea and remove disabled from save button
              if (name == 'sms_text') {
                element.removeClass('er').addClass('ok'); 
                j('.sms.characters').removeClass('error')
                j('#msgsndr_tab_sms .btn_save').removeAttr('disabled');
              } else {
                element.removeClass('er').addClass('ok').next('.error').fadeOut(300);
              }
            }
          } 

        } // for          

      } // if ajax

    } // form_val function



    this.getValue = function(elemName) {

      return j('#'+elemName).val();

    }


    this.formatPhone = function(number){ // must be a 10 digit number with no spaces passed in
      var phone = number;
      var phonePartOne = '(' + phone.substring(0,3) + ') ';
      var phonePartTwo = phone.substring(3,6) + '-';
      var phonePartThree = phone.substring(6,10);
      return phonePartOne + phonePartTwo + phonePartThree;
    };



    // Translate

    this.doTranslate = function(langCodes,txtField,displayArea,msgType) {

      var transTxt = makeTranslatableString(txtField);

      var transURL = 'translate.php?english='+transTxt+'&languages='+langCodes;

      var splitlangCodes = langCodes.split('|');
      var langCount = splitlangCodes.length;

      // $('a[data-target='+displayArea+']').show().text('Fetching translations, please wait...');   
      // $(displayArea).empty();

      j.ajax({
        url: transURL,
        type: 'GET',
        dataType: 'json',
        success: function(data) {

          j.each(data.responseData, function(transIndex, transData) {

            var langCode = splitlangCodes[transIndex];
            var textareaId = '#'+msgType+'_translated_'+langCode;

            transText = transData.translatedText;
            if ( msgType == "email" ) {
              j(textareaId).html(transText);
            } else {
              j(textareaId).text(transText);
            }

          });

          j('img.loading').remove();

        }

      });

    };



      /* 
        TTS Translate
        
        save tts need to generate hidden inputs buit up with value same as nickolas post data.txt
  
        postdata : {"enabled":true,"text":"Translated Text Here","override":false,"gender":"female","englishText":""}

      */

    this.ttsSave = function() {

      var gender        = j('input[name=messagePhoneText_message-gender]:checked').val();
      var enText        = j('#msgsndr_tts_message').val();

      var translate     = j('#msgsndr_form_phonetranslate').attr('checked');

      j('#text').append('<div id="post_data_translations"><input type="hidden" name="phone_translate" /></div>');

      var jsonVal = {"gender":"","text":""};
      jsonVal['text'] = enText;
      jsonVal['gender'] = gender;
      var jsonVal = j.toJSON(jsonVal)

      j('input[name=phone_translate]').val(jsonVal);

      //j('#post_data_translations').empty().append('<input type="hidden" name="phone_translate" value="{"gender": "'+gender+'", "text": "'+enText+'"}" />');

      /*
        Translations will only be built up if translate checkbox is checked
        
        We need to retranslate each language that is checked and store in a hidden input,
        but if they have override check just take what is in the language box and store in hidden input
  
        ;) Simples ....

      */

      if ( translate == 'checked' ) {

        var loopTranslations  = j('input[name=save_translation]:checked');
        var translatedText    = '';
        var langCodes         = '';

        j.each(loopTranslations, function(transI, transD) {
          langCode = j(transD).attr('id').split('_')[1];
          j('#post_data_translations').append('<input type="hidden" name="phone_translate_'+langCode+'">');
        });

        j.each(loopTranslations, function(transI, transD) {

          langCode = j(transD).attr('id').split('_')[1];

          var overRide = j('#tts_override_'+langCode).is(':checked');
          if (overRide == true) {
            transText = j('#tts_translated_'+langCode).val();

            var jsonVal = {"enabled":"true","text":"","override":"true","gender":"","englishText":""};
            jsonVal['text'] = transText;
            jsonVal['gender'] = gender;
            var jsonVal = j.toJSON(jsonVal)

            j('input[name=phone_translate_'+langCode+']').val(jsonVal);
            // j('#post_data_translations').append('<input type="hidden" name="phone_translate_'+langCode+'" value="'+jsonVal+'">');
          } else {
            if (langCodes == '') {
              langCodes = langCode;
            } else {
              langCodes = langCodes + '|' + langCode;
            }
          }
        
        });

        var splitlangCodes = langCodes.split('|');

        var transURL = 'translate.php?english='+enText+'&languages='+langCodes;
        var transText = '';

        var getTranslations = j.ajax({
          url: transURL,
          type: 'GET',
          // async: false,
          dataType: 'json',
          success: function(data) {

            j.each(data.responseData, function(transIndex, transData) {

              var langCode = splitlangCodes[transIndex];

              transText = transData.translatedText;

              var jsonVal = {"enabled":"true","text":"","override":"false","gender":"","englishText":""};
              jsonVal['text'] = transText;
              jsonVal['gender'] = gender;
              var jsonVal = j.toJSON(jsonVal)

              j('input[name=phone_translate_'+langCode+']').val(jsonVal);
              // j('#post_data_translations').append('<input type="hidden" name="phone_translate_'+langCode+'" value='+jsonVal+'>');


            });

          }

        });

        j.when(getTranslations).done(function() {
          j('#text .loading').addClass('hide');
          j('#msgsndr_tab_phone').hide();

          var el = 'phone';
          var nav = '.ophone';

          j('.msg_content_nav li').removeClass('lighten');
          j('.msg_content_nav '+nav).removeClass('active').addClass('complete');

          j('input[name=has_'+el+']').attr('checked', 'checked');

          // Set Message tabs on review tab
          j('#msgsndr_review_'+el).parent().addClass('complete');

          self.checkContent();
        });


       }

    };


    this.emailSave = function() {

      // var enText        = CKEDITOR.instances.reusableckeditor.getData();
      // var translate     = j('#msgsndr_form_emailtranslate').attr('checked');

      // j('#msgsndr_tab_email').append('<div id="post_data_email_translations"></div>');

      // j('#post_data_email_translations').empty().append('<input type="hidden" name="email_translate" val="'+escape(enText)+'">');


      // if ( translate == 'checked' ) {

      //   var loopTranslations  = j('input[name=email_save_translation]:checked');
      //   var translatedText    = '';

      //   j(loopTranslations).each(function(transI, transD) {

      //     var langCode = j(this).attr('id').split('_')[1]; // Gives me language code : en

      //     enText = makeTranslatableString(enText);
      //     translatedText = self.getTranslation(enText,langCode);

      //     j('#post_data_email_translations').append('<input type="hidden" name="email_translate_'+langCode+'" val="{"enabled":true,"text":"'+escape(translatedText)+'","override":"false","englishText":""}">');

      //   });

      // }
    //   ^^^^^^^^^^^^^^^^^^^^^^^^^^^^ OLD 


// {"enabled":true,"text":"","override":false,"englishText":""}

      var enText        = CKEDITOR.instances.reusableckeditor.getData();
      var translate     = j('#msgsndr_form_emailtranslate').attr('checked');

      j('#msgsndr_tab_email').append('<div id="post_data_email_translations"><input type="hidden" name="email_translate" /></div>');

      var jsonVal = {"enabled":true,"text":"","override":false,"englishText":""};
      jsonVal['text'] = enText;
      var jsonVal = j.toJSON(jsonVal)

      j('input[name=email_translate]').val(jsonVal);


      if ( translate == 'checked' ) {

        var loopTranslations  = j('input[name=email_save_translation]:checked');
        var translatedText    = '';
        var langCodes         = '';

        j.each(loopTranslations, function(transI, transD) {
          langCode = j(transD).attr('id').split('_')[1];
          j('#post_data_email_translations').append('<input type="hidden" name="email_translate_'+langCode+'">');
        });

        j.each(loopTranslations, function(transI, transD) {

          langCode = j(transD).attr('id').split('_')[1];

          if (langCodes == '') {
            langCodes = langCode;
          } else {
            langCodes = langCodes + '|' + langCode;
          }
        
        });

        var splitlangCodes = langCodes.split('|');

        var enText = makeTranslatableString(enText);

        var transURL = 'translate.php?english='+enText+'&languages='+langCodes;
        var transText = '';

        var getTranslations = j.ajax({
          url: transURL,
          type: 'GET',
          // async: false,
          dataType: 'json',
          success: function(data) {

            j.each(data.responseData, function(transIndex, transData) {

              var langCode = splitlangCodes[transIndex];

              transText = transData.translatedText;

              var jsonVal = {"enabled":true,"text":"","override":false,"englishText":""};
              jsonVal['text'] = enText;
              var jsonVal = j.toJSON(jsonVal)

              j('input[name=email_translate_'+langCode+']').val(jsonVal);
              // j('#post_data_translations').append('<input type="hidden" name="email_translate_'+langCode+'" value='+jsonVal+'>');


            });

          }

        });

        j.when(getTranslations).done(function() {
          j('#msgsndr_tab_email .loading').addClass('hide');
          j('#msgsndr_tab_email').hide();

          var el = 'email';
          var nav = '.oemail';

          j('.msg_content_nav li').removeClass('lighten');
          j('.msg_content_nav '+nav).removeClass('active').addClass('complete');

          j('input[name=has_'+el+']').attr('checked', 'checked');

          // Set Message tabs on review tab
          j('#msgsndr_review_'+el).parent().addClass('complete');

          self.checkContent();
        });


       }


    };


    this.getTranslation = function(enText,langCode) {

      var transURL = 'translate.php?english='+enText+'&languages='+langCode;

      var transText = '';

      j.ajax({
        url: transURL,
        type: 'GET',
        async: false,
        dataType: 'json',
        success: function(data) {

          transText = data.responseData[0].translatedText;
        }

      });

      return transText;

    };


  } // globalValFunctions()