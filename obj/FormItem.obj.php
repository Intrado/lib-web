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
		$hoverdata = array();
		$counter = 1;
		foreach ($this->args['values'] as $radiovalue => $radioname) {
			$id = $n.'-'.$counter;
			$str .= '<input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($radioname).'</label><br />
			';
			if (isset($this->args['hover'])) {
				$hoverdata[$id] = escapehtml($this->args['hover'][$radiovalue]);
				$hoverdata[$id.'-label'] = escapehtml($this->args['hover'][$radiovalue]);
			}
			$counter++;
		}
		$str .= '</div>
		';
		if (isset($this->args['hover']))
			$str .= '<script type="text/javascript">form_do_hover(' . json_encode($hoverdata) .');</script>
			';
		
		return $str;
	}
}

class TextArea extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$rows = isset($this->args['rows']) ? 'rows="'.$this->args['rows'].'"' : "";
		$cols = isset($this->args['cols']) ? 'cols="'.$this->args['cols'].'"' : "";
		$str = '<textarea id="'.$n.'" name="'.$n.'" '.$rows.' '.$cols.'/>'.escapehtml($value).'</textarea>';
		if(isset($this->args['counter'])) {
			$str .= '<div id="' . $n . 'charsleft">'._L('Characters remaining'). ':&nbsp;'. ( $this->args['counter'] - mb_strlen($value)). '</div>
				<script>
					Event.observe(\'' . $n . '\', \'keyup\', form_count_field_characters.curry(' . $this->args['counter'] . ',\''  . $n . 'charsleft\'));
				</script>';	
		}
		return $str;
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
		
		$hoverdata = array();
		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			$id = $n.'-'.$counter;
			$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
			$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').' /><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />
				';
			if (isset($this->args['hover'])) {
				$hoverdata[$id] = $this->args['hover'][$checkvalue];
				$hoverdata[$id.'-label'] = $this->args['hover'][$checkvalue];
			}
			$counter++;
		}
		$str .= '</div>
		';
		if (isset($this->args['hover']))
			$str .= '<script type="text/javascript">form_do_hover(' . json_encode($hoverdata) .');</script>
			';
		
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
			$str .= '<tr><td><input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /></td><td><label for="'.$id.'"><button type="button" class="regbutton" style=" width: 100%; border: 2px outset; background-color: white; color: black; margin-left: 0px;" onclick="$(\''.$id.'\').click();">'.($radiohtml).'</button></label></td></tr>
				';
			$counter++;
		}
		$str .= '</table></div>';
		return $str;
	}
}

// Text Field with calendar popup. you can pass in a date or a number of days relative to today to restrict dates displayed for selection
// Example:
// "control" => array("TextDate", "size"=>12, "nodatesafter" => 0, "nodatesbefore" => -2)    would allow selection of the two days previous to today and today's date only. 
class TextDate extends FormItem {
	function render ($value) {
		global $LOCALE;
		$dateFilter = "";
		if (isset($this->args['nodatesafter']))
			$dateFilter = "DatePickerUtils.noDatesAfter(".$this->args['nodatesafter'].")";
		if (isset($this->args['nodatesbefore'])) {
			if ($dateFilter)
				$dateFilter .= ".append(DatePickerUtils.noDatesBefore(".$this->args['nodatesbefore']."))";
			else
				$dateFilter = "DatePickerUtils.noDatesBefore(".$this->args['nodatesbefore'].")";
		}
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		$str = '<input id="'.$n.'" name="'.$n.'" type="text" value="'.date("m/d/Y", strtotime($value)).'" maxlength="12" '.$size.'/>';
		$str .= '<script type="text/javascript" src="script/datepicker.js"></script>
			<script type="text/javascript">
				var dpck_fieldname = new DatePicker({
				relative:"'.$n.'",
				keepFieldEmpty:true,
				language:"'.substr($LOCALE,0,2).'",
				enableCloseOnBlur:1,
				topOffset:20,
				'.(($dateFilter)?'dateFilter:'.$dateFilter:'').'
				});
			</script>';
		return $str;
	}
}


// Clones the same select menu several times
// $this->args = array("count" => "3", "values" => array($value1 => $html1, $value2 => $html2, ...))
class MultipleOrderBy extends FormItem {
	// @param $value, array()
	function render ($value) {
		if (!is_array($value))
			$value = array();
			
		$n = $this->form->name."_".$this->name;
		$final = "<div id='$n' class='radiobox'>";
		
		for ($i = 0; $i < $this->args['count']; $i++) {
			$select = "<select name='{$n}[]'>";
			$select .= '<option value=""> -- ' . _L("Not Selected") . ' -- </option>';
			foreach ($this->args['values'] as $txt => $val) {
				$preset = '';
				if (!empty($value[$i]) && $val ==  $value[$i])
					$preset = 'selected';
				$val = escapehtml($val);
				$txt = escapehtml($txt);
				
				$select .= "<option value='$val' $preset>$txt</option>";
			}
			$final .= $select . "</select>";
		}
			
		return "$final</div>";
	}
}

