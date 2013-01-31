/**
 * This is the inline editor JavaScript that services rcieditor_inline.php
 *
 * EXTERNAL DEPENDENCIES
   * ckeditor.js
   * jquery-*.js
   * rcieditor.js
 */
function RCIEditorInline () {
	var self = this;

	self.editableTarget = null;
	self.captureTimeout = 0;

	self.constructRetryDelay = 16;
	self.constructRetryTimeout = null;
	self.construct = function () {

		// If the CKEDITOR object isn't ready yet...
		if ((typeof window.top.RCIEditor === 'undefined') || (typeof CKEDITOR === 'undefined')) {

			// Try again after a few milli's
			clearTimeout(self.constructRetryTimeout);

			// Exponential decay on retry timing
			self.constructRetryDelay *= 2;
			if (self.constructRetryDelay < 1024) {
				self.constructRetryTimeout = window.setTimeout(self.construct, self.constructRetryDelay);
			}
		}

		// This property tells CKEditor to not activate every element with contenteditable=true element.
		CKEDITOR.disableAutoInline = true;

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
				var uploaderURI = window.top.RCIEditor.getSetting('baseUrl') + 'uploadimage.php';
				var max_size;
				if ((max_size = parseInt(window.top.RCIEditor.getSetting('image_scaling'))) > 0) {
					uploaderURI += '?scaleabove=' + max_size;
				}
				editor.config.filebrowserImageUploadUrl = uploaderURI;

				editor.config.extraPlugins = 'aspell,mkfield';
				editor.config.toolbar = [
					['Undo','Redo'],
					['Styles','Format','Font','FontSize'],
					'/',
					['PasteFromWord','SpellCheck','mkField'],
					['Link','Image','Table','HorizontalRule'],
					['Bold','Italic','Underline','Strike','TextColor','BGColor','RemoveFormat'],
					['NumberedList','BulletedList','Outdent','Indent'],
					['JustifyLeft','JustifyCenter','JustifyRight','JustifyBlock']
				];

				self.doValidation();
			});

			editor.on('blur', self.captureChanges);
			editor.on('key', function(event) {
				/*
				The following keys we want to captureChanges() on
				because they just represent changes to the content;
				any other keys are for cursor movement, etc. which
				don't affect the content.
				ref: http://www.webonweboff.com/tips/js/event_key_codes.aspx
				*/
				var keyCode = event.data.keyCode & 255;
				if ((keyCode >= 48) || (keyCode == 8) || (keyCode == 9) || (keyCode == 13) || (keyCode == 32) || (keyCode == 46)) {
					clearTimeout(self.captureTimeout);
					self.captureTimeout = window.setTimeout(self.captureChanges, 500);
				}
			});
			editor.on('saveSnapshot', self.captureChanges);
			editor.on('afterCommandExec', self.captureChanges);
			editor.on('insertHtml', self.captureChanges);
			editor.on('insertElement', self.captureChanges);

		});
	}

	self.makeEditable = function (element, index) {

		// Add an ID for unique association
		var newid = 'editableBlock' + index;
		element.attr('id', newid);
		element.attr('contenteditable', 'true');
		element.attr('tabindex', index);

		// For some reason CKEDITOR.inline() does not like element if it is extended with jQuery or prototype
		// Get an UNextended copy of this same DOM element using the newid we assigned
		// SMK notes this appears to be necessary, possibly because CKEDITOR is initialized seeing and expecting
		// prototype.js extensions, but then the element is delivered with jQuery extensions, causing CKE to not
		// be able to use the object since it doesn't align with its expectations.
		var el = document.getElementById(newid);
		CKEDITOR.inline(el);
	}

	self.makeNonEditable = function (element) {

		// Reverse out the element attributes that we added
		// and also that CKE adds and leaves behind
		element.removeAttr('id');
		element.removeAttr('contenteditable');
		element.removeAttr('tabindex');
		element.removeAttr('style');
		element.removeAttr('spellcheck');
		element.attr('class', 'editableBlock');
	}

	self.init = function (targetName) {
		// The ID attribute of the textarea that has the text we want to edit
		self.editableTarget = targetName;

		// Get the textarea DOM object from the parent window (frame)
		var textarea = 0;
		if (! (textarea = self.getTextarea())) {
			return(false);
		}

		// Get the wysiwygpage div DOM object from this page (below)
		var wysiwygpage = 0;
		if (! (wysiwygpage = self.getPage())) {
			return(false);
		}

		// Grab the contents of the textarea form element
		var content = textarea.html();

		// Convert content entities back to the real characters
		content = content.replace(/&lt;/g, '<');
		content = content.replace(/<</g, '&lt;&lt;');
		content = content.replace(/&gt;/g, '>');
		content = content.replace(/>>/g, '&gt;&gt;');
		content = content.replace(/&amp;/g, '&');

		// Inject the content from the textarea into the wysiwyg page div
		wysiwygpage.empty().append(content);

		// Apply CKE inline editors for each wysiwygpage > div.contenteditable=true
		jQuery('.editableBlock', wysiwygpage).each(function(index) {
			self.makeEditable(jQuery(this), index);
		});

		// Fire the callback indicating initialization is done
		window.frames.top.RCIEditor.callbackEditorLoaded('wysiwygpage');
	}

	self.captureChanges = function () {

		// (1) Get the wysiwygpage div DOM object from this page (below)
		var wysiwygpage = 0;
		if (! (wysiwygpage = self.getPage())) {
			return(false);
		}

		// (2) Get the textarea DOM object from the parent window (frame)
		var textarea = 0;
		if (! (textarea = self.getTextarea())) {
			return(false);
		}

		// (3) Get the wysiwygpresave div DOM object from this page (below)
		var wysiwygpresave = 0;
		if (! (wysiwygpresave = self.getPresaveContainer())) {
			return(false);
		}

		// (4) Stick the wysiwyg page content into the presave container
		wysiwygpresave.empty().html(wysiwygpage.html());

		// (5) Disable editing capabilities on all the presave editableBlocks
		jQuery('.editableBlock', wysiwygpresave).each(function(index) {
			self.makeNonEditable(jQuery(this));
		});

		// (6) Now grab the contents of the wysiwyg presave container
		var content = window.top.RCIEditor.cleanContent(wysiwygpresave.html()); // prototype.js

		// (7) Convert real characters to content entities
		content = content.replace(/&/g, '&amp;');
		content = content.replace(/</g, '&lt;');
		content = content.replace(/>/g, '&gt;');

		// (8) Inject the content from the wysiwyg page div into the textarea form element
		textarea.empty().html(content);

		// (9) Validate the changes as captured
		return(self.doValidation(textarea));
	}

	self.doValidation = function (textarea) {

		if (typeof textarea === 'undefined') {
			textarea = self.getTextarea();
		}

		if (! textarea) return(false);

		// Interface with the legacy form validation
		window.top.RCIEditor.validate();

		return(true);
	}

	self.getTextarea = function () {
		var textarea = null;
		if (! (textarea = jQuery('#' + self.editableTarget, window.top.document))) {
			return(null);
		}
		return(textarea);
	}

	self.getPage = function () {
		var wysiwygpage = null;
		if (! (wysiwygpage = jQuery('#wysiwygpage'))) {
			return(null);
		}
		return(wysiwygpage);
	}

	self.getPresaveContainer = function () {
		var wysiwygpresave = null;
		if (! (wysiwygpresave = jQuery('#wysiwygpresave'))) {
			return(null);
		}
		return(wysiwygpresave);
	}

	self.construct();
}

rcieditorinline = new RCIEditorInline();

