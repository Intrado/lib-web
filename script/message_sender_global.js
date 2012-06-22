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
			var remaining = limit - $element.val().length;
		
			if(remaining < 0) {
				//$element.addClass('er');
				$errorText.addClass("error").text((0 - remaining) + " too many characters");
			} else {
				$errorText.removeClass("error").text(remaining + " characters left");
			}
			
			autoUpdateCharCount($element, limit, $errorText);
		}, 1000);
	};
	
	

	doTranslate = function(langCodes, txtField, displayArea, msgType) {
		var transTxt = makeTranslatableString(txtField);
		var transURL = 'translate.php?english=' + transTxt + '&languages=' + langCodes;

		var splitlangCodes = langCodes.split('|');
		var langCount = splitlangCodes.length;

		$.ajax({
			url : transURL,
			type : 'GET',
			dataType : 'json',
			success : function(data) {
				$.each(data.responseData, function(transIndex, transData) {
					var textareaId = '#' + msgType + '_translated_' + transData.code;
					var transText = transData.translatedText;
					if (msgType == "email") {
						$(textareaId).html(transText);
					} else {
						$(textareaId).val(transText);
					}
				});

				$('img.loading').remove();
			}
		});
	};
	
			
	reTranslate = function(elem) {
		var langName = $(elem).attr('data-text');
		var langCode = $(elem).attr('data-code');
		var txt = $('#tts_translated_' + langCode).val();
		var displayArea = $('#tts_' + langName + '_to_english');
		var msgType = 'tts';
	
		$('#retranslate_' + langCode).removeClass('hide');
		doreTranslate(langCode, txt, displayArea, msgType);
	};
	
	
	doreTranslate = function(langCode, txt, displayArea, msgType) {
		var transURL = 'translate.php?text=' + txt + '&language=' + langCode;
	
		$.ajax({
			url : transURL,
			type : 'GET',
			dataType : 'json',
			success : function(data) {
				$('img.loading').remove();
				$(displayArea).val(data.responseData.translatedText);
			}
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
			async: true,
			success: function(data){
				if ($.isEmptyObject(data) != true) {
					if (typeof(data.facebook) != 'undefined' && data.facebook.accessToken){
						fbToken = data.facebook.accessToken;
					}
					if (typeof(data.twitter) != 'undefined' && data.twitter != ''){
						twToken = data.twitter;
						$('#msgsndr_twittername').append('Posting as <a class="" href="http://twitter.com/'+twToken.screenName+'">@'+twToken.screenName+'</a>');
					} else {
						$('#msgsndr_twittername').append('<a id="msgsndr_twitterauth" href="popuptwitterauth.php" class="btn"><img src="img/icons/custom/twitter.gif" alt="twitter"/> Add Twitter Account</a>');
						$('#msgsndr_twitterauth').on('click', function(){
							event.preventDefault();
							window.open($(this).prop('href'), '', 'height=300, width=600');
						});
					}
				}
			}
		});
	};
	
	// facebook authorized destinations ajax call
	getFbAuthorizedPages = function(){
		return $.get('/'+orgPath+'/api/2/organizations/'+orgid+'/settings/facebookpages', function(data) {
			if ($.isEmptyObject(data) != true && typeof(data.facebookPages) != 'undefined')
				facebookPages = data.facebookPages;
		}, "json");
	};
	
	
	formatPhone = function(number) { // must be a 10 digit number with no spaces passed in
		var phone = number;
		var phonePartOne = '(' + phone.substring(0, 3) + ') ';
		var phonePartTwo = phone.substring(3, 6) + '-';
		var phonePartThree = phone.substring(6, 10);
		
		return phonePartOne + phonePartTwo + phonePartThree;
	};
})(jQuery);