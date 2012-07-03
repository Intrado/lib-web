function SubmitManager() {
	var $ = jQuery;
	
	// The mapping between our keys and the server keys
	var keyMap = {
		"broadcast_formsnum" : "msgsndr-formsnum",
		"msgsndr_name" : "msgsndr_name",
		"msgsndr_jobtype" : "msgsndr_jobtype",
		"msgsndr_addme" : "msgsndr_addme",
		"msgsndr_addmephone" : "msgsndr_addmephone",
		"msgsndr_addmeemail" : "msgsndr_addmeemail",
		"msgsndr_addmesms" : "msgsndr_addmesms",
		"msgsndr_listids" : "msgsndr_listids",
		"msgsndr_hasphone" : "msgsndr_hasphone",
		"msgsndr_phonemessagetype" : "msgsndr_phonemessagetype",
		"msgsndr_phonemessagecallme" : "msgsndr_phonemessagecallme",
		"msgsndr_phonemessagetext" : "msgsndr_phonemessagetext",
		"msgsndr_phonemessagetexttranslate" : "msgsndr_phonemessagetexttranslate",
		"phone_translate_es" : "msgsndr_phonemessagetexttranslateestext",
		"phone_translate_af" : "msgsndr_phonemessagetexttranslateaftext",
		"phone_translate_sq" : "msgsndr_phonemessagetexttranslatesqtext",
		"phone_translate_ar" : "msgsndr_phonemessagetexttranslateartext",
		"phone_translate_be" : "msgsndr_phonemessagetexttranslatebetext",
		"phone_translate_bg" : "msgsndr_phonemessagetexttranslatebgtext",
		"phone_translate_ca" : "msgsndr_phonemessagetexttranslatecatext",
		"phone_translate_zh" : "msgsndr_phonemessagetexttranslatezhtext",
		"phone_translate_hr" : "msgsndr_phonemessagetexttranslateaftext",
		"phone_translate_cs" : "msgsndr_phonemessagetexttranslatecstext",
		"phone_translate_da" : "msgsndr_phonemessagetexttranslatedatext",
		"phone_translate_nl" : "msgsndr_phonemessagetexttranslatenltext",
		"phone_translate_et" : "msgsndr_phonemessagetexttranslateettext",
		"phone_translate_fil" : "msgsndr_phonemessagetexttranslatefiltext",
		"phone_translate_fi" : "msgsndr_phonemessagetexttranslatefitext",
		"phone_translate_fr" : "msgsndr_phonemessagetexttranslatefrtext",
		"phone_translate_gl" : "msgsndr_phonemessagetexttranslategltext",
		"phone_translate_de" : "msgsndr_phonemessagetexttranslatedetext",
		"phone_translate_el" : "msgsndr_phonemessagetexttranslateeltext",
		"phone_translate_ht" : "msgsndr_phonemessagetexttranslatehttext",
		"phone_translate_iw" : "msgsndr_phonemessagetexttranslateiwtext",
		"phone_translate_hi" : "msgsndr_phonemessagetexttranslatehitext",
		"phone_translate_hu" : "msgsndr_phonemessagetexttranslatehutext",
		"phone_translate_is" : "msgsndr_phonemessagetexttranslateistext",
		"phone_translate_id" : "msgsndr_phonemessagetexttranslateidtext",
		"phone_translate_ga" : "msgsndr_phonemessagetexttranslategatext",
		"phone_translate_it" : "msgsndr_phonemessagetexttranslateittext",
		"phone_translate_ja" : "msgsndr_phonemessagetexttranslatejatext",
		"phone_translate_ko" : "msgsndr_phonemessagetexttranslatekotext",
		"phone_translate_lv" : "msgsndr_phonemessagetexttranslatelvtext",
		"phone_translate_lt" : "msgsndr_phonemessagetexttranslatelttext",
		"phone_translate_mk" : "msgsndr_phonemessagetexttranslatemktext",
		"phone_translate_ms" : "msgsndr_phonemessagetexttranslatemstext",
		"phone_translate_mt" : "msgsndr_phonemessagetexttranslatemttext",
		"phone_translate_no" : "msgsndr_phonemessagetexttranslatenotext",
		"phone_translate_fa" : "msgsndr_phonemessagetexttranslatefatext",
		"phone_translate_pt" : "msgsndr_phonemessagetexttranslatepttext",
		"phone_translate_ro" : "msgsndr_phonemessagetexttranslaterotext",
		"phone_translate_ru" : "msgsndr_phonemessagetexttranslaterutext",
		"phone_translate_sr" : "msgsndr_phonemessagetexttranslatesrtext",
		"phone_translate_sk" : "msgsndr_phonemessagetexttranslatesktext",
		"phone_translate_sl" : "msgsndr_phonemessagetexttranslatesltext",
		"phone_translate_sw" : "msgsndr_phonemessagetexttranslateswtext",
		"phone_translate_sv" : "msgsndr_phonemessagetexttranslatesvtext",
		"phone_translate_th" : "msgsndr_phonemessagetexttranslatethtext",
		"phone_translate_tr" : "msgsndr_phonemessagetexttranslatetrtext",
		"phone_translate_uk" : "msgsndr_phonemessagetexttranslateuktext",
		"phone_translate_vi" : "msgsndr_phonemessagetexttranslatevitext",
		"phone_translate_cy" : "msgsndr_phonemessagetexttranslatecytext",
		"phone_translate_yi" : "msgsndr_phonemessagetexttranslateyitext",
		"has_email" : "msgsndr_hasemail",
		"email_name" : "msgsndr_emailmessagefromname",
		"email_address" : "msgsndr_emailmessagefromemail",
		"email_subject" : "msgsndr_emailmessagesubject",
		"email_attachment" : "msgsndr_emailmessageattachment",
		"broadcast_emailbody" : "msgsndr_emailmessagetext",
		"email_translate" : "msgsndr_emailmessagetexttranslate",
		"email_translate_es" : "msgsndr_emailmessagetexttranslateestext",
		"email_translate_af" : "msgsndr_emailmessagetexttranslateaftext",
		"email_translate_sq" : "msgsndr_emailmessagetexttranslatesqtext",
		"email_translate_ar" : "msgsndr_emailmessagetexttranslateartext",
		"email_translate_be" : "msgsndr_emailmessagetexttranslatebetext",
		"email_translate_bg" : "msgsndr_emailmessagetexttranslatebgtext",
		"email_translate_ca" : "msgsndr_emailmessagetexttranslatecatext",
		"email_translate_zh" : "msgsndr_emailmessagetexttranslatezhtext",
		"email_translate_hr" : "msgsndr_emailmessagetexttranslateaftext",
		"email_translate_cs" : "msgsndr_emailmessagetexttranslatecstext",
		"email_translate_da" : "msgsndr_emailmessagetexttranslatedatext",
		"email_translate_nl" : "msgsndr_emailmessagetexttranslatenltext",
		"email_translate_et" : "msgsndr_emailmessagetexttranslateettext",
		"email_translate_fil" : "msgsndr_emailmessagetexttranslatefiltext",
		"email_translate_fi" : "msgsndr_emailmessagetexttranslatefitext",
		"email_translate_fr" : "msgsndr_emailmessagetexttranslatefrtext",
		"email_translate_gl" : "msgsndr_emailmessagetexttranslategltext",
		"email_translate_de" : "msgsndr_emailmessagetexttranslatedetext",
		"email_translate_el" : "msgsndr_emailmessagetexttranslateeltext",
		"email_translate_ht" : "msgsndr_emailmessagetexttranslatehttext",
		"email_translate_iw" : "msgsndr_emailmessagetexttranslateiwtext",
		"email_translate_hi" : "msgsndr_emailmessagetexttranslatehitext",
		"email_translate_hu" : "msgsndr_emailmessagetexttranslatehutext",
		"email_translate_is" : "msgsndr_emailmessagetexttranslateistext",
		"email_translate_id" : "msgsndr_emailmessagetexttranslateidtext",
		"email_translate_ga" : "msgsndr_emailmessagetexttranslategatext",
		"email_translate_it" : "msgsndr_emailmessagetexttranslateittext",
		"email_translate_ja" : "msgsndr_emailmessagetexttranslatejatext",
		"email_translate_ko" : "msgsndr_emailmessagetexttranslatekotext",
		"email_translate_lv" : "msgsndr_emailmessagetexttranslatelvtext",
		"email_translate_lt" : "msgsndr_emailmessagetexttranslatelttext",
		"email_translate_mk" : "msgsndr_emailmessagetexttranslatemktext",
		"email_translate_ms" : "msgsndr_emailmessagetexttranslatemstext",
		"email_translate_mt" : "msgsndr_emailmessagetexttranslatemttext",
		"email_translate_no" : "msgsndr_emailmessagetexttranslatenotext",
		"email_translate_fa" : "msgsndr_emailmessagetexttranslatefatext",
		"email_translate_pt" : "msgsndr_emailmessagetexttranslatepttext",
		"email_translate_ro" : "msgsndr_emailmessagetexttranslaterotext",
		"email_translate_ru" : "msgsndr_emailmessagetexttranslaterutext",
		"email_translate_sr" : "msgsndr_emailmessagetexttranslatesrtext",
		"email_translate_sk" : "msgsndr_emailmessagetexttranslatesktext",
		"email_translate_sl" : "msgsndr_emailmessagetexttranslatesltext",
		"email_translate_sw" : "msgsndr_emailmessagetexttranslateswtext",
		"email_translate_sv" : "msgsndr_emailmessagetexttranslatesvtext",
		"email_translate_th" : "msgsndr_emailmessagetexttranslatethtext",
		"email_translate_tr" : "msgsndr_emailmessagetexttranslatetrtext",
		"email_translate_uk" : "msgsndr_emailmessagetexttranslateuktext",
		"email_translate_vi" : "msgsndr_emailmessagetexttranslatevitext",
		"email_translate_cy" : "msgsndr_emailmessagetexttranslatecytext",
		"email_translate_yi" : "msgsndr_emailmessagetexttranslateyitext",
		"has_sms" : "msgsndr_hassms",
		"sms_text" : "msgsndr_smsmessagetext",
		"has_facebook" : "msgsndr_hasfacebook",
		"facebook_message" : "msgsndr_socialmediafacebookmessage",
		"social_fbpages" : "msgsndr_socialmediafacebookpage",
		"has_twitter" : "msgsndr_hastwitter",
		"twitter_message" : "msgsndr_socialmediatwittermessage",
		"has_feed" : "msgsndr_hasfeed",
		"rss_post" : "msgsndr_socialmediafeedmessage",
		//"feed_categories" : "msgsndr_socialmediafeedcategory[]",
		"msgsndr_optionmaxjobdays" : "msgsndr_optionmaxjobdays",
		"msgsndr_optionleavemessage" : "msgsndr_optionleavemessage",
		"msgsndr_optionmessageconfirmation" : "msgsndr_optionmessageconfirmation",
		"options_autoreport" : "msgsndr_optionautoreport",
		"options_skipduplicates" : "msgsndr_optionskipduplicate",
		"msgsndr_optioncallerid" : "msgsndr_optioncallerid",
		"options_savemessage" : "msgsndr_optionsavemessage",
		"options_savemessagename" : "msgsndr_optionsavemessagename",
		"broadcast_scheduledate" : "msgsndr_scheduledate",
		"broadcast_schedulecallearly" : "msgsndr_schedulecallearly",
		"broadcast_schedulecalllate" : "msgsndr_schedulecalllate",
		"submit" : "submit"
	};

	// this will set the schedule options to send a broadcast out immediately
	scheduleNow = function(onehour) {

		$('#schedule_datepicker').val(moment().format('MM/DD/YYYY'));
		$('#schedulecallearly').append('<option value="'+moment().format('h:mm a')+'" selected="selected">'+moment().format('h:mm a')+'</option>');

		$('#schedulecalllate option').removeAttr('selected')

		// User is submitting a job within the hour window so we need to give the post data and hour gap
		if (typeof onehour != undefined && onehour == true) { 
			//$('#schedulecalllate').append('<option value="'+moment().add('hours', 1).format('h:mm a')+'" selected="selected">'+moment().add('hours', 1).format('h:mm a')+'</option>');
			$('#schedulecalllate option:last-child').attr('selected', 'selected');
		} else {
			$('#schedulecalllate option:last-child').attr('selected', 'selected');
		}
	};

	mapPostData = function() {
		// The store for our new POST data
		
		// changed mapping to work using serialize -- this is so we can pick up the individual feed categories as required server side
		var sendData = {};
		var getFieldClass = "saverclass";
		
			$.each(keyMap, function(fKey, fVal) {
				var $findField = $("[name=" + fKey + "]");
				
				if ($findField.attr("type") == "checkbox") {
					if ($findField.is(":checked")) {
						$findField.val("checked");
					} else {
						$findField.val("");
					}
				} else {
					var value = $findField.val();
					if (value == "[]" || value == "{}") {
						value = "";
					}
						
					$findField.val(value);
				}
				
				$findField.attr("name", fVal);
				
				$findField.addClass(getFieldClass);
			});

		sendData = $("." + getFieldClass).serialize();

		$.each(keyMap, function(fKey, fVal) {
			var $findField = $("[name=" + fVal + "]");
			
			$findField.attr("name", fKey);
			$findField.removeClass(getFieldClass).not("input[name^=msgsndr_socialmediafeedcategory]");
		});
		
		return sendData;
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

		//return false;
		//var formData = $('form[name=broadcast]').serializeArray();
		var formData = mapPostData();
		$.ajax({
			type: 'POST',
			url: '_messagesender.php?form=msgsndr&ajax=true',
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
		});
		
	});
	
};