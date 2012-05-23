jQuery.noConflict();
(function($) { 
  $(function() {


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
      var sPhone = data[0].accessProfile.permissions[5].sendphone;
      var sEmail = data[0].accessProfile.permissions[7].sendemail;
      var sSMS   = data[0].accessProfile.permissions[30].sendsms;


      // Get Type for drop down on inital page
      $.ajax({
        url: "message_sender/jobtypes.json",
        type: "GET",
        isLocal: true,
        dataType: "json",
        success: function(data) {
          
          jobTypes = data[0]['jobTypes'];

          $.each(jobTypes, function(index, jobType) {  // get id jobTypes[0]['jobTypes'][0]['id']

            $('#msgsndr_form_type').append('<option value='+jobType.id+'>'+jobType.info+'</option');

          });

        }
 
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

    }


    function sendPhone() {

      $('li.ophone').removeClass('notactive');

    }

    function sendEmail() {

      $('li.oemail').removeClass('notactive');
    }

    function sendSMS() {

      $('li.osms').removeClass('notactive');

    }

    
  	$('.error, #ctrecord').hide();


    document.formvars = { 
      broadcast: {
        subject:[
          new document.validators["ValRequired"]("broadcast_subject","Subject",{}), 
          new document.validators["ValLength"]("broadcast_subject","Subject",{min:7,max:30})
        ]
      },
      phone: {
        number: [
          new document.validators["ValRequired"]("content_phone","Number to Call",{}),
          new document.validators["ValPhone"]("content_phone","Number to Call",{})
        ]
      },
      email: {
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
      },
      sms: {
        text: [
          new document.validators["ValRequired"]("sms_text","SMS",{}),
          new document.validators["ValLength"]("sms_text","SMS",{max:160})
        ]
      }
    };


    watch = '#msgsndr_form_subject, #msgsndr_form_number, #msgsndr_form_name, #msgsndr_form_email, #msgsndr_form_mailsubject, #msgsndr_form_body';

  	$(watch).on('keyup', function() {

			var elem	=	$(this);
			form_val(elem);

		});


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

				$.ajax({
					type: 'POST',
					url: "message_sender.php?form=broadcast&ajaxvalidator=true&formitem=broadcast_subject",
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






    // Character Count
    $('#msgsndr_form_sms').on('keyup', function() {
      charCount(this, '160', '.sms.characters');
    });

    $('#msgsndr_form_fbmsg').on('keyup', function() {
      charCount(this, '420', '.fb.characters');
    });

    $('#msgsndr_form_tmsg').on('keyup', function() {
      charCount(this, '140', '.twit.characters');
    })


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
