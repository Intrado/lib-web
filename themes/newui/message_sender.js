/*
 * jQuery for newui message sender
 ***********************************
 */

jQuery.noConflict();
(function($) { 
  $(function() {
		
		// Hiding stuff which is not needed
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


		// get the available lists from lists.php
    loadLists = function() {
      $.ajax({
        type: 'GET',
        url: "lists.php?ajax=true&filter=filter&pagestart=activepage",
        success: function(response) {
            var lists = response.list;
            $.each(lists, function(index, list) {
            	$('#lists_list').append('<li class="choose_lists_row"><input type="checkbox" id="' + list.itemid + '" name="choose_list_add" value="' + list.itemid + '" data-title="' + list.title + '"/><label for="choose_list_add">' + list.title + '</label></li>');       
            });
          }
      });
    };
    
    // load the lists into the modal window ...
    loadLists();

    
    // remove lists from the table of recipients
		$('.removelist').live("click", function(){
			var listId 			= $(this).attr('id');
			var listRow 		= $(this).closest('.list_row');
			var listsHidden	= $('#msgsndr_list_choices').children();
			if ( listRow.attr('data-id') == listId ) {
				listRow.remove();
			}
			$.each( listsHidden, function(index, listHidden) {
				console.log(listHidden);
				if ( $(this).attr('id') == listId ) {
					// remove the input from the hidden fieldset
					$('#msgsndr_list_choices > input[name=choose_list_add][id=' + listId +']').remove();
					// revert the list item in the modal to its unchosen state
					$('.choose_lists_row > input[name=choose_list_add][id=' + listId +']').attr('disabled', false).attr('checked', false);
				}
			});
			
		});
 
 		// add lists to the table of recipients, and add a hidden checkbox to the form for submission
 		$('#choose_list_add_btn').live("click", function() {
    	var listItems = $('.choose_lists_row > input:checkbox[name=choose_list_add]:checked');
    	$.each(listItems, function(index, listItem) {
    		var listHtml	= $(this);
    		var listTitle = $(this).attr('data-title');
    		var listId 		= $(this).attr('value');
    		console.log(listHtml);
    		// check if the list has been chosen already, if not, add it in...
    		if ( listHtml.attr('disabled') != 'disabled') {
    			$('#msgsndr_list_info_tbody').append('<tr class="list_row" data-id="' + listId + '"><td><a id="' + listId + '" class="removelist" title="Remove List"></a><a id="' + listId + '" class="savelist" title="Save List"></a></td><td>' + listTitle + '</td><td>[count]</td></tr>');
    			// clone the input in the msgsndr_list_choices fieldset 
    			listHtml.clone().appendTo('#msgsndr_list_choices');
    			// set the checkbox to disabled so users can't choose a list twice
    			listHtml.attr('disabled', true);	
    		}
    	});
    	// shut that modal down and make sure the first panel is open...
    	$('#msgsndr_choose_list').modal('hide');
    	$('.msg_steps').find('li:eq(0)').addClass('active');
    	$('#msg_section_1').show();

    });
   


  });
}) (jQuery);