<?
/**
 *
 * @todo SMK notes 2013-01-24 that jquery conversion is still needed; initial attempts
 * to replace prototype with jquery seem to cause some sort of conflict within ckeditor.js
 * which causes CKEDITOR to no longer be able to see it's own '$' member for XML processing.
 * It is possible that instantiating jQuery triggers this result despite running the noConflict().
 * CHANGE LOG
   * SMK created
 */

function scrub_ascii($string, $lower_accept = null, $upper_accept = null, $encode = 'js') {
	if (! strlen($string)) return('');
	if (is_null($lower_accept)) $lower_accept = 9;          // TAB
	if (is_null($upper_accept)) $upper_accept = 126;        // '~'

	$ascii = '';
	for ($i = 0; $i < strlen($string); $i++) {
		$chord = ord($string{$i});

		// is the character within our accepted range?
		if (($chord >= $lower_accept) && ($chord <= $upper_accept)) {
			// yep - just tack it on to the end of the output string
			$ascii .= $string{$i};
			continue;
		}
		$hex = str_pad(dechex($chord), 2, '0', STR_PAD_LEFT);

		// nope - encode the byte code according to the speficied encoding method
		switch ($encode) {
			case 'js': // Javascript notation
				$ascii .= "\x{$hex}";
				break;

			case 'xml': // XML character entitiy ("numeric character reference")
				$ascii .= "&#x{$hex};";
				break;

			case 'url': // URL escaped
				$ascii .= "%{$hex}";
				break;

			default: // Worst case fall-back C notation, plain text representation
				$ascii .= '0x' . strtoupper($hex);
				// TODO - SMK notes an alert is in order here because
				// the caller blew the encoding param - not a user issue
				break;
		}
	}

	return($ascii);
}

