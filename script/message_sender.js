// jQuery for newui message sender
////////////////////////////////////

jQuery.noConflict();
(function($) { 
  $(function() {

    // Global Functions
    var notVal = new globalValidationFunctions();
		
		// Hiding stuff which is not needed
		$('#msg_section_2, #msg_section_3, .close, .facebook, .twitter, .feed, div[id^="msgsndr_tab"]').hide();
		//$('#audiolink').hide();

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
		      	notVal.reviewSend();
		      }

			}
		});


		$('.msg_content_nav a').on('click', function(event) {
			event.preventDefault();

			var elm = $(this).attr('id');
			var elm = elm.split('_');  			// I need number elm[2]

			$('.msg_content_nav li').removeClass('active').addClass('lighten');
			$('.tab_panel').hide();
			$('#msgsndr_tab_'+elm[2]).show();
			$(this).parent().removeClass('lighten').addClass('active');

			if (elm[2] == "phone") {

				// Show paste from email button if text-to-speech is clicked and emailData is not empty
				var emailBody = CKEDITOR.instances.reusableckeditor.document.$.body.innerText;
				if (emailBody != '') {
					$('button.paste-from').removeClass('hidden');
				}

				$('button.paste-from').on('click', function(e) {
					e.preventDefault();

					var pasteTo = $(this).attr('data-textarea');

					$('#'+pasteTo).val(emailBody);


				});

			}

			if (elm[2] == "email") {
				emSubject();
			}

			if (elm[2] == 'social' && $('#msgsndr_ctrl_phone').parent().hasClass('complete')) {
				$('#audiolink input').removeAttr('disabled');
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

		});


		// Add myself toggle -- TODO: Synchronise with the listpicker plugin
		$('#msgsndr_form_myself').on('click', function() {

			// call the populate lists function to add the lists id's in
			// populateRecipientIdList();

			$('#list_ids').val('[addme]').addClass('ok');

			$('#addme').slideToggle('slow', function() {
				$('#msgsndr_form_mephone').focus();
				notVal.watchSection('msg_section_1');
			});

		});

		

		// Toggle Collapse - Generic 
		$('.toggle-more').on('click', function(event) {
			event.preventDefault();

			var etarget = $(this).attr('data-target');
			$(etarget).slideToggle();

			offset = $(this).offset();
				$('html, body').animate({scrollTop: offset.top },500);

		});


		$('.playAudio').live('click', function(e) {
			e.preventDefault();

			if ($('#messagePhoneText_message-female').is(':checked')) {
				var gender = "female";
			} else {
				var gender = "male";
			}
			var txtArea = $(this).attr('data-text');
			var langC		= $(this).attr('data-code');
			var val 		= $('#'+txtArea).val();
		
			showPreview({gender:gender,text:val,language:langC});
		})


		// Translations Toggle
		$('.translations').on('click', "input", function() {

			$(this).next('.controls').toggleClass('hide');

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

      $('.window_panel').hide();
      $('#msg_section_'+tabn).show();

      if (tabn == "3") {
      	notVal.reviewSend();
      }

    });
  




		function emSubject() {

			if ( emailSubject == '') {
				$('#msgsndr_form_mailsubject').val($('#msgsndr_form_subject').val());
			} else {
				$('#msgsndr_form_mailsubject').val(emailSubject);
			}

			$('#msgsndr_form_mailsubject').on('change', function() {
				emailSubject = $(this).val();
			});

		}


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









// lists -- to be replaced by listbuilder
//////////////////////////////////////////

/*  Commenting out these functions for now, to be removed once addme is integrated with new listpicker
    // list id populator ...
    function populateRecipientIdList() {
      var listContainer = [];

      $(".list_row", "#msgsndr_list_info_tbody").each(function() {
        listContainer.push($(this).attr("data-id"));
      });

      if($("#msgsndr_form_myself").is(":checked")) {
        listContainer.push("addme");
      }

      var listStr = listContainer.join(",");
      $("#list_ids").val(listStr);
    };


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

			notVal.watchSection('msg_section_1');
			
		});
 
 		// add lists to the table of recipients, and add a hidden checkbox to the form for submission
 		$('#choose_list_add_btn').live("click", function() {

    	var listItems = $('.choose_lists_row > input:checkbox[name=choose_list_add]:checked');

    	$.each(listItems, function(index, listItem) {
    		var listHtml	= $(this);
    		var listTitle = $(this).attr('data-title');
    		var listId 		= $(this).attr('value');
    		
    		if ( listHtml.attr('disabled') != 'disabled') { // check if the list has been chosen already, if not, add it in...
    			$('#msgsndr_list_info_tbody').append('<tr class="list_row" data-id="' + listId + '"><td><a id="' + listId + '" class="removelist" title="Remove List"></a><a id="' + listId + '" class="savelist" title="Save List"></a></td><td>' + listTitle + '</td><td>[count]</td></tr>');
    			// listHtml.clone().appendTo('#msgsndr_list_choices'); // clone the input in the msgsndr_list_choices fieldset 

    			//$('#list_ids').val(listId);

    			listHtml.attr('disabled', true); // set the checkbox to disabled so users can't choose a list twice
    		}

    		$('#list_ids').addClass('ok');

    	});

    	notVal.watchSection('msg_section_1');
    	// close the modal and make sure the first panel and section is open...
    	$('#msgsndr_choose_list').modal('hide');
    	$('.msg_steps').find('li:eq(0)').addClass('active');
    	$('#msg_section_1').show();

    });
*/

  });
}) (jQuery);



