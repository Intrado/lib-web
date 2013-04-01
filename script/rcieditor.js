/**
 * Multi-mode HTML editor
 *
 * Note that as-written, this class is only good for one "editor" on a given page,
 * whether it's a single, fullly chromed CKE instance, or grouped multiple-inline
 * editor set - either way, it's for a single document. It would be possible to
 * modify this to support multi-document editing, but many aspects would beed to
 * become arrays/objects in order to handle the multiple instances.
 *
 * This multi-mode editor class container provides all the interfacing necessary
 * to manage CKEditor from the outside by starting it up, filling it with content,
 * refreshing, hooking into validation with event handling, tearing it down, and
 * reinitializing to a different mode. The editor mode is "null" to start to indicate
 * that it has not been initialized yet and returns to this state when deconstruct()ed.
 * The modes 'plain', 'normal', and 'full' are all essentially the same full CKEditor
 * but with differences in which custom plugins are added to the toolbar. The 'inline'
 * mode is entirely different and requires an external PHP page to host the interior
 * of an IFRAME with the document and inline mode CKEditor initiated into it. Note
 * that it may be possible to fully populate the iframe from within this JS at the
 * time of creation and eliminate the need for the external PHP.
 *
 * Note that this script is commonly included into pages that still have Prototype.js
 * running on them and even though the code wraps jQuery into $, Prototype still extends
 * common DOM objects. As such it is frequently necessary to seemingly redundantly cast
 * newly created objects with jQuery - even when the object is freshly created by
 * jQuery. These additional wrap calls will be come unnecessary when Prototype.js goes
 * away, however they will not break anything.
 *
 * EXTERNAL DEPENDENCIES
   * jquery.js
   * rcieditor_inline.js
   * rcieditor_inline.php
   * "message sender" -> for the pasteFromPhone plugin availability
 */

