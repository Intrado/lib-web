<?

abstract class FormItem {
	var $name;
	var $label;
	var $helpstep;
	var $args;
	var $style;
	
	abstract function render ($form,$value) ;
}

//text|password|checkbox|radio|submit|reset|file|hidden|image|button
//textarea

class TextField extends FormItem {
	
	function TextField ($name,$label,$helpstep,$args,$style = "") {
		$this->name = $name;
		$this->label = $label;
		$this->helpstep = $helpstep;
		$this->args = $args;
		$this->style = $style;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		return '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}


class CheckBox extends FormItem {
	
	function CheckBox ($name,$label,$helpstep,$args,$style = "") {
		$this->name = $name;
		$this->label = $label;
		$this->helpstep = $helpstep;
		$this->args = $args;
		$this->style = $style;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		
		return '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value == "true" ? 'checked' : '').' />';
	}
}

class RadioButton extends FormItem {
	
	function RadioButton ($name,$label,$helpstep,$args,$style = "") {
		$this->name = $name;
		$this->label = $label;
		$this->helpstep = $helpstep;
		$this->args = $args;
		$this->style = $style;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		
		$str = '<div id='.$n.' class="radiobox">';
		
		$counter = 1;
		foreach ($this->args as $radiovalue => $radioname) {
			$id = $n.'-'.$counter;
			$str .= '<input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /><label for='.$id.'>'.escapehtml($radioname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>';
		
		return $str;		
	}
}
?>