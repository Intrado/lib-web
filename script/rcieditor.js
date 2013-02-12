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

(function ($) {
window.RCIEditor = function (editor_mode, textarea_id, extra_data) {
	var myself = this;

	this.textarea = null; // The textarea ELEMENT, not the ID

	this.container = null; // The container ELEMENT, to contain the editor, not the ID
	this.editorMode = null;

	this.basename = 'rcicke';
	this.scratch_id = 'rcieditor_scratch';

	// Associative array support for settings; use of set/getter's is encouraged
	this.settings = null;

	// Setting setter
	this.setSetting = function (name, value) {
		this.settings[name] = value;
	};

	// Setting getter
	this.getSetting = function (name) {
		return(this.settings[name]);
	};

	this.construct = function (editor_mode, textarea_id, extra_data) {

		// Reset all internal properties
		this.reset();

		// if the editor scratch space doesn't yet exist...
		var scratch = $('#' + this.scratch_id);
		if (! scratch.length) {

			// Define and add it to the DOM
			scratch = $('<div id="' + this.scratch_id + '" style="display: none;"></div>');
			$('body').append(scratch);
		}

		this.setSetting('extra_data', extra_data);

		var container_id = textarea_id + '-htmleditor';
		var res = this.applyEditor(editor_mode, textarea_id, container_id);
		return(res);
	};

	this.reconstruct = function (editor_mode, textarea_id, extra_data) {

		// If the editorMode is defined...
		if (typeof this.editorMode !== 'undefined') {

			// We need to deconstruct before we construct...
			if (! this.deconstruct()) return(false);
		}
		return(this.construct(editor_mode, textarea_id, extra_data));
	};

	this.deconstruct = function () {

		// Show the loading spinner
		this.setLoadingVisibility(true);

		if (typeof this.textarea !== 'object') {
			return(false);
		}

		// Tear down whatever editor is in place
		switch (this.editorMode) {
			case 'inline':

				// We can get rid of the IFRAME'd inline editor
				// just by emptying out the container
				var container =  $('#' +  this.basename + '-htmleditor');
				container.empty();
				return(true);

			case 'plain':
			case 'normal':
			case 'full':
				var htmleditorobject = this.getHtmlEditorObject();
				if (! htmleditorobject) {
					return(false);
				}

				// Capture the textarea content
				var content = this.textarea.val();

				// Let CKE do whatever it does while destroying itself
				htmleditorobject.instance.destroy();

				// And restore the textarea's content
				this.textarea.val(content);

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
	this.reset = function () {

		// reset misc. properties
		this.textarea = null;
		this.container = null;
		this.editorMode = null;

		// reset the settings array
		this.settings = Array();

		// Image scaling is disabled by default
		this.setSetting('image_scaling', 0);

		// Get the base URL for requests that require absolute pathing
		var t = window.top.location;
		var tmp = new String(t);
		var baseUrl = tmp.substr(0, tmp.lastIndexOf('/') + 1);
		this.setSetting('baseUrl', baseUrl);
	};

	/**
	 * Switch editor modes for this document.
	 *
	 * @param editorMode string One of either: 'inline', 'plain', 'normal', or 'full'
	 */
	this.changeMode = function (editorMode) {

		// If we're already in this same mode
		if (this.editorMode == editorMode) {

			// Then there's nothing to do...
			return(true);
		}

		// Remember these two things for context...
		var textarea_id = this.textarea.attr('id');
		var extra_data = this.getSetting('extra_data');

		// Then tear down the existing editor
		if (! this.deconstruct()) {
			return(false);
		}

		// And make a new one
		var res = this.construct(editorMode, textarea_id, extra_data);
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
	this.applyEditor = function(editorMode, textarea_id, container_id) {

		// Hide the text area form field until we are done initializing
		this.textarea = $('#' + textarea_id);
		this.textarea.hide();
		this.setLoadingVisibility(true);

		// If we are already running
		if (this.editorMode) {

			// And we want to apply to the same textarea/container
			if ((textarea_id == this.textarea.attr('id')) && (container_id == this.container.attr('id'))) {

				// Then change the mode which will call us when ready
				return(this.changeMode(editorMode));
			}

			// Applying to a different textarea/container is a totally different story
			// (and is probably a bad request since we only support a single editor)
			return(false);
		}

		// If CKEDITOR is not ready, check back here every second until it is
		if ((typeof CKEDITOR == 'undefined') || (! CKEDITOR)) {

			// We will try again in one second since CKE is not ready yet
			var that = this;
			window.setTimeout(function() { that.applyEditor(editorMode, textarea_id, container_id); }, 1000);
			return('deferred');
		}

		// base name of the text element; we'll make several new elements with derived names
		this.basename = textarea_id;
		this.container = $('#' + container_id);

		// The second new element has the same id with a 'hider' suffix
		var hider = $('#' + this.basename + 'hider');
		if (! hider.length) {

			hider = $('<div id="' + this.basename + 'hider" style="display: none;"></div>');
			this.container.append(hider);
		}

		var cke = $('<div id="' + this.basename + '_box"></div>');

		if (editorMode == 'inline') {
			this.setSetting('image_scaling', 500);

			// Add an IFRAME to the page that will load up the inline editor
			cke.html('<iframe src="' + this.getSetting('baseUrl') + 'rcieditor_inline.php?t=' + container_id + '" style="width: 800px; height: 400px; border: 1px solid #999999;"/>');

			// So now we have the inline editor component loading in an iframe;
			// the next move is up to the iframe content to call back the next
			// function below to get the two halves communicating cross-frame.

		}
		else {

			// For the full CKEditor, the toolbars/plugins
			// are different depending on the editorMode
			var extraPlugins = 'aspell';
			var extraButtons = []; //['PasteFromWord','SpellCheck'];
			switch (editorMode) {

				default:
					// If editorMode was not supplied, we need to set it
					editorMode = 'plain';
				case 'plain':
					// Nothing extra to add for the plain legacy editor
					break;

				case 'normal':
					this.setSetting('image_scaling', 500);

					// Add the mkField plugin
					if (extraPlugins.length) extraPlugins += ',';
					extraPlugins += 'mkfield';

					// Add the mkfield button
					extraButtons.push('mkField');
					break;

				case 'full':
					this.setSetting('image_scaling', 500);

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
			var scratch = $('#' + this.scratch_id);
			this.setSetting(this.scratch_id, scratch);

			// SMK added to selectively enable reduction scaling for uploaded images;
			// page that includes CKE must set global var htmlEditorImageScalingEnable
			// to true to enable scaling, otherwise scaling will be disabled by default;
			// uploader.php will pass the argument on to f.handleFileUpload() which will
			// ultimately be responsible for enforcement of this flag
			var uploaderURI = this.getSetting('baseUrl') + 'uploadimage.php';
			var max_size;
			if ((max_size = parseInt(this.getSetting('image_scaling'))) > 0) {
				uploaderURI += '?scaleabove=' + max_size;
			}

			var that = this;

			// Now attach CKE to the form element with the name basename
			// TODO - see if there's a way to get this CKE to insert itself into hider element
			CKEDITOR.replace(this.basename, {
				'customConfig': '',
				'disableNativeSpellChecker': false,
				'browserContextMenuOnCtrl': true,
				'width': '100%',
				'height': '400px',
				'filebrowserImageUploadUrl' : uploaderURI,
				'toolbarStartupExpanded' : (! this.getSetting('hidetoolbar')),
				'toolbarCanCollapse' : true,
				'extraPlugins': extraPlugins,

				'toolbar_RCI' : [
					{ name: 'r1g1', items : [ 'Print', 'Source' ] },
					{ name: 'r1g2', items : [ '-', 'Undo', 'Redo'] },
					{ name: 'r1g3', items : [ 'NumberedList', 'BulletedList', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'Outdent', 'Indent' ] },
					{ name: 'r1g4', items : [ 'PasteFromWord', 'SpellCheck' ] },
					{ name: 'r1g5', items : [ 'Link', 'Image', 'Table', 'HorizontalRule' ] },
					{ name: 'r1g6', items : [ 'ShowBlocks', 'Maximize' ] },
					'/',
					{ name: 'r2g1', items : [ 'Bold', 'Italic', 'Underline', 'Strike', 'TextColor', 'BGColor', 'RemoveFormat' ] },
					{ name: 'r2g2', items : [ 'Styles', 'Format', 'Font', 'FontSize' ] },
					{ name: 'r2g3', items : extraButtons }
				],

				'toolbar' : 'RCI',

				'on': {
					'instanceReady': function(event) {
						that.callbackEditorLoaded(this);
					},
					'key': ( function () { that.eventListener(); } ),
					'blur': ( function () { that.eventListener(); } ),
					'saveSnapshot': ( function () { that.eventListener(); } ),
					'afterCommandExec': ( function () { that.eventListener(); } ),
					'insertHtml': ( function () { that.eventListener(); } ),
					'insertElement': ( function () { that.eventListener(); } ),
					'focus': ( function () { that.eventListener(); } )
				}

			});

			// Now we are just waiting on CKE to finish its business;
			// it will fire instanceReady() function when it is done.
		}

		// Capture the editorMode
		this.editorMode = editorMode;


		hider.html(cke);

		return(true);
	};

	/**
	 * This method is called onload from the IFRAME'd inline editor
	 * page that was loaded by applyEditor(). The container is the ID of
	 * the div that we want to load our textarea content into for the
	 * inline editor to have at.
	 */
	this.callbackEditorLoaded = function(activeContainerId) {
		this.setSetting('activeContainerId', activeContainerId);

		// Hide our AJAXy loading indicator
		this.setLoadingVisibility(false);
		// 'plain', 'normal', and 'full'; nothing to do for 'inline'
		if (this.editorMode !== 'inline') {

			// The presence of the HtmlEditor classname signals
			this.textarea.hide().addClass('HtmlEditor');

			var htmleditorobject = this.getHtmlEditorObject();
			if (! htmleditorobject) {
				// failed to get the htmleditorobject
				return;
			}

			// A little data sanitizing for the raw textarea form content
			var html = this.textarea.val().replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
			htmleditorobject.instance.setData(html);

			// Initial validation, only if there is content in the HTML already...
			if (html.length) {
				this.validate();
			}
		}
	};

	/**
	 * Show or hide a spinning, AJAXy loading indicator
	 */
	this.loadingVisible = false;
	this.setLoadingVisibility = function (visible) {

		// If we want to make it visible...
		if (visible) {

			// If we're already visible or we have no textarea to work with
			if (this.loadingVisible || (! this.textarea)) {

				// Then there's nothing to do
				return;
			}

			// If the graphic container isn't already on the page...
			if (! $('#htmleditorloadericon').length) {

				// Make a new container element for the spinny loader iocn
				var htmleditorloadericon = $('<span id="htmleditorloadericon"><img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads.</span>');

				// jam it into the textarea's parent so that it appears over the top
				htmleditorloadericon.insertBefore(this.textarea);
			}

			// And hide the editor (redundant if pre-hidden, but lets us call arbitrarily
                        $('#' + this.basename + 'hider').hide();

			this.loadingVisible = true;
		}

		// Otherwise we're hiding it
		else {

			// If it's already hidden
			if (! this.loadingVisible) {

				// Then there's nothing to do
				return;
			}

			// Get rid of it - we'll readd it again later if necessary
			$('#htmleditorloadericon').remove();

			// And show the editor
                        $('#' + this.basename + 'hider').show();

			this.loadingVisible = false;
		}
	};

	this.hideHtmlEditor = function () {
		// hide the editor
		$('#' + this.basename + 'hider').hide();
	};


	/**
	 * Returns the textarea that the html editor is currently replacing
	 */
	this.getHtmlEditorObject = function () {

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
			if (CKEDITOR.instances[i].name == this.basename) {
				instance = CKEDITOR.instances[i];
			}
		}
		if (! instance) {
			return res;
		}

		var container_name = 'cke_' + this.basename;
		var container = $(container_name);

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
	this.saveHtmlEditorContent = function (existinghtmleditorobject) {

		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		var content = htmleditorobject.instance.getData();
		this.textarea.val(this.cleanContent(content));
		// FIXME - what is this fired event supposed to do? appears to connect to nothing.
		//this.textarea.fire('HtmlEditor:SavedContent'); // prototype.js
		//this.textarea.trigger('SavedContent'); // jquery.js

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
	this.refreshHtmlEditorContent = function (existinghtmleditorobject) {

		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		var content = this.textarea.val();
		htmleditorobject.instance.setData(content);
	};

	/**
	 * Completely clear the contents of the editor
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	this.clearHtmlEditorContent = function (existinghtmleditorobject) {
		this.setHtmlEditorContent('', existinghtmleditorobject);
	};

	/**
	 * Set the contents of the editor; the textarea should be cleared by the editor
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	this.setHtmlEditorContent = function (content, existinghtmleditorobject) {

		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (!htmleditorobject) {
			return null;
		}
		
		htmleditorobject.instance.setData(content);
		this.textarea.val(this.cleanContent(content));
	};

	/**
	 * Generic whitespace trimmer to use internally
	 */
	this.trim = function (str) {
		return(str.replace(/^\s+|\s+$/g, ''));
	};

	/**
	 * SMK extracted this portion of code from f.saveHtmlEditorContent() since that
	 * function requires the presence of a single CKEDITOR instance, but the multi-
	 * instance scenario for inline editing also needs to be able to do the same
	 * type of cleanup.
	 */
	this.cleanContent = function (content) {
		var tempdiv = $('<div></div>').html(content);

		// Unstyle any image elements having src="viewimage.php?id=.."
		var images = $('img', tempdiv).each(function () {
			var src = $(this).attr('src');
			var matches = src.match(/viewimage\.php\?id=(\d+)/);
			if (matches) {
				this.replace('<img src="viewimage.php?id=' + matches[1] + '">');
			}
		});

		var html = this.cleanFieldInserts(tempdiv.html()).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>');

		// CKEditor inserts blank tags even if the user has deleted everything.
		// check if there is an image or href tag... if not, strip the tags and see if there is any text
		if (! html.match(/[img,href]/)) {

			// For plain text, $ does not seem to extend it the same (there won't be any tags anyway)
			if (typeof html.text !== 'undefined') {

				// strips all html tags, then strips whitespace. If there is nothing left... set the html to an empty string
				if (this.trim(html.text()).replace(/[&nbsp;,\n,\r,\t]/g, '') == '') {
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
	this.cleanFieldInserts = function (html) {
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

			field = this.trim(field);
			html = html.replace(matches[i], beforeinsert + '&lt;&lt;' + field + '&gt;&gt;' + afterinsert);
		}
		return html;
	};

	this.htmlEditorIsReady = function () {
		var htmleditorobject;
		if (! (htmleditorobject = this.getHtmlEditorObject())) {
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
	this.eventTimer = null;
	this.eventListener = function () {

		// We got a new event so reset the timer
		window.clearTimeout(this.eventTimer);

		// Get the Editor that we're working with
		var htmleditor = this.getHtmlEditorObject();
		var that = this;

		// Set a new timer to fire the save/check
		this.eventTimer = window.setTimeout(function() {

			// Save the changes to the hidden textarea
			that.saveHtmlEditorContent();

			// Run the form validation against the textarea
			myself.validate();
		}, 500);
	};

	this.validator_fn = null;

	this.setValidatorFunction = function (validator_fn) {
		this.validator_fn = validator_fn;
	};

	this.resetValidatorFunction = function () {
		this.validator_fn = null;
	}

	this.validate = function() {
		if (typeof this.validator_fn === 'function') {
			this.validator_fn();
		}
	};

	this.registerHtmlEditorKeyListener = function (listener_fn) {

		// TODO - this function is here only for compatibility with the legacy htmleditor.js interface;
		// Anything that calls it should be rewritten to use the validator model instead which does not
		// require the caller to provide its own key listened
		this.setValidatorFunction(listener_fn);
	};

	this.construct(editor_mode, textarea_id, extra_data);
}
}) (jQuery);

