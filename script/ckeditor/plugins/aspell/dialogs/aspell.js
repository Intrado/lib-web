
var FCKLang;
var OnSpellerControlsLoad;

CKEDITOR.dialog.add('aspell', function( editor )
{
	var number = CKEDITOR.tools.getNextNumber(),
		iframeId = 'cke_frame_' + number,
		textareaId = 'cke_data_' + number,
		scratchId = 'cke_scratch_' + number,
		interval,
		errorMsg = editor.lang.aspell.notAvailable;
	
	var spellHTML =
	// Input for exchanging data CK<--->spellcheck
		'<textarea name="'+textareaId+'" id="'+textareaId+
		'" style="display: none;"></textarea>' +
	// Scratch space for doing DOM work
		'<div style="display: none;" id="' + scratchId + '"></div>' +
	// Spellcheck iframe
		'<iframe src="" style="width:485px; height:380px"' +
		' frameborder="0" name="' + iframeId + '" id="' + iframeId + '"' +
		' allowtransparency="1"></iframe>';
	
	function spellTime(dialog, errorMsg)
	{
		var i = 0;
		return function()
		{
			if (typeof window.spellChecker == 'function')
			{
				// Call from window.setInteval expected at once.
				if (typeof interval != 'undefined')
					window.clearInterval(interval);
				
				// Create spellcheck object, set options/attributes
				var oSpeller = new spellChecker(document.getElementById(textareaId));
				oSpeller.spellCheckScript = editor.plugins.aspell.path+'spellerpages/server-scripts/spellchecker.php';
				oSpeller.OnFinished = function (numChanges) { oSpeller_OnFinished(dialog, numChanges); };
				oSpeller.popUpUrl = editor.plugins.aspell.path+'spellerpages/spellchecker.html';
				oSpeller.popUpName = iframeId;
				oSpeller.popUpProps = null;
				
				// Place language in global variable;
				// A bit of a hack, but how does e.g. controls.html know which language to use?
				FCKLang = {};

				// spellChecker.js
				FCKLang.DlgSpellNoChanges     = editor.lang.aspell.noChanges;
				FCKLang.DlgSpellNoMispell     = editor.lang.aspell.noMispell;
				FCKLang.DlgSpellOneChange     = editor.lang.aspell.oneChange;
				FCKLang.DlgSpellManyChanges   = editor.lang.aspell.manyChanges;
				// controls.html
				FCKLang.DlgSpellNotInDic      = editor.lang.aspell.notInDic;
				FCKLang.DlgSpellChangeTo      = editor.lang.aspell.changeTo;
				FCKLang.DlgSpellBtnIgnore     = editor.lang.aspell.btnIgnore;
				FCKLang.DlgSpellBtnIgnoreAll  = editor.lang.aspell.btnIgnoreAll;
				FCKLang.DlgSpellBtnReplace    = editor.lang.aspell.btnReplace;
				FCKLang.DlgSpellBtnReplaceAll = editor.lang.aspell.btnReplaceAll;
				FCKLang.DlgSpellBtnUndo       = editor.lang.aspell.btnUndo;
				// controlWindow.js
				FCKLang.DlgSpellNoSuggestions = editor.lang.aspell.noSuggestions;
				// spellchecker.html
				FCKLang.DlgSpellProgress      = editor.lang.aspell.progress;
				// End language
				
				// Start spellcheck!
				oSpeller.openChecker();
			}
			else if (i++ == 180) // Timeout: 180 * 250ms = 45s.
			{
				alert(errorMsg);
				dialog.hide();
			}
		};
	}
	
	function oSpeller_OnFinished(dialog, numberOCorrections)
	{
		if (numberOCorrections > 0)
		{
			editor.focus();
			editor.fire('saveSnapshot'); // Best way I could find to trigger undo steps.

			// Intermediate step to be able to restore altered data
			var scratch = document.getElementById(scratchId);
			scratch.innerHTML = document.getElementById(textareaId).value;
			( function ($) {

				// Get the base URL for requests that require absolute pathing
				var url = window.parent.location.protocol + "//" + window.parent.location.host + window.parent.location.pathname;
				var baseUrl = url.substr(0, url.lastIndexOf('/') + 1);        // Get everything thru the last '/'

				// Replace all placeholder icons with original images
				$(scratch).find('img').each(function () {
					var el = $(this); // reextend with jQuery in case prototype is fooling with our heads
					el.attr('src', el.attr('data-aspell-saved-src'));
					el.removeAttr('data-aspell-saved-src');
				});
				dialog.getParentEditor().setData(scratch.innerHTML);
				// IE breaks on this cross-frame request; so we'll try to leave it off
				if (! $.browser.msie) {
					editor.fire('saveSnapshot'); // But there's a blank one between!
				}
			}) (jQuery);
		}
		dialog.hide();
	}
	
	// Fx and IE don't see the same sizes, it seems. That or Fx is allowing everything to grow.
	var minW = 485;
	var minH = 380;
	if (document.all)
	{
		minW = 510;
		minH = 405;
	}
	
	return {
		title: editor.lang.aspell.title,
		minWidth: minW,
		minHeight: minH,
		buttons: [ CKEDITOR.dialog.cancelButton ],
		onShow: function()
		{
			// Put spellcheck input and iframe in the dialog content
			var contentArea = this.getContentElement('general', 'content').getElement();
			contentArea.setHtml(spellHTML);
			
			// Define spellcheck init function
			OnSpellerControlsLoad = function (controlsWindow)
			{
				// Translate the dialog box texts
				var spans  = controlsWindow.document.getElementsByTagName('span');
				var inputs = controlsWindow.document.getElementsByTagName('input');
				var i, attr;
				
				for (i=0; i < spans.length; i++)
				{
					attr = spans[i].getAttribute && spans[i].getAttribute('fckLang');
					if (attr)
						spans[i].innerHTML = FCKLang[attr];
				}
				for (i=0; i < inputs.length; i++)
				{
					attr = inputs[i].getAttribute && inputs[i].getAttribute('fckLang');
					if (attr)
						inputs[i].value = FCKLang[attr];
				}
			}
			
			// Add spellcheck script to head
			CKEDITOR.document.getHead().append(CKEDITOR.document.createElement('script', {
				attributes: {
					type: 'text/javascript',
					src: editor.plugins.aspell.path+'spellerpages/spellChecker.js'
			}}));
			
			// Intermediate step to be able to alter data coming
			// into the spell checker instead of using the raw HTML
			var scratch = document.getElementById(scratchId);
			scratch.innerHTML = editor.getData();
			( function ($) {

				// Get the base URL for requests that require absolute pathing
				var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
				var baseUrl = url.substr(0, url.lastIndexOf('/') + 1);        // Get everything thru the last '/'

				// Replace all images with a placeholder icon
				$(scratch).find('img').each(function () {
					var el = $(this); // reextend with jQuery in case prototype is fooling with our heads
					el.attr('data-aspell-saved-src', el.attr('src'));
					el.attr('src', baseUrl + 'script/ckeditor/plugins/aspell/icons/viewimage.gif');
				});
			}) (jQuery);

			//CKEDITOR.document.getById(textareaId).setValue(sData); <-- doesn't work for some reason
			document.getElementById(textareaId).value = scratch.innerHTML;
			
			// Wait for spellcheck script to load, then execute
			interval = window.setInterval(spellTime(this, errorMsg), 250);
		},
		onHide: function()
		{
			window.ooo = undefined;
			window.int_framsetLoaded = undefined;
			window.framesetLoaded = undefined;
			window.is_window_opened = false;
			
			OnSpellerControlsLoad = null;
			FCKLang = null;
		},
		contents: [
			{
				id: 'general',
				label: editor.lang.aspell.title,
				padding: 0,
				elements: [
					{
						type: 'html',
						id: 'content',
						style: 'width:485;height:380px',
						html: '<div></div>'
					}
				]
			}
		]
	};
});
