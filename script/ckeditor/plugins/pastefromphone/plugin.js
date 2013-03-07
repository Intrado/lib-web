CKEDITOR.plugins.add( 'pastefromphone', {
	icons: 'pastefromphone',
	init: function( editor ) {
		editor.addCommand( 'pasteFromPhone', {
			exec: function( editor ) {
				var message = window.top.rcieditor.getSetting('clipboard');
				if (message && message.length) {
					editor.insertText(message);
				}
				else {
					alert('There is no "Text to Speech" message entered for the phone yet.');
				}
			}
		});
		editor.ui.addButton('pasteFromPhone', {
			label: 'Paste text from Phone',
			command: 'pasteFromPhone'
		});
	}
});

