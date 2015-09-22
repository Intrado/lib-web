<?

/**
 * HtmlTextArea Form Item object
 */
class HtmlTextArea extends FormItem {
	function render ($value) {
		global $USER;

		$n = $this->form->name."_".$this->name;
		if (! $value) {
			$value = '';
		}

		$rows = isset($this->args['rows']) ? 'rows="' . $this->args['rows'] . '"' : "";

		$v = escapehtml($value);

		$str = '<textarea id="' . $n . '" name="' . $n . '" ' . $rows . ' style="display: none;">' . $v . '</textarea>
			<div id ="' . $n . '-htmleditor"></div>';

		// SMK added 2013-03-07 to force this button's label to show in the toolbar
		$str .= '
		<style type="text/css">
			.cke_button__pastefromphone_label {
				display: inline-block;
			}
		</style>';

		return $str;
	}


	function renderJavascriptLibraries() {
		global $USER;

		$n = $this->form->name."_".$this->name;

		$subtype = (isset($this->args['subtype'])) ? $this->args['subtype'] : 'html';

		// Make editor able to switch modalities for any FI of this type
		$editor_mode = isset($this->args['editor_mode']) ? $this->args['editor_mode'] : 'plain';

		// A set of settings overrides to pass into the constructor,
		// necessary because the constructor actually initializes and
		// shows CKEditor, so everything needed must be passed in.
		$overridesettings = Array(
			'hidetoolbar' => ($USER->getSetting('hideemailtools', false) ? true : false),
			'fieldinsert_list' => FieldMap::getAuthorizeFieldInsertNames()
		);

		// Append in a set of any other override settings that we support
		if (isset($this->args['overridesettings'])) {
			$overridesettings = $overridesettings + $this->args['overridesettings'];
		}

		// ref: http://stackoverflow.com/questions/7034485/contenteditable-trigger-event-on-image-resize-when-using-handles
		$str = '<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
			<script type="text/javascript" src="script/rcieditor.js"></script>
			<script type="text/javascript">

				// apply the ckeditor to the textarea
				document.observe("dom:loaded", function() {
					var overrideSettings = ' . json_encode($overridesettings) . ';

					rcieditor = new RCIEditor("' . $editor_mode . '", "' . $n . '", overrideSettings);
					rcieditor.setValidatorFunction(function () {
						var form = $("' . $this->form->name . '");
						var field = $("'.$n.'");
						form_do_validation(form, field);
					});

					NodeRegistry
						.addNode("emailEditor", NodeRegistry.makeNode(rcieditor))
						.setEventHandler( "beforePreview", "saveHtmlEditorContent");
				});

			</script>';

		if ($subtype == "plain" && isset($this->args['spellcheck']) && $this->args['spellcheck']) {
			$str .= '<script src="script/speller/spellChecker.js"></script>';
		}

		return $str;
	}
}
?>
