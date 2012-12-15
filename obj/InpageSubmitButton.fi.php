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
		
		if (isset($this->args['icon']) && $this->args['icon'])
			$str .= icon_button($this->args['name'],$this->args['icon'],$onclick);
		else
			$str .= button($this->args['name'],$onclick);
		
		return $str;
	}
}
?>