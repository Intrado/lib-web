function ContentSaveManager() {
	var $ = jQuery;
	
	var saveProcess = {
		"email" : function() {
			var enText = CKEDITOR.instances.reusableckeditor.getData();
			var translate = $('#msgsndr_form_emailtranslate').is(':checked');

			$('#msgsndr_tab_email').append('<div id="post_data_email_translations"><input type="hidden" name="email_translate" /></div>');
			
			var jsonVal = $.toJSON({
				"enabled" : true,
				"text" : enText,
				"override" : false,
				"englishText" : ""
			});

			$('input[name=email_translate]').val(jsonVal);

			var getTranslations = true;
			if(translate) {
				var translatedText = '';
				var langCodes = [];

				$('input[name=email_save_translation]:checked').each(function(transI, transD) {
					var langCode = $(transD).attr('id').split('_')[1];
					$('#post_data_email_translations').append('<input type="hidden" name="email_translate_' + langCode + '">');
					langCodes.push(langCode);
				});

				enText = makeTranslatableString(enText);

				var transURL = 'translate.php?english=' + enText + '&languages=' + langCodes.join("|");

				getTranslations = $.ajax({
					url : transURL,
					type : 'GET',
					dataType : 'json',
					success : function(data) {
						$.each(data.responseData, function(transIndex, transData) {
							var langCode = langCodes[transIndex];
							
							var jsonVal = $.toJSON({
								"enabled" : true,
								"text" : transData.translatedText,
								"override" : false,
								"englishText" : ""
							});

							$('input[name=email_translate_' + langCode + ']').val(jsonVal);
						});
					},
					complete: function() {
						/*$('#msgsndr_tab_email .loading').addClass('hide');
						$('#msgsndr_tab_email').hide();

						var el = 'email';
						var nav = '.oemail';

						$('.msg_content_nav li').removeClass('lighten');
						$('.msg_content_nav ' + nav).removeClass('active').addClass('complete');

						$('input[name=has_' + el + ']').attr('checked', 'checked');

						// Set Message tabs on review tab
						$('#msgsndr_review_' + el).parent().addClass('complete');*/
					}
				});
			}
			
			return getTranslations;
		},
		"translation" : function() {
			var gender = $('input[name=messagePhoneText_message-gender]:checked').val();
			var enText = $('#msgsndr_tts_message').val();
			var translate = $('#msgsndr_form_phonetranslate').is(':checked');
			var getTranslations = true;

			$('#text').append('<div id="post_data_translations"><input type="hidden" name="phone_translate" /></div>');
			
			var jsonVal = $.toJSON({
				"gender" : gender,
				"text" : enText
			});

			$('input[name=phone_translate]').val(jsonVal);

			if(translate) {
				var translatedText = '';
				var langCodes = [];

				$('input[name=save_translation]:checked').each(function(transI, transD) {
					var langCode = $(transD).attr('id').split('_')[1];
					$('#post_data_translations').append('<input type="hidden" name="phone_translate_' + langCode + '">');
					
					var overRide = $('#tts_override_' + langCode).is(':checked');
					if(overRide) {
						transText = $('#tts_translated_' + langCode).val();
						
						var jsonVal = j.toJSON({
							"enabled" : "true",
							"text" : transText,
							"override" : "true",
							"gender" : gender,
							"englishText" : ""
						});
		
						$('input[name=phone_translate_' + langCode + ']').val(jsonVal);
					} else {
						langCodes.push(langCode);
					}
				});

				var transURL = 'translate.php?english=' + enText + '&languages=' + langCodes.join("|");
				
				getTranslations = $.ajax({
					url : transURL,
					type : 'GET',
					dataType : 'json',
					success : function(data) {
						$.each(data.responseData, function(transIndex, transData) {
							var langCode = langCodes[transIndex];

							var jsonVal = $.toJSON({
								"enabled" : "true",
								"text" : transData.translatedText,
								"override" : "false",
								"gender" : gender,
								"englishText" : ""
							});

							$('input[name=phone_translate_' + langCode + ']').val(jsonVal);
						});
					},
					complete : function() {
						/*$('#text .loading').addClass('hide');
						$('#msgsndr_tab_phone').hide();

						var el = 'phone';
						var nav = '.ophone';

						$('.msg_content_nav li').removeClass('lighten');
						$('.msg_content_nav ' + nav).removeClass('active').addClass('complete');

						$('input[name=has_' + el + ']').attr('checked', 'checked');

						// Set Message tabs on review tab
						$('#msgsndr_review_' + el).parent().addClass('complete');*/
					}

				});
			}
			
			return getTranslations;
		}
	};
	
	this.save = function(saveType) {
		if(typeof(saveProcess[saveType]) != "undefined") {
			return saveProcess[saveType]();
		}
	};
}