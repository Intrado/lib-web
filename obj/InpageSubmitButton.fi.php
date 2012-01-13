<?
class InpageSubmitButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value=""/>';
		$submitvalue = (isset($this->args['submitvalue'])?$this->args['submitvalue']:'inpagesubmit');
		return $str.submit_button($this->args['name'], $submitvalue, $this->args['icon']);
	}
}
?>