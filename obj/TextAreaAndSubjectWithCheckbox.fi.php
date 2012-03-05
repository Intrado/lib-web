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
 *  defaultmessage - can be used to set a default message to populate the text area
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
class TextAreaAndSubjectWithCheckbox extends FormItem {
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
		
		$defaultsubject = isset($this->args['defaultsubject'])?$this->args['defaultsubject']:"";
		$defaultmessage = isset($this->args['defaultmessage'])?$this->args['defaultmessage']:"";
		$subject = (isset($jsvalue['subject']) && $jsvalue['subject'])?$jsvalue['subject']:$defaultsubject;
		$message = (isset($jsvalue['message']) && $jsvalue['message'])?$jsvalue['message']:$defaultmessage;
		
		$str = '
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
			<input id="'.$n.'-enable" name="'.$n.'-enable" type="checkbox" '.($jsvalue["message"]?"checked":"").' /><label for="'.$n.'-enable">'. $enabletext .'</label>
			<div id="'.$n.'-textareadiv" style="display:'.($jsvalue["message"]?"block":"none").'">
				<input id="'.$n.'-subject" style="display:'.($subjectshow?"block":"none").'" type="input" '.$subjectmax.' '.$subjectsize.' value="'.escapehtml($subject).'" /><div style="clear:both;"></div>
				<textarea id="'.$n.'-message" '.$rows.' '.$cols.'>'.escapehtml($message).'</textarea>';
		
		if(isset($this->args['counter']))
			$str .= '<div id="' . $n . 'charsleft">'._L('Characters remaining'). ':&nbsp;'. ( $this->args['counter'] - mb_strlen($message) ). '</div>';

		if ($spellcheck) {
			$str .= '<div>' . action_link(_L("Spell Check"), "spellcheck", null, '(new spellChecker($(\''.$n.'-message\')) ).openChecker();') . '</div>';
		}
		
		$str .= '</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		// observe changes to the enable checkbox and text fields
		$str = '
			$("'.$n.'-enable").observe("change", textAreaAndSubjectEnable.curry("'.$n.'"));
		
			$("'.$n.'-subject").observe("change", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-subject").observe("blur", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-subject").observe("keyup", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-subject").observe("focus", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-subject").observe("click", setSaveTimer.curry("'.$n.'"));
				
			$("'.$n.'-message").observe("change", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-message").observe("blur", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-message").observe("keyup", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-message").observe("focus", setSaveTimer.curry("'.$n.'"));
			$("'.$n.'-message").observe("click", setSaveTimer.curry("'.$n.'"));';
		
		// if the counter is enabled, observe the keyup event on the text area
		if(isset($this->args['counter']))
			$str .= '$("'.$n.'-message").observe("keyup", form_count_field_characters.curry(' . $this->args['counter'] . ',"'  . $n . 'charsleft")); ';
		
		return $str;
	}
	
	function renderJavascriptLibraries() {	
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];

		$str = '<script type="text/javascript">
			function textAreaAndSubjectEnable(formitem, event) {
				formitem = $(formitem);
				var textareadiv = $(formitem.id + "-textareadiv");
				var checkbox = event.element();
				
				if (checkbox.checked)
					textareadiv.show();
				else
					textareadiv.hide();
				
				textAreaSave(formitem);
			}
			function setSaveTimer(formitem, event) {
				formitem = $(formitem);
				var form = event.findElement("form");
				var formvars = document.formvars[form.name]
				if (formvars[formitem.id + "_savetimer_"]) {
					window.clearTimeout(formvars[formitem.id + "_savetimer_"]);
				}
				formvars[formitem.id + "_savetimer_"] = window.setTimeout(function () {
						textAreaSave(formitem);
					},
					event.type == "keyup" ? 500 : 100
				);
			}
			
			function textAreaSave(formitem) {
				formitem = $(formitem);
				var checkbox = $(formitem.id + "-enable");
				var subject = $(formitem.id + "-subject");
				var message = $(formitem.id + "-message");
				
				if (checkbox.checked)
					formitem.value = Object.toJSON({"subject":subject.value,"message":message.value});
				else
					formitem.value = "";
				
				form_do_validation(formitem.form, formitem);
			}
			</script>';
		
		if ($spellcheck) {
			$str .= '<script src="script/speller/spellChecker.js"></script>';
		}
		
		return $str;
	}
}
?>