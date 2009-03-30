<?

abstract class FormItem {
	var $name;
	var $args;
	
	abstract function render ($form,$value) ;
}

// HiddenField | TextField | PasswordField | CheckBox | RadioButton | TextArea | MultiSelect | MultiCheckbox

class HiddenField extends FormItem {
	function HiddenField ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		return '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
	}
}

class TextField extends FormItem {
	function TextField ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		return '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}

class PasswordField extends FormItem {
	function PasswordField ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		return '<input id="'.$n.'" name="'.$n.'" type="password" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}

class CheckBox extends FormItem {
	function CheckBox ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		return '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value == "true" ? 'checked' : '').' />';
	}
}

class RadioButton extends FormItem {
	function RadioButton ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$str = '<div id='.$n.' class="radiobox">';
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radioname) {
			$id = $n.'-'.$counter;
			$str .= '<input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /><label for='.$id.'>'.escapehtml($radioname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>';
		return $str;		
	}
}

class TextArea extends FormItem {
	function TextArea ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$cols = isset($this->args['cols']) ? 'rows="'.$this->args['cols'].'"' : "";
		return '<textarea id="'.$n.'" name="'.$n.'" '.$rows.' '.$cols.'/>'.escapehtml($value).'</textarea>';
	}
}

//TODO
class SelectMenu extends FormItem {
	function SelectMenu ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		return '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}

class MultiSelect extends FormItem {
	function MultiSelect ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = '<select multiple id='.$n.' name="'.$n.'[]" '.$size .' >';
		foreach ($this->args['values'] as $selectvalue => $selectname) {
			$checked = $value == $selectvalue || (is_array($value) && in_array($selectvalue, $value));
			$str .= '<option value="'.escapehtml($selectvalue).'" '.$checked.' >'.escapehtml($selectname).'</option>
				';
		}
		$str .= '</select>';
		return $str;
	}
}

class MultiCheckbox extends FormItem {
	
	function MultiCheckbox ($name,$args) {
		$this->name = $name;
		$this->args = $args;
	}
	function render ($form,$value) {
		$n = $form->name."_".$this->name;
		$style = isset($this->args['height']) ? ('style="height: ' . $this->args['height'] . '; overflow: auto;"') : '';
		
		$str = '<div id='.$n.' class="radiobox" '.$style.'>';
		
		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').' /><label for='.$id.'>'.escapehtml($checkname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>';
		
		return $str;		
	}
}


?>