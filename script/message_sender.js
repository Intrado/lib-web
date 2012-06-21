// general dom manipulation functions
///////////////////////////////////////

jQuery.noConflict();
(function($) { 
  $(function() {

    // Global Functions
    var global = new globalValidationFunctions();
		
		// Hiding stuff which is not needed
		$('#msg_section_2, #msg_section_3, .close, .facebook, .twitter, .feed, div[id^="msgsndr_tab"]').hide();
		//$('#audiolink').hide();
		$('#msg_section_2 .msg_confirm').addClass('hide');

		// Email Flag
		emailSubject 	= "";


		//  Hide/Show Logic - for message steps 
		$('.msg_steps a').on('click', function(event) {
			event.preventDefault();
			// Get the clicked elements id
			if ($(this).attr('data-active') != "false") {
				var elm = $(this).attr('id');
				// Gives me the number from the circle so can use it later on 
				var tabn = elm.split('_')[1];
				// Remove active class from tabs, and set selected one to have active class
				$('.msg_steps li').removeClass('active');
				$(this).parent().addClass('active');
				// Hide panels then show the one relevant to the users selection
				$('.window_panel').hide();
				$('#msg_section_'+tabn).show();

		      if (tabn == "3") {
		      	global.reviewSend();
		      }

			}
		});


		$('.msg_content_nav button').on('click', function(event) {
			event.preventDefault();

			j('#msg_section_2 .msg_confirm').addClass('hide');

			var elm = $(this).attr('id');
			var elm = elm.split('_');  			// I need number elm[2]

			$('.msg_content_nav li').removeClass('active').addClass('lighten');
			$('.tab_panel').hide();
			$('#msgsndr_tab_'+elm[2]).show();
			$(this).parent().removeClass('lighten').addClass('active');

			if (elm[2] == "phone") {

/*
Paste from email button: Low proirity: 
	Issues with getting text from CKEDITOR as comes through as HTML, need to strip this and 
	take in consideration for HTML elements like images, etc

				$('button.paste-from').addClass('hidden');

				// Show paste from email button if text-to-speech is clicked and emailData is not empty
				var emailBody = CKEDITOR.dom.element.createFromHtml(CKEDITOR.instances.reusableckeditor.getData());
				var emailBody = emailBody.getText();
				var emailBody = $.trim(emailBody);

				if (emailBody != '') {
					$('button.paste-from').removeClass('hidden');
				}

				$('button.paste-from').on('click', function(e) {
					e.preventDefault();

					var pasteTo = $(this).attr('data-textarea');

					$('#'+pasteTo).val(emailBody);
				});

*/
			}

			if (elm[2] == "email") {
				emSubject();
			}

			if (elm[2] == 'social' && $('#msgsndr_ctrl_phone').parent().hasClass('complete')) {
				$('#audiolink').removeClass('hidden');
			}


		});


		// Switch Audio 
		$('#switchaudio button').on('click', function(event) {
			event.preventDefault();
			var type = $(this).attr('data-type');
			
			$('div.audio').hide();
			$('#'+type+'').show();
			$('#switchaudio button').removeClass('active');
			$(this).addClass('active');

			// set the type value in a hidden input for the postdata
			$('#msgsndr_phonetype').attr('value', type);

			if ( type == 'callme') {
				$('#text .phone_advanced_options').appendTo('#callme_advanced_options');
			} else {
				$('#callme .phone_advanced_options').appendTo('#text_advanced_options');
			}
		});


		// Add myself toggle -- TODO: Synchronise with the listpicker plugin
		$('#msgsndr_form_myself').on('click', function() {

			// call the populate lists function to add the lists id's in
			// populateRecipientIdList();

			$('#list_ids').val('[addme]').addClass('ok');

			$('#addme').slideToggle('slow', function() {
				$('#msgsndr_form_mephone').focus();
				global.watchSection('msg_section_1');
			});

		});

		

		// Toggle Collapse - Generic 
		$('.toggle-more').on('click', function(event) {
			event.preventDefault();

			var etarget = $(this).attr('data-target');
			$(etarget).slideToggle();
			$(this).toggleClass('active');

		});


		$('.playAudio').live('click', function(e) {
			e.preventDefault();

			var Gender = 'male';
			if ($('#messagePhoneText_message-female').is(':checked')) {
				Gender = "female";
			}

			var textArea = $(this).attr('data-text');
			var langCode = $(this).attr('data-code');
			var textVal = $('#'+textArea).val();

			var previewdata = {
				gender: Gender,
				text: textVal,
				language: langCode
			};

			showPreview(previewdata);
		})


		// Translations Toggle
		$('#tts_translate').on('click', "input.translations", function() {
			
			$(this).next().next('.controls').toggleClass('hide');

			// $('#'+tArea).slideToggle();

		});

		// Translations Toggle
		$('#email_translate').on('click', "input.translations", function() {
			
			$(this).next().next('.controls').toggleClass('hide');

			// $('#'+tArea).slideToggle();

		});



		// Continue Buttons to move to next section
    $('button.btn_confirm').on('click', function(e) {
      e.preventDefault();

      var tabn = $(this).attr('data-next');
      var tabp = tabn-1;
      $('.msg_steps li').removeClass('active');
      $('a#tab_'+tabn).parent().addClass('active');
      $('a#tab_'+tabp).parent().addClass('complete');

      $('a#tab_'+tabn).attr('data-active','true');

      $('.window_panel').hide();
      $('#msg_section_'+tabn).show();

      if (tabn == "3") {
      	global.reviewSend();
      }

    });
    
    // datepicker for scheduling
   	// TODO: add facebook token expiry date 
		$( "#schedule_datepicker" ).datepicker({
			minDate: 0 
		});

    
    // todays date in US date format
    function todaysDate(){
      var today = new Date();
      var DD = today.getDate();
      var MM = today.getMonth();
      var YY = today.getFullYear();
      var todaysDate = MM+1 + '/' + DD + '/' + YY;
      return todaysDate;
    };


    // set the schedule options startdate to today's date by default (uses moment.js)
 		$('#schedule_datepicker').val(moment().format('MM/DD/YYYY'));

   



		function emSubject() {

			var emailSubject = $('#msgsndr_form_mailsubject');
			var bSubject = $('#msgsndr_form_subject').val();
			
			if (emailSubject.val().length == 0 && bSubject.length != 0) {
				emailSubject.val(bSubject).addClass('ok');
			}
		};

		// modal windows -- script/bootstrap-modal.js
		$('#msgsndr_choose_list').modal({
			show: false
		});
		
		$('#msgsndr_build_list').modal({
			show: false
		});	
		
		$('#msgsndr_saved_message').modal({
			show: false
		});
		
		$('#schedule_options').modal({
			show: false
		});
		

  });
}) (jQuery);