(function ($) {

// SMK added 2013-04-01 to see if being in an iframe (powerschool) is a problem
// for cross-domain and if this fixes it. It seems like our domain is not matching
// that of CKEditor's iframe which prevents it from communicating back to
// window.parent.rcieditor...
document.domain = window.location.host;
if (console) console.log("rcieditor's domain is: [" + document.domain);


window.RCIEditor = function (editor_mode, textarea_id, overrideSettings) {
	var textarea = null;	// The textarea ELEMENT, not the ID
	var container = null;	// The container ELEMENT, to contain the editor, not the ID
	var editorMode = null;	// Either null (uninitialized) or [plain|normal|full|inline]
	var basename = 'rcicke';
	var customsettings = {};

	// Lifted from utils.js so that we don't need to include that whole thing as an external dep.
	this.getBaseUrl = function() {
		var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
		var baseUrl = url.substr(0, url.lastIndexOf('/') + 1);        // Get everything thru the last '/'
		return(baseUrl);
	};

	// Associative array support for settings; use of set/getter's is required
	var settings = null;

	this.clearSettings = function () {
		settings = Array();
	};

	this.setSetting = function (name, value) {
		settings[name] = value;
	};

	this.getSetting = function (name) {
		if (typeof(settings[name]) !== "undefined") 
		return((typeof(settings[name]) !== "undefined") ? settings[name] : false);
	};

	/**
	 * This pseudo-constructor puts all the working initialization code into
	 * a re-callable method; it tears down an already constructed instance and
	 * reinitializes it with new settings.
	 *
	 * @param string editor_mode [plain|normal|full|inline]
	 * @param string textarea_id the HTML id attribute of the text area the
	 * editor should be attached to
	 * @param overrideSettings array of name/value setting pairs to override
	 * defaults upon initialization
	 */
	this.reconstruct = function (editor_mode, textarea_id, overrideSettings) {

		// (1) If the editorMode is defined...
		if (editorMode) {

			// We need to deconstruct before we construct...
			if (! this.deconstruct()) return(false);
		}

		// (2) Reset all internal properties
		textarea = null;
		container = null;
		editorMode = null;

		// reset the settings array
		this.clearSettings();

		// Image scaling is disabled by default
		this.setSetting('image_scaling', 0);

		// The default settings for custom toolbar buttons
		this.setSetting('tool_mkfield', false);
		this.setSetting('tool_mkblock', false);
		this.setSetting('tool_thememgr', false);
		this.setSetting('tool_pastefromphone', false);
		this.setSetting('hidetoolbar', false);
		this.setSetting('fieldinsert_list', {});

		// Make a generic, reusable text clipboard
		this.setSetting('clipboard', '');

		// Get the base URL for requests that require absolute pathing
		this.setSetting('baseUrl', this.getBaseUrl());

		// Clear any validator that was set
		this.resetValidatorFunction();

		// (3) Apply the editor to the chosen textarea
		var container_id = textarea_id + '-htmleditor';
		return(this.applyEditor(editor_mode, textarea_id, container_id, overrideSettings));
	};

	/**
	 * This pseudo destructor tears down the editor interface, but leaves the main
	 * object ready to continue working with a subsequent call to the construct method.
	 */
	this.deconstruct = function () {

		// Show the loading spinner
		this.setLoadingVisibility(true);

		// Tear down whatever editor is in place
		switch (editorMode) {
			case 'inline':

				// We can get rid of the IFRAME'd inline editor
				// just by emptying out the container
				var tmpcontainer =  $('#' +  basename + '-htmleditor');
				if (! tmpcontainer.length) {
					return(false);
				}

				tmpcontainer.empty();
				return(true);

			case 'plain':
			case 'normal':
			case 'full':
				var htmleditorobject = this.getHtmlEditorObject();
				if (! htmleditorobject) {
					return(false);
				}

				if (typeof textarea !== 'object') {
					return(false);
				}

				// Capture the textarea content to prevent CKE from further altering it
				var content = textarea.val();

				// Let CKE do whatever it does while destroying itself
				htmleditorobject.instance.destroy();

				// And restore the textarea's content
				textarea.val(content);

				return(true);

			// An attempt to deconstruct an unconstructed object;
			// caller is unaware of our status, but we'll let it
			// slide so that reconstruct() method can be called any
			// time without penalty:
			case null:
				return(true);
		}

		// Only an unsupported editorMode will end up here:
		return(false);
	};

	/**
	 * Switch editor modes for this document.
	 *
	 * @param editorMode string One of either: 'inline', 'plain', 'normal', or 'full'
	 */
	this.changeMode = function (newEditorMode) {

		// If we're already in this same mode
		if (editorMode == newEditorMode) {

			// Then there's nothing to do...
			return(true);
		}

		// Remember a couple things for context...
		var textarea_id = textarea.attr('id');
		var overrideSettings = customSettings;

		// And remake ourselves
		var res = this.reconstruct(newEditorMode, textarea_id, overrideSettings);
		return(res);
	};

	/**
	 * Activate multi-mode CKEditor
	 *
	 * SMK added 2012-12-17
	 *
	 * @param setEditorMode string One of either: 'inline', 'plain', 'normal',
	 * or 'full'
	 * @param textarea_id string The id of the textarea form element we are
	 * attaching the editor to
	 * @param container_id string The id of the container that the editor code
	 * will be injected into
	 * @param overrideSettings array of name/value setting pairs to override
	 * defaults upon initialization
	 *
	 * @return Mixed boolean true/false on success or failure or string
	 * 'deferred' if execution deferred due to asynchronous dependencies not
	 * being available
	 */
	this.applyEditor = function(setEditorMode, textarea_id, container_id, overrideSettings) {

		// Hide the text area form field until we are done initializing
		textarea = $('#' + textarea_id);
		textarea.hide();
		this.setLoadingVisibility(true);

		// If we are already running
		if (editorMode) {

			// And we want to apply to the same textarea/container
			if ((textarea_id == textarea.attr('id')) && (container_id == container.attr('id'))) {

				// Then change the mode which will call us when ready
				return(this.changeMode(setEditorMode));
			}

			// Applying to a different textarea/container is not allowed
			return(false);
		}

		// If CKEDITOR is not ready, check back here every second until it is
		if ((typeof CKEDITOR == 'undefined') || (! CKEDITOR)) {

			// We will try again in one second since CKE is not ready yet
			var that = this;
			window.setTimeout(function() { that.applyEditor(setEditorMode, textarea_id, container_id, overrideSettings); }, 1000);
			return('deferred');
		}

		// Override some settings
		for (var setting in overrideSettings) {
			this.setSetting(setting, overrideSettings[setting]);
		}

		// base name of the text element; we'll make several new elements with derived names
		basename = textarea_id;
		container = $('#' + container_id);

		// The first new element has the same id with a 'hider' suffix
		var hider = $('#' + basename + 'hider');
		if (! hider.length) {
			hider = $('<div id="' + basename + 'hider" style="display: none;"></div>');
			container.append(hider);
		}

		var cke = $('<div id="' + basename + '_box"></div>');

		// For the full CKEditor, the toolbars/plugins
		// are different depending on the editorMode
		var extraPlugins = ['aspell'];
		var extraButtons = [];
		switch (setEditorMode) {

			default:
				// If editorMode was not supplied, we need to set it
				setEditorMode = 'plain';
			case 'plain':
				// Nothing extra to add for the plain legacy editor
				break;

			case 'inline':
				// Add the mkField tool only
				this.setSetting('tool_mkfield', true);
				break;

			case 'normal':
				// Add the mkField tool only
				this.setSetting('tool_mkfield', true);

				// FIXME SMK disabled image_scaling pending clarification of desired behavior
				//this.setSetting('image_scaling', 500);
				break;

			case 'full':
				// Add the mkField, mkBlock, and themeMgr tools
				this.setSetting('tool_mkfield', true);
				this.setSetting('tool_mkblock', true);
				this.setSetting('tool_thememgr', true);

				// FIXME SMK disabled image_scaling pending clarification of desired behavior
				//this.setSetting('image_scaling', 500);
				break;
		}

		if (setEditorMode == 'inline') {
			// FIXME SMK disabled image_scaling pending clarification of desired behavior
			//this.setSetting('image_scaling', 500);

			// Add an IFRAME to the page that will load up the inline editor
			cke.html('<iframe ' +
					'src="' + this.getSetting('baseUrl') + 'rcieditor_inline.php?t=' + basename + '&d=' + document.domain + '" ' +
					'name="' + basename + '_iframe" ' +
					'style="width: 100%; height: 400px; border: 1px solid #999999;"/>'
			);

			// So now we have the inline editor component loading in an iframe;
			// the next move is up to the iframe content to call back the next
			// function below to get the two halves communicating cross-frame.
		}
		else {

			// Activate whatever tools are enabled based on mode
			var custom_tools = [ 'mkField', 'mkBlock', 'themeMgr', 'pasteFromPhone' ];
			var that = this;
			// SMK notes that array.forEach() is not supported on IE8, so we'll use jQuery to iterate instead
			$(custom_tools).each(function (index) {
				var toolname = custom_tools[index];
				var lowertool = toolname.toLowerCase();
				if (that.getSetting('tool_' + lowertool)) {
					extraPlugins.push(lowertool);
					extraButtons.push(toolname);
				}
			});

			// SMK added to selectively enable reduction scaling for uploaded images;
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
			CKEDITOR.replace(basename, {
				'customConfig': '',
				'disableNativeSpellChecker': false,
				'browserContextMenuOnCtrl': true,
				'width': '100%',
				'height': '400px',
				'filebrowserImageUploadUrl' : uploaderURI,
				'toolbarStartupExpanded' : (this.getSetting('hidetoolbar') ? false : true),
				'toolbarCanCollapse' : true,
				'extraPlugins': extraPlugins.join(),
				'disableObjectResizing' : true, // disabled only because the message_parts data model cannot capture resized image attributes
				'pasteFromWordRemoveFontStyles' : false,
				'pasteFromWordRemoveStyles' : false,

				'toolbar_RCI' : [
					{ name: 'r1g1', items : [ 'Print', 'Source' ] },
					{ name: 'r1g2', items : [ 'Undo', 'Redo'] },
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
		editorMode = setEditorMode;
		customsettings = overrideSettings;

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

		// Just trigger a validation event if there is something to look at
		var html = textarea.val();
		switch (editorMode) {

			case 'inline':
				// Nothing special to do for inline mode at this time
				break;

			case 'plain':
			case 'normal':
			case 'full':

				// The presence of the HtmlEditor classname signals
				textarea.hide().addClass('HtmlEditor');

				var htmleditorobject = this.getHtmlEditorObject();
				if (! htmleditorobject) {
					// failed to get the htmleditorobject
					return;
				}

				// A little data sanitizing for the raw textarea form content
				htmleditorobject.instance.setData(html.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;"));
				break;
		}

		// Initial validation, only if there is content in the HTML already;
		// this allows the FI validation icon to show required field state instead
		// of an initial error condition if the user hasn't entered anything yet.
		if (html.length) {
			this.validate();
		}
	};

	/**
	 * Show or hide a spinning, AJAXy loading indicator
	 *
	 * @param boolean visible Visible state of the "Please wait" indicator, true to show, false to hide
	 */
	this.loadingVisible = false;
	this.setLoadingVisibility = function (visible) {

		// If we want to make it visible...
		if (visible) {

			// If we're already visible or we have no textarea to work with
			if (this.loadingVisible || (! textarea)) {

				// Then there's nothing to do
				return;
			}

			// If the graphic container isn't already on the page...
			if (! $('#htmleditorloadericon').length) {

				// Make a new container element for the spinny loader iocn
				var htmleditorloadericon = $('<span id="htmleditorloadericon"><img src="img/ajax-loader.gif"/> Please wait while the HTML editor loads.</span>');

				// jam it into the textarea's parent so that it appears over the top
				htmleditorloadericon.insertBefore(textarea);
			}

			// And hide the editor (redundant if pre-hidden, but lets us call arbitrarily
                        $('#' + basename + 'hider').hide();

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
                        $('#' + basename + 'hider').show();

			this.loadingVisible = false;
		}
	};

	/**
	 * Attempts to hide the HTMLEditor while we are manipulating it
	 *
	 * FIXME: The new version of CKE (4.x) does not seem to be able to
	 * insert itself into the "hider" div which prevents us from being
	 * able to hide it!
	 */
	this.hideHtmlEditor = function () {
		// hide the editor
		$('#' + basename + 'hider').hide();
	};


	/**
	 * Returns the textarea that the html editor is currently replacing
	 * This should only be used internally, and should be largely unnecesary
	 * now that we hold everything as object properties
	 */
	this.getHtmlEditorObject = function () {

		if ((typeof(CKEDITOR) == 'undefined') || !CKEDITOR) {
			return(false);
		}

		if (typeof(CKEDITOR.instances) == 'undefined') {
			return(false);
		}

		if (! CKEDITOR.instances) {
			return(false);
		}

		var instance = false;
		for (var i in CKEDITOR.instances) {
			if (CKEDITOR.instances[i].name == basename) {
				instance = CKEDITOR.instances[i];
			}
		}
		if (! instance) {
			return(false);
		}

		var tmpcontainer = $('#cke_' + basename);
		if (! tmpcontainer) {
			return(false);
		}

		var textarea = tmpcontainer.prev();
		return({
			'instance': instance,
			'container': tmpcontainer,
			'currenttextarea': (textarea && textarea.hasClass('HtmlEditor')) ? textarea : null
		});
	};

	/**
	 * Updates the textarea that the html editor replaces with the latest content.
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 *
	 * @return object containing the html editor instance and container, or null if not loaded
	 */
	this.saveHtmlEditorContent = function (existinghtmleditorobject) {
		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (!htmleditorobject) {
			return(false);
		}
		
		var content = htmleditorobject.instance.getData();
		textarea.val(this.cleanContent(content));

		return(true);
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

		// Refresh is neither supported nor necessary for inline mode
		if (editorMode == 'inline') {
			return;
		}

		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (htmleditorobject) {
			var content = textarea.val();
			htmleditorobject.instance.setData(content);
		}
	};

	/**
	 * Sets the "primary" content for the editor to the supplied string; for
	 * the full/regular editor, this means replacing the entire document.
	 * For the inline editor, we will only replace the content in editable
	 * Blocks with an attribute indicating that it is/they are primary, not
	 * the entire document.
	 *
	 * @param string content The block of HTML content that we want to set the
	 * textarea/editor to
	 *
	 * @return boolean true on success, else false
	 */
	this.setHtmlEditorContentPrimary = function (content) {
		switch (editorMode) {
			case 'inline':

				var rcieditorinline = window.frames[basename + '_iframe'].window.rcieditorinline;

				// Textarea is NOT a blockelement that contains HTML; it is a form field with a value
				// so we have to get the value of the field, stick it in jquery space temporarily,
				// manipulated it, and then put it back into the textarea again:
				if (rcieditorinline.activeEditorId) {
					var tempdiv = $('<div></div>').html(textarea.val());
					$('#' + rcieditorinline.activeEditorId, tempdiv).each(function () {

						var jQthis = $(this);
						jQthis.html(content);
					});
					textarea.val(tempdiv.html());

					// Now make the inline editor's view refresh itself to capture the change
					// ref: http://stackoverflow.com/questions/1952359/calling-iframe-function
					rcieditorinline.refresh();
					return(true);
				}
				else {
					alert('First click into the editable block that you want to paste the text into...');
				}
				return(false);

			case 'plain':
			case 'normal':
			case 'full':
				textarea.val(content);
				this.refreshHtmlEditorContent();
				return(true);

			default:
				return(false);
		}
	};

	/**
	 * Completely clear the contents of the editor
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	this.clearHtmlEditorContent = function (existinghtmleditorobject) {
		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (! htmleditorobject) {
			return(false);
		}
		return(this.setHtmlEditorContent('', htmleditorobject));
	};

	/**
	 * Set the contents of the editor; the textarea should be cleared by the editor
	 *
	 * @param object existinghtmleditorobject  Optional, rarely used CKE
	 * object other than the one we're using internally
	 */
	this.setHtmlEditorContent = function (content, existinghtmleditorobject) {
		var htmleditorobject = existinghtmleditorobject || this.getHtmlEditorObject();
		if (! htmleditorobject) {
			return(false);
		}
		htmleditorobject.instance.setData(content);
		textarea.val(this.cleanContent(content));
		return(true);
	};

	/**
	 * Generic whitespace trimmer to use internally
	 *
	 * @param string str the string that we want to trim whitespace off the head/tail of
	 *
	 * @return string the trimmed down string
	 */
	this.trim = function (str) {
		return(str.replace(/^\s+|\s+$/g, ''));
	};

	/**
	 * SMK extracted this portion of code from f.saveHtmlEditorContent() since that
	 * function requires the presence of a single CKEDITOR instance, but the multi-
	 * instance scenario for inline editing also needs to be able to do the same
	 * type of cleanup.
	 *
	 * @param string content The content from the editor that we want to clean up
	 *
	 * @return string The cleaned up content ready for saving
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
	 *
	 * @param string html The HTML code from the editor that we want to clean up
	 *
	 * @return string The cleaned up HTML
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
			that.validate();
		}, 500);
	};

	/**
	 * Someone on the outside will be responsible for validating this form
	 * whenever the content changes; this function and the associated
	 * methods for managing it will be responsible for doing that work.
	 *
	 * @param function validator_fn The function to invoke whenever a content change occurs
	 */
	this.validator_fn = null;
	this.setValidatorFunction = function (validator_fn) {
		this.validator_fn = validator_fn;
	};

	/**
	 * Disables a validator function that was previously set
	 */
	this.resetValidatorFunction = function () {
		this.validator_fn = null;
	}

	/**
	 * Called internally whenever a change occurs that needs validation; if
	 * a validator function is set then it will be invoked, otherwise nada.
	 */
	this.validate = function() {
		if (typeof this.validator_fn === 'function') {
			this.validator_fn();
		}
	};

	/**
	 * this function is here only for compatibility with the legacy
	 * htmleditor.js interface; anything that calls it should be rewritten
	 * to use the validator model instead which does not require the caller
	 * to provide its own key listened
	 *
	 * @todo Get rid of this method once all external calls to it are gone
	 */
	this.registerHtmlEditorKeyListener = function (listener_fn) {
		this.setValidatorFunction(listener_fn);
	};

	// Invoke out contstuct() method with the new() arguments supplied
	this.reconstruct(editor_mode, textarea_id, overrideSettings);
}
}) (jQuery);

