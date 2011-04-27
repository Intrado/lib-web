<?
class InpageSubmitButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value=""/>';
		return $str.submit_button($this->args['name'], 'inpagesubmit', $this->args['icon']);
	}
}
?>