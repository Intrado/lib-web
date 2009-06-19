<?

abstract class FormItem {
	var $form;
	var $name;
	var $args;
	
	function FormItem ($form, $name,$args) {
		$this->form = $form;
		$this->name = $name;
		$this->args = $args;
	}
	
	abstract function render ($value) ;
	
	function jsGetValue () {
		return "form_default_get_value"; //must evaluate to an existing function. anonymous functions dont seem to work, and inline function definitions not suggested.
		// eg "function foo () {...}; foo" seemd to work (define function foo, then eval foo), but defines or overwrites foo in the default scope
	}
}

// HiddenField | TextField | PasswordField | CheckBox | RadioButton | TextArea | MultiSelect | MultiCheckBox

class HiddenField extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		return '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
	}
}

class TextField extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		return '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}

class PasswordField extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		return '<input id="'.$n.'" name="'.$n.'" type="password" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}

class CheckBox extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		return '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value ? 'checked' : '').' />';
	}
}

class RadioButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<div id='.$n.' class="radiobox">';
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radioname) {
			$id = $n.'-'.$counter;
			$str .= '<input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /><label for="'.$id.'">'.escapehtml($radioname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>';
		return $str;		
	}
}

class TextArea extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$cols = isset($this->args['cols']) ? 'rows="'.$this->args['cols'].'"' : "";
		return '<textarea id="'.$n.'" name="'.$n.'" '.$rows.' '.$cols.'/>'.escapehtml($value).'</textarea>';
	}
}

class SelectMenu extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = '<select id='.$n.' name="'.$n.'" '.$size .' >';
		foreach ($this->args['values'] as $selectvalue => $selectname) {
			$checked = $value == $selectvalue;
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>
				';
		}
		$str .= '</select>';
		return $str;
	}

}

class MultiSelect extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = '<select multiple id='.$n.' name="'.$n.'[]" '.$size .' >';
		foreach ($this->args['values'] as $selectvalue => $selectname) {
			$checked = $value == $selectvalue || (is_array($value) && in_array($selectvalue, $value));
			$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml($selectname).'</option>
				';
		}
		$str .= '</select>';
		return $str;
	}
}

class MultiCheckBox extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$style = isset($this->args['height']) ? ('style="height: ' . $this->args['height'] . '; overflow: auto;"') : '';
		
		$str = '<div id='.$n.' class="radiobox" '.$style.'>';
		
		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').' /><label for="'.$id.'">'.escapehtml($checkname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>';
		
		return $str;		
	}
}

// allows ad-hoc html
class FormHtml extends FormItem {
	function render ($value) {
		return $this->args['html'];
	}
}

class HtmlRadioButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<div id='.$n.' class="radiobox"><table>';
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radiohtml) {
			$id = $n.'-'.$counter;
			$str .= '<tr><td><input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /></td><td><label for="'.$id.'"><button type="button" class="regbutton" style="border: 2px outset; background-color: white; color: black; margin-left: 0px;" onclick="$(\''.$id.'\').click();">'.($radiohtml).'</button></label></td></tr>
				';
			$counter++;
		}
		$str .= '</table></div>';
		return $str;		
	}
}

?>
