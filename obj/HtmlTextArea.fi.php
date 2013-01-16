<?

/**
 * HtmlTextArea Form Item object
 *
 * @todo SMK notes 2013-01-02 that the code below needs prototype to jquery port
 */
class HtmlTextArea extends FormItem {
	function render ($value) {
		global $USER;

		// SMK added 2013-01-02 to be able to switch modalities for any FI of this type
		$editor_mode = isset($this->args['editor_mode']) ? $this->args['editor_mode'] : 'normal';

		$n = $this->form->name."_".$this->name;
		if (! $value) {
			$value = '';
		}

		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";

		switch ($mode) {
			case 'wysiwyg':
				// This editor mode is full WYSIWYG inline, requires
				// click to edit divs with class="editableBlock"
				$editorInitScript = 'script/wysiwygeditor.js';
				$editorApplyFn = "applyWysiwygEditor(e, e.id + '-htmleditor')";
				break;

			case 'full':
				// This editor mode is like normal but with
				// extra tools for editing "stationery"
				break;

			case 'normal':
			default:
				// This is the original basic, full
				// editor with no extra/special tools
				$editorInitScript = 'script/htmleditor.js';
				$editorApplyFn = "applyHtmlEditor(e, true, elemName + '-htmleditor',{$USER->getSetting('hideemailtools', 'false')})";
				break;
		}

		$v = escapehtml($value);

		$str = <<<END
			<textarea id="{$n}" name="{$n}" {$rows}/>{$v}</textarea>
			<div id ="{$n}htmleditor"></div>
			<script type="text/javascript" src="script/ckeditor/ckeditor.js"></script>
			<script type="text/javascript" src="{$editorInitsScript}"></script>
			<script type="text/javascript">
				document.observe("dom:loaded",
					function() {
						var elemName = "{$n}";
						var e = \$(elemName);

						// add the ckeditor to the textarea
						{$editorApplyFn};

						// set up a keytimer to save content and validate
						var htmlTextArea_keytimer = null;
						registerHtmlEditorKeyListener(function (event) {
							window.clearTimeout(htmlTextArea_keytimer);
							var htmleditor = getHtmlEditorObject();
							htmlTextArea_keytimer = window.setTimeout(function() {
								saveHtmlEditorContent(htmleditor);
								form_do_validation(htmleditor.currenttextarea.up("form"), htmleditor.currenttextarea);
							}, 500);
						});
					});
			</script>
END;
		return $str;
	}
}
?>
