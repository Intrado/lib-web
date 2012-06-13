<?

abstract class FormItem {
	var $form;
	var $name;
	var $args;
	var $clearonsubmit = false;
	var $clearvalue = false;

	function FormItem ($form, $name,$args) {
		$this->form = $form;
		$this->name = $name;
		$this->args = $args;
	}

	abstract function render ($value) ;
	
	// Return a string containing any javascript dependencies.
	// NOTE: The returned string should contain script tags.
	function renderJavascriptLibraries() {
		return '';
	}
	
	// Return a string containing any javascript to be executed.
	// NOTE: The returned string should NOT contain script tags.
	function renderJavascript($value) {
		return '';
	}

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
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
		
		if ($spellcheck) {
			$str .= action_link(_L("Spell Check"), "spellcheck", null, '(new spellChecker($(\''.$n.'\')) ).openChecker();');
		}
				
		if (isset($this->args['autocomplete'])) {
			$str .= '<span id="'.$n.'_autocomplete_indicator" style="display: none"><img src="img/ajax-loader.gif" alt="Working..." /></span>';	
			$str .= '<div id="'.$n.'_autocomplete_choices" class="autocomplete"></div>';
		}

		return $str;
	}
	
	function renderJavascript($value) {
		$n = $this->form->name."_".$this->name;
		$js = "";
		
		//autocomplete using scriptaculous and autocomplete.php as the back-end
		//set arg autocomplete=myautocompletename and add a handler for it in autocomplete.php
		if (isset($this->args['autocomplete'])) {
			$autoname = $this->args['autocomplete'];
			$autominchars = $size = isset($this->args['autocompleteminchars']) ? $this->args['autocompleteminchars'] : 2;
			
			$js .= '
				new Ajax.Autocompleter("'.$n.'", "'.$n.'_autocomplete_choices", "autocomplete.php", {
				  paramName: "'.$autoname.'", 
				  minChars: '.$autominchars.', 
				  indicator: \''.$n.'_autocomplete_indicator\'
				});
			';
		}
		
		if (isset($this->args['blankfieldvalue'])) {
			$js = '
				blankFieldValue("'.$n.'","'.$this->args['blankfieldvalue'].'");
			';
		}
		
		return $js;
	}
	
	function renderJavascriptLibraries() {
		$n = $this->form->name."_".$this->name;
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];
		$js = "";
		
		if ($spellcheck) {
			$js .= '<script src="script/speller/spellChecker.js"></script>';
		}
		return $js;
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
	var $clearonsubmit = true;
	var $clearvalue = false;
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		
		if (isset($this->args['label']))
			$label = $this->args['label'];
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value ? 'checked' : '').' />';
		if (isset($label))
			$str .= '<label for="'.$n.'">'. $label . '</label>';
		return $str;
	}
}

