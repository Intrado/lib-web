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

      j('input[name=has_'+el+']').val(1);


      // Set Message tabs on review tab
      j('#msgsndr_review_'+el).parent().addClass('complete');


    } // saveBtn

  } // globalValFunctions()