// Replaces dateOptions() in reportutils.inc.php
// $args['infinite'] = true or false
class ReldateOptions extends FormItem {
	// @param $valueJSON = ['reldate':'', 'xdays':'', 'startdate':'', 'enddate':'']
	function render ($valueJSON) {
		global $LOCALE;
		
		$n = $this->form->name."_".$this->name;
		
		$hiddenField = "<input id='$n' name='$n' type='hidden' value='".escapehtml($valueJSON)."' />";
		
		$data = json_decode($valueJSON, true);
		if (!is_array($data) || empty($data))
			$data = array('reldate' => '', 'xdays' => '', 'startdate' => '', 'enddate' => '');
		
		$onchange = "if (this.value != \"xdays\") { $(\"{$n}_xdaysContainer\").hide(); } else { $(\"{$n}_xdaysContainer\").show(); } if (this.value != \"daterange\") { $(\"{$n}_dateContainer\").hide(); } else { $(\"{$n}_dateContainer\").show(); } ";
		$onchange .= " $(\"$n\").value = \$H({\"reldate\":this.value}).toJSON(); ";
		$onchange .= " $(\"{$n}_xdays\").value = \"\"; $(\"{$n}_startdate\").value = \"\"; $(\"{$n}_enddate\").value = \"\";";
		$selectbox = "<select id='{$n}_reldate' onchange='$onchange'>";
			if (!empty($this->args['infinite']))
				$selectbox .= "<option value=''>" . _L("-- Select Date Range --") . "</option>";
			$reldateValues = array(
				'today' => _L('Today'),
				'yesterday' => _L('Yesterday'),
				'lastweekday' => _L('Last Week Day'),
				'weektodate' => _L('Week to Date'),
				'monthtodate' => _L('Month to Date'),
				'xdays' => _L('Last X Days'),
				'daterange' => _L('Date Range(inclusive)')
			);
			foreach ($reldateValues as $optionValue => $text) {
				$selected = ($data['reldate'] == $optionValue) ? 'selected' : '';
				$selectbox .= "<option value='$optionValue' $selected>$text</option>";
			}
		$selectbox .= "</select>";
		
		$xdaysValue = !empty($data['xdays']) ? $data['xdays'] : '';
		$xdaysChange = "$(\"$n\").value = \$H({\"reldate\":$(\"{$n}_reldate\").value, \"xdays\":this.value}).toJSON();";
		$xdays = _L("Days: ") . "<input type='text' size='3' id='{$n}_xdays' value='$xdaysValue' onchange='$xdaysChange'/>";
		
		$dateChange = " $(\"$n\").value = \$H({\"reldate\":$(\"{$n}_reldate\").value, \"startdate\":$(\"{$n}_startdate\").value, \"enddate\":$(\"{$n}_enddate\").value}).toJSON(); ";
		$dateFocus = " this.select(); $dateChange; ";
		$startdateValue = !empty($data['startdate']) ? $data['startdate'] : '';
		$enddateValue = !empty($data['enddate']) ? $data['enddate'] : '';
		$dateboxes = _L("From: ") . "<input id='{$n}_startdate' value='$startdateValue' type='text' size='20' onblur='$dateChange' onclick='$dateChange; setInterval(\"".addslashes($dateChange)."\", 300);' onfocus='$dateFocus ; pickDate(this, true,true)' onchange='$dateChange'/>";
		$dateboxes .= _L("To: ") . "<input id='{$n}_enddate' value='$enddateValue' type='text' size='20' onblur='$dateChange' onclick='$dateChange; setInterval(\"".addslashes($dateChange)."\", 300);' onfocus='$dateFocus ; pickDate(this, true,true)' onchange='$dateChange'/>";
		
		$xdaysHidden = ($data['reldate'] != 'xdays') ? 'display:none;' : '';
		$dateHidden = ($data['reldate'] != 'daterange') ? 'display:none;' : '';
		return "<div>
				$hiddenField
				$selectbox
				<span style='white-space:nowrap'>
					<div id='{$n}_xdaysContainer' style='$xdaysHidden'>$xdays</div>
					<div id='{$n}_dateContainer' style='$dateHidden'>$dateboxes</div>
				</span>
			</div>
			<script type='text/javascript' src='script/datepicker.js'></script>
		";
	}
}

?>