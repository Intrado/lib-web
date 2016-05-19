/**
 * Aspell plug-in for CKeditor 4.0
 * Ported from FCKeditor 2.x by Christian Boisjoli, SilenceIT
 * Ported from CKEditor 3.x by Sean M. Kelly, Reliance Communications, Inc.
 * Requires toolbar, aspell
 */

//CKEDITOR.plugins.addExternal('rcidata', '/newjackcity/scripts/rcidata.js');
CKEDITOR.plugins.add('aspell', {

	// Local icon is needed now since it was removed from CKE 4 (SMK)
	icons: 'spellcheck',

	lang: 'af,en',

	init: function (editor) {

		// Create dialog-based command named "aspell"
		editor.addCommand('aspell', new CKEDITOR.dialogCommand('aspell'));
		
		// Add button to toolbar.
		editor.ui.addButton('SpellCheck', {
			//label: editor.lang[editor.langCode].spellCheck.toolbar,
			label: editor.lang.aspell.toolbar,
			command: 'aspell'
		});
		
		// Add link dialog code
		CKEDITOR.dialog.add('aspell', this.path + 'dialogs/aspell.js');
		
		// Add CSS
		var aspellCSS = document.createElement('link');
		aspellCSS.setAttribute( 'rel', 'stylesheet');
		aspellCSS.setAttribute('type', 'text/css');
		aspellCSS.setAttribute('href', this.path+'aspell.css');
		document.getElementsByTagName("head")[0].appendChild(aspellCSS);
		delete aspellCSS;
	},
	requires: ['toolbar']
});

