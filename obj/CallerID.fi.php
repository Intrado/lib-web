<? 


class CallerID extends FormItem {
	var $includeSelectMenu;
	var $includeInputBox;
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$this->includeSelectMenu = isset($this->args['selectvalues']) && count($this->args['selectvalues']);
		$this->includeInputBox =  isset($this->args['allowedit']) && $this->args['allowedit'];
		
		$max = isset($this->args['maxlength']) ? 'maxlength="'.$this->args['maxlength'].'"' : "";
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
		$displayInputBox = false;
		if ($this->includeSelectMenu) {
			if (!$this->includeInputBox && count($this->args['selectvalues']) == 1 && current($this->args['selectvalues']) == $value) {
				$str .= Phone::format($value);
				$this->includeSelectMenu = false;
			} else {
				$str .= '<select id="'.$n.'select" name="'.$n.'select">';
				$str .= '<option value="" ' . ($value == ""?'selected':'') . ' > -- ' . _L('Select Caller ID') . ' -- </option>';
				$notInList = !in_array($value, $this->args['selectvalues']);
				if ($this->includeInputBox) {
					$displayInputBox = $notInList;
					$str .= '<option value="other" ' . ($displayInputBox?'selected':''). '>' . _L('Other') . '</option>';
				} else {
					if ($notInList && $value != "") {
						$str .= '<option value="' . $value . '" selected disabled>' . escapehtml(Phone::format($value)) . '</option>';
					}
				}
				
				foreach ($this->args['selectvalues'] as $selectvalue) {
					$checked = $value == $selectvalue;
					$str .= '<option value="'.escapehtml($selectvalue).'" '.($checked ? 'selected' : '').' >'.escapehtml(Phone::format($selectvalue)).'</option>';
				}
				$str .= '</select>';
			}
		} else {
			$displayInputBox = true;
		}
		
		if ($this->includeInputBox) {
			$str .= '&nbsp;<input id="'.$n.'freeform" name="'.$n.'freeform" type="text" value="'. ($displayInputBox?escapehtml(Phone::format($value)):'') .'" '.$max.' '.$size.' style="' . (!$displayInputBox?'display:none':''). ';"/>';
		}
		return $str;
	}

	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		$str = '';
		if ($this->includeSelectMenu) {
			if ($this->includeInputBox) {
				$str .= '
					$(\''.$n.'select\').observe("change",function(event) {
						if($(\''.$n.'select\').value==\'other\') {
							$(\''.$n.'freeform\').show();
						} else {
							$(\''.$n.'freeform\').hide();
							$(\''.$n.'\').value = $(\''.$n.'select\').value;
							form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
						} 
					});';
			} else {
				$str .= '
					$(\''.$n.'select\').observe("change",function(event) {
						$(\''.$n.'\').value = $(\''.$n.'select\').value;
						form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
					});';
			}
		
		}
		if ($this->includeInputBox) {
			$str .= '
				$(\''.$n.'freeform\').observe("change",function(event) {
					e = event.element();
					$(\''.$n.'\').value = e.value;
					form_do_validation($("' . $this->form->name . '"), $("' . $n . '"));
				});';
		}
		
		return $str;
	}
}


class ValCallerID extends Validator {
	var $onlyserverside = true;

	function validate ($value, $args) {
		global $USER;
		$callerid = Phone::parse($value);
		if (!canSetCallerid($callerid)) {
			return _L('Callerid is not Authorized');
		}
		return true;

	}
}

?>