jQuery.noConflict();
(function($) {
	// prototype to jquery event bridge
	var oldjQueryTrigger = $.event.trigger;
	var oldPrototypeFire = Element.fire;

	//trigger Prototype event handlers if jQuery fires an allowable Prototype custom event
	$.event.trigger = function(event, data, elem, onlyHandlers) {
		if (elem && event && typeof(event) === 'string' && event.indexOf(':') > 0) {
			if ($(elem).is(document)) {
				document.fire(event, data ? data[0] : null);
			} else {
				oldPrototypeFire(elem, event, data ? data[0] : null, !onlyHandlers);
			}
		}
		return oldjQueryTrigger(event, data, elem, onlyHandlers);
	};

	//trigger jQuery event handlers if Prototype fires a custom event
	Element.addMethods( {
		fire: function(element, eventName, memo, bubble) {
			if (eventName.indexOf(':') > 0) {
				oldjQueryTrigger(eventName, memo ? [memo] : null, element, !bubble);
			}
			return oldPrototypeFire(element, eventName, memo, bubble);
		}
	});
	
	$.fn.loadJobTypes = function(jobtypeid) {
		$this = this;
		// Get Type for drop down on inital page
		$.ajax({
			url : '/' + orgPath + '/api/2/users/' + userid + '/roles/' + userRoleId + '/settings/jobtypes',
			type : "GET",
            data : { "limit" : 1000 },
			dataType : "json",
			success : function(data) {
				sortTypes = data.jobTypes;
				jobTypes = [];

				for(var sortPr = 9; sortPr >= 1; sortPr--) {
					$.each(sortTypes, function(index, jobType) {
						if (jobType.priority == sortPr) {
							jobTypes.push(jobType);
						}
					});
				}

				$.each(jobTypes, function(index, jobType) {
					// exclude surveys ...
					if (jobType.isSurvey == false) {
						$this.append('<option value=' + jobType.id + '>' + jobType.name + '</option');
					}
				});

				if(jobtypeid) {
					$this.val(jobtypeid);
				}

			} // success
		});
	};


    /**
     * Saves or restores a memento for the form fields found below the current element(s).  If the data
     * parameter is undefined or not an object, returns the current state of each input as an object.
     * Otherwise, sets the state of each input based on the contents of the data object.
     * @param data null to save the current state, or an object where keys correspond to form field names to restore
     * @return when saving, an object with the current state of the matched form fields; when restoring, the jquery object
     */
    $.fn.memento = function(data) {
        var inputs = $(this).find(":input").get();

        if(data == null || typeof data != "object") {
            // return all data
            data = {};

            $.each(inputs, function() {
                if (typeof this.name !== 'undefined'
                        && (this.checked
                                    || /select|textarea/i.test(this.nodeName)
                        || /text|hidden|password/i.test(this.type))) {
                    data[this.name] = $(this).val();
                }
            });
            return data;
        } else {
            $.each(inputs, function() {
                if (typeof this.name !== 'undefined'
                        && typeof data[this.name] !== 'undefined') {
                    if(this.type == "checkbox" || this.type == "radio") {
                        $(this).prop("checked", (data[this.name] == $(this).val()));
                    } else {
                        $(this).val(data[this.name]);
                    }
                } else if (this.type == "checkbox") {
                    $(this).prop("checked", false);
                }
            });
            return $(this);
        }
    };
	
	// observe changes to the phone tts inputs and save to hidden field
	doCommonEventCallback($("#msgsndr_tts_message,input[name=messagePhoneText_message-gender]"), function() {
		var gender = $('input[name=messagePhoneText_message-gender]:checked').val();
		var enText = $('#msgsndr_tts_message').val();
		
		var jsonVal = $.toJSON({
			"gender" : gender,
			"text" : enText
		});

		$('#msgsndr_phonemessagetext').val(jsonVal);
		obj_valManager.runValidate($('#msgsndr_phonemessagetext'));
	});
	
	// observe changes to the feed subject/message inputs and save to hidden field
	doCommonEventCallback($("#msgsndr_form_rsstitle,#msgsndr_form_rssmsg"), function() {
		var subject = $("#msgsndr_form_rsstitle").val();
		var message = $("#msgsndr_form_rssmsg").val();
		
		$("#msgsndr_socialmediafeedmessage").val($.toJSON({ "subject": subject, "message": message }));
		obj_valManager.runValidate($('#msgsndr_socialmediafeedmessage'));
	});
	
	$(function() {
		// hide a few items
		$('.close, .facebook, .twitter, .feed, .error, div[id^="msgsndr_tab"]').hide();
		
		// initialise global variables
		orgFeatures = {};
		orgOptions = {};
		userPrefs = {};
		userPermissions = {};
		orgPath = window.location.pathname.split('/')[1]; // This gives us the the URL path needed for first part of AJAX call
		emailSubject = "";
		emailData = "";
		userRoleId = "";
		loadMsg = new $.loadMessage();
		loadMsg.init();
		recipientTrack = 0;

        mementos = {};
		
		//start up permission manager
		obj_permissionManager = new PermissionManager();
		
		obj_permissionManager.onPermissionsLoaded(function() {
			// ckeditor
			applyCkEditor('msgsndr_emailmessagetext');
			
			$(document).ready(function() {
				// subject
				if (subject)
					$('#msgsndr_name').val(subject).addClass("ok");
				
				// List Picker
				$('.add-recipients').listPicker({
					prepickedListIds: lists
				}).on("updated", function(listData) {
					recipientTrack = listData.numRecipients;
					
					if($("#msgsndr_addme").is(":checked")) {
						recipientTrack++;
					}
					
					obj_stepManager.updateStepStatus();
				});
				
				// jobtype
				$("#msgsndr_jobtype").loadJobTypes(jtid);
				
				// message group
				if (mgid != 0)
					loadMsg.loadMessageGroup(mgid);
			});
		});
		
		obj_permissionManager.getRoles();
		
		// start up validation manager
		obj_valManager = new ValidationManager();
		obj_valManager.init();
		//start up step manager
		obj_stepManager = new StepManager(obj_valManager);
		//start up content manager
		obj_contentManager = new ContentManager();
		//start up submit manager
		obj_submitManager = new SubmitManager();

		obj_stepManager.onStepChange(function(lastStep, nextStep) {
			//alert("stepChange! " + lastStep + " to " + nextStep);
			obj_valManager.forceRunValidate(nextStep);
		});

        obj_contentManager.onContentDiscard(function(contentMode) {
            var idSelector = "#msgsndr_tab_" + contentMode;

            //Hide various toggle-able HTML components, if they are currently open...
            //close up the translators
            if (contentMode == "phone" || contentMode == "email") {
                if ($(idSelector + " .toggle-translations").text().substring(0, 4) == "Hide") {
                    $(idSelector + " .toggle-translations").click();
                }
            }

            //close up the Post to ___ regions
            if (contentMode == "social") {
                $("input.social").each(function(index, element) {
                    if (element.checked) {
                        element.click();
                    }
                });
            }

            //Restore original form field states
            if (mementos[contentMode]) {
                $(idSelector).memento(mementos[contentMode]);
            }

            clearHtmlEditorContent();
        });


		obj_contentManager.onContentStart(function(contentMode) {
            if (!mementos[contentMode]) {
                mementos[contentMode] = $("#msgsndr_tab_" + contentMode).memento(null);
            }

            if(contentMode == "phone") {
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
				});*/
			} else if(contentMode == "email") {
				var emailSubject = $('#msgsndr_emailmessagesubject');
				var bSubject = $('#msgsndr_name').val();
				
				if (emailSubject.val().length == 0 && bSubject.length != 0) {
					emailSubject.val(bSubject).addClass('ok');
				}
			} else if(contentMode == "social" && $('#msgsndr_ctrl_phone').parent().hasClass('complete')) {
				
				var fieldinsertcheck = $('#msgsndr_phonemessagetext').val();
				if (fieldinsertcheck.indexOf('<<') == -1) {
					$('#audiolink').removeClass('hidden');
				} else {
					$('#audiolink').addClass('hidden');
				}
			}
			
			obj_valManager.forceRunValidate(2);
			//alert("contentModeStarted! " + contentMode);
		});
		
		obj_contentManager.onContentSave(function(contentMode) {
			//alert("contentModeSaved! " + contentMode);
		});
		
		//step 1
		obj_stepManager.gotoStep(1);
		
		//ADDITIONAL EVENT BINDING FOR CERTAIN ACTIVITIES
		// Switch Audio 
		$('#switchaudio button').on('click', function(event) {
			event.preventDefault();
			var type = $(this).attr('data-type');
			
			$('div.audio').hide();
			$('#'+type+'').show();
			$('#switchaudio button').removeClass('active');
			$(this).addClass('active');

			// set the type value in a hidden input for the postdata
			$('#msgsndr_phonemessagetype').attr('value', type);

			if ( type == 'callme') {
				$('#text .phone_advanced_options').appendTo('#callme_advanced_options');
			} else {
				$('#callme .phone_advanced_options').appendTo('#text_advanced_options');
			}

			obj_contentManager.updateContentStatus();
			
		});
		
		// Add myself toggle
		$('#msgsndr_addme').on('click', function() {
			//$('#list_ids').val('[addme]');//.addClass('ok');
			if($(this).is(":checked")) {
				recipientTrack++;
			} else {
				recipientTrack--;
			}
			
			$('#addme').slideToggle('slow', function() {
				if($('#msgsndr_addme').is(':checked')){
					if(userInfo.phone != '' && userInfo.email != '') {
			        	$('#msgsndr_addmephone').attr('value', userInfo.phoneFormatted);
			        	$('#msgsndr_addmeemail').attr('value', userInfo.email);
			        } else if(userInfo.phone != '' && userInfo.email == ''){
				        $('#msgsndr_addmephone').attr('value', userInfo.phoneFormatted);
				        $('#msgsndr_addmeemail').focus();
			        } else if(userInfo.phone == '' && userInfo.email != '') {
			        	$('#msgsndr_addmeemail').attr('value', userInfo.email);
				        $('#msgsndr_addmephone').focus();
			        }
				} else {
					$('#msgsndr_addmephone').attr('value', '');
					$('#msgsndr_addmeemail').attr('value', '');
				}
				
				obj_stepManager.updateStepStatus();
			});
		});
		
		// Toggle Collapse - Generic 
		$('.toggle-more').on('click', function(event) {
			event.preventDefault();

			var etarget = $(this).attr('data-target');
			$(etarget).slideToggle();
			$(this).toggleClass('active');

		});

		// Play audio on TTS
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
		});

		// Translations Toggle
		$('#tts_translate').on('click', "input.translations", function() {
			$(this).next().next('.controls').toggleClass('hide');
		});

		// Translations Toggle
		$('#email_translate').on('click', "input", function() {
			$(this).next().next('.controls').toggleClass('hide');
		});
		
		
	    // datepicker for scheduling
	   	// TODO: add facebook token expiry date 
		$("#msgsndr_scheduledate").datepicker({
			minDate: 0
		});
		
	    // set the schedule options startdate to today's date by default (uses moment.js)
	 	$('#msgsndr_scheduledate').val(moment().format('MM/DD/YYYY'));
	 	
		// modal windows -- script/bootstrap-modal.js
		$('#msgsndr_choose_list, #msgsndr_build_list, #msgsndr_saved_message, #schedule_options, #msgsndr_submit_confirmation').modal({
			show : false
		});
	});
})(jQuery);