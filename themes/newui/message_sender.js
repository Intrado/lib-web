/*
 * jQuery for newui message sender
 ***********************************
 */

jQuery.noConflict();
(function($) { 
  $(function() {
		
		// Hiding stuf which is not needed
		$('#msg_section_2, #msg_section_3, .close, .facebook, .twitter, .feed').hide();


		// Tab Hide/Show Logic
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
			}
		});


		// hide the +Add ... content for the message content section 
		$('div[id^="msgsndr_tab"]').hide();

		$('.msg_content_nav a').on('click', function(event) {
			event.preventDefault();

			var elm = $(this).attr('id');
			var elm = elm.split('_');  			// I need number elm[2]

			$('.msg_content_nav li').removeClass('active');
			$('.tab_panel').hide();
			$('#msgsndr_tab_'+elm[2]).show();
			$(this).parent().addClass('active');

			if (elm[2] == 'email') {
				$('#msgsndr_form_mailsubject').val($('#msgsndr_form_subject').val());
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
		});


		// Add myself toggle 
		$('#msgsndr_form_myself').on('click', function() {

			$('#addme').slideToggle('slow', function() {
				$('#msgsndr_form_mephone').focus();
			});

		});

		

		// Toggle Collapse - Generic 
		$('.toggle-more').on('click', function(event) {
			event.preventDefault();

			var etarget = $(this).attr('data-target');
			$(etarget).slideToggle();

			offset = $(this).offset();
				$('html, body').animate({scrollTop: offset.top },2000);

		});



		// Social Inputs hide show
		$('input.social').on('click', function(event) {

			var elem = $(this).attr('id');
			var elem = elem.split('_') 				// Going to be using elem[2]

			$('.'+elem[2]).slideToggle('slow', function() { 

				if (elem[2] == 'feed') { // if Post to Feeds set focus to the Post title input
					$('#msgsndr_form_rsstitle').focus();
				} else { // Set focus to the textarea
					$('.'+elem[2]+' textarea').focus();
				}
				
			});

			// This scrolls the page up to bring the element that has just opened into view
			offset = $(this).offset();
			$('html, body').animate({scrollTop: offset.top },2000);

		});


		// Spell Checker
		$(".loading").hide();

		$("#sms_sc").click(function(e){
			e.preventDefault();
			$(".loading").show();

			$("#msgsndr_form_sms")
			.spellchecker({
				lang: "en",
				engine: "google",
				suggestBoxPosition: "above"
			})
			.spellchecker("check", function(result){

				// spell checker has finished checking words
				$(".loading").hide();

				// if result is true then there are no badly spelt words
				if (result) {
					alert('There are no incorrectly spelt words.');
				}
			});
		});


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

  });
}) (jQuery);