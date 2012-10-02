<?
class LogoRadioButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$str = '<div id='.$n.' class="radiobox"><table>';
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radiohtml) {
			$id = $n.'-'.$counter;
			$str .= '<tr><td><input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /></td><td><label for="'.$id.'"><div style="width: 100%; border: 2px outset; background-color: white; color: black; margin-left: 0px;">'.($radiohtml).'</div></label></td></tr>
				';
			$counter++;
		}
		$str .= '</table></div>';
		return $str;
	}
}
?>