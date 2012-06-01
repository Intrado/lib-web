  function globalValidationFunctions() {

    // Instead of using $ we use j
    j = jQuery;

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

      console.log('watchSocial');
      j('div[data-social='+section+'] input, div[data-social='+section+'] textarea').on('keyup', function() {
        checkSocial(section);
      });

    }

    this.unwatchSocial = function(section) {

      console.log('unwatchSocial');
      j('div[data-social='+section+'] input, div[data-social='+section+'] textarea').off()
      
      clearSocial(section);
    

    }

    checkSocial = function(section) {

      var reqFields   = j('div[data-social='+section+'] .required');

      var reqCount    = 0;
      var reqAmount   = parseInt(reqFields.length);

      j.each(reqFields, function(index, ele) {
        if (j(ele).val() != "") {
          reqCount++;
        } else if (reqCount != 0) {
          reqCount--;
        }

      });

      if (reqCount == reqAmount) {
        j('#msgsndr_tab_social button.btn_save').removeAttr('disabled');
      } else {
        j('#msgsndr_tab_social button.btn_save').attr('disabled','disabled');
      }

    }

    clearSocial = function(section) {

      var reqFields   = j('div[data-social='+section+'] .required');

      j.each(reqFields, function(index, ele) {
        console.log(j(ele).val());
        j(ele).val('');

      });


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

      j('input[name=has_'+el+']').val(1);


    } // saveBtn

  } // globalValFunctions()