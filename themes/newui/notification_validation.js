jQuery.noConflict();
(function($) { 
  $(function() {


    $('.error, #ctrecord').hide();

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
        url: "message_sender/roles.json",
        type: "GET",
        isLocal: true,
        dataType: "json",
        success: function(data) {
          //dd = data;
          setUp(data); // Send Data over to the function setUp();
       }
    });

    /* --
      The setUp function will do alot of the inital work, and call other functions based on users roles
    -- */

    function setUp(data) {

      // Setting variables from data passed through from the initial ajax call above ^
      var sPhone      = data[0].accessProfile.permissions[5].sendphone;
      var sEmail      = data[0].accessProfile.permissions[7].sendemail;
      var sSMS        = data[0].accessProfile.permissions[30].sendsms;
      var sFacebook   = data[0].accessProfile.permissions[42].facebookpost;
      var sTwitter    = data[0].accessProfile.permissions[43].twiiterpost;
      var sFeed       = data[0].accessProfile.permissions[45].feedpost;


      // Get Type for drop down on inital page
      $.ajax({
        url: "message_sender/jobtypes.json",
        type: "GET",
        isLocal: true,
        dataType: "json",
        success: function(data) {
          
          var jobTypes = data;

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

      if (sPhone == 1) {
        sendPhone();
      }

      if (sEmail == 1 ) {
        sendEmail();
      }

      if (sSMS == 1) {
        sendSMS();
      }

      if (sFacebook == 1 || sTwitter == 1 || sFeed == 1) {

        $('li.osocial').removeClass('notactive');

        if (sFacebook == 1) {
          socialFB();
        }
        if (sTwitter == 1) {
          socailTwitter();
        }
        if (sFeed == 1) {
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
    }

    function sendEmail() {

      $('li.oemail').removeClass('notactive');

      document.formvars['email'] = {
        name: [
          new document.validators["ValRequired"]("email_name","Name",{}), 
          new document.validators["ValLength"]("email_name","Name",{min:7,max:30})
        ],
        address: [
          new document.validators["ValRequired"]("email_address","Email Address",{}), 
          new document.validators["ValEmail"]("email_address","Email Address",{min:7,max:30})
        ],
        subject: [
          new document.validators["ValRequired"]("email_subject","Subject",{}), 
          new document.validators["ValLength"]("email_subject","Subject",{min:7,max:30})
        ],
        body: [
          new document.validators["ValRequired"]("email_body","Subject",{}), 
          new document.validators["ValLength"]("email_body","Subject",{min:7,max:30})
        ]
      }

      watchFields('#msgsndr_form_name, #msgsndr_form_email, #msgsndr_form_mailsubject, #msgsndr_form_body');

    }

    function sendSMS() {

      $('li.osms').removeClass('notactive');

      document.formvars['sms'] = {
        text: [
          new document.validators["ValRequired"]("sms_text","SMS",{}),
          new document.validators["ValLength"]("sms_text","SMS",{max:160})
        ]
      };

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

      // Character Count
      $('#msgsndr_form_tmsg').on('keyup', function() {
        charCount(this, '140', '.twit.characters');
      });

    }

    function socialFeed() {

      $('div[data-social=feed]').removeClass('hidden');

    }


  


    function watchFields(fieldId) {

      var watch = watch + ', ' + fieldId;

  	  $(watch).on('keyup', function() {
		    var elem	=	$(this);
			  form_val(elem);
		  });

    }

		function form_val(element) {

			var name  = element.attr('name');
      var form  = name.split("_")[0];
      var field = name.split("_")[1];

			var value = element.val();

      var isValid = true;
			var validators = document.formvars[form][field];
			requiredvalues = [];

			if (element.attr('data-ajax') == 'true') {

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
              element.removeClass('ok').addClass('er').next('.error').show().text(res);
							break; 
						} else {
              element.removeClass('er').addClass('ok').next('.error').hide();
            }
					} 

				} // for

          if (res == true && field == "number") {
            $('#ctrecord').show();
          } 
          

			} // if ajax

		} // form_val function



    function charCount(elem, limit, text) {
       
      var e = $(elem);
      var status = $(text);
      var remaining = limit - e.val().length;

      if (remaining < 0) {
        e.addClass('er');
        status.text("Too many characters " + (0 - remaining));
      } else {
        status.text(remaining + " Characters left");
      }

    }




	});
}) (jQuery);
