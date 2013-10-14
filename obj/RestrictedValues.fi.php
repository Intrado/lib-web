<?

class RestrictedValues extends FormItem {
	var $clearonsubmit = true;
	var $clearvalue = array();
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (count($this->args['values']) == 0) {
			return '<img src="img/icons/information.png" alt="Information" /> ' . _L("No Restrictable Fields");
		}

		$extraStyle = isset($this->args['height']) ? ('height: ' . $this->args['height'] . '; overflow: auto;') : '';

		$type = (isset($this->args['type'])) ? $this->args['type'] : 'fields';
		$label = (isset($this->args['label']) && $this->args['label'])? $this->args['label']: _L('Restrict to these %1$s:', $type);
		$restrictchecked = count($value) > 0 ? "checked" : "";
		$str = '<div><input type="checkbox" id="'.$n.'-restrict" '.$restrictchecked .' onclick="restrictcheck(\''.$n.'-restrict\', \''.$n.'\')"><label for="'.$n.'-restrict">'.$label.'</label></div>';

		$str .= '<div id='.$n.' class="radiobox" style="margin-left: 1em;' . $extraStyle . '">';

		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').'  onclick="datafieldcheck(\''.$id.'\', \''.$n.'-restrict\')"/><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />
				';
			$counter++;
		}
		$str .= '</div>
		';
		return $str;
	}
	
	function renderJavascript($value) {
		return '
		//if we uncheck the restrict box, uncheck each field
		function restrictcheck(restrictcheckbox, checkboxdiv) {
			restrictcheckbox = $(restrictcheckbox);
			checkboxdiv = $(checkboxdiv);
			if (!restrictcheckbox.checked) {
				checkboxdiv.descendants().each(function(e) {
					e.checked = false;
				});
			}
		}

		// if a data field is checked. Check the restrict box
		function datafieldcheck(checkbox, restrictcheckbox) {
			checkbox = $(checkbox);
			restrictcheckbox = $(restrictcheckbox);
			if (checkbox.checked)
					restrictcheckbox.checked = true;
		}';
	}
}

?>
