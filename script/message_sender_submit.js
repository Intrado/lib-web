function SubmitManager() {
	var $ = jQuery;

	// this will set the schedule options to send a broadcast out immediately
	scheduleNow = function(onehour) {

		$('#msgsndr_scheduledate').val(moment().format('MM/DD/YYYY'));
		$('#msgsndr_schedulecallearly').append('<option value="'+moment().format('h:mm a')+'" selected="selected">'+moment().format('h:mm a')+'</option>');

		$('#msgsndr_schedulecalllate option').removeAttr('selected')

		// User is submitting a job within the hour window so we need to give the post data and hour gap
		if (typeof onehour != undefined && onehour == true) { 
			$('#msgsndr_schedulecalllate option:last-child').attr('selected', 'selected');
		} else {
			$('#msgsndr_schedulecalllate option:last-child').attr('selected', 'selected');
		}
	};

	// Send Message Buttons -- section 3 on main page and schedule modal
	$('.submit_broadcast').on('click', function(e) {
		e.preventDefault();

		//if the send now button is clicked
		if( $(this).attr('id') == 'send_now_broadcast'){
			// check for role permissions settings
			if (rolecallearly != false && rolecalllate != false){
				// getting the time now, and setting up a moment object with it. 
				// reset the date to the epoch first -- we don't have a date with the settings times 
				var timenow       = moment().format('h:mm a');
				var timecheck     = moment('1970,01,01,'+timenow).unix();
				var callearlytime = moment('1970,01,01,'+rolecallearly).unix();
				var calllatetime  = moment('1970,01,01,'+rolecalllate).unix();

				// compare times
				var isTooEarly    = timecheck <= callearlytime;
				var isTooLate     = timecheck >= calllatetime;

				// hour check - need to check if the difference in time between calllatetime and timecheck
				// onehour is set to false if they have more than an hour window available, else it's true
				var onehour = true;
				var hourcheck = (timecheck - calllatetime) / 3600;
				if ( hourcheck > 1) {
					var onehour = false 
				}

				if(isTooEarly == false && isTooLate == false){
					// send now if the timecheck is passed
					scheduleNow(onehour);
				} else if(isTooEarly == true || isTooLate == true){
					alert("You do not have authorization to send broadcasts at this time, please schedule your broadcast.");
					// return so the postdata isn't submitted.
					return;
				} 
			} else {
				// no role settings for callearly/calllate, we're good to go!
				scheduleNow();
			}
		}
		
		// FIXME: check over all the inputs and clear any that are empty objects
		$("input").each(function (index, input) {
			var value = $(input).val();
			if (value == "[]" || value == "{}")
				$(input).val("");
		})
		
		var result = form_submit(e, 'submit', document.getElementById('msgsndr'));
	});
};

(function($) {
	// overload form.js.php behavior for handling submit
	form_handle_submit = function(form,event) {
		var formvars = document.formvars[form.name];
		
		//don't allow more than one submit at a time
		if (formvars.submitting)
			return;
		formvars.submitting = true;
		
		//stop any pending validations from occuring
		if (formvars.keyuptimer) {
			window.clearTimeout(formvars.keyuptimer);
		}
		
		//prep an ajax call with entire form contents and post back to server
		//server side will validate
		//if successful, results with have some action to take and/or code
		//otherwise responce has validation results for each item,
		//update each element's msg area, and throw up an alert box explaining there are unresolved issues.
	
		//add an ajax marker
		var posturl = form_make_url(formvars.scriptname, {
			'ajax': 'true'
		});
		
		//start spinner
		var spinner =  $(form.name + "_spinner");
		if (spinner) {
			spinner.show();
		}
		
		new Ajax.Request(posturl, {
			method:'post',
			parameters: form.serialize(true),
			onSuccess: function(response) {
				if (spinner) {
					spinner.hide();
				}
				var res = response.responseJSON;
				try {
					if (res == null) {
						//HACK: check to see if we hit the login page (due to logout)
						if (response.responseText.indexOf(" Login</title>") != -1) {
							// this is to differentiate errors where the form hasn't submitted properly (possibly due to session expiry)
							$('#msgsndr_submit_title').html("Submit Error");
							$('#msgsndr_submit_message').html("Your changes cannot be saved because your session has expired or logged out.");
							window.location="index.php?logout=1";
						} else {
							$('#msgsndr_submit_title').html("Submit Error");
							$('#msgsndr_submit_message').html("There was a problem submitting the form. Please try again.");
						}
					} else if ("fail" == res.status) {
						$('#msgsndr_submit_title').html("Submit Error");
						//show the validation results
						if (res.validationerrors)
							$('#msgsndr_submit_message').html("There are some errors on this form.<br>Please correct them before trying again.");
						
						if (res.datachange) {
							$('#msgsndr_submit_message').html("The data on this form has changed.<br>Your changes cannot be saved.");
							window.location=formvars.scriptname;
						}
					} else if ("success" == res.status && res.nexturl) {
						$('#msgsndr_submit_title').html("Message Submitted");
						$('#msgsndr_submit_message').html("Broadcasting message to " + recipientTrack + " recipients");
							
						$('#msgsndr_submit_confirmbutton').on('click', function(event) {
							window.location = res.nexturl;
						});
						$('#msgsndr_submit_confirmation').bind("hide", function() {
							window.location = res.nexturl;
						});
					} else if ("modify" == res.status) {
						$(res.name).update(res.content);
					} else if ("fireevent" == res.status) {
						$(form).trigger("Form:Submitted", res.memo);
					}
				} catch (e) {
					alert(e.message + "\n" + response.responseText);
				}
				formvars.submitting = false;
				$('#msgsndr_submit_confirmation').modal('show');
			},
			onFailure: function(){
				if (spinner) {
					spinner.hide();
				}
				alert('There was a problem submitting the form. Please try again.'); //TODO better error handling
				formvars.submitting = false;
			}
		});
	}
})(jQuery);