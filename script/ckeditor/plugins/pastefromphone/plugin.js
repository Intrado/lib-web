CKEDITOR.plugins.add( 'pastefromphone', {
	icons: 'pastefromphone',
	init: function( editor ) {
		editor.addCommand( 'pasteFromPhone', {
			exec: function( editor ) {
				// $ does not appear to be jQuery in the context of message sender...
				(function ($) {
					var tts_message = $('#msgsndr_tts_message', window.top.document);
					if (tts_message && tts_message.length) {
						editor.insertText(tts_message.val());
					}
					else {
						alert('There is no "Text to Speech" message entered for the phone yet.');
					}
				}) (jQuery);
			}
		});
		editor.ui.addButton('pasteFromPhone', {
			label: 'Paste text from Phone',
			command: 'pasteFromPhone'
		});
	}
});