class RadioButton extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		// is the radioname data html formatted?
		$ishtml = false;
		if (isset($this->args['ishtml']) && $this->args['ishtml'])
			$ishtml = true; 
		$str = '<ul id='.$n.' class="radiobox">';
		$hoverdata = array();
		$counter = 1;
		$autoselect = count($this->args['values']) == 1; //if there is only one value, autoselect it
		foreach ($this->args['values'] as $radiovalue => $radioname) {
			if ($radioname == "#-#") {
				$str .= "<hr />\n";
			} else {
				$id = $n.'-'.$counter;
				$str .= '<li><input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue || $autoselect ? 'checked' : '').' /><label id="'.$id.'-label" for="'.$id.'">'.($ishtml?$radioname:escapehtml($radioname)).'</label></li>
				';
				if (isset($this->args['hover'])) {
					$hoverdata[$id] = $this->args['hover'][$radiovalue];
					$hoverdata[$id.'-label'] = $this->args['hover'][$radiovalue];
				}
				$counter++;
			}
		}
		$str .= '</ul>
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
		$disablebrowserspellcheck = isset($this->args['disablebrowserspellcheck']) && $this->args['disablebrowserspellcheck'] ? 'spellcheck="false"' : "";
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];
		
		$str = '<textarea id="'.$n.'" name="'.$n.'" '.$rows.' '.$cols.' '.$disablebrowserspellcheck.'/>'.escapehtml($value).'</textarea>';
		
		//FIXME this belongs in renderJavaScript()
		if(isset($this->args['counter'])) {
			$str .= '<div id="' . $n . 'charsleft">'._L('Characters remaining'). ':&nbsp;'. ( $this->args['counter'] - mb_strlen($value)). '</div>
				<script>
					Event.observe(\'' . $n . '\', \'keyup\', form_count_field_characters.curry(' . $this->args['counter'] . ',\''  . $n . 'charsleft\'));
				</script>';
		}

		if ($spellcheck) {
			$str .= '<ul class="actionlinks">' . action_link(_L("Spell Check"), "spellcheck", null, '(new spellChecker($(\''.$n.'\')) ).openChecker();') . '</ul>';
		}
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$n = $this->form->name."_".$this->name;
		$spellcheck = isset($this->args['spellcheck']) && $this->args['spellcheck'];
		$js = "";
		
		if ($spellcheck) {
			$js .= '<script src="script/speller/spellChecker.js"></script>';
		}
		return $js;
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
	var $clearonsubmit = true;
	var $clearvalue = array();
	
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$style = isset($this->args['height']) ? ('style="height: ' . $this->args['height'] . '; overflow: auto;"') : '';

		$str = '<div><div id='.$n.' class="multicheckbox" '.$style.'>';

		$hoverdata = array();
		$counter = 1;
		foreach ($this->args['values'] as $checkvalue => $checkname) {
			if (preg_match("/^#-.*-#$/", $checkname)) {
				$str .= '<div style="font-weight:bold;padding-left:22px;">'.substr($checkname, 2, strlen($checkname) - 4)."</div>";
			} else if ($checkname == "#-#") {
				$str .= "<hr />\n";
			} else {
				$id = $n.'-'.$counter;
				$checked = $value == $checkvalue || (is_array($value) && in_array($checkvalue, $value));
				$str .= '<input id="'.$id.'" name="'.$n.'[]" type="checkbox" value="'.escapehtml($checkvalue).'" '.($checked ? 'checked' : '').' /><label id="'.$id.'-label" for="'.$id.'">'.escapehtml($checkname).'</label><br />
					';
				if (isset($this->args['hover']) && $this->args['hover'][$checkvalue]) {
					$hoverdata[$id] = $this->args['hover'][$checkvalue];
					$hoverdata[$id.'-label'] = $this->args['hover'][$checkvalue];
				}
				$counter++;
			}
		}
		$str .= '</div></div>';
		if (isset($this->args['hover']))
			$str .= '<script type="text/javascript">form_do_hover(' . json_encode($hoverdata) .');</script>
			';

		return $str;
	}
}

