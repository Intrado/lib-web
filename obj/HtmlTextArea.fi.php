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

		$str = '<textarea id="' . $n . '" name="' . $n . '" ' . $rows . ' style="display: none;"/>' . $v . '</textarea>
			<div id ="' . $n . '-htmleditor"></div>';
		// SMK notes that there was a stray "</script>" tag here... appeared to be connected to nothing.
		return $str;
	}


	function renderJavascriptLibraries() {
		global $USER;

		$n = $this->form->name."_".$this->name;

		$subtype = (isset($this->args['subtype'])) ? $this->args['subtype'] : 'html';

		// Make editor able to switch modalities for any FI of this type
		$editor_mode = isset($this->args['editor_mode']) ? $this->args['editor_mode'] : 'plain';

		$str = '<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
			<script type="text/javascript" src="script/USER.js.php"></script>
			<script type="text/javascript" src="script/rcieditor.js"></script>
			<script type="text/javascript">

				// apply the ckeditor to the textarea
				document.observe("dom:loaded", function() {
					rcieditor = new RCIEditor("' . $editor_mode . '", "' . $n . '", ' . $USER->getSetting('hideemailtools', 'false') . ');
					rcieditor.setValidatorFunction(function () {
						var form = $("' . $this->form->name . '");
						var field = $("'.$n.'");
						form_do_validation(form, field);
					});
				});
			</script>';

		if ($subtype == "plain" && isset($this->args['spellcheck']) && $this->args['spellcheck']) {
			$str .= '<script src="script/speller/spellChecker.js"></script>';
		}

		return $str;
	}
}
?>
