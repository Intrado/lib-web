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

class CaptchaField extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (isset($this->args['iData'])) {
			$iData = '?iData='.$this->args['iData'];
			$max = 'maxlength="50"';
			$size = 'size="14"';
		} else {
			// TODO what if no iData?
			$iData = "";
			$max = "";
			$size = "";
		}
		return '<img src="captcha.png.php'.$iData.'" /><br><input id="'.$n.'" name="'.$n.'" type="text" value="'.escapehtml($value).'" '.$max.' '.$size.'/>';
	}
}

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
		return '<input id="'.$n.'" name="'.$n.'" type="checkbox" value="true" '. ($value == "true" ? 'checked' : '').' />';
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

// Brand, Theme, color selector form item
class BrandTheme extends FormItem {

	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if (!$value)
			$value = "{}";
		$phpvalue = json_decode($value);
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
		if ($this->args['toggle'])
			$str .= '<input id="'.$n.'customize" name="'.$n.'customize" type="checkbox" value="true" '. ($phpvalue->customize ? 'checked' : '').' onchange="showTheme()" />';
		$str .= '<table id="'.$n.'themetable" name="'.$n.'themetable" class="msgdetails">';
		$str .= '<tr><td class="msglabel">'._L("Theme").':</td>';
		$str .= '<td><select id="'.$n.'theme" name="'.$n.'theme" onchange="loadTheme();" >';
		foreach ($this->args['values'] as $selectvalue => $selectname)
			$str .= '<option value="'.escapehtml($selectvalue).'" '.(($phpvalue->theme == $selectvalue)? "selected" : "").' )>'.escapehtml($selectname['displayname']).'</option>';
		$str .= '</select></td></tr>';
		$str .= '<tr><td class="msglabel">'._L("Primary Color").':</td>';
		$str .= '<td><input id="'.$n.'color" name="'.$n.'color" type="text" value="'.$phpvalue->color.'" onchange="storeTheme();" />';
		$str .= '<img src="img/sel.gif" type="image" onclick="TCP.popup($(\''.$n.'color\')); "/></td></tr>';
		$str .= '<tr><td class="msglabel">'._L("Shader Ratio").':</td>';
		$str .= '<td><input id="'.$n.'ratio" name="'.$n.'ratio" type="text" value="'.$phpvalue->ratio.'" onchange="storeTheme();" /></td></tr>';
		$str .= '</table>';
		$str .= '<script type="text/javascript" src="script/picker.js"></script>
			<script type="text/javascript">
			
			themeformname = "'.$this->form->name.'";
			themeformitem = "'.$n.'";
			themecolorschemes = '.json_encode($this->args['values']).';
			themecustomize = "'.$this->args['toggle'].'";
			
			if (themecustomize && $(themeformitem+"customize").checked == false)
				$(themeformitem+"themetable").hide();
			
			// When customization check box is checked. Show the theme table.
			function showTheme() {
				if ($(themeformitem+"customize").checked == true)
					$(themeformitem+"themetable").show();
				else
					$(themeformitem+"themetable").hide();
				storeTheme();
			}
			
			// When a new theme is selected. Update the color and ratio fields with default values
			function loadTheme() {
				theme = $(themeformitem+"theme").value;
				$(themeformitem+"color").value = themecolorschemes[theme]._brandprimary;
				$(themeformitem+"ratio").value = themecolorschemes[theme]._brandratio;
				storeTheme();
			}
			
			// Save changes in the hidden field
			function storeTheme() {
				$(themeformitem).value = Object.toJSON({
					"customize": themecustomize?$(themeformitem+"customize").checked:true,
					"theme": $(themeformitem+"theme").value,
					"color": $(themeformitem+"color").value,
					"ratio": $(themeformitem+"ratio").value
				});
				form_do_validation($(themeformname), $(themeformitem));
			}
			
			// Title: Tigra Color Picker
			// URL: http://www.softcomplex.com/products/tigra_color_picker/
			// Version: 1.1
			// Date: 06/26/2003 (mm/dd/yyyy)
			// Note: Permission given to use this script in ANY kind of applications if
			//    header lines are left unchanged.
			// Note: Script consists of two files: picker.js and picker.html

			// Modified by Joshua Lai jlai@schoolmessenger.com
			// Modified by Nickolas Heckman nheckman@schoolmessenger.com

			var TCP = new TColorPicker();

			function TCPopup(field) {
				this.field = field;
				this.initPalette = 0;
				var w = 600, h = 250,
				move = screen ? 
					",left=" + ((screen.width - w) >> 1) + ",top=" + ((screen.height - h) >> 1) : "", 
				o_colWindow = window.open("picker.php", null, "help=no,status=no,scrollbars=no,resizable=no" + move + ",width=" + w + ",height=" + h + ",dependent=yes", true);
				o_colWindow.opener = window;
				o_colWindow.focus();
			}

			function TCBuildCell (R, G, B, w, h) {
				return "<td style=\"border:0px solid red; \" bgcolor=\"#" + this.dec2hex((R << 16) + (G << 8) + B) + "\"><a style=\"border: 0px solid green;\" href=\"javascript:P.S(\"" + this.dec2hex((R << 16) + (G << 8) + B) + "\") onmouseover=\"P.P(\"" + this.dec2hex((R << 16) + (G << 8) + B) + "\")\"><img style=\"border: 0px solid black;\" src=\"img/pixel.gif\" width=\"" + w + "\" height=\"" + h + "\" border=\"0\"></a></td>";
			}

			function TCSelect(c) {
				// Removed # from return value.  -JJL
				this.field.value = c.toUpperCase();
				storeTheme();
				this.win.close();
			}

			function TCPaint(c, b_noPref) {
				c = (b_noPref ? "" : "#") + c.toUpperCase();
				if (this.o_samp) 
					this.o_samp.innerHTML = "<font face=Tahoma size=2>" + c +" <font color=white>" + c + "</font></font>";
				if(this.doc.layers)
					this.sample.bgColor = c;
				else { 
					if (this.sample.backgroundColor != null) this.sample.backgroundColor = c;
					else if (this.sample.background != null) this.sample.background = c;
				}
			}

			function TCDec2Hex(v) {
				v = v.toString(16);
				for(; v.length < 6; v = "0" + v);
				return v;
			}

			function TColorPicker(field) {
				this.show = document.layers ? 
					function (div) { this.divs[div].visibility = "show" } :
					function (div) { this.divs[div].visibility = "visible" };
				this.hide = document.layers ? 
					function (div) { this.divs[div].visibility = "hide" } :
					function (div) { this.divs[div].visibility = "hidden" };
				// event handlers
				this.S       = TCSelect;
				this.P       = TCPaint;
				this.popup   = TCPopup;
				this.dec2hex = TCDec2Hex;
				this.bldCell = TCBuildCell;
				this.divs = [];
			}
			
			</script>';
		return $str;
	}
}

?>
