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
          j('#msg_section_2 .btn_confirm').removeAttr('disabled');
        }
      });

    } // checkContent


    this.saveBtn = function(ele) {

      var nav = j(ele).attr('data-nav');
      var el  = nav.substr(2);

      j('.msg_content_nav '+nav).removeClass('active').addClass('complete');
      j('#msgsndr_tab_'+el).hide();

      j('input[name=has_'+el+']').val('on');


      // Set Message tabs on review tab
      j('#msgsndr_review_'+el).parent().addClass('complete');

    } // saveBtn




    /* Section Three - Review and Send */
    this.reviewSend = function() {

      j('.review_subject p').text(j('#msgsndr_form_subject').val());
      j('.review_type p').text(j('#msgsndr_form_type option:selected').text());
      j('.review_count p').text('Insert Number here from list builder');

      afterGetPrefs = function() {

        // document.formvars['broadcast']['date'] = {
        //   date: [

        //   ]
        // }
        document.formvars['broadcast']['schedulecallearly'] = [
            // new document.validators["ValRequired"]("schedule_late","schedule_date"),
            new document.validators["ValTimeCheck"]("schedulecallearly","Early",{min:userPrefs.callearly,max:userPrefs.calllate}), 
            new document.validators["ValTimeWindowCallEarly"]("schedulecallearly")
        ];
        document.formvars['broadcast']['schedulecalllate'] = [
          // new document.validators["ValRequired"]("schedule_early","schedule_date"),
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

        j('#schedulecallearly option[value="'+userPrefs.callearly+'"]').attr('selected','selected');
        j('#schedulecalllate option[value="'+userPrefs.calllate+'"]').attr('selected','selected');

        // Populate the max attempts select box
        var maxAttempts = userPrefs.callmax;
        j('#msgsndr_form_maxattempts').empty();
        for(i=1;i<=maxAttempts;i++) {
          j('#msgsndr_form_maxattempts').append('<option value="'+i+'">'+i+'</option>');
        };

      };

      this.getUserPrefs(afterGetPrefs);

    }



    // Set keyup event to the fields that need validating - These fields passed through from functions above
    this.watchFields = function(fieldId) {

      var watch = watch + ', ' + fieldId;

      j(watch).on('keyup', function() {
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
                element.removeClass('ok').addClass('er').next('.error').show().text(res.vmsg);
              } else {
                element.removeClass('er').addClass('ok').next('.error').hide();
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
                element.removeClass('ok').addClass('er'); 
                j('#msgsndr_tab_sms .btn_save').attr('disabled','disabled');
              } else {
                element.removeClass('ok').addClass('er').next('.error').show().text(res);
              }
              break; 
            } else {
              // If SMS - add class ok to textarea and remove disabled from save button
              if (name == 'sms_text') {
                element.removeClass('er').addClass('ok'); 
                j('#msgsndr_tab_sms .btn_save').removeAttr('disabled');
              } else {
                element.removeClass('er').addClass('ok').next('.error').hide();
              }
            }
          } 

        } // for

          if (res == true && field == "number") {
            j('#ctrecord').show();
          } 
          

      } // if ajax

    } // form_val function



    this.getValue = function(elemName) {

      return j('#'+elemName).val();

    }




    // get user preferences ...
    this.getUserPrefs = function(callback){

      userPrefs = {};
      
      j.ajax({
        url: '/'+orgPath+'/api/2/users/'+userid+'/preferences',
        type: "GET",
        dataType: "json",
        success: function(data) {

          j.each(data.preferences, function(uIndex, uPrefs){
            userPrefs[uPrefs.name] = uPrefs.value;
          });

          callback();
        }
      });
  
      //return userPrefs;
  
    };




  } // globalValFunctions()