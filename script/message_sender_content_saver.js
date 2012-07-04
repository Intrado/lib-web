function ContentSaveManager() {
	var $ = jQuery;
	
	var saveProcess = {
		"email" : function() {
			var enText = $("#msgsndr_emailmessagetext").val();
			var translate = $('#msgsndr_emailmessagetexttranslate').is(':checked');

			var getTranslations = true;
			var langCodes = [];

			$('input[name=email_save_translation]:checked').each(function(transI, transD) {
				var langCode = $(transD).attr('id').split('_')[1];
				if (translate) {
					langCodes.push(langCode);
				} else {
					$('input[name=email_translate_' + transData.code + ']').html("");
				}
			});

			if (langCodes.length > 0) {
				getTranslations = $.translate(enText, langCodes, function(data) {
					$.each(data.responseData, function(transIndex, transData) {
						updateTranslatedField(
								$('input[name=email_translate_' + transData.code + ']'),
								transData.translatedText, true, false, null);
					});
				});
			}
			
			return getTranslations;
		},
		"translation" : function() {
			var gender = $('input[name=messagePhoneText_message-gender]:checked').val();
			var enText = $('#msgsndr_tts_message').val();
			var translate = $('#msgsndr_phonemessagetexttranslate').is(':checked');
			var getTranslations = true;

			var langCodes = [];

			$('input[name=save_translation]:checked').each(function(transI, transD) {
				var langCode = $(transD).attr('id').split('_')[1];
				
				if (translate) {
					var overRide = $('#tts_override_' + langCode).is(':checked');
					if(overRide) {
						updateTranslatedField(
								$('#msgsndr_phonemessagetexttranslate' + langCode + 'text'),
								$('#tts_translated_' + langCode).val(), true, true, gender);
					} else {
						langCodes.push(langCode);
					}
				} else {
					$('#msgsndr_phonemessagetexttranslate' + langCode + 'text').val("");
				}
			});

			if (langCodes.length > 0) {
				getTranslations = $.translate(enText, langCodes, function(data) {
					$.each(data.responseData, function(transIndex, transData) {
						updateTranslatedField(
								$('#msgsndr_phonemessagetexttranslate' + transData.code + 'text'),
								transData.translatedText, true, false, gender);
					});
				});
			}
			
			return getTranslations;
		},
		"feed" : function() {
			var titleVal = $('#msgsndr_form_rsstitle').val();
			var messageVal = $('#msgsndr_form_rssmsg').val();
			var rssPost = $.toJSON({
				"subject" : titleVal,
				"message" : messageVal
			});
			$('#msgsndr_socialmediafeedmessage').val(rssPost);
			
			/*var rssCats = [];
			$('input[name=feed_categories]').each(function(findex, fitem){
				var feedid = $(this).attr('value');
				if($(this).is(':checked')){
					rssCats.push(feedid);
				}
			});
			$('#msgsndr_feed_categories').val(rssCats);*/
		}
	};
	
	this.save = function(saveType) {
		if(typeof(saveProcess[saveType]) != "undefined") {
			return saveProcess[saveType]();
		}
	};
}