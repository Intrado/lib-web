<?
/* Textarea (and optional subject) with an enabled check box
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
 *  hassubject - require a subject to be entered
 *  subjectmax - max subject length
 *  subjectsize - size of the input to be displayed
 *  defaultsubject - used to set the default subject text
 *     useful if your inital value is disabled, but you want to populate on enable
 *  spellcheck - turn on spell checker option
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
		$subjectmax = isset($this->args['subjectmax']) ? 'max="'.$this->args['subjectmax'].'"' : "";
		$subjectsize = isset($this->args['subjectsize']) ? 'size="'.$this->args['subjectsize'].'"' : "";
		$subjectshow = isset($this->args['hassubject']) && $this->args['hassubject'];
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$cols = isset($this->args['cols']) ? 'cols="'.$this->args['cols'].'"' : "";
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];
		
		$enabletext = (isset($this->args['enabletext'])?$this->args['enabletext']:_L("Enabled"));
		
		// value is json encoded with the format {subject:"<subject text>",message:"<message text>"}
		if ($value)
			$jsvalue = json_decode($value, true);
		else
			$jsvalue = array("subject"=>"","message"=>"");
		
		$defaultsubject = (isset($this->args['defaultsubject'])?$this->args['defaultsubject']:$jsvalue["subject"]);
		$defaultvalue = (isset($this->args['defaultvalue'])?$this->args['defaultvalue']:$jsvalue["message"]);
		
		$str = '
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
			<input id="'.$n.'-enable" name="'.$n.'-enable" type="checkbox" '.($jsvalue["message"]?"checked":"").' /><label for="'.$n.'-enable">'. $enabletext .'</label>
			<input id="'.$n.'-oldsubject" type="hidden" value="'.escapehtml($defaultsubject).'" />
			<input id="'.$n.'-oldtext" type="hidden" value="'.escapehtml($defaultvalue).'" />
			<div id="'.$n.'-textareadiv" style="display:'.($jsvalue["message"]?"block":"none").'">
				<input id="'.$n.'-subject" style="display:'.($subjectshow?"block":"none").'" type="input" '.$subjectmax.' '.$subjectsize.' value="'.escapehtml($jsvalue["subject"]).'" /><div style="clear:both;"></div>
				<textarea id="'.$n.'-message" '.$rows.' '.$cols.'>'.escapehtml($jsvalue["message"]).'</textarea>';
		
		if(isset($this->args['counter']))
			$str .= '<div id="' . $n . 'charsleft">'._L('Characters remaining'). ':&nbsp;'. ( $this->args['counter'] - mb_strlen(($jsvalue["message"]?$jsvalue["message"]:$defaultvalue)) ). '</div>';

		if ($spellcheck) {
			$str .= '<div>' . action_link(_L("Spell Check"), "spellcheck", null, '(new spellChecker($(\''.$n.'\')) ).openChecker();') . '</div>';
		}
		
		$str .= '</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		// observe changes to the enable checkbox and text fields
		$str = '
			$("'.$n.'-enable").observe("change", textAreaEnable.curry("'.$n.'"));
		
			$("'.$n.'-subject").observe("change", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-subject").observe("blur", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-subject").observe("keyup", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-subject").observe("focus", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-subject").observe("click", textAreaSave.curry("'.$n.'"));
				
			$("'.$n.'-message").observe("change", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-message").observe("blur", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-message").observe("keyup", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-message").observe("focus", textAreaSave.curry("'.$n.'"));
			$("'.$n.'-message").observe("click", textAreaSave.curry("'.$n.'"));';
		
		// if the counter is enabled, observe the keyup event on the text area
		if(isset($this->args['counter']))
			$str .= '$("'.$n.'-message").observe("keyup", form_count_field_characters.curry(' . $this->args['counter'] . ',"'  . $n . 'charsleft")); ';
		
		return $str;
	}
	
	function renderJavascriptLibraries() {	
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];

		$str = '<script type="text/javascript">
			function textAreaEnable(formitem, event) {
				formitem = $(formitem);
				var textareadiv = $(formitem.id + "-textareadiv");
				var hiddensubject = $(formitem.id + "-oldsubject");
				var hiddentext = $(formitem.id + "-oldtext");
				var subject = $(formitem.id + "-subject");
				var message = $(formitem.id + "-message");
				var checkbox = event.element();
				
				if (checkbox.checked) {
					textareadiv.show();
					// copy the data from the hidden field into the text area
					subject.value = hiddensubject.value;
					message.value = hiddentext.value;
					textAreaSave(formitem,event);
				} else {
					// save the current text in the hidden field
					hiddensubject.value = subject.value;
					hiddentext.value = message.value;
					formitem.value = "";
					textareadiv.hide();
				}
			}
			function textAreaSave(formitem, event) {
				formitem = $(formitem);
				var subject = $(formitem.id + "-subject");
				var message = $(formitem.id + "-message");
				formitem.value = Object.toJSON({"subject":subject.value,"message":message.value});
			}
			</script>';
		
		if ($spellcheck) {
			$str .= '<script src="script/speller/spellChecker.js"></script>';
		}
		
		return $str;
	}
}
?>