$parts = explode('-', $_REQUEST['t']);
$target = scrub_ascii($parts[0], ord('A'), ord('z'));
?>
<!DOCTYPE html>
<html>
	<meta charset="utf-8"/>
	<head>
		<style type="text/css">

			<?/*
			SMK notes that white space is needed at
			least above the first editable region in order
			for CKE inline toolbar to have some place to
			position itself other than over the top of the
			text area.
			*/?>
			html, body {
				background-color: white;
				padding: 15px;
				margin: 0px;
			}

			<?/*
			SMK notes that these guidelines are used to visually
			indicate that the user hase made their email tempalte
			exceed the standard 600 pixel width.
			*/?>
			/*
			div.guidebox {
				border-right: 1px solid black;
			}

			div.guidewidth {
				height: 15px;
				background-color: yellow;
				border-bottom: 1px solid black;
			}

			div.guidewidthok {
				height: 15px;
				width: 600px;
				background-color: green;
			}
			*/

			div.editableBlock {
				border: none;
				padding: 1px;
			}

			div.editableBlock:hover {
				background-color: #FFFF99;
				cursor: pointer;
				border: 1px dashed #999999;
				padding: 0px;
			}

			div.cke_focus:hover {
				border: none;
				padding: 1px;
			}

			/**
			 *	CKEditor editables are automatically set with the "cke_editable" class
			 *	plus cke_editable_(inline|themed) depending on the editor type.
			 */

			/* Style a bit the inline editables. */
			.cke_editable.cke_editable_inline {
				cursor: pointer;
			}

			/* Once an editable element gets focused, the "cke_focus" class is
			   added to it, so we can style it differently. */
			.cke_editable.cke_editable_inline.cke_focus {
				box-shadow: inset 0px 0px 20px 3px #ddd, inset 0 0 1px #000;
				outline: none;
				background: #eee;
				cursor: text;
			}

			/* Avoid pre-formatted overflows inline editable. */
			.cke_editable_inline pre {
				white-space: pre-wrap;
				word-wrap: break-word;
			}

		</style>
		<script src="prototype.js"></script>
		<script src="console.js"></script>
		<script src="ckeditor/ckeditor.js"></script>
		<script language="JavaScript">

			// This property tells CKEditor to not activate every element with contenteditable=true element.
			CKEDITOR.disableAutoInline = true;

			var captureDelay = 0;

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

					//console.log('configured!');
					doValidation();
				});

				editor.on('blur', captureChanges);
				editor.on('key', function(event) {
					<?/*
					The following keys we want to captureChanges() on
					because they just represent changes tot he content;
					any other keys are for cursor movement, etc. which
					don't affect the content.
					ref: http://www.webonweboff.com/tips/js/event_key_codes.aspx
					*/?>
					var keyCode = event.data.keyCode & 255;
					if ((keyCode >= 48) || (keyCode == 8) || (keyCode == 9) || (keyCode == 13) || (keyCode == 32) || (keyCode == 46)) {
						clearTimeout(captureDelay);
						captureDelay = window.setTimeout(captureChanges, 500);
					}
					//else {
					//	console.log('event data:');
					//	console.log(event.data);
					//	console.log('keycode to string = [' + String.fromCharCode(event.data.keyCode) + ']');
					//}
				});
				editor.on('saveSnapshot', captureChanges);
				editor.on('afterCommandExec', captureChanges);
				editor.on('insertHtml', captureChanges);
				editor.on('insertElement', captureChanges);

			});

                        function makeEditable(element, index) {

				// Add an ID for unique association
				element.setAttribute('id', 'editableBlock' + index);
				element.setAttribute('contenteditable', 'true');
				element.setAttribute('tabindex', index);

				CKEDITOR.inline(element);
                        }

			function makeNonEditable(element) {

				// Reverse out the element attributes that we added
				// and also that CKE adds and leaves behind
				element.removeAttribute('id');
				element.removeAttribute('contenteditable');
				element.removeAttribute('tabindex');
				element.removeAttribute('style');
				element.removeAttribute('spellcheck');
				element.setAttribute('class', 'editableBlock');
			}


			// The ID attribute of the textarea that has the text we want to edit
			var editableTarget = '<? echo $target; ?>';

			function wysiwygInit() {

				// Get the textarea DOM object from the parent window (frame)
				var textarea = 0;
				if (! (textarea = getTextarea())) {
					return(false);
				}

				// Get the wysiwygpage div DOM object from this page (below)
				var wysiwygpage = 0;
				if (! (wysiwygpage = getPage())) {
					return(false);
				}

				// Grab the contents of the textarea form element
				//var content = textarea.html(); // jquery.js
				var content = textarea.innerHTML; // prototype.js


				// Convert content entities back to the real characters
				content = content.replace(/&lt;/g, '<');
				content = content.replace(/<</g, '&lt;&lt;');
				content = content.replace(/&gt;/g, '>');
				content = content.replace(/>>/g, '&gt;&gt;');
				content = content.replace(/&amp;/g, '&');

				// Inject the content from the textarea into the wysiwyg page div
				//wysiwygpage.empty().append(content); // jquery.js
				wysiwygpage.insert(content); // prototype.js

				// Apply CKE inline editors for each wysiwygpage > div.contenteditable=true
				//$('.editableBlock', wysiwygpage).each(function(index) { makeEditable(this, index); }); // jquery.js
				var editableBlocks = $(wysiwygpage).select('.editableBlock'); // prototype.js (+3...)
				for (var i = 0; i < editableBlocks.length; i++) {
					makeEditable(editableBlocks[i], i);
				}

				// Fire the callback indicating initialization is done
				window.frames.top.RCIEditor.callbackEditorLoaded('wysiwygpage');
			}

			var editorDirty = false;
			function captureChanges() {

				// (1) Get the wysiwygpage div DOM object from this page (below)
				var wysiwygpage = 0;
				if (! (wysiwygpage = getPage())) {
					return(false);
				}

				// (2) Get the textarea DOM object from the parent window (frame)
				var textarea = 0;
				if (! (textarea = getTextarea())) {
					return(false);
				}

				// (3) Get the wysiwygpresave div DOM object from this page (below)
				var wysiwygpresave = 0;
				if (! (wysiwygpresave = getPresaveContainer())) {
					return(false);
				}

                                // (4) Stick the wysiwyg page content into the presave container
                                //wysiwygpresave.empty().append(wysiwygpage.html()); // jquery.js
                                wysiwygpresave.update(wysiwygpage.innerHTML); // prototype.js

                                // (5) Disable editing capabilities on all the presave editableBlocks
				/* // jquery.js
                                $('.editableBlock', wysiwygpresave).each(function(index) {
                                        this.className = 'editableBlock';
                                        this.removeAttribute('contenteditable');
                                        this.removeAttribute('spellcheck');
                                        this.removeAttribute('style');
                                        this.removeAttribute('id');
                                        this.removeAttribute('tabindex');
                                });
				*/
				var editableBlocks = $(wysiwygpresave).select('.editableBlock'); // prototype.js (+3...)
				for (var i = 0; i < editableBlocks.length; i++) {
					makeNonEditable(editableBlocks[i]);
				}

                                // (6) Now grab the contents of the wysiwyg presave container
                                //content = window.top.RCIEditor.cleanContent(wysiwygpresave.html()); // jquery.js
				var content = window.top.RCIEditor.cleanContent(wysiwygpresave.innerHTML); // prototype.js

				// (7) Convert real characters to content entities
				content = content.replace(/&/g, '&amp;');
				content = content.replace(/</g, '&lt;');
				content = content.replace(/>/g, '&gt;');

				// (8) Inject the content from the wysiwyg page div into the textarea form element
				//textarea.empty().append(content); // jquery.js
				textarea.update(content); // prototype.js

				// (9) Validate the changes as captured
				//console.log('captured!');
				return(doValidation(textarea));
			}

			function doValidation(textarea) {
				if (typeof textarea === 'undefined') {
					textarea = getTextarea();
				}

				if (! textarea) return(false);

				// Interface with the legacy form validation
				//window.top.form_do_validation(textarea.closest('form'), textarea); // jquery.js
				window.top.form_do_validation(textarea.up("form"), textarea); // prototype.js

				return(true);
			}

			function getTextarea() {
				var textarea = null;
				//if (! (textarea = $('#' + editableTarget, window.top.document))) { // jquery.js
				if (! (textarea = $(window.top.document.getElementById(editableTarget)))) { // prototype.js
					//console.log('failed to find target [' + editableTarget + ']');
					return(null);
				}
				return(textarea);
			}

			function getPage() {
				var wysiwygpage = null;
				//if (! (wysiwygpage = $('#wysiwygpage'))) { // jquery.js
				if (! (wysiwygpage = $('wysiwygpage'))) { // prototype.js
					//console.log('failed to find wysiwygpage');
					return(null);
				}
				return(wysiwygpage);
			}

			function getPresaveContainer() {
				var wysiwygpresave = null;
				//if (! (wysiwygpage = $('#wysiwygpresave'))) { // jquery.js
				if (! (wysiwygpresave = $('wysiwygpresave'))) { // prototype.js
					console.log('failed to find wysiwygpresave');
					return(null);
				}
				return(wysiwygpresave);
			}
		</script>
	</head>
	<body onload="wysiwygInit();">
		<div class="guidebox">
			<div class="guidewidth">
				<div class="guidewidthok">&nbsp;</div>
			</div>
			<div id="wysiwygpage"></div>
			<div id="wysiwygpresave" style="display: none;"></div>
			<!--div id="wysiwygpresave" style="background-color:#FFFFCC; color: blue;">oye</div-->
			<div id="wysiwygcssoverrides">
				<style>
					.cke_hc .cke_button_label { display: none; }
					.cke_hc .cke_button_icon { display: block; }
				</style>
			</div>
		</div>
	</body>
</html>

