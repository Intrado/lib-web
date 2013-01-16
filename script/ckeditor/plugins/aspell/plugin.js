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

	pluginLang: {},

	init: function (editor) {

		// spellCheck was shamefully removed from CKE 4; here it is in English only (SMK)
		editor.lang[editor.langCode] = {};
		switch (editor.langCode) {
			case 'af':
				//editor.lang[editor.langCode]['spellCheck']
				this.pluginLang = {toolbar:'Spelling nagaan',title:'Spell Check',notAvailable:'Sorry, but service is unavailable now.',errorLoading:'Error loading application service host: %s.',notInDic:'Nie in woordeboek nie',changeTo:'Verander na',btnIgnore:'Ignoreer',btnIgnoreAll:'Ignoreer na-volgende',btnReplace:'Vervang',btnReplaceAll:'vervang na-volgende',btnUndo:'Ont-skep',noSuggestions:'- Geen voorstel -',progress:'Spelling word beproef...',noMispell:'Spellproef kompleet: Geen foute',noChanges:'Spellproef kompleet: Geen woord veranderings',oneChange:'Spellproef kompleet: Een woord verander',manyChanges:'Spellproef kompleet: %1 woorde verander',ieSpellDownload:'Geen Spellproefer geinstaleer nie. Wil U dit aflaai?'};
				break;

			case 'en':
				this.pluginLang = {toolbar:'Check Spelling',title:'Spell Check',notAvailable:'Sorry, but service is unavailable now.',errorLoading:'Error loading application service host: %s.',notInDic:'Not in dictionary',changeTo:'Change to',btnIgnore:'Ignore',btnIgnoreAll:'Ignore All',btnReplace:'Replace',btnReplaceAll:'Replace All',btnUndo:'Undo',noSuggestions:'- No suggestions -',progress:'Spell check in progress...',noMispell:'Spell check complete: No misspellings found',noChanges:'Spell check complete: No words changed',oneChange:'Spell check complete: One word changed',manyChanges:'Spell check complete: %1 words changed',ieSpellDownload:'Spell checker not installed. Do you want to download it now?'};
				break;

			// TODO: add the other languages back in here just as above by pulling string JSON from CKE 3.x/lang/*.js
		}

		// Create dialog-based command named "aspell"
		editor.addCommand('aspell', new CKEDITOR.dialogCommand('aspell'));
		
		// Add button to toolbar. Not sure why only that name works for me.
		editor.ui.addButton('SpellCheck', {
			//label: editor.lang[editor.langCode].spellCheck.toolbar,
			label: this.pluginLang.toolbar,
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

