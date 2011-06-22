<?
/* Textarea with an enabled check box
 * The idea is, you can uncheck the enable checkbox and it will
 * hide the text area.
 * 
 * Possible args
 *  rows - number of rows for the text area to display
 *  cols - number of columns for the text area to display
 *  enabletext - label to use on the enable/disable checkbox
 *  counter - creates a decrementing counter indicating the maximum text length
 *  defaultvalue - can be used to set a default value to populate the text area
 *     useful if your inital value is disabled, but you want to populate on enable
 *  
 * Supporting the following feature set
 * 	Inputing text with a visual clue (checkbox) that it's optional
 * 
 * Requires the following objects:
 * 	
 * Nickolas Heckman
 */
class TextAreaWithEnableCheckbox extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$cols = isset($this->args['cols']) ? 'cols="'.$this->args['cols'].'"' : "";
		
		$enabletext = (isset($this->args['enabletext'])?$this->args['enabletext']:_L("Enabled"));
		$defaultvalue = (isset($this->args['defaultvalue'])?$this->args['defaultvalue']:_L("Enabled"));
		$str = '
			<input id="'.$n.'-enable" name="'.$n.'-enable" type="checkbox" '.($value?"checked":"").' /><label for="'.$n.'-enable">'. $enabletext .'</label>
			<input id="'.$n.'-oldtext" type="hidden" value="'.escapehtml($defaultvalue).'" />
			<div id="'.$n.'-textareadiv" style="display:'.($value?"block":"none").'">
				<textarea id="'.$n.'" name="'.$n.'" '.$rows.' '.$cols.'>'.escapehtml($value).'</textarea>';
		if(isset($this->args['counter']))
			$str .= '<div id="' . $n . 'charsleft">'._L('Characters remaining'). ':&nbsp;'. ( $this->args['counter'] - mb_strlen($value)). '</div>';
		$str .= '</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		// observe changes to the enable checkbox
		$str = '$("'.$n.'-enable").observe("change", textAreaEnable.curry("'.$n.'")); ';
		// if the counter is enabled, observe the keyup event on the text area
		if(isset($this->args['counter']))
			$str .= '$("'.$n.'").observe("keyup", form_count_field_characters.curry(' . $this->args['counter'] . ',"'  . $n . 'charsleft")); ';
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		
		$str = '<script type="text/javascript">
			function textAreaEnable(formitem, event) {
				var textareadiv = $(formitem + "-textareadiv");
				var hiddenfield = $(formitem + "-oldtext");
				var checkbox = event.element();
				formitem = $(formitem);
				
				if (checkbox.checked) {
					textareadiv.show();
					// copy the data from the hidden field into the text area
					formitem.value = hiddenfield.value;
				} else {
					// save the current text in the hidden field
					hiddenfield.value = formitem.value;
					formitem.value = "";
					textareadiv.hide();
				}
			}
			</script>';
		return $str;
	}
}
?>