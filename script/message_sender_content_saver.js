function ContentSaveManager() {
	var $ = jQuery;
	
	var saveProcess = {
		"email" : function() {
			var enText = $("#msgsndr_form_body").val();
			var translate = $('#msgsndr_form_emailtranslate').is(':checked');

			var getTranslations = true;
			if(translate) {
				var langCodes = [];

				$('input[name=email_save_translation]:checked').each(function(transI, transD) {
					var langCode = $(transD).attr('id').split('_')[1];
					$('#post_data_email_translations').append();
					langCodes.push(langCode);
				});

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
			var translate = $('#msgsndr_form_phonetranslate').is(':checked');
			var getTranslations = true;
			
			var jsonVal = $.toJSON({
				"gender" : gender,
				"text" : enText
			});

			$('input[name=phone_translate]').val(jsonVal);

			if(translate) {
				var langCodes = [];

				$('input[name=save_translation]:checked').each(function(transI, transD) {
					var langCode = $(transD).attr('id').split('_')[1];
					
					var overRide = $('#tts_override_' + langCode).is(':checked');
					if(overRide) {
						updateTranslatedField(
								$('input[name=phone_translate_' + langCode + ']'),
								$('#tts_translated_' + langCode).val(), true, true, gender);
					} else {
						langCodes.push(langCode);
					}
				});

				getTranslations = $.translate(enText, langCodes, function(data) {
					$.each(data.responseData, function(transIndex, transData) {
						updateTranslatedField(
								$('input[name=phone_translate_' + transData.code + ']'),
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
			$('#msgsndr_rsspost').val(rssPost);
			
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