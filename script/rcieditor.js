/**
 * Multi-mode HTML editor
 *
 * EXTERNAL DEPENDENCIES
   * prototype.js
   * jquery.js
   * json2.js
 *
 * CHANGE LOG
   * SMK create 2012-12-01
 */
function rcieditor() {
	var self = this;

	self.textarea = null;

	self.basename = 'rcicke';

	// Associative array support for settings; use of set/getter's is encouraged
	self.settings = Array();

	// Setting setter
	self.setSetting = function (name, value) {
		self.settings[name] = value;
	};

	// Setting getter
	self.getSetting = function (name) {
		return(self.settings[name]);
	};

	// In lieu of a constructor, this function will put us into a known good state
	self.reset = function () {

		// Image scaling is disabled by default
		self.setSetting('image_scaling', 0);

		// Get the base URL for requests that require absolute pathing
		var baseUrl= new String(document.location);
		baseUrl = baseUrl.substr(0, baseUrl.lastIndexOf('/') + 1);
		self.setSetting('baseUrl', baseUrl);
	};

	// SMK added 2012-12-17 to activate CKE inline editing
	self.applyEditor = function(editorMode, textarea, target) {

		// If CKEDITOR is not ready, check back here every second until it is
		if ((typeof CKEDITOR == 'undefined') || (! CKEDITOR)) {

			// We will try again in one second since CKE is not ready yet
			window.setTimeout(function() { self.applyEditor(editorMode, textarea, target); }, 1000);
			return;
		}

		// stash away the editorMode for reference in other methods
		self.setSetting('editorMode', editorMode);

		// Re-extend with prototype in case we're on a jquery page
		self.textarea = $(textarea);

		// Hide the text area form field until we are done initializing
		self.textarea.hide();

		self.setLoadingVisibility(true);

		// base name of the text element; we'll make several new elements with derived names
		self.basename = self.textarea.id;

		var cke = null;

		if (editorMode == 'wysiwyg') {
			self.setSetting('image_scaling', 500);

			// Add an IFRAME to the page that will load up the inline editor
			cke = new Element('iframe', {
				'id': self.basename + 'inline',
				'src': self.getSetting('baseUrl') + 'script/rcieditor.php?t=' + target,
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
			switch (self.getSetting('editorMode')) {

				default:
				case 'plain':
					// Nothing extra to add for the plain legacy editor
					break;

				case 'normal':
					self.setSetting('image_scaling', 500);

					// Add the mkField plugin
					if (extraPlugins.length) extraPlugins += ',';
					extraPlugins += 'mkfield';

					// Add the mkfield button
					extraButtons.push('mkField');
					break;

				case 'full':
					self.setSetting('image_scaling', 500);

					// Add the mkField and mkBlock plugins
					if (extraPlugins.length) extraPlugins += ',';
					extraPlugins += 'mkfield,mkblock,thememgr';

					// Add the mkfield button
					extraButtons.push('mkField');

					// Add the mkblock button
					extraButtons.push('mkBlock');

					// Add the thememgr button
					extraButtons.push('themeMgr');
					break;
			}

			// Grab the scratch space to use for this kind of editor
			var scratch = jQuery('#rcieditor_scratch');
			self.setSetting('rcieditor_scratch', scratch);

			// Here's the first new element we'll make - its id is basename
			cke = new Element('div', { 'id': self.basename });

			// SMK added to selectively enable reduction scaling for uploaded images;
			// page that includes CKE must set global var htmlEditorImageScalingEnable
			// to true to enable scaling, otherwise scaling will be disabled by default;
			// uploader.php will pass the argument on to f.handleFileUpload() which will
			// ultimately be responsible for enforcement of this flag
			var uploaderURI = self.getSetting('baseUrl') + 'uploadimage.php';
			var max_size;
			if ((max_size = parseInt(self.getSetting('image_scaling'))) > 0) {
//console.log('Image Scaling enabled!');
				uploaderURI += '?scaleabove=' + max_size;
			}

			// Now attach CKE to the form element with the name basename
			CKEDITOR.replace(self.basename, {
				'customConfig': '', // Prevent ckeditor from trying to load an external configuration file, should improve startup time.
				'disableNativeSpellChecker': false,
				'browserContextMenuOnCtrl': true,
				'width': '100%',
				'height': '400px',
				'filebrowserImageUploadUrl' : uploaderURI,
				'toolbarStartupExpanded' : (! self.getSetting('hidetoolbar')),
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
						RCIEditor.callbackEditorLoaded(this);
					}.bindAsEventListener(self.textarea),
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
		var hider = new Element('div', { 'id': self.basename + 'hider' });
		hider.hide();

		// hider contains the CKEditor to show/hide the whole thing as needed
		hider.insert(cke);

		// And here will stick hider into the DOM
		if (target != undefined) {
			$(target).insert(hider);
		} else {
			document.body.insert(hider);
		}
	};

	/**
	 * This method is called onload from the IFRAMED wysiwyg editor
	 * page that was loaded by applyEditor(). The target is the ID of
	 * the div that we want to load our textarea content into for the
	 * inline editor to have at.
	 */
	self.callbackEditorLoaded = function(activeContainerId) {
		console.log('callbackEditorLoaded called');

		self.setSetting('activeContainerId', activeContainerId);

		self.setLoadingVisibility(false);

		if (self.getSetting('editorMode') == 'wysiwyg') {
			// 'wysiwyg' comes here, nothing really to do though
		}
		else {
			// 'plain', 'normal', and 'full' end up here...

			// Hide our AJAXy loading indicator
			self.setLoadingVisibility(false);

			var htmleditorobject = self.getHtmlEditorObject();
			if (! htmleditorobject) {
console.log('FAIL!');
				return;
			}

			// A little data sanitizing for the raw textarea form content
			var html = self.textarea.value.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
			htmleditorobject.instance.setData(html);

			// The presence of the HtmlEditor classname signals
			// f.getHtmlEditorObject() to use CKE instead of the bare textarea
			self.textarea.hide().addClassName('HtmlEditor');

			// Initial validation - hopefully it checks out!
			self.validate();
		}
	};

	self.loadingVisible = false;
	self.setLoadingVisibility = function (visible) {
		if (visible) {
			if (self.loadingVisible || (! self.textarea)) {
				return;
			}

			// Show an AJAXy loading indication
			if (! $('htmleditorloadericon')) {
				self.textarea.insert({'before': '<span class="HTMLEditorAjaxLoader" id="htmleditorloadericon"><img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads. </span>'});
			}
			self.loadingVisible = true;
		}
		else {
			if (! self.loadingVisible) {
				return;
			}

			// Hide our AJAXy loading indicator
			if ($('htmleditorloadericon')) {
				$('htmleditorloadericon').remove();
			}
			$(self.basename + 'hider').show();
			self.loadingVisible = false;
		}
	};

	/**
	 * Returns the textarea that the html editor is currently replacing
	 */
	self.getHtmlEditorObject = function () {

		try {

			if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {
				throw 'CKEDITOR is not loaded';
			}

			if (typeof CKEDITOR.instances == 'undefined') {
				throw 'There are no CKEDITOR instances';
			}

			if (! CKEDITOR.instances) {
				throw 'There are no CKEDITOR instances';
			}

			var instance = false;
			for (var i in CKEDITOR.instances) {
				if (CKEDITOR.instances[i].name == self.basename) {
					instance = CKEDITOR.instances[i];
				}
				else {
//console.log('CKEDITOR instance.name = [' + CKEDITOR.instances[i].name + ']');
				}
			}
			if (! instance) {
				throw 'Could not locate our CKEDITOR instance';
			}

			var container_name = 'cke_' + self.basename;
			var container = $(container_name);
			if (! container) {
				throw 'Could not locate our container [' + container_name + ']';
			}

			var textarea = container.previous();
			var textareauseshtmleditor = textarea && textarea.match('textarea.HtmlEditor');

			return {'instance': instance, 'container': container, 'currenttextarea': textareauseshtmleditor ? textarea : null};
		}
		catch (msg) {
console.log('ERROR in RCIEditor.getHtmlEditorObject(): ' + msg);
		}

		return null;
	};

	/**
	 * Updates the textarea that the html editor replaces with the latest content.
	 *
	 * @return object containing the html editor instance and container, or null if not loaded
	 */
	self.saveHtmlEditorContent = function (existinghtmleditorobject) {

		var htmleditorobject = existinghtmleditorobject || self.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		var content = htmleditorobject.instance.getData();
		self.textarea.value = self.cleanContent(content);
		self.textarea.fire('HtmlEditor:SavedContent');
		
		return htmleditorobject;
	};

	/**
	 * SMK extracted this portion of code from f.saveHtmlEditorContent() since that
	 * function requires the presence of a single CKEDITOR instance, but the multi-
	 * instance scenario for wysiwyg inline editing also needs to be able to do the
	 * same type of cleanup.
	 */
	self.cleanContent = function (content) {
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

		var html = self.cleanFieldInserts(tempdiv.innerHTML).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>');

		// CKEditor inserts blank tags even if the user has deleted everything.
		// check if there is an image or href tag... if not, strip the tags and see if there is any text
		if (! html.match(/[img,href]/)) {

			// strips all html tags, then strips whitespace. If there is nothing left... set the html to an empty string
			if (html.stripTags().strip().replace(/[&nbsp;,\n,\r,\t]/g, '') == '')
				html = '';
		}

		return(html);
	};

	// Corrects any html tags that may be inside a data-field insert.
	// Example: &lt;&lt;First <b>Name</b>&gt;&gt; becomes <b>&lt;&lt;First Name&gt;&gt;
	// NOTE: It is assumed that the tokens are &lt;&lt; and &gt;&gt; instead of << and >>.
	self.cleanFieldInserts = function (html) {
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
	};

	self.htmlEditorIsReady = function () {
		var htmleditorobject;
		if (! (htmleditorobject = self.getHtmlEditorObject())) {
			return(false);
		}
		return(htmleditorobject);
	};

	/**
	 * Events that trigger this listener are keystrokes, and content changes
	 * within CKEditor. we wait for half a second before proceeding to grab
	 * any changes made and run them through the validator; the delay prevents
	 * it from running promiscuously every time someone presses a key while
	 * typing.
	 */
	self.eventTimer = null;
	self.eventListener = function () {

		// We got a new event so reset the timer
		window.clearTimeout(self.eventTimer);

		// Get the Editor that we're working with
		var htmleditor = RCIEditor.getHtmlEditorObject();

		// Set a new timer to fire the save/check
		self.eventTimer = window.setTimeout(function() {

			// Save the changes to the hidden textarea
			RCIEditor.saveHtmlEditorContent(htmleditor);

			// Run the form validation against the textarea
			RCIEditor.validate();
		}, 500);
	};

	self.validate = function() {
		window.top.form_do_validation(self.textarea.up('form'), self.textarea);
	};

	self.reset();
}

RCIEditor = new rcieditor();

/**
 * Legacy function wrappers - get rid of these once
 * all calls are converted to RCIEditor.applyEditor()
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

