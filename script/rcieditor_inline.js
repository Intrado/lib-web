/**
 * This is the inline editor JavaScript that services rcieditor_inline.php
 *

 * EXTERNAL DEPENDENCIES
   * ckeditor.js
   * jquery-*.js
   * rcieditor.js
 */
(function ($) {
window.RCIEditorInline = function () {
	var self = this;

	this.editableTarget = null;
	this.captureTimeout = 0;

	this.constructed = false;
	this.constructRetryDelay = 16;
	this.constructRetryTimeout = null;

	this.initialized = false;
	this.initRetryDelay = 16;
	this.initRetryTimeout = null;

	/**
	 * Our pseudo-constructor executes when the script is loaded
	 *
	 * Checks the parent frame/window for presence of RCIEditor and this frame for CKEDITOR;
	 * if either is missing, creates a timer for deferred initialization to try again in a few milli's.
	 *
	 * Sets up an instanceCreated event handler for CKEDITOR so that when we create the inline editors
	 * associated with the various editableBlocks of the document, each will bear these same properties
	 * and methods.
	 */
	this.construct = function () {

		// If the CKEDITOR object isn't ready yet...
		if ((typeof window.top.rcieditor === 'undefined') || (typeof CKEDITOR === 'undefined')) {

			// Try again after a few milli's
			clearTimeout(this.constructRetryTimeout);

			// Exponential decay on retry timing; we won't delay any longer than 1.6 seconds,
			// but we will continue retrying on this interval as long as necessary.
			if (this.constructRetryDelay < 16384) {
				this.constructRetryDelay *= 2;
			}
			this.constructRetryTimeout = window.setTimeout(this.construct, this.constructRetryDelay);
		}

		// This property tells CKEditor to not activate every element with contenteditable=true element.
		CKEDITOR.disableAutoInline = true;

		var that = this;
		CKEDITOR.on( 'instanceCreated', function( event ) {
			var editor = event.editor;

			editor.on( 'configLoaded', function() {

				// Prevent ckeditor from trying to load an external configuration file
				editor.config.customConfig = '';

				// SMK added to selectively enable reduction scaling for uploaded images;
				// page that includes CKE must set global var htmlEditorImageScalingEnable
				// to true to enable scaling, otherwise scaling will be disabled by default;
				// uploader.php will pass the argument on to f.handleFileUpload() which will
				// ultimately be responsible for enforcement of this flag
				var uploaderURI = window.top.rcieditor.getSetting('baseUrl') + 'uploadimage.php';
				var max_size;
				if ((max_size = parseInt(window.top.rcieditor.getSetting('image_scaling'))) > 0) {
					uploaderURI += '?scaleabove=' + max_size;
				}
				editor.config.filebrowserImageUploadUrl = uploaderURI;

				editor.config.extraPlugins = 'aspell,mkfield';
				editor.config.toolbar = [
					['Undo','Redo'],
					['PasteFromWord','SpellCheck','mkField'],
					['Styles','Format','Font','FontSize'],
					'/',
					['Link','Image','Table','HorizontalRule'],
					['Bold','Italic','Underline','Strike','TextColor','BGColor','RemoveFormat'],
					['NumberedList','BulletedList','Outdent','Indent'],
					['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']
				];
			});

			editor.on('key', function(event) {

				// The following keys we want to captureChanges() on because they just represent changes to the content;
				// any other keys are for cursor movement, etc. which don't affect the content.
				// ref: http://www.webonweboff.com/tips/js/event_key_codes.aspx
				var keyCode = event.data.keyCode & 255;
				if ((keyCode >= 48) || (keyCode == 8) || (keyCode == 9) || (keyCode == 13) || (keyCode == 32) || (keyCode == 46)) {
					clearTimeout(this.captureTimeout);
					this.captureTimeout = window.setTimeout( (function () { that.captureChanges(); }), 500);
				}
			});
			editor.on('blur', (function () { console.log('hrm!'); that.captureChanges(); }) );
			editor.on('saveSnapshot', (function () { that.captureChanges(); }) );
			editor.on('afterCommandExec', (function () { that.captureChanges(); }) );
			editor.on('insertHtml', (function () { that.captureChanges(); }) );
			editor.on('insertElement', (function () { that.captureChanges(); }) );

		});

		this.constructed = true;
	}

	/**
	 * This initialization function is called when the IFRAME'd page is loaded
	 *
	 * Requires that the construct method has successfully executed, and retries
	 * indefinitely if it has not.
	 *
	 * Grabs the current contents of the textarea from the parent frame, sticks it
	 * into this frame's body as the main content, and then attaches inline editors
	 * to each of the editableBlock CLASS'ed DIV's.
	 */
	this.init = function (targetName) {

		// If SELF object isn't ready yet...
		if (! this.constructed) {

			// Try again after a few milli's
			clearTimeout(this.initRetryTimeout);

			// Exponential decay on retry timing; we won't delay any longer than 1.6 seconds,
			// but we will continue retrying on this interval as long as necessary.
			if (this.initRetryDelay < 16384) {
				this.initRetryDelay *= 2;
			}
			this.initRetryTimeout = window.setTimeout(this.init, this.initRetryDelay);
		}
		this.initialized = true;

		if (targetName == "null") {
			return(false);
		}

		// The ID attribute of the textarea that has the text we want to edit
		this.editableTarget = targetName;

		// Get the textarea DOM object from the parent window (frame)
		var textarea = 0;
		if (! (textarea = this.getTextarea())) {
			return(false);
		}

		// Get the wysiwygpage div DOM object from this page (below)
		var wysiwygpage = 0;
		if (! (wysiwygpage = this.getPage())) {
			return(false);
		}

		// Grab the contents of the textarea form element
		//var content = textarea.html();
		var content = textarea.val();

		// Convert content entities back to the real characters, but the double angles '<<' and '>>'
		// will be left in entity form in order to prevent the browser rendered from trying to do
		// something "smart" with them (field inserts).
		content = content.replace(/&lt;/g, '<');
		content = content.replace(/<</g, '&lt;&lt;');
		content = content.replace(/&gt;/g, '>');
		content = content.replace(/>>/g, '&gt;&gt;');
		content = content.replace(/&amp;/g, '&');

		// Inject the content from the textarea into the wysiwyg page div
		wysiwygpage.html(content);

		// Apply CKE inline editors for each wysiwygpage > div.contenteditable=true
		var that = this;
		$('.editableBlock', wysiwygpage).each(function(index) {
			that.makeEditable($(this), index);
		});

		// Fire the callback indicating initialization is done
		window.frames.top.rcieditor.callbackEditorLoaded('wysiwygpage');

		// Do an initial validation only once for the
		// entire editor document instead of once per editor
		this.captureChanges();
	}

	this.makeEditable = function (element, index) {

		// Add an ID for unique association
		var newid = 'editableBlock' + index;
		element.attr('id', newid);
		element.attr('contenteditable', 'true');
		element.attr('tabindex', index);

		// CKEDITOR.inline() does not like element if it is extended with $ or prototype
		// Get an UNextended copy of this same DOM element using the newid we assigned
		// SMK notes this appears to be necessary, possibly because CKEDITOR is initialized seeing and expecting
		// prototype.js extensions, but then the element is delivered with $ extensions, causing CKE to not
		// be able to use the object since it doesn't align with its expectations.
		var el = document.getElementById(newid);
		CKEDITOR.inline(el);
	}

	this.makeNonEditable = function (element) {

		// Reverse out the element attributes that we added
		// and also that CKE adds and leaves behind
		element.removeAttr('id');
		element.removeAttr('contenteditable');
		element.removeAttr('tabindex');
		element.removeAttr('style');
		element.removeAttr('spellcheck');
		element.attr('class', 'editableBlock');
	}

	this.captureChanges = function () {

		// (1) Get the wysiwygpage div DOM object from this page (below)
		var wysiwygpage = 0;
		if (! (wysiwygpage = this.getPage())) {
			return(false);
		}

		// (2) Get the textarea DOM object from the parent window (frame)
		var textarea = 0;
		if (! (textarea = this.getTextarea())) {
			return(false);
		}

		// (3) Get the wysiwygpresave div DOM object from this page (below)
		var wysiwygpresave = 0;
		if (! (wysiwygpresave = this.getPresaveContainer())) {
			return(false);
		}

		// (4) Stick the wysiwyg page content into the presave container
		wysiwygpresave.html(wysiwygpage.html());

		// (5) Disable editing capabilities on all the presave editableBlocks
		var that = this;
		$('.editableBlock', wysiwygpresave).each(function(index) {
			that.makeNonEditable($(this));
		});

		// (6) Now grab the contents of the wysiwyg presave container
		var content = window.top.rcieditor.cleanContent(wysiwygpresave.html());

		// (7) Inject the content from the wysiwyg page div into the textarea form element
		textarea.val(content);

		// (8) Validate the changes as captured
		window.top.rcieditor.validate();

		return(true);
	}

	this.getTextarea = function () {
		var textarea = null;
		if (! (textarea = $('#' + this.editableTarget, window.top.document))) {
			return(null);
		}
		return(textarea);
	}

	this.getPage = function () {
		var wysiwygpage = null;
		if (! (wysiwygpage = $('#wysiwygpage'))) {
			return(null);
		}
		return(wysiwygpage);
	}

	this.getPresaveContainer = function () {
		var wysiwygpresave = null;
		if (! (wysiwygpresave = $('#wysiwygpresave'))) {
			return(null);
		}
		return(wysiwygpresave);
	}

	this.construct();
}
}) (jQuery);

rcieditorinline = new RCIEditorInline();

