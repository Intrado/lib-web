/**
 * This is the inline editor JavaScript that services rcieditor_inline.php
 *
 * @todo SMK notes 2013-01-24 that jquery conversion is still needed; initial
 * attempts to replace prototype with jquery seem to cause some sort of conflict
 * within ckeditor.js which causes CKEDITOR to no longer be able to see it's own
 * '$' member for XML processing. It is possible that instantiating jQuery
 * triggers this result despite running the noConflict().
 *
 * EXTERNAL DEPENDENCIES
   * ckeditor.js
   * prototype.js
   * rcieditor.js
 */
function RCIEditorInline () {
	var self = this;

	self.editableTarget = null;
	self.captureTimeout = 0;

	self.constructRetryDelay = 16;
	self.constructRetryTimeout = null;
	self.construct = function () {

//console.log('rcieditorinline::construct() A');
		// If the CKEDITOR object isn't ready yet...
		if ((typeof window.top.RCIEditor === 'undefined') || (typeof CKEDITOR === 'undefined')) {

			// Try again after a few milli's
			clearTimeout(self.constructRetryTimeout);

			// Exponential decay on retry timing
			self.constructRetryDelay *= 2;
			if (self.constructRetryDelay < 1024) {
				self.constructRetryTimeout = window.setTimeout(self.construct, self.constructRetryDelay);
			}
			// else console.log('RCIEditorInline construct failed: CKEDITOR object never became availabkle');
		}

//console.log('rcieditorinline::construct() B');
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

//console.log('rcieditorinline::construct() C');
				self.doValidation();
//console.log('rcieditorinline::construct() D');
			});

//console.log('rcieditorinline::construct() E');
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
				//else {
				//	console.log('event data:');
				//	console.log(event.data);
				//	console.log('keycode to string = [' + String.fromCharCode(event.data.keyCode) + ']');
				//}
			});
			editor.on('saveSnapshot', self.captureChanges);
			editor.on('afterCommandExec', self.captureChanges);
			editor.on('insertHtml', self.captureChanges);
			editor.on('insertElement', self.captureChanges);

