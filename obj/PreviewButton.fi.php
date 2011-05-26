<? 

class PreviewButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="" />'; //always blank out value
		$str .= icon_button( _L('Preview'),"fugue/control", "$('$n').value='true'; form_submit(event,'samestep');");

		return $str;
	}
}

?>