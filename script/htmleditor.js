
// Returns the textarea that the html editor is currently replacing.
function getHtmlEditorObject() {
	if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {
		return null;
	}

	var instance;
	if ((typeof CKEDITOR.instances == 'undefined') || !CKEDITOR.instances || !(instance = CKEDITOR.instances['reusableckeditor']))
		return null;

	var container = $('cke_reusableckeditor');
	
	if (!container)
		return null;

	var textarea = container.previous();
	var textareauseshtmleditor = textarea && textarea.match('textarea.HtmlEditor');
	
	return {'instance': instance, 'container': container, 'currenttextarea': textareauseshtmleditor ? textarea : null};
}

var currenthtmleditorkeylistener = null; // Global variable used to keep track of the key listener so that it can be unregistered when registerHtmlEditorKeyListener() is called with a new listener.
var pendinghtmleditorkeylistener = null; // Will register this listener when the instance is ready, 
function registerHtmlEditorKeyListener(listener) {
	var htmleditorobject = getHtmlEditorObject();
	if (!htmleditorobject) {
		currenthtmleditorkeylistener = null;
		pendinghtmleditorkeylistener = listener; // Will register this listener when the instance is ready, called in applyHtmlEditor().
		
		return;
	}
	
	if (currenthtmleditorkeylistener)
		htmleditorobject.instance.removeListener('key', currenthtmleditorkeylistener);
	if (listener)
		htmleditorobject.instance.on('key', listener);
	
	currenthtmleditorkeylistener = listener;
}

// Updates the textarea that the html editor replaces with the latest content.
// Returns null if the html editor is not loaded.
// Returns an object containing the html editor instance and also the html editor's container.
function saveHtmlEditorContent(existinghtmleditorobject) {
	var htmleditorobject = existinghtmleditorobject || getHtmlEditorObject();
	if (!htmleditorobject) {
		return null;
	}
	
	var textarea = htmleditorobject.currenttextarea;
	if (textarea) {
		var tempdiv = new Element('div').insert(htmleditorobject.instance.getData());

		// Unstyle any image elements having src="viewimage.php?id=.."
		var images = tempdiv.select('img');
		for (var i = 0, count = images.length; i < count; i++) {
			var image = images[i];
			var matches = image.src.match(/viewimage\.php\?id=(\d+)/);
			if (matches.length == 2) {
				image.replace('<img src="viewimage.php?id=' + matches[1] + '">');
			}
		}

		var html = cleanFieldInserts(tempdiv.innerHTML).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>');

		if (images.length < 1) {
			// CKEditor inserts blank tags even if the user has deleted everything.
			if (html.stripTags().strip() == '')
				html = '';
		}

		textarea.value = html;
		textarea.fire('HtmlEditor:SavedContent');
	}
	
	return htmleditorobject;
}

// Corrects any html tags that may be inside a data-field insert.
// Example: &lt;&lt;First <b>Name</b>&gt;&gt; becomes <b>&lt;&lt;First Name&gt;&gt;
// NOTE: It is assumed that the tokens are &lt;&lt; and &gt;&gt; instead of << and >>.
function cleanFieldInserts(html) {
	var regex = /&lt;(<.*?>)*?&lt;(.+)?&gt;(<.*?>)?&gt;/g;
	var matches = html.match(regex);
	
	if (!matches)
		return html;

	for (var i = 0, count = matches.length; i < count; i++) {
		var cleaner = matches[i].replace(regex, '$1&lt;&lt;$2&gt;&gt;$3');
		var beforeinsert = cleaner.match(/^(.*)?&lt;&lt;/)[1] || '';
		var afterinsert = cleaner.match(/&gt;&gt;(.*)?$/)[1] || '';
	
		var field = cleaner.match(/&lt;&lt;(.+)?&gt;&gt;/)[1] || '';
		
		var opentags = field.match(/<[^\/]*?>/g);
		if (opentags)
			beforeinsert += opentags.join('');
		
		var closedtags = field.match(/<\/.*?>/g);
		if (closedtags)
			afterinsert = closedtags.join('') + afterinsert;
		
		var field = field.stripTags().strip();
		html = html.replace(matches[i],
			beforeinsert + '&lt;&lt;' + field + '&gt;&gt;' + afterinsert);
	}
	return html;
}

function hideHtmlEditor() {
	if ($('cke_reusableckeditor'))
		$('reusableckeditorhider').insert($('cke_reusableckeditor'));
}

function clearHtmlEditorContent() {
	var htmleditorobject = getHtmlEditorObject();
	if (!htmleditorobject)
		return;
	
	htmleditorobject.instance.setData('');
}

// Loads the html editor if necessary.
// NOTE: It is assumed that there be only a single html editor on the page; CKEditor is buggy with multiple instances.
function applyHtmlEditor(textarea) {
	textarea = $(textarea);

	var editorobject = getHtmlEditorObject();
	if (!editorobject) {
		if ($('reusableckeditor'))
			return; // The editor instance is still loading.

		textarea.insert({'before': '<span class="HTMLEditorAjaxLoader"><img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads. </span>'});
		textarea.hide();

		var reusableckeditor = new Element('div', {'id':'reusableckeditor'});
		if (document.body) {
			document.body.insert(new Element('div', {'id':'reusableckeditorhider'}).hide().insert(reusableckeditor));
			CKEDITOR.replace(reusableckeditor, {
				'customConfig': '', // Prevent ckeditor from trying to load an external configuration file, should improve startup time.
				'removePlugins': 'wsc,scayt,smiley,showblocks,flash,elementspath,save',
				'toolbar': [
					['Preview','Print'],
					['Undo','Redo','-','SelectAll','Cut','Copy','Paste','PasteText','PasteFromWord','Find','Replace'],
					'/',
					['Styles','Format'],
					['NumberedList','BulletedList','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Outdent','Indent'],
					'/',
					['Font','FontSize','Bold', 'Italic', 'Underline','Strike','Subscript','Superscript',  'TextColor','BGColor', 'RemoveFormat'],
					['SpecialChar','Blockquote','Link', 'Image','Table','HorizontalRule','PageBreak']
				],
				'disableObjectResizing': true,
				'resize_enabled': false,
				'width': '100%',
				'filebrowserImageUploadUrl' : 'uploadimage.php',
				'on': {
					'instanceReady': function(event) {
						this.previous('.HTMLEditorAjaxLoader').remove();
						applyHtmlEditor(this);
						registerHtmlEditorKeyListener(pendinghtmleditorkeylistener);
						pendinghtmleditorkeylistener = null;
					}.bindAsEventListener(textarea)
				}
			});
		} else {
			document.observe('dom:loaded', function(event) {
				applyHtmlEditor(this);
			}.bindAseventListener(textaera));
		}

		return;
	}
	
	if (!editorobject.currenttextarea || editorobject.currenttextarea.identify() != textarea.identify()) {
		saveHtmlEditorContent(editorobject);
	}

	var html = textarea.value.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
	editorobject.instance.setData(html);
	
	textarea.hide().addClassName('HtmlEditor').insert({'after':editorobject.container});
}
