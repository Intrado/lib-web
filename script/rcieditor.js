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
	window.RCIEditor = function (editor_mode, textarea_id, overrideSettings) {

		var textarea = null;	// The textarea ELEMENT, not the ID
		var container = null;	// The container ELEMENT, to contain the editor, not the ID
		var editorMode = null;	// Either null (uninitialized) or [plain|normal|full|inline]
		var basename = 'rcicke';
		var iframeIdName = 'rcicke_iframe';
		var customsettings = {};
		var inlineIframe = null;

		var that = this;
	
		// Lifted from utils.js so that we don't need to include that whole thing as an external dep.
		this.getBaseUrl = function () {
			var url = window.location.protocol + "//" + window.location.host + window.location.pathname;
			var baseUrl = url.substr(0, url.lastIndexOf('/') + 1);        // Get everything thru the last '/'
			return(baseUrl);
		};
	
		// Associative array support for settings; use of set/getter's is required
		var settings = null;
	
		this.clearSettings = function () {
			settings = {};
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
				if (! that.deconstruct()) return(false);
			}
	
			// (2) Reset all internal properties
			textarea = null;
			container = null;
			editorMode = null;
	
			// reset the settings array
			that.clearSettings();
	
			// Image scaling is disabled by default
			that.setSetting('image_scaling', 0);
	
			// The default settings for custom toolbar buttons
			that.setSetting('tool_mkfield', false);
			that.setSetting('tool_attachmentlink', false);
			that.setSetting('tool_mkblock', false);
			that.setSetting('tool_thememgr', false);
			that.setSetting('tool_pastefromphone', false);
			that.setSetting('hidetoolbar', false);
			that.setSetting('fieldinsert_list', {});
			that.setSetting('callback_onready_fn', null); // Optional callback fn to exec when editor is ready
			that.setSetting('callback_onchange_fn', null); // Optional callback fn to exec when editor content changes
			that.setSetting('callback_oncapture_fn', null); // Optional callback fn to exec when inline editor's content is captured

			// Make a generic, reusable text clipboard
			that.setSetting('clipboard', '');
	
			// Get the base URL for requests that require absolute pathing
			that.setSetting('baseUrl', that.getBaseUrl());
	
			// Clear any validator that was set
			that.resetValidatorFunction();
	
			// (3) Apply the editor to the chosen textarea
			var container_id = textarea_id + '-htmleditor';
			return(that.applyEditor(editor_mode, textarea_id, container_id, overrideSettings));
		};
	
		/**
		 * This pseudo destructor tears down the editor interface, but leaves the main
		 * object ready to continue working with a subsequent call to the construct method.
		 */
		this.deconstruct = function () {
	
			// Show the loading spinner
			that.setLoadingVisibility(true);

			// Tear down whatever editor is in place
			switch (editorMode) {
				case 'inline':
	
					// We can get rid of the IFRAME'd inline editor
					// just by emptying out the container
					var tmpcontainer = $('#' + basename + '-htmleditor');
					if (!tmpcontainer.length) {
						break;
					}
	
					tmpcontainer.empty();
					return(true);
	
				case 'plain':
				case 'normal':
				case 'full':
					var htmleditorobject = that.getHtmlEditorObject();
					if (!htmleditorobject) {
						break;
					}
	
					if (typeof textarea !== 'object') {
						break;
					}
	
					// Capture the textarea content to prevent CKE from further altering it
					var content = textarea.val();
	
					// Let CKE do whatever it does while destroying itself
					try {
						htmleditorobject.instance.destroy();
					}
					catch (ex) {
						//console.log('caught exception: [' + ex.message + ']');
					}
	
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
			var res = that.reconstruct(newEditorMode, textarea_id, overrideSettings);
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
		this.applyEditor = function (setEditorMode, textarea_id, container_id, overrideSettings) {
	
			// Hide the text area form field until we are done initializing
			textarea = $('#' + textarea_id);
			textarea.hide();
			that.setLoadingVisibility(true);
	
			// If we are already running
			if (editorMode) {
	
				// And we want to apply to the same textarea/container
				if ((textarea_id == textarea.attr('id')) && (container_id == container.attr('id'))) {
	
					// Then change the mode which will call us when ready
					return(that.changeMode(setEditorMode));
				}
	
				// Applying to a different textarea/container is not allowed
				return(false);
			}
	
			// If CKEDITOR is not ready, check back here every second until it is
			if ((typeof CKEDITOR == 'undefined') || (!CKEDITOR)) {
	
				// We will try again in one second since CKE is not ready yet
				window.setTimeout(function () {
					that.applyEditor(setEditorMode, textarea_id, container_id, overrideSettings);
				}, 10);
				return('deferred');
			}
	
			// Override some settings
			for (var setting in overrideSettings) {
				that.setSetting(setting, overrideSettings[setting]);
			}
	
			// base name of the text element; we'll make several new elements with derived names
			basename = textarea_id;
			container = $('#' + container_id);
	
			// The first new element has the same id with a 'hider' suffix
			var hider = $('#' + basename + 'hider');
			if (!hider.length) {
				hider = $('<div id="' + basename + 'hider" style="display: none;"></div>');
				container.append(hider);
			}
	
			var cke = $('<div id="' + basename + '_box"></div>');
	
			// For the full CKEditor, the toolbars/plugins
			// are different depending on the editorMode
			var extraButtons = [];
			var extraPlugins = [];
			extraPlugins.push('aspell'); // We always want the spell checker added
			extraPlugins.push('autogrow'); // We always want the autogrow added
			extraPlugins.push('dragresize'); // dragresize fixes image resizing in webkit-based browsers missing from the native CKE support
			switch (setEditorMode) {
	
				default:
					// If editorMode was not supplied, we need to set it
					setEditorMode = 'plain';
					that.setSetting('tool_mkfield', true);
					that.setSetting('tool_attachmentlink', true);
					// Nothing extra to add for the plain legacy editor
					break;
				case 'plain':
					// Nothing extra to add for the plain legacy editor
					break;
	
				case 'inline':
					// Add the mkField tool only
					that.setSetting('tool_mkfield', true);
					that.setSetting('tool_attachmentlink', true);
					break;
	
				case 'normal':
					// Add the mkField tool only
					that.setSetting('tool_mkfield', true);
					that.setSetting('tool_attachmentlink', true);
					// FIXME SMK disabled image_scaling pending clarification of desired behavior
					//that.setSetting('image_scaling', 500);
					break;
	
				case 'full':
					// Add the mkField, mkBlock, and themeMgr tools
					that.setSetting('tool_mkfield', true);
					that.setSetting('tool_attachmentlink', true);
					that.setSetting('tool_mkblock', true);
					that.setSetting('tool_thememgr', true);

					// FIXME SMK disabled image_scaling pending clarification of desired behavior
					//that.setSetting('image_scaling', 500);
					break;
			}
	
			if (setEditorMode == 'inline') {
				// FIXME SMK disabled image_scaling pending clarification of desired behavior
				//that.setSetting('image_scaling', 500);
	
				// Add an IFRAME to the page that will load up the inline editor
				iframeIdName = basename+'_iframe';
				inlineIframe = $('<iframe ' +
					'src="' + that.getSetting('baseUrl') + 'rcieditor_inline.php?t=' + basename + '&d=' + document.domain + '" ' +
					'name="' + iframeIdName + '" id="' + iframeIdName + '"' +
					'style="width: 100%; height: 400px; border: 1px solid #999999; overflow-y: hidden;" scrolling="no">'
				);
				cke.empty().append(inlineIframe);
	
				// So now we have the inline editor component loading in an iframe;
				// the next move is up to the iframe content to call back the next
				// function below to get the two halves communicating cross-frame.
			}
			else {
	
				// Activate whatever tools are enabled based on mode
				var custom_tools = [ 'mkField', 'AttachmentLink', 'mkBlock', 'themeMgr', 'pasteFromPhone' ];

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
				var uploaderURI = that.getSetting('baseUrl') + 'uploadimage.php';
				var max_size;
				if ((max_size = parseInt(that.getSetting('image_scaling'))) > 0) {
					uploaderURI += '?scaleabove=' + max_size;
				}

				var uploadattachment= this.getSetting('baseUrl') + 'uploadattachment.php';
				var toolBars = [
					{ name:'r1g1', items:[ 'Print', 'Source' ] },
					{ name:'r1g2', items:[ 'Undo', 'Redo'] },
					{ name:'r1g3', items:[ 'NumberedList', 'BulletedList', 'JustifyLeft', 'JustifyCenter', 'JustifyRight', 'JustifyBlock', 'Outdent', 'Indent' ] },
					{ name:'r1g4', items:[ 'PasteFromWord', 'SpellCheck' ] },
					{ name:'r1g5', items:[ 'Link', 'Image', 'Table', 'HorizontalRule' ] },
					{ name:'r1g6', items:[ 'ShowBlocks', 'Maximize' ] },
					'/',
					{ name:'r2g1', items:[ 'Bold', 'Italic', 'Underline', 'Strike', 'TextColor', 'BGColor', 'RemoveFormat' ] },
					{ name:'r2g2', items:[ 'Styles', 'Format', 'Font', 'FontSize' ] },
					{ name:'r2g3', items:extraButtons }
				];

				cke_config = {
					'width': '100%',
					'height': '400px',
					'autoGrow_onStartup': true,
					'autoGrow_maxHeight': ($(window).height() - 150),
					'customConfig': '',
					'allowedContent': true,
					'disableNativeSpellChecker': false,
					'browserContextMenuOnCtrl': true,
					'filebrowserImageUploadUrl': uploaderURI,
					'filebrowserUploadUrl':uploadattachment,
					'toolbarStartupExpanded': ( that.getSetting('hidetoolbar') ? false : true),
					'toolbarCanCollapse': true,
					'extraPlugins': extraPlugins.join(),
					'disableObjectResizing': false,
					'resize_enabled': true,
					'pasteFromWordRemoveFontStyles': false,
					'pasteFromWordRemoveStyles': false,
                                        
                                        // specifically name which fonts CKEditor can display in order to remove Comic Sans
                                        'font_names':"Arial/Arial, Helvetica, sans-serif;"+
                                                    "Courier New/Courier New, Courier, monospace;"+
                                                    "Georgia/Georgia, serif;"+
                                                    "Lucida Sans Unicode/Lucida Sans Unicode, Lucida Grande, sans-serif;"+
                                                    "Tahoma/Tahoma, Geneva, sans-serif;Times New Roman/Times New Roman, Times, serif;"+
                                                    "Trebuchet MS/Trebuchet MS, Helvetica, sans-serif;"+
                                                    "Verdana/Verdana, Geneva, sans-serif",
	
					'toolbar_RCI': toolBars,
	
					'toolbar': 'RCI',
	
					'on': {
						'instanceReady': function (event) {
							that.callbackEditorLoaded(that);
						},
						'key': ( function (evt) {
							that.eventListener(evt);
						} ),
						'blur': ( function (evt) {
							that.eventListener(evt);
						} ),
						'saveSnapshot': ( function (evt) {
							that.eventListener(evt);
						} ),
						'afterCommandExec': ( function (evt) {
							that.eventListener(evt);
						} ),
						'insertHtml': ( function (evt) {
							that.eventListener(evt);
						} ),
						'insertElement': ( function (evt) {
							that.eventListener(evt);
						} )
					}
				};

				// Support for the caller conditionally remove some built-in plugins
				if (that.getSetting('cke_remove_plugins')) {
					cke_config.removePlugins = that.getSetting('cke_remove_plugins');
				}

				// Now attach CKE to the form element with the name basename
				// TODO - see if there's a way to get this CKE to insert itself into hider element
				// FIXME - autoGrow_maxHeight establishes a height to keep toolbar/menu in view at current
				// browser size, however if the user resizes the browser, this value needs to be adjusted.
				CKEDITOR.replace(basename, cke_config);
	
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
		this.callbackEditorLoaded = function (activeContainerId) {
			that.setSetting('activeContainerId', activeContainerId);
	
			// Hide our AJAXy loading indicator
			that.setLoadingVisibility(false);

			// Hook a call to caller's callback for onready event
			var callback;
			if (callback = that.getSetting('callback_onready_fn')) {
				callback(true);
			}

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
	
					var htmleditorobject = that.getHtmlEditorObject();
					if (! htmleditorobject) {
						// failed to get the htmleditorobject
						return;
					}
	
					// A little data sanitizing for the raw textarea form content
					htmleditorobject.instance.setData(html.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;").replace(/<{/g, "&lt;{").replace(/}>/g, "}&gt;"));
					break;
			}
	
			// Initial validation, only if there is content in the HTML already;
			// this allows the FI validation icon to show required field state instead
			// of an initial error condition if the user hasn't entered anything yet.
			if (html.length) {
				that.validate();
			}
		};
	
		/**
		 * Show or hide a spinning, AJAXy loading indicator
		 *
		 * @param boolean visible Visible state of the "Please wait" indicator, true to show, false to hide
		 */
		that.loadingVisible = false;
		that.setLoadingVisibility = function (visible) {
	
			// If we want to make it visible...
			if (visible) {
	
				// If we're already visible or we have no textarea to work with
				if (that.loadingVisible || (!textarea)) {
	
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
	
				that.loadingVisible = true;
			}
	
			// Otherwise we're hiding it
			else {
	
				// If it's already hidden
				if (! that.loadingVisible) {
	
					// Then there's nothing to do
					return;
				}
	
				// Get rid of it - we'll readd it again later if necessary
				$('#htmleditorloadericon').remove();
	
				// And show the editor
				$('#' + basename + 'hider').show();
	
				that.loadingVisible = false;
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
	
			if (!CKEDITOR.instances) {
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
				'instance':instance,
				'container':tmpcontainer,
				'currenttextarea':(textarea && textarea.hasClass('HtmlEditor')) ? textarea : null
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
			if (editorMode === 'inline') {
				var rcieditorinline = that.getInlineEditor();
				rcieditorinline.captureChanges();
			}
			else {	
				var htmleditorobject = existinghtmleditorobject || that.getHtmlEditorObject();
				if (! htmleditorobject) {
					return(false);
				}
				var content = htmleditorobject.instance.getData();
				var cleanedContent = that.cleanContent(content);
				textarea.val(cleanedContent);
			}

			// Hook a call to caller's callback for onchange event
			var callback;
			if (callback = that.getSetting('callback_onchange_fn')) {
				callback(cleanedContent);
			}

			return(true);
		};

		/**
		 * Gets the inline editor object across frames (if applicable)
		 */	
		this.getInlineEditor = function () {
			return (editorMode === 'inline') ? window.frames[basename + '_iframe'].window.rcieditorinline : null;
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
	
			var htmleditorobject = existinghtmleditorobject || that.getHtmlEditorObject();
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
	
					var rcieditorinline = that.getInlineEditor();
	
					// Textarea is NOT a blockelement that contains HTML; it is a form field with a value
					// so we have to get the value of the field, stick it in jquery space temporarily,
					// manipulated it, and then put it back into the textarea again:
					if (rcieditorinline.activeEditorId) {
						var tempdiv = $('<div></div>').html(textarea.val());
						$('#' + rcieditorinline.activeEditorId, tempdiv).each(function () {
	
							var jQthis = $(that);
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
					that.refreshHtmlEditorContent();
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
			var htmleditorobject = existinghtmleditorobject || that.getHtmlEditorObject();
			if (!htmleditorobject) {
				return(false);
			}
			return(that.setHtmlEditorContent('', htmleditorobject));
		};
	
		/**
		 * Set the contents of the editor; the textarea should be cleared by the editor
		 *
		 * @param object existinghtmleditorobject  Optional, rarely used CKE
		 * object other than the one we're using internally
		 */
		this.setHtmlEditorContent = function (content, existinghtmleditorobject) {
			var htmleditorobject = existinghtmleditorobject || that.getHtmlEditorObject();
			if (!htmleditorobject) {
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
			var html = that.cleanFieldInserts(tempdiv.html()).replace(/&lt;&lt;/g, '<<').replace(/&gt;&gt;/g, '>>').replace(/&lt;{/g, '<{').replace(/}&gt;/g, '}>');
	
			// CKEditor inserts blank tags even if the user has deleted everything.
			// check if there is an image or href tag... if not, strip the tags and see if there is any text
			if (! html.match(/[img,href]/)) {
	
				// For plain text, $ does not seem to extend it the same (there won't be any tags anyway)
				if (typeof html.text !== 'undefined') {
	
					// strips all html tags, then strips whitespace. If there is nothing left... set the html to an empty string
					if (that.trim(html.text()).replace(/[&nbsp;,\n,\r,\t]/g, '') == '') {
						html = '';
					}
				}
			}
			//remove unnecessary cke attributes
			html = html.replace(/data-cke-saved-href=.+?\/emailattachment.php\?.+?href=/g, 'href=');
			return(html);
		};

		/**
		 * Corrects any html tags that may be inside a data-field insert.
		 *
		 * Example: &lt;&lt;First <b>Name</b>&gt;&gt; becomes <b>&lt;&lt;First Name&gt;&gt;
		 * NOTE: It is assumed that the tokens are &lt;&lt; and &gt;&gt; instead of << and >>.
		 * 					   &lt;{ and }&gt; instead of <{ and }>.
		 *
		 * @param string html The HTML code from the editor that we want to clean up
		 *
		 * @return string The cleaned up HTML
		 */
		this.cleanFieldInserts = function (html) {

			// Some HTML tag getting inserted into our marker?
			// FIXME: This function does not appear... to function... as intended.
			var possibilities = [];

			// A normal FI with a possible disruption at beginning and/or end
			//possibilities.push('/&lt;(<.*?>)*?(&lt;)(.+?)(&gt;)(<.*?>)*?&gt;/g');
			possibilities.push({
				'opener': '&lt;&lt;',
				'opener_pattern': '&lt;(<.*?>)*?&lt;',
				'closer': '&gt;&gt;',
				'closer_pattern': '&gt;(<.*?>)*?&gt;'
			});

			// An SDD FI with a possible disruption at beginning and/or end
			//possibilities.push('/&lt;(<.*?>)*?(\{)(.+?)(\})(<.*?>)*?&gt;/g');
			possibilities.push({
				'opener': '&lt;{',
				'opener_pattern': '&lt;(<.*?>)*?\{',
				'closer': '}&gt;',
				'closer_pattern': '\}(<.*?>)*?&gt;'
			});

			// For each of the possibilities properties...
			for (var num in possibilities) {

				// ref: http://stackoverflow.com/questions/9329446/how-to-do-for-each-over-an-array-in-javascript
				if  (! (possibilities.hasOwnProperty(num) &&	// Unless possibilities has this property (not inherited)
					/^0$|^[1-9]\d*$/.test(num) &&		// and it's an integer number
					num <= 4294967294)) {			// and it's within integer range for an array index
					continue;				// move on to the next object property to iterate
				}

				var possibility = possibilities[num];

				// Find any FI's that start with this possible opener's pattern and have the matching closer
				//var regex = '/' + possibility.opener_pattern + '(.+?)' + possibility.closer_pattern + '/g';
				var regex = new RegExp(possibility.opener_pattern + '(.+?)' + possibility.closer_pattern, 'g');

				// And if we find any like this...
				var matches = html.match(regex);
				if (matches) {

					// Replace them with a version that shoves text inserted in the middle of the markers to the outside
					var replacewith = '$1' + possibility.opener + '$2' + possibility.closer + '$3';

					// For each one found...
					for (var i = 0, count = matches.length; i < count; i++) {

						// Do the replacement
						var cleaner = matches[i].replace(regex, replacewith);

						// Capture the text to the left and right of the FI...
						var beforeinsert = cleaner.match(new RegExp('^(.*)?' + possibility.opener))[1] || '';
						var afterinsert = cleaner.match(new RegExp(possibility.closer + '(.*)?$'))[1] || '';

						// Capture just the text *within* the FI itself
						var field = cleaner.match(new RegExp(possibility.opener + '(.+)?' + possibility.closer))[1] || '';
			
						// For any tags opening within the FI...
						var opentags = field.match(/<[^\/]*?>/g);
						if (opentags) {

							// ... move them to before the FI begins
							beforeinsert += opentags.join('');
						}
			
						// For any tags closing within the FI...
						var closedtags = field.match(/<\/.*?>/g);
						if (closedtags) {

							// ... move them to after the FI ends
							afterinsert = closedtags.join('') + afterinsert;
						}

						// TODO - scrub any tags beginning or ending within the FI and trim it...
						field = that.trim(field.replace(/<.+?>/g, ''));

						// And finally replace the originally matched FI with one reconstituted
						// with leading and trailing formatting information
						var replacement = beforeinsert + possibility.opener + field + possibility.closer + afterinsert;

						html = html.replace(matches[i], replacement);
					}
				}
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
		this.eventListener = function (evt) {
			// For null-effect events such as certain key/mouse operations, we will
			// suppress normal event handling just to quiet things down a bit;
			// otherwise we'll trigger validation after every one of these.
			if (evt.name == 'key') {
				switch (evt.data.keyCode) {
					case 2228240: // SHIFT
					case 4456466: // ALT
					case 1114129: // CTRL
					case 112: // F1
					case 113: // F2
					case 114: // F3
					case 115: // F4
					case 116: // F5
					case 117: // F6
					case 118: // F7
					case 119: // F8
					case 120: // F9
					case 121: // F10
					case 122: // F11
					case 123: // F12
					case 38: // UP ARROW
					case 39: // RIGHT ARROW
					case 40: // DOWN ARROW
					case 37: // LEFT ARROW
					case 33: // PAGE UP
					case 34: // PAGE DOWN
					case 36: // HOME
					case 35: // END
					case 45: // INSERT
					case 0: // APPLICATION CONTROL KEYS
					case 20: // CAPSLOCK
					case 144: // NUMLOCK
					case 91: // OS/WINDOWS
					case 27: // ESCAPE
						return;
				}
			}

			// The new "autogrow" plugin triggers afterExec events
			// that we don't need to respond to since they don't
			// result in any change to the content
			if ((evt.name == 'afterExec') && (evt.data.name == 'autogrow')) return;
	
			// We got a new event so reset the timer
			window.clearTimeout(that.eventTimer);
	
			// Get the Editor that we're working with
			var htmleditor = that.getHtmlEditorObject();

			// Set a new timer to fire the save/check
			that.eventTimer = window.setTimeout(function () {

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
			that.validator_fn = validator_fn;
		};
	
		/**
		 * Disables a validator function that was previously set
		 */
		this.resetValidatorFunction = function () {
			that.validator_fn = null;
		}
	
		/**
		 * Called internally whenever a change occurs that needs validation; if
		 * a validator function is set then it will be invoked, otherwise nada.
		 */
		this.validate = function () {
			if (typeof that.validator_fn === 'function') {
				that.validator_fn();
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
			that.setValidatorFunction(listener_fn);
		};

		/**
		 * Adjust the height of the inline editor's iframe so that we can
		 * eliminate its scrollbar but keep opening it up to accommodate
		 * longer documents as needed.
		 */
		this.adjustInlineHeight = function (newHeight) {	

			// This heith adjustment method is only for the inline mode iframe
			if (editorMode != 'inline') return;

			// The actual height needs to accommodate for html,
			// body padding which we can't get otherwise:
			var actualHeight = Math.max(700, newHeight) + 60;
			inlineIframe.height(actualHeight);
		};

		// Invoke our contstuct() method with the new() arguments supplied
		this.reconstruct(editor_mode, textarea_id, overrideSettings);
	}
})(jQuery);