//console.log('rcieditorinline::construct() F');
		});
	}

	self.makeEditable = function (element, index) {

		// Add an ID for unique association
		var newid = 'editableBlock' + index;
		element.attr('id', newid);
		element.attr('contenteditable', 'true');
		element.attr('tabindex', index);

		// For some reason CKEDITOR.inline() does not like element if it is extended with jQuery or prototype
//console.log('rcieditorinline::makeEditable() A');
		// Get an UNextended copy of this same DOM element using the newid we assigned
		// SMK notes this appears to be necessary, possibly because CKEDITOR is initialized seeing and expecting
		// prototype.js extensions, but then the element is delivered with jQuery extensions, causing CKE to not
		// be able to use the object since it doesn't align with its expectations.
		var el = document.getElementById(newid);
		CKEDITOR.inline(el);
//console.log('rcieditorinline::makeEditable() B');
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
		var content = textarea.html(); // jquery.js
		//var content = textarea.innerHTML; // prototype.js

		// Convert content entities back to the real characters
		content = content.replace(/&lt;/g, '<');
		content = content.replace(/<</g, '&lt;&lt;');
		content = content.replace(/&gt;/g, '>');
		content = content.replace(/>>/g, '&gt;&gt;');
		content = content.replace(/&amp;/g, '&');

		// Inject the content from the textarea into the wysiwyg page div
		wysiwygpage.empty().append(content); // jquery.js
		//wysiwygpage.insert(content); // prototype.js

		// Apply CKE inline editors for each wysiwygpage > div.contenteditable=true
		jQuery('.editableBlock', wysiwygpage).each(function(index) { // jquery.js
//console.log('Making editable [' + index + ']');
			self.makeEditable(jQuery(this), index);
		});
		//var editableBlocks = $(wysiwygpage).select('.editableBlock'); // prototype.js (+3...)
		//for (var i = 0; i < editableBlocks.length; i++) {
		//	self.makeEditable(editableBlocks[i], i);
		//}

		// Fire the callback indicating initialization is done
		window.frames.top.RCIEditor.callbackEditorLoaded('wysiwygpage');
	}

	//self.editorDirty = false;
	self.captureChanges = function () {
//console.log('rcieditorinline::captureChanges() A');

		// (1) Get the wysiwygpage div DOM object from this page (below)
		var wysiwygpage = 0;
		if (! (wysiwygpage = self.getPage())) {
			return(false);
		}

//console.log('rcieditorinline::captureChanges() B');
		// (2) Get the textarea DOM object from the parent window (frame)
		var textarea = 0;
		if (! (textarea = self.getTextarea())) {
			return(false);
		}

//console.log('rcieditorinline::captureChanges() C');
		// (3) Get the wysiwygpresave div DOM object from this page (below)
		var wysiwygpresave = 0;
		if (! (wysiwygpresave = self.getPresaveContainer())) {
			return(false);
		}

//console.log('rcieditorinline::captureChanges() D');
		// (4) Stick the wysiwyg page content into the presave container
		wysiwygpresave.empty().html(wysiwygpage.html()); // jquery.js
		//wysiwygpresave.update(wysiwygpage.innerHTML); // prototype.js

		// (5) Disable editing capabilities on all the presave editableBlocks
		// jquery.js
		jQuery('.editableBlock', wysiwygpresave).each(function(index) {
			self.makeNonEditable(jQuery(this));
		});
		//var editableBlocks = $(wysiwygpresave).select('.editableBlock'); // prototype.js (+3...)
		//for (var i = 0; i < editableBlocks.length; i++) {
		//	self.makeNonEditable(editableBlocks[i]);
		//}

		// (6) Now grab the contents of the wysiwyg presave container
		//content = window.top.RCIEditor.cleanContent(wysiwygpresave.html()); // jquery.js
		var content = window.top.RCIEditor.cleanContent(wysiwygpresave.html()); // prototype.js

		// (7) Convert real characters to content entities
		content = content.replace(/&/g, '&amp;');
		content = content.replace(/</g, '&lt;');
		content = content.replace(/>/g, '&gt;');

		// (8) Inject the content from the wysiwyg page div into the textarea form element
		textarea.empty().html(content); // jquery.js
		//textarea.update(content); // prototype.js

		// (9) Validate the changes as captured
//console.log('captured!');
//console.log('rcieditorinline::captureChanges() E');
		return(self.doValidation(textarea));
	}

	self.doValidation = function (textarea) {

//console.log('rcieditorinline::doValidation() A');
		if (typeof textarea === 'undefined') {
			textarea = self.getTextarea();
		}

//console.log('rcieditorinline::doValidation() B');
		if (! textarea) return(false);

		// Interface with the legacy form validation
		window.top.RCIEditor.validate();

//console.log('rcieditorinline::doValidation() D');
		return(true);
	}

	self.getTextarea = function () {
		var textarea = null;
		if (! (textarea = jQuery('#' + self.editableTarget, window.top.document))) { // jquery.js
		//if (! (textarea = $(window.top.document.getElementById(self.editableTarget)))) { // prototype.js
			//console.log('failed to find target [' + self.editableTarget + ']');
			return(null);
		}
		return(textarea);
	}

	self.getPage = function () {
		var wysiwygpage = null;
		if (! (wysiwygpage = jQuery('#wysiwygpage'))) { // jquery.js
		//if (! (wysiwygpage = $('wysiwygpage'))) { // prototype.js
			//console.log('failed to find wysiwygpage');
			return(null);
		}
		return(wysiwygpage);
	}

	self.getPresaveContainer = function () {
		var wysiwygpresave = null;
		if (! (wysiwygpresave = jQuery('#wysiwygpresave'))) { // jquery.js
		//if (! (wysiwygpresave = $('wysiwygpresave'))) { // prototype.js
			console.log('failed to find wysiwygpresave');
			return(null);
		}
		return(wysiwygpresave);
	}

	self.construct();
}

rcieditorinline = new RCIEditorInline();

