(function($){
	// Translate from english to specified language codes, pass the result to the callback
	$.translate = function(text, langCodes, callback) {
		var data = {
			"english":makeTranslatableString(text),
			"languages": langCodes.join("|")};
		
		return $.post("translate.php", data, callback);
	};

	// Translate from passed text, in specified language code, into english. Pass the result to the callback
	$.reverseTranslate = function(text, langCode, callback) {
		var data = {
			"text":makeTranslatableString(text),
			"language": langCode};
		
		return $.post("translate.php", data, callback);
	};
})(jQuery);