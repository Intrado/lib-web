
/*

// Global variable used to keep track of the key listener so that it can be
// unregistered when registerHtmlEditorKeyListener() is called with a new listener.
var currenthtmleditorkeylistener = null;

// Will register this listener when the instance is ready, 
var pendinghtmleditorkeylistener = null;

function registerHtmlEditorKeyListener(listener) {

	// Will register this listener when the instance is ready
	var htmleditorobject;
	if (! (htmleditorobject = RCIEditor.htmlEditorIsReady())) {
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
*/


RCIEditor = {

	textarea: null,

	basename: 'rcicke',

	// Associative array support for settings;
	// use of set/getter's is encouraged
	settings: Array(),

	setSetting: function (name, value) {
		this.settings[name] = value;
	},

	getSetting: function (name) {
		return(this.settings[name]);
	},

	// In lieu of a constructor, this function will put us into a known good state
	reset: function () {
		//console.log('reset fired!');

		// TODO: confirm that imageScaling will not affect "attachments" uploaded on the same message page
		this.setSetting('image_scaling', 0);

		// SMK added 2012-12-13 to get the base URL for requests that require absolute pathing
		var baseUrl= new String(document.location);
		baseUrl = baseUrl.substr(0, baseUrl.lastIndexOf('/') + 1);
		this.setSetting('baseUrl', baseUrl);
	},

	// SMK added 2012-12-17 to activate CKE inline editing
	applyEditor: function(editorMode, textarea, target) {

		// If CKEDITOR is not ready, check back here every second until it is
		if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {

			// We will try again in one second since CKE is not ready yet
			window.setTimeout(function() { this.applyEditor(editorMode, textarea, target)}, 1000);
			return;
		}

		// stash away the editorMode for reference in other methods
		this.setSetting('editorMode', editorMode);

		// Re-extend with prototype in case we're on a jquery page
		this.textarea = $(textarea);

		// Hide the text area form field until we are done initializing
		this.textarea.hide();

		this.setLoadingVisibility(true);

		// Here's the base name of the text element; we'll
		// make several new elements with derived names
		this.basename = this.textarea.id;

		var cke = null;

		if (editorMode == 'wysiwyg') {
			// Add an IFRAME to the page that will load up the inline editor
			cke = new Element('iframe', {
				'id': this.basename + 'inline',
				'src': this.getSetting('baseUrl') + 'script/rcieditor.php?t=' + target,
				'style': 'width: 800px; height: 400px; border: 1px solid #999999;'
			});

			// So now we have the inline editor component loading in an iframe;
			// the next move is up to the iframe content to call back the next
			// function below to get the two halves communicating cross-frame.
		}
		else {
			// For the full CKEditor, the toolbars/plugins
			// are different depending on the editorMode
			var extraPlugins = 'aspell';
			var extraButtons = ['PasteFromWord','SpellCheck'];
			switch (this.getSetting('editorMode')) {

				default:
				case 'plain':
					// Nothing extra to add for the plain legacy editor
					break;

				case 'normal':
					// Add the mkField plugin
					if (extraPlugins.length) extraPlugins += ',';
					extraPlugins += 'mkfield';

					// Add the mkfield button
					extraButtons.push('mkField');
					break;

				case 'full':
					// Add the mkField and mkBlock plugins
					if (extraPlugins.length) extraPlugins += ',';
					extraPlugins += 'mkfield,mkblock';

					// Add the mkfield button
					extraButtons.push('mkField');
					// Add the mkblock button
					extraButtons.push('mkBlock');
					// Add the thememgr button
					// extraButtons.push('thememgr');
					break;
			}

			// Here's the first new element we'll make - its id is basename
			cke = new Element('div', { 'id': this.basename });

			// SMK added to selectively enable reduction scaling for uploaded images;
			// page that includes CKE must set global var htmlEditorImageScalingEnable
			// to true to enable scaling, otherwise scaling will be disabled by default;
			// uploader.php will pass the argument on to f.handleFileUpload() which will
			// ultimately be responsible for enforcement of this flag
			var uploaderURI = this.getSetting('baseUrl') + 'uploadimage.php';
			if ((max_size = parseInt(this.getSetting('image_scaling'))) > 0) {
				//console.log('Image Scaling enabled!');
				uploaderURI += '?scaleabove=' + max_size;
			}


			// Now attach CKE to the form element with the name basename
			CKEDITOR.replace(this.basename, {
				'customConfig': '', // Prevent ckeditor from trying to load an external configuration file, should improve startup time.
				'disableNativeSpellChecker': false,
				'browserContextMenuOnCtrl': true,
				'width': '100%',
				'height': '400px',
				'filebrowserImageUploadUrl' : uploaderURI,
				'toolbarStartupExpanded' : (! this.getSetting('hidetoolbar')),
				'extraPlugins': extraPlugins,
				'toolbar': [
					['Print','Source'],
					['Styles','Format','Font','FontSize'],
					['Undo','Redo'],
					'/',
					extraButtons,
					['Link','Image','Table','HorizontalRule'],
					['Bold','Italic','Underline','Strike','TextColor','BGColor','RemoveFormat'],
					['NumberedList','BulletedList','JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock','Outdent','Indent']
				],
				'on': {
					'instanceReady': function(event) {
						//htmlEditorFinishAttachment(this);
						RCIEditor.callbackEditorLoaded(this);
					}.bindAsEventListener(this.textarea),
					'key': RCIEditor.eventListener,
					'blur': RCIEditor.eventListener,
					'saveSnapshot': RCIEditor.eventListener,
					'afterCommandExec': RCIEditor.eventListener,
					'insertHtml': RCIEditor.eventListener,
					'insertElement': RCIEditor.eventListener,
					'focus': RCIEditor.eventListener
				}
			});

			// Now we are just waiting on CKE to finish its business;
			// it will fire instanceReady() function when it is done.
		}
		// The second new element has the same id with a 'hider' suffix
		var hider = new Element('div', {'id': this.basename + 'hider'});
		hider.hide();

		// The hider contains the CKEditor so that we
		// can show/hide the whole thing as needed
		hider.insert(cke);

		// And here will stick hider into the DOM
		if (target != undefined) {
			$(target).insert(hider);
		} else {
			document.body.insert(hider);
		}
	},

	/**
	 * This method is called onload from the IFRAMED wysiwyg editor
	 * page that was loaded by applyEditor(). The target is the ID of
	 * the div that we want to load our textarea content into for the
	 * inline editor to have at.
	 */
	callbackEditorLoaded: function(activeContainerId) {
		//console.log('callbackEditorLoaded called');

		this.setSetting('activeContainerId', activeContainerId);

		this.setLoadingVisibility(false);

		if (this.getSetting('editorMode') == 'wysiwyg') {
			/*
			// SMK notes 2012-12-20 that the code below works to find the
			// wysiwyg frame, but we don't seem to need it for anything...
			if (fr = document.getElementById(this.basename + 'inline')) {
				t = fr.contentWindow.document.getElementById(activeContainerId);
				if (! (wysiwygEditor = $(t))) {
					console.log("Couldn't find the active editor's container ID [" + activeContainerId + ']');
				}
			}
			else {
				console.log("Couldn't find the iframe [" + this.basename + 'inline' + ']');
			}
			*/

			//wysiwygEditor.innerHTML = 'oye!';
		}
		else {

			// 'plain', 'normal', and 'full' end up here...

			// SMK added 2012-12-06 - The third new element is a CSS fix for
			// generic div/span heights compressed by external page CSS
			var ckecssoverrides = new Element('style');
			var css_toolbars = 'span.cke_toolgroup { height: 27px; } a.cke_dialog_tab { height: 26px; } a.cke_button { height: 25px; }';
			ckecssoverrides.innerHTML = css_toolbars;
			this.textarea.up().insert(ckecssoverrides);

/*
// Finish our event listener for the CKE object
registerHtmlEditorKeyListener(pendinghtmleditorkeylistener);
pendinghtmleditorkeylistener = null;
*/

			// Hide our AJAXy loading indicator
			this.setLoadingVisibility(false);

			var htmleditorobject = this.getHtmlEditorObject();
			if (! htmleditorobject) {
				console.log('FAIL!');
				return;
			}

/*
// SMK note: this is strange code - maybe we can cut it. if there is no textarea, or the id is different than we think it should be then save it? the conditions and the resulting action appear to bear no relation to one another.
if (! htmleditorobject.currenttextarea || htmleditorobject.currenttextarea.identify() != this.textarea.identify()) {
saveHtmlEditorContent(htmleditorobject);
}
*/

			// A little data sanitizing for the raw textarea form content
			var html = this.textarea.value.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
			htmleditorobject.instance.setData(html);

			// The presence of the HtmlEditor classname signals
			// f.getHtmlEditorObject() to use CKE instead of the bare textarea
			this.textarea.hide().addClassName('HtmlEditor');

			// Initial validation - hopefully it checks out!
			this.validate();
		}
	},

	loadingVisible: false,
	setLoadingVisibility: function (visible) {
		if (visible) {
			if (this.loadingVisible || (! this.textarea)) {
				return;
			}

			// Show an AJAXy loading indication
			if (! $('htmleditorloadericon')) {
				this.textarea.insert({'before': '<span class="HTMLEditorAjaxLoader" id="htmleditorloadericon"><img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads. </span>'});
			}
			this.loadingVisible = true;
		}
		else {
			if (! this.loadingVisible) {
				return;
			}

			// Hide our AJAXy loading indicator
			if ($('htmleditorloadericon')) {
				$('htmleditorloadericon').remove();
			}
			$(this.basename + 'hider').show();
			this.loadingVisible = false;
		}
	},

	/**
	 * Returns the textarea that the html editor is currently replacing
	 */
	getHtmlEditorObject: function () {

		do { // do this once...

			if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {
				break;
			}

			if (typeof CKEDITOR.instances == 'undefined') {
				break;
			}

			if (! CKEDITOR.instances) {
				break;
			}

			var instance = false;
			for (var i in CKEDITOR.instances) {
				if (CKEDITOR.instances[i].name == this.basename) {
					instance = CKEDITOR.instances[i];
				}
				else {
					//console.log('CKEDITOR instance.name = [' + CKEDITOR.instances[i].name + ']');
				}
			}
			if (! instance) {
				break;
			}

			var container = $('cke_' + this.basename);
			if (! container) {
				break;
			}

			// TODO - verify that we can just use this.textarea instead of locating it again here:
			var textarea = container.previous();
			var textareauseshtmleditor = textarea && textarea.match('textarea.HtmlEditor');

			return {'instance': instance, 'container': container, 'currenttextarea': textareauseshtmleditor ? textarea : null};
		} while (false);

		return null;
	},

	/**
	 * Updates the textarea that the html editor replaces with the latest content.
	 *
	 * @return object containing the html editor instance and container, or null if not loaded
	 */
	saveHtmlEditorContent: function (existinghtmleditorobject) {

		// TODO: use this.textarea if possible instead of trying to find it again
		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		var textarea = htmleditorobject.currenttextarea;
		if (textarea) {
			var content = htmleditorobject.instance.getData();
			textarea.value = this.cleanContent(content);
			textarea.fire('HtmlEditor:SavedContent');
		}
		
		return htmleditorobject;
	},

	/**
	 * SMK extracted this portion of code from f.saveHtmlEditorContent() since that
	 * function requires the presence of a single CKEDITOR instance, but the multi-
	 * instance scenario for wysiwyg inline editing also needs to be able to do the
	 * same type of cleanup.
	 */
	cleanContent: function (content) {
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

		var html = this.cleanFieldInserts(tempdiv.innerHTML).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>');

		// CKEditor inserts blank tags even if the user has deleted everything.
		// check if there is an image or href tag... if not, strip the tags and see if there is any text
		if (! html.match(/[img,href]/)) {

			// strips all html tags, then strips whitespace. If there is nothing left... set the html to an empty string
			if (html.stripTags().strip().replace(/[&nbsp;,\n,\r,\t]/g, '') == '')
				html = '';
		}

		return(html);
	},

	// Corrects any html tags that may be inside a data-field insert.
	// Example: &lt;&lt;First <b>Name</b>&gt;&gt; becomes <b>&lt;&lt;First Name&gt;&gt;
	// NOTE: It is assumed that the tokens are &lt;&lt; and &gt;&gt; instead of << and >>.
	cleanFieldInserts: function (html) {
		var regex = /&lt;(<.*?>)*?&lt;(.+?)&gt;(<.*?>)*?&gt;/g;
		var matches = html.match(regex);
		
		if (! matches) {
			return html;
		}

		for (var i = 0, count = matches.length; i < count; i++) {
			var cleaner = matches[i].replace(regex, '$1&lt;&lt;$2&gt;&gt;$3');
			var beforeinsert = cleaner.match(/^(.*)?&lt;&lt;/)[1] || '';
			var afterinsert = cleaner.match(/&gt;&gt;(.*)?$/)[1] || '';
		
			var field = cleaner.match(/&lt;&lt;(.+)?&gt;&gt;/)[1] || '';
			
			var opentags = field.match(/<[^\/]*?>/g);
			if (opentags) {
				beforeinsert += opentags.join('');
			}

			var closedtags = field.match(/<\/.*?>/g);
			if (closedtags) {
				afterinsert = closedtags.join('') + afterinsert;
			}

			var field = field.stripTags().strip();
			html = html.replace(matches[i], beforeinsert + '&lt;&lt;' + field + '&gt;&gt;' + afterinsert);
		}
		return html;
	},

	/*
	//SMK notes that these appear to be unused. disabling temporarily to see if anything breaks before removal
	hideHtmlEditor: function () {
		if ($('cke_' + this.basename)) {
			$(this.basename + 'hider').insert($('cke_' + this.basename));
		}
	},

	clearHtmlEditorContent: function () {
		var htmleditorobject;
		if (! (htmleditorobject = this.htmlEditorIsReady())) {
			return;
		}
		htmleditorobject.instance.setData('');
	},
	*/

	htmlEditorIsReady: function () {
		var htmleditorobject;
		if (! (htmleditorobject = this.getHtmlEditorObject())) {
			return(false);
		}
		return(htmleditorobject);
	},

	/**
	 * Events that trigger this listener are keystrokes, and content changes
	 * within CKEditor. we wait for half a second before proceeding to grab
	 * any changes made and run them through the validator; the delay prevents
	 * it from running promiscuously every time someone presses a key while
	 * typing.
	 */
	eventTimer: null,
	eventListener: function () {

		// We got a new event so reset the timer
		window.clearTimeout(this.eventTimer);

		// Get the Editor that we're working with
		var htmleditor = RCIEditor.getHtmlEditorObject();

		// Set a new timer to fire the save/check
		this.eventTimer = window.setTimeout(function() {

			// Save the changes to the hidden textarea
			RCIEditor.saveHtmlEditorContent(htmleditor);

			// Run the form validation against the textarea
			//form_do_validation(htmleditor.currenttextarea.up("form"), htmleditor.currenttextarea);
			RCIEditor.validate();
		}, 500);
	},

	validate: function() {
		window.top.form_do_validation(this.textarea.up("form"), this.textarea);
	}
}

RCIEditor.reset();

/**
 * Legacy function wrappers - get rid of these once
 * all calls are converted to f.applyEditor()
 */
function applyHtmlEditor (textarea, target, hidetoolbar) {
	RCIEditor.reset();
	RCIEditor.setSetting('hidetoolbar', hidetoolbar);
	RCIEditor.applyEditor('plain', textarea, target);
}

function applyFullEditor (textarea, target, hidetoolbar) {
	RCIEditor.reset();
	RCIEditor.setSetting('hidetoolbar', hidetoolbar);
	RCIEditor.applyEditor('full', textarea, target);
}

function applyNormalEditor (textarea, target, hidetoolbar) {
	RCIEditor.reset();
	RCIEditor.setSetting('hidetoolbar', hidetoolbar);
	RCIEditor.applyEditor('normal', textarea, target);
}

function applyWysiwygEditor (textarea, target) {
	RCIEditor.reset();
	RCIEditor.applyEditor('wysiwyg', textarea, target);
}

