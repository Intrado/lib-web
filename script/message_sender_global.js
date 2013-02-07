jQuery.noConflict();
(function($) {
	//GLOBAL FUNCTIONS
	autoCharCount = [];
	autoUpdateCharCount = function($element, limit, $errorText) {
		var getId = $element.attr("id");
		
		if(typeof(autoCharCount[getId]) != "undefined") {
			clearTimeout(autoCharCount[getId]);
		}
		
		autoCharCount[getId] = setTimeout(function() {
			if(!$element.hasClass("er")){
				var remaining = limit - $element.val().length;
			
				if(remaining < 0) {
					//$element.addClass('er');
					$errorText.addClass("error").text((0 - remaining) + " too many characters");
				} else {
					$errorText.removeClass("error").text(remaining + " characters left");
				}
			}
			
			autoUpdateCharCount($element, limit, $errorText);
		}, 1000);
	};
	
	reTranslate = function(elem) {
		var langName = $(elem).attr('data-text');
		var langCode = $(elem).attr('data-code');
		var txt = $('#tts_translated_' + langCode).val();
		var displayArea = $('#tts_' + langName + '_to_english');
	
		$('#retranslate_' + langCode).removeClass('hide');
		$.reverseTranslate(txt, langCode, function(data) {
			$(elem).parent().find('img.loading').remove();
			$(displayArea).val(data.responseData.translatedText);
		});
	};
	
	
	// social token ajax call
	getTokens = function(){
		fbToken = false;
		twToken = false;
		window.tokenCheck = $.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/tokens',
			method: 'GET',
			dataType: 'json',
			async: false,
			success: function(data){
				if ($.isEmptyObject(data) != true) {
					if (typeof(data.facebook) != 'undefined' && data.facebook.accessToken){
						fbToken = data.facebook.accessToken;
					}
					if (typeof(data.twitter) != 'undefined' && (data.twitter != '' || data.twitter != 'false')){
						twToken = data.twitter;
						addTwitterHandle(twToken);
						if ( typeof($('#msgsndr_twitterauth')) != 'undefined' ){
							$('#msgsndr_twitterauth').remove();	
						}
					} else {
						addTwitterAuth();
					}
				}
			}
		});
	};
	
	// add auth button for twitter
	addTwitterAuth = function(){
		$('#msgsndr_twittername').append('<a id="msgsndr_twitterauth" href="popuptwitterauth.php" class="btn"><img src="img/icons/custom/twitter.gif" alt="twitter"/> Add Twitter Account</a>');
		$('#msgsndr_twitterauth').on('click', function(){
			event.preventDefault();
			window.open($(this).prop('href'), '', 'height=300, width=600');
		});
	};
	
	// add twitter handle based on api details for twitter account
	addTwitterHandle = function(token){
		$('#msgsndr_twittername').append('Posting as <a class="" href="http://twitter.com/'+token.screenName+'">@'+token.screenName+'</a>');
	};
	
	// facebook authorized destinations ajax call
	getFbAuthorizedPages = function(){
		return $.get('/'+orgPath+'/api/2/organizations/'+orgid+'/settings/facebookpages', function(data) {
			if ($.isEmptyObject(data) != true && typeof(data.facebookPages) != 'undefined')
				facebookPages = data.facebookPages;
		}, "json");
	};
	
	
	formatPhone = function(number) { // must be a 10 digit number with no spaces passed in
		var phone = number.replace(/[^0-9]*/g, '');
		var phonePartOne = '(' + phone.substring(0, 3) + ') ';
		var phonePartTwo = phone.substring(3, 6) + '-';
		var phonePartThree = phone.substring(6, 10);
		
		return phonePartOne + phonePartTwo + phonePartThree;
	};
	
	
	// schedule atypical call time check
	changeTimeSelectNote = function(element, value) {
		var earlyTime = new Date("1/1/2010 7:00 am");
		var lateTime = new Date("1/1/2010 9:00 pm");
		var thisTime = new Date("1/1/2010 " + value);
			if (thisTime < earlyTime || thisTime > lateTime) {
				$(element + "_timenote").html('<img src="img/icons/moon_16.gif" alt="WARNING: Outside typical calling hours"/> WARNING: Outside typical calling hours.');
			} else {
				$(element + "_timenote").html('<img src="img/icons/weather_sun.gif" alt="Within typical calling hours"/>');
			}
	};
	
	// using helper functions in htmleditor.js, set up the ckeditor on the textarea with id "elementid"
	applyCkEditor = function(elementid, editor_mode) {

		if (typeof rcieditor === 'undefined') {
			rcieditor = new RCIEditor(editor_mode, elementid);
		}
		else {
			rcieditor.reconstruct(editor_mode, elementid);
		}

		rcieditor.setValidatorFunction(function () {
			obj_valManager.runValidateEventDriven(elementid);
		});
	};
	
	// stuff translation info into the specified hidden input field
	updateTranslatedField = function(field, text, enable, override, gender) {
		$(field).val($.toJSON({
			"enabled" : enable,
			"text" : text,
			"override" : override,
			"englishText" : "",
			"gender": gender
		}));
	};
	
	// do callback based on common events
	var doCommonEventCallbackTimers = {};
	doCommonEventCallback = function(elements, callback) {
		elements.on('click change blur focus keyup', function() {
			var $elem = $(this);
			var elemId = $(this).attr("id");
	
			if (typeof (doCommonEventCallbackTimers[elemId]) == "undefined")
				doCommonEventCallbackTimers[elemId] = null;
			
			clearTimeout(doCommonEventCallbackTimers[elemId]);
			
			doCommonEventCallbackTimers[elemId] = setTimeout(function() {
				callback();
			}, 500);
		});
	};


	timeUpdate = function() {

		var clientTimeNow = Math.round((new Date()).getTime() / 1000);
		var clientTimeDiff = clientTimeNow - startClientTime;
		var newServerTime = startServerTime + clientTimeDiff;

		return newServerTime;

	};


	timezoneOffset = function(timestamp, format) {

		var offset = orgOptions.timezoneOffset;

		function getMins(hours,mins) {

			var hoursMins = parseInt(hours) * 60,
					minutes = parseInt(hoursMins) + mins;

			return minutes;

		}

		if (offset.length == 5) { // subtract time

			var hours = offset.substr(1,2),
					mins = offset.substr(3,2),
					minutes = getMins(hours,mins),
					timestamp = parseInt(timestamp) - parseInt(minutes);

			var timeOffset = moment(timestamp*1000).format(format);

		} else { // Add time

			var hours = offset.substr(0,2),
					mins = offset.substr(2,2),
					minutes = getMins(hours,mins),
					timestamp = parseInt(timestamp) + parseInt(minutes);

			var timeOffset = moment(timestamp*1000).format(format);

		}

		return timeOffset;

	};

})(jQuery);
