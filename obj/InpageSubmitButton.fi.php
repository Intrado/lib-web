<?
/* Submit button which can be included in the middle of a form.
* To be used anywhere where a submit is required to perform some action
* other than saving data.
* 
* Possible args
*  name - button name
*  icon - icon to be displayed, optional
*  submitvalue - the submit button value, optional (defaults to "inpagesubmit")
*  confirm - require a confirm dialog, optional (value should be the text for the dialog)
*
* Requires the following objects:
*
* Nickolas Heckman
*/
class InpageSubmitButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value=""/>';
		$submitvalue = (isset($this->args['submitvalue'])?$this->args['submitvalue']:'inpagesubmit');
		
		// create a submit button
		$theme = getBrandTheme();
		$onclick = 'return form_submit(event,\''.escapehtml($submitvalue).'\');';
		if (isset($this->args['confirm']) && $this->args['confirm'])
			$onclick = 'if (confirm(\''.escapehtml($this->args['confirm']).'\')) { '.$onclick.' } else { return false; }';
			
		$str .= '<button class="btn" type="submit" name="submit" value="'.escapehtml($submitvalue).'" onmouseover="btn_rollover(this);" onmouseout="btn_rollout(this);" onclick="'.$onclick.'"><div class="btn_wrap cf"><span class="btn_left"></span><span class="btn_middle">';
		
		if (isset($this->args['icon']) && $this->args['icon'])
			$str .= '<img src="img/icons/'.$this->args['icon'].'.gif" alt="">';
		else
			$str .= '<img src="img/pixel.gif" alt="" height="16" width="1">';
		
		$str .= escapehtml($this->args['name']) . '</span><span class="btn_right"></span></div></button>';
		
		return $str;
	}
}
?>