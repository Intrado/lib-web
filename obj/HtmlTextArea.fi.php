<?

class HtmlTextArea extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (!$value)
			$value = '';
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$str = '<textarea id="'.$n.'" name="'.$n.'" '.$rows.'/>'.escapehtml($value).'</textarea>
			<div id ="'.$n.'htmleditor"></div>
			<script type="text/javascript" src="script/ckeditor/ckeditor_basic.js"></script>
			<script type="text/javascript" src="script/htmleditor.js"></script>
			<script type="text/javascript">
				document.observe("dom:loaded",
					function() {
						// add the ckeditor to the textarea
						applyHtmlEditor($("'.$n.'"),true,"'.$n.'htmleditor");

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
		';
		return $str;
	}
}
?>