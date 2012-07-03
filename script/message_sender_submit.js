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
		/*$.ajax({
			type: 'POST',
			url: 'message_sender.php?form=msgsndr&ajax=true',
			data: formData,
			dataType: 'json',
			success: function(response) {
				var res = response;
				if(typeof(res.status) != "undefined" && typeof(res.nexturl) != "undefined" && res.status == "success") {
					//success
					$('#msgsndr_submit_title').html("Message Submitted");
					$('#msgsndr_submit_message').html("Broadcasting message to " + recipientTrack + " recipients");
					$('#msgsndr_submit_confirmbutton').on('click', function(event) {
						window.location = res.nexturl;
					});
					$('#msgsndr_submit_confirmation').bind("hide", function() {
						window.location = res.nexturl;
					});
				} else if (typeof(res.validationerrors) == "undefined") {
					// this is to differentiate errors where the form hasn't submitted properly (possibly due to session expiry)
					$('#msgsndr_submit_title').html("Submit Error");
					$('#msgsndr_submit_message').html("Hmmm, looks like something went wrong. <br/>That's all we know.");
				} else {
					$('#msgsndr_submit_title').html("Submit Error");
					$('#msgsndr_submit_message').html("Oops! That didn't quite work out.<br/>Perhaps something is missing or not quite right with the broadcast information entered?");
					
				}
				$('#msgsndr_submit_confirmation').modal('show');
			}
		});*/
		
	});
	
};