
// Global vars added by SMK 2012-11-30 used as the base name for the ID's of the
// CKE elements; CKE 4.x handles this differenly from 3.X
var reusableckeditor_basename = 'reusableckeditor';
var htmlEditorImageScale = 0;
// TODO: confirm that imageScaling will not affect "attachments" uploaded on the same message page

// SMK added 2012-12-13 to get the base URL for requests that require absolute pathing
var htmlEditorBaseUrl = new String(document.location);
htmlEditorBaseUrl = htmlEditorBaseUrl.substr(0, htmlEditorBaseUrl.lastIndexOf('/') + 1);

// Returns the textarea that the html editor is currently replacing.
function getHtmlEditorObject() {

	do { // do this once...

		if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {
			break;
		}

		//if ((typeof CKEDITOR.instances == 'undefined') || !CKEDITOR.instances || !(instance = CKEDITOR.instances['reusableckeditor'])) {
		if (typeof CKEDITOR.instances == 'undefined') {
			break;
		}

		if (! CKEDITOR.instances) {
			break;
		}

		var instance = false;
		for (var i in CKEDITOR.instances) {
			if (CKEDITOR.instances[i].name == reusableckeditor_basename) {
				instance = CKEDITOR.instances[i];
			}
			else console.log('CKEDITOR instance.name = [' + CKEDITOR.instances[i].name + ']');
		}
		if (! instance) {
			break;
		}

		var container = $('cke_' + reusableckeditor_basename);
		if (! container) {
			break;
		}

		var textarea = container.previous();
		var textareauseshtmleditor = textarea && textarea.match('textarea.HtmlEditor');

		return {'instance': instance, 'container': container, 'currenttextarea': textareauseshtmleditor ? textarea : null};
	} while (false);

	return null;
}

// Global variable used to keep track of the key listener so that it can be
// unregistered when registerHtmlEditorKeyListener() is called with a new listener.
var currenthtmleditorkeylistener = null;

// Will register this listener when the instance is ready, 
var pendinghtmleditorkeylistener = null;

function registerHtmlEditorKeyListener(listener) {

	// Will register this listener when the instance is ready
	var htmleditorobject;
	if (! (htmleditorobject = htmlEditorIsReady())) {
		currenthtmleditorkeylistener = null;
		pendinghtmleditorkeylistener = listener;
		return;
	}
	
	if (currenthtmleditorkeylistener) {
		htmleditorobject.instance.removeListener('key', currenthtmleditorkeylistener);
		htmleditorobject.instance.removeListener('blur', currenthtmleditorkeylistener);
		htmleditorobject.instance.removeListener('saveSnapshot', currenthtmleditorkeylistener);
		htmleditorobject.instance.removeListener('afterCommandExec', currenthtmleditorkeylistener);
		htmleditorobject.instance.removeListener('insertHtml', currenthtmleditorkeylistener);
		htmleditorobject.instance.removeListener('insertElement', currenthtmleditorkeylistener);

		// Needed for Link plugin's OK button:
		htmleditorobject.instance.removeListener('focus', currenthtmleditorkeylistener);
	}
	
	if (listener) {
		htmleditorobject.instance.on('key', listener);
		htmleditorobject.instance.on('blur', listener);
		htmleditorobject.instance.on('saveSnapshot', listener);
		htmleditorobject.instance.on('afterCommandExec', listener);
		htmleditorobject.instance.on('insertHtml', listener);
		htmleditorobject.instance.on('insertElement', listener);

		// Needed for Link plugin's OK button:
		htmleditorobject.instance.on('focus', listener);
	}
	
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
		var content = htmleditorobject.instance.getData();
		textarea.value = cleanContent(content);
		textarea.fire('HtmlEditor:SavedContent');
	}
	
	return htmleditorobject;
}

// SMK extracted this portion of code from f.saveHtmlEditorContent() since that
// function requires the presence of a single CKEDITOR instance, but the multi-
// instance scenario for wysiwyg inline editing also needs to be able to do the
// same type of cleanup.
function cleanContent(content) {
	var tempdiv = new Element('div').insert(content);

	// Unstyle any image elements having src="viewimage.php?id=.."
	var images = tempdiv.select('img');
	for (var i = 0, count = images.length; i < count; i++) {
		var image = images[i];
		var matches = image.src.match(/viewimage\.php\?id=(\d+)/);
		if (matches) {
			image.replace('<img src="viewimage.php?id=' + matches[1] + '">');
		}
	}

	var html = cleanFieldInserts(tempdiv.innerHTML).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>');

	// CKEditor inserts blank tags even if the user has deleted everything.
	// check if there is an image or href tag... if not, strip the tags and see if there is any text
	if (! html.match(/[img,href]/)) {

		// strips all html tags, then strips whitespace. If there is nothing left... set the html to an empty string
		if (html.stripTags().strip().replace(/[&nbsp;,\n,\r,\t]/g, '') == '')
			html = '';
	}

	return(html);
}

// Corrects any html tags that may be inside a data-field insert.
// Example: &lt;&lt;First <b>Name</b>&gt;&gt; becomes <b>&lt;&lt;First Name&gt;&gt;
// NOTE: It is assumed that the tokens are &lt;&lt; and &gt;&gt; instead of << and >>.
function cleanFieldInserts(html) {
	var regex = /&lt;(<.*?>)*?&lt;(.+?)&gt;(<.*?>)*?&gt;/g;
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
	if ($('cke_' + reusableckeditor_basename)) {
		$(reusableckeditor_basename + 'hider').insert($('cke_' + reusableckeditor_basename));
	}
}

