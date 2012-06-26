var stepMap = {
	"1" : "#msg_section_1",
	"2" : "#msg_section_2",
	"3" : "#msg_section_3"
};

function StepManager(_valManager) {
	var $ = jQuery;
	var self = this;
	var currentStep = 0;
	var validationManager = _valManager;
	
	var eventManager = {
		beforeStepChange: [],
		onStepChange: []
	};
	
	this.beforeStepChange = function(callback) {
		//callback(laststep, nextstep)
		eventManager.beforeStepChange.push(callback);
		return eventManager.beforeStepChange.length - 1;
	};
	
	this.unbindBeforeStepChange = function(id) {
		eventManager.beforeStepChange.splice(id, 1);
	};
	
	this.onStepChange = function(callback) {
		//callback(laststep, nextstep)
		eventManager.onStepChange.push(callback);
		return eventManager.onStepChange.length - 1;
	};
	
	this.unbindOnStepChange = function(id) {
		eventManager.onStepChange.splice(id, 1);
	};
	
	this.updateStepStatus = function() {
		var readyForSave = true;
		
		if(currentStep == 1) {
			if(recipientTrack == 0) {
				readyForSave = false;
			}
		}
		
		if(currentStep == 2) {
			if($(".msg_content_nav > li.complete").not(".osocial").size() == 0) {
				readyForSave = false;
			}
		} else {
			if($(".er:visible").size() > 0) {
				readyForSave = false;
			}
		}
		/*$(".required:visible", stepMap[currentStep]).each(function() {
			if(!$(this).hasClass("ok")) {
				readyForSave = false;
			}
		});*/
		
		if(readyForSave) {
			$("button.btn_confirm", stepMap[currentStep]).removeAttr('disabled');
		} else {
			$('button.btn_confirm', stepMap[currentStep]).attr('disabled','disabled');
		}
	};
	
	this.gotoStep = function(step) {
		//work with steps as strings
		if(typeof(step) == "number") {
			step = step.toString();
		}
		
		//DONT SWITCH FOR SAME STEP
		if(currentStep == step) {
			return false;
		}
		
		var stopSwitch = false;
		
		//STOP SWITCH IF MOVING FORWARD WITHOUT COMPLETE STEP
		if(!currentStep == 0 && step > currentStep && typeof($("button.btn_confirm", stepMap[currentStep]).attr("disabled")) != "undefined") {
			stopSwitch = true;
		}
		
		//STOP SWITCH IF CONTENT IS BEING EDITED
		if(!$(".msg_confirm").is(":visible")){
			stopSwitch = true;
		}

		//RUN BEFORE EVENTS
		$.each(eventManager.beforeStepChange, function(eIndex, eEvent) {
			var result = eEvent(currentStep, step);
			if(typeof(result) == "boolean" && result == false) {
				stopSwitch = true;
			}
		});
		
		if(!stopSwitch) {
			//UNBIND VAL
			validationManager.unbindValidations(currentStep);
			
			//SWITCH STEP
			$("[id^=msg_section_]").hide();
			$('.msg_steps li').removeClass('active');
			//ONLY COMPLETE IF MOVING FORWARD
			if(step > currentStep) {
				$('a#tab_' + currentStep, ".msg_steps").parent().addClass('complete');
			}
			currentStep = step;
			$(stepMap[currentStep]).show();
			$("a#tab_" + currentStep, ".msg_steps").parent().addClass('active').addClass("allow");
			
			//BIND VALIDATIONS
			validationManager.bindValidations(currentStep);
			
			//RUN ON CHANGE EVENTS
			$.each(eventManager.onStepChange, function(eIndex, eEvent) {
				eEvent(currentStep, step);
			});
			
			if(currentStep == 3) {
				self.preSubmitConfig();
			}
		}
	};
	
	this.preSubmitConfig = function() {
		$('.review_subject p').text($('#msgsndr_form_subject').val());
		$('.review_type p').text($('#msgsndr_form_type option:selected').text());
		
		$("#schedule_broadcast").text("Send to " + recipientTrack + " Recipients");
		$("#send_now_broadcast").text("Send Now to " + recipientTrack + " Recipients");
		$('.review_count p').text(recipientTrack);
		
		/*
		if a user has a callearly/calllate preference (in userPrefs), make them selected
		show all the time options from the preference set in userPermissions
		if no preferences are set in userPermissions, show all times. 
		 */

		defaultcallearly = '8:00 am';
		defaultcalllate = '5:00 pm';
		usercallearly = false;
		rolecallearly = false;
		usercalllate = false;
		rolecalllate = false;

		// checking if the user and role preferences exist, and if they contain any data
		if (typeof(userPrefs.callearly) != 'undefined' && userPrefs.callearly != ''){
			usercallearly = userPrefs.callearly;
		}
		if (typeof(userPermissions.callearly) != 'undefined' && userPermissions.callearly != ''){
			rolecallearly = userPermissions.callearly;
		}
		if (typeof(userPrefs.calllate) != 'undefined' && userPrefs.calllate != ''){
			usercalllate = userPrefs.calllate;
		}
		if (typeof(userPermissions.calllate) != 'undefined' && userPermissions.calllate != ''){
        	rolecalllate = userPermissions.calllate;
		}

		// set up values in the callearly select ...
		if (usercallearly != false && rolecallearly != false) {
			// select the users preference and remove earlier times than the role preference
			$('#schedulecallearly option[value="'+usercallearly+'"]').attr('selected','selected');
			$('#schedulecallearly option[value="'+rolecallearly+'"]').prevAll().remove();
			$('#schedulecalllate option[value="'+rolecallearly+'"]').prevAll().remove();

		} else if (usercallearly != false && rolecallearly == false) {
			// select the users preference and leave all the times available for selection
			$('#schedulecallearly option[value="'+usercallearly+'"]').attr('selected','selected');

		} else if (usercallearly == false && rolecallearly != false) {
			// select the role permission and remove all earlier times
			$('#schedulecallearly option[value="'+rolecallearly+'"]').attr('selected','selected').prevAll().remove();
			$('#schedulecalllate option[value="'+rolecallearly+'"]').prevAll().remove();

		} else {
			// select the default time and leave all times as options
			$('#schedulecallearly option[value="'+defaultcallearly+'"]').attr('selected','selected');
		}

		// set up values in the calllate select ...
		if (usercalllate != false && rolecalllate != false) {
			// select the users preference and remove later times than the role preference
			$('#schedulecalllate option[value="'+usercalllate+'"]').attr('selected','selected');
			$('#schedulecalllate option[value="'+rolecalllate+'"]').nextAll().remove();
			$('#schedulecallearly option[value="'+rolecalllate+'"]').nextAll().remove();

		} else if (usercalllate != false && rolecalllate == false) {
			// select the users preference and leave all the times available for selection
			$('#schedulecalllate option[value="'+usercalllate+'"]').attr('selected','selected');
			
		} else if (usercalllate == false && rolecalllate != false) {
			// select the role permission and remove all later times
			$('#schedulecalllate option[value="'+rolecalllate+'"]').attr('selected','selected').nextAll().remove();
			$('#schedulecallearly option[value="'+rolecalllate+'"]').nextAll().remove();

		} else {
			// select the default time and leave all times as options
			$('#schedulecalllate option[value="'+defaultcalllate+'"]').attr('selected','selected');
		}

		// skip duplicates checkbox show / hide based on saved phone or email content
		if ($('.msg_content_nav .ophone').hasClass('complete') == true || $('.msg_content_nav .oemail').hasClass('complete') == true){
			if ($('#skip_duplicates').hasClass('hidden') == true){
				$('#skip_duplicates').removeClass('hidden');
			} else {
				$('#skip_duplicates').addClass('hidden');
			}
		} else {
			if ($('#skip_duplicates').hasClass('hidden') == false){
				$('#skip_duplicates').addClass('hidden');
			}
		}

		// Populate the max attempts select box
		var maxAttempts = userPrefs.callmax;
		$('#msgsndr_form_maxattempts').empty();
		for(i=1;i<=maxAttempts;i++) {
			$('#msgsndr_form_maxattempts').append('<option value="'+i+'">'+i+'</option>');
		};
	};
	
	//BIND STEP BUTTONS
	$('a', '.msg_steps').on('click', function(event) {
		event.preventDefault();
		//NEXT STEP ID
		var getStepId = $.trim($(".icon", this).text());
		//SWITCH STEP
		//STOP IF TRYING TO PROCEED TO A NEW STEP WITHOUT CONTINUE USE
		var $checkStepState = $(this).closest("li");
		if($checkStepState.hasClass("allow") || $checkStepState.hasClass("complete")) {
			self.gotoStep(getStepId);
		}
	});
	
	//BIND CONTINUE BUTTONS
	$('button.btn_confirm').on('click', function(e) {
		e.preventDefault();
		//NEXT STEP ID
		var getStepId = $(this).attr('data-next');
		//SWITCH STEP
		self.gotoStep(getStepId);
	});
	
	//BIND STEP 3 SAVE MESSAGE
	$('#save_later').on('click', function() {
		var saveMsgName = $('input[name=options_savemessagename]');
		if ($(this).attr('checked') == 'checked') {
			saveMsgName.removeAttr('disabled');
			var bSubject = $('#msgsndr_form_subject').val();
			$('input[name=options_savemessagename]').val(bSubject).focus();
			
		} else {
			saveMsgName.attr('disabled', 'disabled');
			$('input[name=options_savemessagename]').val('');
		}
	});
};