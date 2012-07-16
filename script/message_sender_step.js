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
	
	this.getCurrentStep = function () {
		return currentStep;
	}
	
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
	
	this.updateStepStatus = function(step) {
		// step 1 is special, you must have a list or have "addme"
		if (step == 1 && ($("#msgsndr_listids").val() == "[]" || $("#msgsndr_listids").val() == "") && 
				!$("#msgsndr_addme").is(":checked")) {
			$('button.btn_confirm', stepMap[step]).attr('disabled','disabled');
			return;
		}
		// step 2 is special, you must have atleast a phone, email or sms message
		if (currentStep == 2 && !$("#msgsndr_hasphone").is(":checked") && !$("#msgsndr_hasemail").is(":checked") &&
				!$("#msgsndr_hassms").is(":checked")) {
			$('button.btn_confirm', stepMap[step]).attr('disabled','disabled');
			return;
		}
			
		obj_valManager.validateStep(currentStep, false, function (passed) {
			if(passed) {
				$("button.btn_confirm", stepMap[step]).removeAttr('disabled');
			} else {
				$('button.btn_confirm', stepMap[step]).attr('disabled','disabled');
			}
		});
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
		if(obj_contentManager.isEditing()){
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
			// stop observing old step validation
			obj_valManager.offFormEventHandler(currentStep);
			
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
			
			//RUN ON CHANGE EVENTS
			$.each(eventManager.onStepChange, function(eIndex, eEvent) {
				eEvent(currentStep, step);
			});
			
			if(currentStep == 3) {
				self.preSubmitConfig();
			}

			// observe validation on the items in step 1, update status appropriatly
			if (currentStep == 1) {
				obj_valManager.onFormEventHandler(currentStep, false, function (event, memo) {
					self.updateStepStatus(step);
				});
			}
			self.updateStepStatus(step);
		}
	};
	
	this.preSubmitConfig = function() {
		$('.review_subject p').text($('#msgsndr_name').val());
		$('.review_type p').text($('#msgsndr_jobtype option:selected').text());
		
		$("#schedule_broadcast").text("Send to " + recipientTrack + " Recipients");
		$("#send_now_broadcast").text("Send Now to " + recipientTrack + " Recipients");
		$('.review_count p').text(recipientTrack);

		$('#schedule_options .warning').remove();
		
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
			$('#msgsndr_schedulecallearly option[value="'+usercallearly+'"]').attr('selected','selected');
			$('#msgsndr_schedulecallearly option[value="'+rolecallearly+'"]').prevAll().remove();
			$('#msgsndr_schedulecalllate option[value="'+rolecallearly+'"]').prevAll().remove();

		} else if (usercallearly != false && rolecallearly == false) {
			// select the users preference and leave all the times available for selection
			$('#msgsndr_schedulecallearly option[value="'+usercallearly+'"]').attr('selected','selected');

		} else if (usercallearly == false && rolecallearly != false) {
			// select the role permission and remove all earlier times
			$('#msgsndr_schedulecallearly option[value="'+rolecallearly+'"]').attr('selected','selected').prevAll().remove();
			$('#msgsndr_schedulecalllate option[value="'+rolecallearly+'"]').prevAll().remove();

		} else {
			// select the default time and leave all times as options
			$('#msgsndr_schedulecallearly option[value="'+defaultcallearly+'"]').attr('selected','selected');
		}

		// set up values in the calllate select ...
		if (usercalllate != false && rolecalllate != false) {
			// select the users preference and remove later times than the role preference
			$('#msgsndr_schedulecalllate option[value="'+usercalllate+'"]').attr('selected','selected');
			$('#msgsndr_schedulecalllate option[value="'+rolecalllate+'"]').nextAll().remove();
			$('#msgsndr_schedulecallearly option[value="'+rolecalllate+'"]').nextAll().remove();

		} else if (usercalllate != false && rolecalllate == false) {
			// select the users preference and leave all the times available for selection
			$('#msgsndr_schedulecalllate option[value="'+usercalllate+'"]').attr('selected','selected');
			
		} else if (usercalllate == false && rolecalllate != false) {
			// select the role permission and remove all later times
			$('#msgsndr_schedulecalllate option[value="'+rolecalllate+'"]').attr('selected','selected').nextAll().remove();
			$('#msgsndr_schedulecallearly option[value="'+rolecalllate+'"]').nextAll().remove();

		} else {
			// select the default time and leave all times as options
			$('#msgsndr_schedulecalllate option[value="'+defaultcalllate+'"]').attr('selected','selected');
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

		var callearlytime = moment(serverDate+rolecallearly).unix();
		var calllatetime  = moment(serverDate+rolecalllate).unix();

		var timecheck = timeUpdate();
		// compare times
		// var isTooEarly    = timecheck <= callearlytime;
		// var isTooLate     = timecheck >= calllatetime;
		if (timecheck <= callearlytime) {
			var isTooEarly = true
		} else {
			var isTooEarly = false
		}
		if (timecheck >= calllatetime) {
			var isTooLate = true
		} else {
			var isTooLate = false
		}

		if(isTooEarly == true || isTooLate == true){
			$('#send_now_broadcast').attr('disabled','disabled');
			if (isTooLate == true) {
				var addOneDay = moment().add('days',1); 
				var futureDay = addOneDay.format('MM/DD/YYYY');
				$('#msgsndr_scheduledate').val(futureDay);
				$('#msgsndr_scheduledate').parent().parent().prepend('<p class="warning">Note: Start Date has been adjusted for tomorrow due to the limitations of your account</p>');
			}
		}

		// Populate the max attempts select box
		var maxAttempts = userPrefs.callmax;
		$('#msgsndr_form_maxattempts').empty();
		for(i=1;i<=maxAttempts;i++) {
			$('#msgsndr_form_maxattempts').append('<option value="'+i+'">'+i+'</option>');
		};
		
		// check callearly time for day/night
		$("#msgsndr_schedulecallearly").on("change", function(){
			var el = $("#msgsndr_schedulecallearly option:selected");
			changeTimeSelectNote("#msgsndr_schedulecallearly", el.val());
		});
		
		// check calllatetime for day/night
		$("select#msgsndr_schedulecalllate").on("change", function(){
			var el = $("#msgsndr_schedulecalllate option:selected");
			changeTimeSelectNote("#msgsndr_schedulecalllate", el.val());
		});
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
		var btn = $(this);
		var oldname = btn.html();
		btn.text("Validating...");
		btn.attr("disabled","disabled");
		//NEXT STEP ID
		var getStepId = $(this).attr('data-next');
		validationManager.validateStep(currentStep, false, function (passed) {
			btn.html(oldname);
			btn.removeAttr("disabled");
			// check that there are no validation errors
			if (passed) {
				//SWITCH STEP
				self.gotoStep(getStepId);
			} else {
				alert("Some fields failed validation!");
			}
		});
	});
	
	//BIND STEP 3 SAVE MESSAGE
	$('#msgsndr_optionsavemessage').on('click', function() {
		var saveMsgName = $('#msgsndr_optionsavemessagename');
		if ($(this).attr('checked') == 'checked') {
			saveMsgName.removeAttr('disabled');
			var bSubject = $('#msgsndr_name').val();
			$('#msgsndr_optionsavemessagename').val(bSubject).focus();
			
		} else {
			saveMsgName.attr('disabled', 'disabled');
			$('#msgsndr_optionsavemessagename').val('');
		}
	});
};