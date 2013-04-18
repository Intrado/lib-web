CKEDITOR.plugins.add( 'pastefromphone', {
	icons: 'pastefromphone',
	init: function( editor ) {
		editor.addCommand( 'pasteFromPhone', {
			exec: function( editor ) {
				// Depending on iframe/containment of the editor, rcieditor may be
				// here or up one parent level; grab a refernece to it either way
				var rcie;
				if (typeof(rcieditor) == 'object') {
					rcie = rcieditor;
				}
				else rcie = window.parent.rcieditor;

				var message = rcie.getSetting('clipboard');
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