// allows ad-hoc html
// DO NOT SET 'validators' on this formitem, do not set to empty array, it will cause an error popup "domain TypeError: formvars.jsgetvalue[targetname] is not a function"
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
			$str .= '<tr><td><input id="'.$id.'" name="'.$n.'" type="radio" value="'.escapehtml($radiovalue).'" '.($value == $radiovalue ? 'checked' : '').' /></td><td><label for="'.$id.'"><button type="button" class="regbutton htmlradiobutton" onclick="$(\''.$id.'\').click();">'.($radiohtml).'</button></label></td></tr>
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
		$str = '<input id="'.$n.'" name="'.$n.'" type="text" value="'.($value?date("m/d/Y", strtotime($value)):'').'" maxlength="12" '.$size.'/>';
		$str .= '<script type="text/javascript" src="script/datepicker.js"></script>
			<script type="text/javascript">
				var dpck_fieldname = new DatePicker({
				relative:"'.$n.'",
				keepFieldEmpty:true,
				language:"'.substr($LOCALE,0,2).'",
				enableCloseOnBlur:1,
				topOffset:20,
				zindex: 99999
				'.(($dateFilter)?',dateFilter:'.$dateFilter:'').'
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

/** Replaces dateOptions() in reportutils.inc.php
 * @var Boolean $args['infinite'] If true shows 'Select Date Range'
 * @var Boolean $args['rangedonly'] If true, only allow 'xdays' and 'daterange'
 * @var Integer $args['defaultxdays']
 */
class ReldateOptions extends FormItem {
	// @param $valueJSON = ['reldate':'', 'xdays':'', 'startdate':'', 'enddate':'']
	function render ($valueJSON) {
		global $LOCALE;

		$defaultxdays = !isset($this->args['defaultxdays']) ? '' : $this->args['defaultxdays'];

		$n = $this->form->name."_".$this->name;

		$hiddenField = "<input id='$n' name='$n' type='hidden' value='".escapehtml($valueJSON)."' />";

		$data = json_decode($valueJSON, true);
		if (!is_array($data) || empty($data)) {
			$data = array('reldate' => '', 'xdays' => $defaultxdays, 'startdate' => '', 'enddate' => '');
		}

		$onchange = "if (this.value != \"xdays\") {
						 $(\"{$n}_xdaysContainer\").hide();
					} else {
						$(\"{$n}_xdaysContainer\").show();
					}
					if (this.value != \"daterange\") {
						$(\"{$n}_dateContainer\").hide();
					} else {
						$(\"{$n}_dateContainer\").show();
					}
					
					$(\"$n\").value = Object.toJSON({\"reldate\":this.value});
					$(\"{$n}_xdays\").value = \"$defaultxdays\";
					$(\"{$n}_startdate\").value = \"\";
					$(\"{$n}_enddate\").value = \"\";";

		$selectbox = "<select id='{$n}_reldate' onchange='$onchange'>";
			if (!empty($this->args['infinite']))
				$selectbox .= "<option value=''>" . _L("-- Select Date Range --") . "</option>";

			if (!empty($this->args['rangedonly'])) {
				$reldateValues = array(
					'xdays' => _L('Last X Days'),
					'daterange' => _L('Date Range(inclusive)')
				);
			} else {
				$reldateValues = array(
					'today' => _L('Today'),
					'yesterday' => _L('Yesterday'),
					'lastweekday' => _L('Last Week Day'),
					'weektodate' => _L('Week to Date'),
					'monthtodate' => _L('Month to Date'),
					'xdays' => _L('Last X Days'),
					'daterange' => _L('Date Range(inclusive)')
				);
			}

			foreach ($reldateValues as $optionValue => $text) {
				$selected = ($data['reldate'] == $optionValue) ? 'selected' : '';
				$selectbox .= "<option value='$optionValue' $selected>$text</option>";
			}
		$selectbox .= "</select>";

		$xdaysValue = isset($data['xdays']) ? $data['xdays'] : $defaultxdays;
		$xdaysChange = "$(\"$n\").value = Object.toJSON({\"reldate\":$(\"{$n}_reldate\").value, \"xdays\":this.value});";
		$xdays = _L("Days: ") . "<input type='text' size='3' id='{$n}_xdays' value='$xdaysValue' onclick='$xdaysChange' onfocus='$xdaysChange' onblur='$xdaysChange' onchange='$xdaysChange'/>";

		$startdateValue = !empty($data['startdate']) ? $data['startdate'] : '';
		$enddateValue = !empty($data['enddate']) ? $data['enddate'] : '';
		$dateboxes = _L("From: ") . "<input id='{$n}_startdate' value='$startdateValue' type='text' size='20' onblur='{$n}_datechange()' onchange='{$n}_datechange()' onfocus='{$n}_datepick(this);' />";
		$dateboxes .= _L("To: ") . "<input id='{$n}_enddate' value='$enddateValue' type='text' size='20' onblur='{$n}_datechange()' onchange='{$n}_datechange()' onfocus='{$n}_datepick(this);' />";
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
			<script type='text/javascript'>
				function {$n}_datechange() {
					$(\"$n\").value = Object.toJSON({\"reldate\":$(\"{$n}_reldate\").value, \"startdate\":$(\"{$n}_startdate\").value, \"enddate\":$(\"{$n}_enddate\").value}); 
					form_do_validation($('{$this->form->name}'), $('$n'));
				}
				function {$n}_datepick(element) {
					element.select();
					pickDate(element, true,true,false,{$n}_datechange);
				}
			</script>
		";
	}
}

?>
