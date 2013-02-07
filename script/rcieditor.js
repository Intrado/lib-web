/**
 * Multi-mode HTML editor
 *
 * Note that as-written, this class is only good for one "editor" on a given page,
 * whether it's a single, fullly chromed CKE instance, or grouped multiple-inline
 * editor set - either way, it's for a single document. It would be possible to
 * modify this to support multi-document editing, but many aspects would beed to
 * become arrays/objects in order to handle the multiple instances.
 *
 * EXTERNAL DEPENDENCIES
   * jquery.js
   * json2.js
   * rcieditor_inline.js
 */
function RCIEditor(editor_mode, textarea_id, extra_data) {
	var self = this;

	self.textarea = null; // The textarea ELEMENT, not the ID

	self.container = null; // The container ELEMENT, to contain the editor, not the ID
	self.editorMode = null;

	self.basename = 'rcicke';
	self.scratch_id = 'rcieditor_scratch';

	// Associative array support for settings; use of set/getter's is encouraged
	self.settings = null;

	// Setting setter
	self.setSetting = function (name, value) {
		self.settings[name] = value;
	};

	// Setting getter
	self.getSetting = function (name) {
		return(self.settings[name]);
	};

	self.construct = function (editor_mode, textarea_id, extra_data) {

		// Reset all internal properties
		self.reset();

		// if the editor scratch space doesn't yet exist...
		var scratch = jQuery('#' + self.scratch_id);
		if (! scratch.length) {

			// Define and add it to the DOM
			// TODO - fixme: this is based on prototype.js (??)
			scratch = new Element('div', {
				'id': self.scratch_id,
				'style': 'display: none;'
			});

			var body = jQuery('body');
			body.append(scratch);
		}

		self.setSetting('extra_data', extra_data);

		var container_id = textarea_id + '-htmleditor';
		var res = self.applyEditor(editor_mode, textarea_id, container_id);
		return(res);
	};

	self.reconstruct = function (editor_mode, textarea_id, extra_data) {
		if (self.deconstruct()) {
			return(self.construct(editor_mode, textarea_id, extra_data));
		}
		return(false);
	};

	self.deconstruct = function () {

		// Show the loading spinner
		self.setLoadingVisibility(true);

		if (typeof self.textarea !== 'object') {
			return(false);
		}

		// Tear down whatever editor is in place
		switch (self.editorMode) {
			case 'inline':
				var iframe = jQuery('#' +  self.basename + 'inline');
				iframe.remove();
				return(true);

			case 'plain':
			case 'normal':
			case 'full':
				var htmleditorobject = self.getHtmlEditorObject();
				if (! htmleditorobject) {
					return(false);
				}

				htmleditorobject.instance.destroy();
				return(true);

			case null:
				return(true);
		}

		// Only an unsupported editorMode will end up here:
		return(false);
	};

	/**
	 * Put us into a known good state
	 */
	self.reset = function () {

		// reset misc. properties
		self.textarea = null;
		self.container = null;
		self.editorMode = null;

		// reset the settings array
		self.settings = Array();

		// Image scaling is disabled by default
		self.setSetting('image_scaling', 0);

		// Get the base URL for requests that require absolute pathing
		var t = window.top.location;
		var tmp = new String(t);
		var baseUrl = tmp.substr(0, tmp.lastIndexOf('/') + 1);
		self.setSetting('baseUrl', baseUrl);
	};

	/**
	 * Switch editor modes for this document.
	 *
	 * @param editorMode string One of either: 'inline', 'plain', 'normal', or 'full'
	 */
	self.changeMode = function (editorMode) {

		// If we're already in this same mode
		if (self.editorMode == editorMode) {

			// Then there's nothing to do...
			return(true);
		}

		// Remember these two things for context...
		var textarea_id = self.textarea.attr('id');
		var extra_data = self.getSetting('extra_data');

		// Then tear down the existing editor
		if (! self.deconstruct()) {
			return(false);
		}

		// And make a new one
		var res = self.construct(editorMode, textarea_id, extra_data);
		return(res);
	};

	/**
	 * Activate multi-mode CKEditor
	 *
	 * SMK added 2012-12-17
	 *
	 * @param editorMode string One of either: 'inline', 'plain', 'normal',
	 * or 'full'
	 * @param textarea string The id of the textarea form element we are
	 * attaching the editor to
	 * @param container string The id of the container that the editor code
	 * will be injected into
	 *
	 * @return Mixed boolean true/false on success or failure or string
	 * 'deferred' if execution deferred due to asynchronous dependencies not
	 * being available
	 */
	self.applyEditor = function(editorMode, textarea_id, container_id) {

		// If we are already running
		if (self.editorMode) {

			// And we want to apply to the same textarea/container
			if ((textarea_id == self.textarea.attr('id')) && (container_id == self.container.attr('id'))) {

				// Then change the mode which will call us when ready
				return(self.changeMode(editorMode));
			}

			// Applying to a different textarea/container is a totally different story
			// (and is probably a bad request since we only support a single editor)
			return(false);
		}

		// If CKEDITOR is not ready, check back here every second until it is
		if ((typeof CKEDITOR == 'undefined') || (! CKEDITOR)) {

			// We will try again in one second since CKE is not ready yet
			window.setTimeout(function() { self.applyEditor(editorMode, textarea_id, container_id); }, 1000);
			return('deferred');
		}

		self.textarea = jQuery('#' + textarea_id);
		self.container = jQuery('#' + container_id);

		// Hide the text area form field until we are done initializing
		self.textarea.hide();
		self.setLoadingVisibility(true);

		// base name of the text element; we'll make several new elements with derived names
		self.basename = textarea_id;
		self.editorMode = editorMode;

		var cke = null;

		if (editorMode == 'inline') {
			self.setSetting('image_scaling', 500);

			// Add an IFRAME to the page that will load up the inline editor
			cke = new Element('iframe', {
				'id': self.basename + 'inline',
				'src': self.getSetting('baseUrl') + 'rcieditor_inline.php?t=' + container_id,
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
			switch (self.editorMode) {

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
			var scratch = jQuery('#' + self.scratch_id);
			self.setSetting(self.scratch_id, scratch);

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
				uploaderURI += '?scaleabove=' + max_size;
			}

			// Now attach CKE to the form element with the name basename
			CKEDITOR.replace(self.basename, {
				'customConfig': '',
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
						self.callbackEditorLoaded(this);
					}.bindAsEventListener(self.textarea),
					'key': self.eventListener,
					'blur': self.eventListener,
					'saveSnapshot': self.eventListener,
					'afterCommandExec': self.eventListener,
					'insertHtml': self.eventListener,
					'insertElement': self.eventListener,
					'focus': self.eventListener
				}

			});

			// Now we are just waiting on CKE to finish its business;
			// it will fire instanceReady() function when it is done.
		}

		// The second new element has the same id with a 'hider' suffix
		var hider = jQuery(new Element('div', { 'id': self.basename + 'hider' }));
		hider.hide();

		// hider contains the CKEditor to show/hide the whole thing as needed
		hider.html(cke);

		// And here will stick hider into the DOM
		self.container.html(hider);

		return(true);
	};

	/**
	 * This method is called onload from the IFRAME'd inline editor
	 * page that was loaded by applyEditor(). The container is the ID of
	 * the div that we want to load our textarea content into for the
	 * inline editor to have at.
	 */
	self.callbackEditorLoaded = function(activeContainerId) {
		try {
			self.setSetting('activeContainerId', activeContainerId);

			// Hide our AJAXy loading indicator
			self.setLoadingVisibility(false);
			// 'plain', 'normal', and 'full'; nothing to do for 'inline'
			if (self.editorMode !== 'inline') {

				// The presence of the HtmlEditor classname signals
				self.textarea.hide().addClass('HtmlEditor');

				var htmleditorobject = self.getHtmlEditorObject();
				if (! htmleditorobject) {
					throw 'failed to get the htmleditorobject';
				}

				// A little data sanitizing for the raw textarea form content
				var html = self.textarea.val().replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
				htmleditorobject.instance.setData(html);

				// Initial validation, only if there is content in the HTML already...
				if (html.length) {
					self.validate();
				}
			}
		}
		catch (msg) {
			//console.log('ERROR in RCIEditor.callbackEditorLoaded(): ' + msg);
		}
	};

	/**
	 * Show or hide a spinning, AJAXy loading indicator
	 */
	self.loadingVisible = false;
	self.setLoadingVisibility = function (visible) {

		// If we want to make it visible...
		if (visible) {

			// If we're already visible or we have no textarea to work with
			if (self.loadingVisible || (! self.textarea)) {

				// Then there's nothing to do
				return;
			}

			// If the graphic container isn't already on the page...
			if (! jQuery('#htmleditorloadericon').length) {

				// Make a new container element for the spinny loader iocn
				var htmleditorloadericon = jQuery(new Element('span'));

				// give it an id so we can find it to kill it later
				htmleditorloadericon.attr('id', 'htmleditorloadericon');

				// feed the image graphic and a message into the middle of it
				htmleditorloadericon.html('<img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads.');

				// jam it into the textarea's parent so that it appears over the top
				htmleditorloadericon.insertBefore(self.textarea);
			}

			// And hide the editor (redundant if pre-hidden, but lets us call arbitrarily
                        jQuery('#' + self.basename + 'hider').hide();

			self.loadingVisible = true;
		}

		// Otherwise we're hiding it
		else {

			// If it's already hidden
			if (! self.loadingVisible) {

				// Then there's nothing to do
				return;
			}

			// Get rid of it - we'll readd it again later if necessary
			jQuery('#htmleditorloadericon').remove();

			// And show the editor
                        jQuery('#' + self.basename + 'hider').show();

			self.loadingVisible = false;
		}
	};

	/**
	 * Returns the textarea that the html editor is currently replacing
	 */
	self.getHtmlEditorObject = function () {

		var res = null;

		if ((typeof CKEDITOR == 'undefined') || !CKEDITOR) {
			return res;
		}

		if (typeof CKEDITOR.instances == 'undefined') {
			return res;
		}

		if (! CKEDITOR.instances) {
			return res;
		}

		var instance = false;
		for (var i in CKEDITOR.instances) {
			if (CKEDITOR.instances[i].name == self.basename) {
				instance = CKEDITOR.instances[i];
			}
		}
		if (! instance) {
			return res;
		}

		var container_name = 'cke_' + self.basename;
		var container = jQuery(container_name);

		if (! container) {
			return res;
		}

		var textarea = container.prev();
		var textareauseshtmleditor = textarea && textarea.hasClass('HtmlEditor');
		return {'instance': instance, 'container': container, 'currenttextarea': textareauseshtmleditor ? textarea : null};
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
		self.textarea.val(self.cleanContent(content));
		// FIXME - what is this fired event supposed to do? appears to connect to nothing.
		//self.textarea.fire('HtmlEditor:SavedContent'); // prototype.js
		//self.textarea.trigger('SavedContent'); // jquery.js

		return htmleditorobject;
	};

	/**
	 * The converse of saveHtmlEditorContent(), takes the current value of
	 * the textarea and jams it into the editor; useful when outside code
	 * modifies the textarea content and we need the editor to update.
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	self.refreshHtmlEditorContent = function (existinghtmleditorobject) {

		var htmleditorobject = existinghtmleditorobject || self.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		var content = self.textarea.val();
		htmleditorobject.instance.setData(content);
	};

	/**
	 * Completely clear the contents of the editor
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	self.clearHtmlEditorContent = function (existinghtmleditorobject) {
		self.setHtmlEditorContent('', existinghtmleditorobject);
	};

	/**
	 * Set the contents of the editor; the textarea should be cleared by the editor
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	self.setHtmlEditorContent = function (content, existinghtmleditorobject) {

		var htmleditorobject = existinghtmleditorobject || self.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		htmleditorobject.instance.setData(content);
		self.textarea.val(self.cleanContent(content));
	};

	/**
	 * Generic whitespace trimmer to use internally
	 */
	self.trim = function (str) {
		return(str.replace(/^\s+|\s+$/g, ''));
	};

	/**
	 * SMK extracted this portion of code from f.saveHtmlEditorContent() since that
	 * function requires the presence of a single CKEDITOR instance, but the multi-
	 * instance scenario for inline editing also needs to be able to do the same
	 * type of cleanup.
	 */
	self.cleanContent = function (content) {
		var tempdiv = jQuery(new Element('div')).empty().html(content);

		// Unstyle any image elements having src="viewimage.php?id=.."
		var images = jQuery('img', tempdiv).each(function () {
			var src = jQuery(this).attr('src');
			var matches = src.match(/viewimage\.php\?id=(\d+)/);
			if (matches) {
				this.replace('<img src="viewimage.php?id=' + matches[1] + '">');
			}
		});

		var html = self.cleanFieldInserts(tempdiv.html()).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>');

		// CKEditor inserts blank tags even if the user has deleted everything.
		// check if there is an image or href tag... if not, strip the tags and see if there is any text
		if (! html.match(/[img,href]/)) {

			// For plain text, jQuery does not seem to extend it the same (there won't be any tags anyway)
			if (typeof html.text !== 'undefined') {

				// strips all html tags, then strips whitespace. If there is nothing left... set the html to an empty string
				if (self.trim(html.text()).replace(/[&nbsp;,\n,\r,\t]/g, '') == '') {
					html = '';
				}
			}
		}

		return(html);
	};

	/**
	 * Corrects any html tags that may be inside a data-field insert.
	 *
	 * Example: &lt;&lt;First <b>Name</b>&gt;&gt; becomes <b>&lt;&lt;First Name&gt;&gt;
	 * NOTE: It is assumed that the tokens are &lt;&lt; and &gt;&gt; instead of << and >>.
	 */
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

			field = self.trim(field);
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
		var htmleditor = self.getHtmlEditorObject();

		// Set a new timer to fire the save/check
		self.eventTimer = window.setTimeout(function() {

			// Save the changes to the hidden textarea
			self.saveHtmlEditorContent(htmleditor);

			// Run the form validation against the textarea
			self.validate();
		}, 500);
	};

	self.validator_fn = null;

	self.setValidatorFunction = function (validator_fn) {
		self.validator_fn = validator_fn;
	};

	self.resetValidatorFunction = function () {
		self.validator_fn = null;
	}

	self.validate = function() {
		if (typeof self.validator_fn === 'function') {
			self.validator_fn();
		}
	};

	self.construct(editor_mode, textarea_id, extra_data);
}

