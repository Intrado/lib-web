<?
// color selector form item
class ColorPicker extends FormItem {

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		
		return '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$size.' /><img src="img/sel.gif" type="image" onclick="TCP.popup($(\''.$n.'\')); "/>';
	}
	
	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/picker.js"></script>';
	}
}

?>