// email attachment code (prototype.js)
///////////////////////////////////////

function startUpload(){
	$('upload_process').show();	
	return true;
}

function stopUpload(id,name,size,errormessage, formname, itemname) {
	if (!formname || !itemname) {
		return;
	}
	// stopUpload() is called automatically when the iframe is loaded, which may be before document.formvars is initialized by form_load().
	// In that case, just return.
	if (!document.formvars || !document.formvars[formname])
		return;
		
	setTimeout ("var uploadprocess = $(\'upload_process\'); if (uploadprocess) uploadprocess.hide();", 500 );
	
	
	var values = {};
	var fieldelement = $(itemname);
	var uploadedfiles = $("uploadedfiles");
	
	if (!fieldelement)
		return;
	var field = fieldelement.value;
	if(field != "") 
		values = field.evalJSON();
	if(id && name && size && !errormessage) {
		values[id] = {"size":size,"name":name};
	}
	
	// if there are attachments display the div that shows them
	if (Object.keys(values).length > 0)
		uploadedfiles.setStyle({"display":"block"}).update();
	else
		uploadedfiles.update().setStyle({"display":"none"});
	
	var str = "";
	for(var contentid in values) {
		var content = values[contentid];
		
		var downloadlink = new Element("a", {"href": "emailattachment.php?id="  + contentid +  "&name=" + encodeURIComponent(encodeURIComponent(content.name))});
		
		downloadlink.update(content.name);
		
		var sizeinfo = "&nbsp;(Size: " + Math.round(content.size/1024) + "k)&nbsp;";
		
		var removelink = new Element("a", {"href":"#"});
		
		removelink.update("Remove");
		
		removelink.observe("click", function(event, contentid, formname, itemname) {
			event.stop();
			removeAttachment(contentid, formname, itemname);
		}.bindAsEventListener(uploadedfiles, contentid, formname, itemname));
		uploadedfiles.insert(downloadlink).insert(sizeinfo).insert(removelink).insert("<br/>");				 		
	}

	fieldelement.value = Object.toJSON( $H(values) );
	
	if (errormessage) {
		form_validation_display($(itemname), "blank", errormessage);
	} else {
		form_do_validation($(formname), fieldelement);
	}
	return true;
}

function removeAttachment(id, formname, itemname) {
	if (!formname || !itemname)
		return;
	var values = $(itemname).value.evalJSON();
	delete values[id];
	
	// if there are attachments display the div that shows them
	var uploadedfiles = $("uploadedfiles");
	if (Object.keys(values).length > 0) {
		console.log('hi');
		uploadedfiles.setStyle({"display":"block"}).update();
	} else {
		uploadedfiles.update().setStyle({"display":"none"});
	}
	Object.keys(values).each(function (contentid) {
		var content = values[contentid];
		var contentname = content.name;
		
		uploadedfiles.insert(
			new Element("a", {"href": "emailattachment.php?id=" + contentid + "&name=" + encodeURIComponent(encodeURIComponent(contentname))}).insert(contentname)
		).insert(
			"&nbsp;(Size: " + Math.round(content.size / 1024) + "k)&nbsp;"
		).insert(
			new Element("a", {"href": "#"}).insert("Remove").observe("click", function(event, contentid, formname, itemname) {
				event.stop();
				removeAttachment(contentid, formname, itemname);
			}.bindAsEventListener(uploadedfiles, contentid, formname, itemname))
		).insert(
			"<br/>"
		);
	});
	$(itemname).value = Object.toJSON(values);
	form_do_validation($(formname), $(itemname));
}