function clearHtmlEditorContent() {
	var htmleditorobject;
	if (! (htmleditorobject = htmlEditorIsReady())) {
		return;
	}
	htmleditorobject.instance.setData('');
}

// Loads the html editor if necessary.
// NOTE: It is assumed that there be only a single html editor on the page; CKEditor is buggy with multiple instances.
var htmleditorloadinterval = null;

function htmlEditorIsReady() {
	var htmleditorobject;
	if (! (htmleditorobject = getHtmlEditorObject())) {
		return(false);
	}
	return(htmleditorobject);
}

function applyHtmlEditor(textarea, dontwait, target, hidetoolbar) {

	// Re-extend with prototype in case we're on a jquery page
	textarea = $(textarea);

	// Hide the text area form field until we are done initializing
	textarea.hide();

	// Show an AJAXy loading indication
	if (! $('htmleditorloadericon')) {
		textarea.insert({'before': '<span class="HTMLEditorAjaxLoader" id="htmleditorloadericon"><img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads. </span>'});
	}

	// Here's the base name of the text element; we'll make several new elements with derived names
	reusableckeditor_basename = textarea.id;

	// Here's the first new element we'll make - its id is basename
	var reusableckeditor = new Element('div', {'id':reusableckeditor_basename});

	// The second new element has the same id with a 'hider' suffix
	var hider = new Element('div', {'id':reusableckeditor_basename + 'hider'});
	hider.hide();

	// The hider contains the reusable CKE
	hider.insert(reusableckeditor);

	// And here will stick hider into the DOM
	if (target != undefined) {
		$(target).insert(hider);
	} else {
		document.body.insert(hider);
	}

	// Now wait around for the CKE object to be accessible
	htmlEditorWaitForCKE(textarea, dontwait, hidetoolbar);
}

function htmlEditorWaitForCKE(textarea, dontwait, hidetoolbar) {

	// If CKEDITOR is not ready, check back here every second until it is
        if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {
		window.setTimeout(function() { htmlEditorWaitForCKE(textarea, false, hidetoolbar); }, (dontwait ? 10 : 1000));
		return;
        }

	// SMK added to selectively enable reduction scaling for uploaded images;
	// page that includes CKE must set global var htmlEditorImageScalingEnable
	// to true to enable scaling, otherwise scaling will be disabled by default;
	// uploader.php will pass the argument on to f.handleFileUpload() which will
	// ultimately be responsible for enforcement of this flag
	var uploaderURI = htmlEditorBaseUrl + 'uploadimage.php';
	if ((typeof htmlEditorImageScale !== 'undefined') && (htmlEditorImageScale > 0)) {
		//console.log('Image Scaling enabled!');
		uploaderURI += '?scaleabove=' + parseInt(htmlEditorImageScale);
	}

	// Now attach CKE to the form element with the name basename
	CKEDITOR.replace(reusableckeditor_basename, {
		'customConfig': '', // Prevent ckeditor from trying to load an external configuration file, should improve startup time.
		'disableNativeSpellChecker': false,
		'browserContextMenuOnCtrl': true,
		'width': '100%',
		'height': '400px',
		'filebrowserImageUploadUrl' : uploaderURI,
		'toolbarStartupExpanded' : !hidetoolbar,
		'extraPlugins': 'aspell,mkfield,attachmentlink',
		'toolbar': [
			    ['Print','Source'],
			    ['Undo','Redo'],
			    ['PasteFromWord','SpellCheck','mkField','AttachmentLink'],
			    ['Link','Image','Table','HorizontalRule'],
			    '/',
			    ['Bold','Italic','Underline','Strike','TextColor','BGColor','RemoveFormat'],
			    ['NumberedList','BulletedList','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Outdent','Indent'],
			    '/',
			    ['Styles','Format','Font','FontSize']
			],
		'on': {
			'instanceReady': function(event) {
				htmlEditorFinishAttachment(this);
			}.bindAsEventListener(textarea)
		}
	});

	// Now we are just waiting on CKE to finish its business;
	// it will fire instanceReady() function when it is done.
}

function htmlEditorFinishAttachment(textarea) {

	// SMK added 2012-12-06 - The third new element is a CSS fix for
	// generic div/span heights compressed by external page CSS
	var ckecssoverrides = new Element('style');
	var css_toolbars = 'span.cke_toolgroup { height: 27px; } a.cke_dialog_tab { height: 26px; } a.cke_button { height: 25px; }';
	ckecssoverrides.innerHTML = css_toolbars;
	textarea.up().insert(ckecssoverrides);

	// Finish our event listener for the CKE object
	registerHtmlEditorKeyListener(pendinghtmleditorkeylistener);
	pendinghtmleditorkeylistener = null;

	// Hide our AJAXy loading indicator
	textarea.previous('.HTMLEditorAjaxLoader').remove();
	if ($('htmleditorloadericon'))
		$('htmleditorloadericon').remove();

	var htmleditorobject = getHtmlEditorObject();
	if (! htmleditorobject) {
		console.log('FAIL!');
		return;
	}

	if (! htmleditorobject.currenttextarea || htmleditorobject.currenttextarea.identify() != textarea.identify()) {
		saveHtmlEditorContent(htmleditorobject);
	}

	// A little data sanitizing for the raw textarea form content
	var html = textarea.value.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
	htmleditorobject.instance.setData(html);

	// The presence of the HtmlEditor classname signals
	// f.getHtmlEditorObject() to use CKE instead of the bare textarea
	textarea.hide().addClassName('HtmlEditor');

}

