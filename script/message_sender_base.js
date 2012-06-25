jQuery.noConflict();
(function($) {
	$.fn.loadJobTypes = function(jobtypeid) {
		$this = this;
		// Get Type for drop down on inital page
		$.ajax({
			url : '/' + orgPath + '/api/2/users/' + userid + '/roles/' + userRoleId + '/settings/jobtypes',
			type : "GET",
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
	
	$(function() {
		// hide a few items
		$('.close, .facebook, .twitter, .feed, .error, div[id^="msgsndr_tab"]').hide();
		
		// initialise global variables
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
		
		document.formvars = {
			broadcast : {
				subject : {}
			},
			phone : {},
			email : {},
			sms : {},
			social : {},
			addme : {}
		};
		
		getTokens();
		
		
		//start up permission manager
		obj_permissionManager = new PermissionManager();
		
		obj_permissionManager.onPermissionsLoaded(function() {
			// retreive a new serialnumber for the postdata form
			$.get('_messagesender.php?snum', function (data) {
				$('[name|=broadcast_formsnum]').val(data.snum);
			}, "json");
			
			// ckeditor
			applyHtmlEditor('msgsndr_form_body', true);
			
			$(document).ready(function() {
				// subject
				if (subject)
					$('#msgsndr_form_subject').val(subject).addClass("ok");
				
				// List Picker
				$('.add-recipients').listPicker({
					prepickedListIds: lists
				}).on("updated", function(listData) {
					recipientTrack = listData.numRecipients;
					
					if($("#msgsndr_form_myself").is(":checked")) {
						recipientTrack++;
					}
					
					obj_stepManager.updateStepStatus();
				});
				
				// jobtype
				$("#msgsndr_form_type").loadJobTypes(jtid);
				
				// message group
				if (mgid != 0)
					loadMsg.loadMessageGroup(mgid);
			});
		});
		
		obj_permissionManager.getRoles();
		
		// start up validation manager
		obj_valManager = new ValidationManager();
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
		
		obj_contentManager.onContentStart(function(contentMode) {
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
				var emailSubject = $('#msgsndr_form_mailsubject');
				var bSubject = $('#msgsndr_form_subject').val();
				
				if (emailSubject.val().length == 0 && bSubject.length != 0) {
					emailSubject.val(bSubject).addClass('ok');
				}
			} else if(contentMode == "social" && $('#msgsndr_ctrl_phone').parent().hasClass('complete')) {
				$('#audiolink').removeClass('hidden');
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
			$('#msgsndr_phonetype').attr('value', type);

			if ( type == 'callme') {
				$('#text .phone_advanced_options').appendTo('#callme_advanced_options');
			} else {
				$('#callme .phone_advanced_options').appendTo('#text_advanced_options');
			}

			obj_contentManager.updateContentStatus();
			
		});
		
		// Add myself toggle
		$('#msgsndr_form_myself').on('click', function() {
			//$('#list_ids').val('[addme]');//.addClass('ok');
			if($(this).is(":checked")) {
				recipientTrack++;
			} else {
				recipientTrack--;
			}
			
			$('#addme').slideToggle('slow', function() {
				if($('#msgsndr_form_myself').is(':checked')){
					if(userInfo.phone != '' && userInfo.email != '') {
			        	$('#msgsndr_form_mephone').attr('value', userInfo.phoneFormatted);
			        	$('#msgsndr_form_meemail').attr('value', userInfo.email);
			        } else if(userInfo.phone != '' && userInfo.email == ''){
				        $('#msgsndr_form_mephone').attr('value', userInfo.phoneFormatted);
				        $('#msgsndr_form_meemail').focus();
			        } else if(userInfo.phone == '' && userInfo.email != '') {
			        	$('#msgsndr_form_meemail').attr('value', userInfo.email);
				        $('#msgsndr_form_mephone').focus();
			        }
				} else {
					$('#msgsndr_form_mephone').attr('value', '');
					$('#msgsndr_form_meemail').attr('value', '');
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
		$("#schedule_datepicker").datepicker({
			minDate: 0
		});
		
	    // set the schedule options startdate to today's date by default (uses moment.js)
	 	$('#schedule_datepicker').val(moment().format('MM/DD/YYYY'));
	 	
		// modal windows -- script/bootstrap-modal.js
		$('#msgsndr_choose_list, #msgsndr_build_list, #msgsndr_saved_message, #schedule_options').modal({
			show : false
		});
	});
})(jQuery);