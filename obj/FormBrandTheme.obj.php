<?
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
		$str .= '<table id="'.$n.'themetable" name="'.$n.'themetable" class="msgdetails">
			<tr>
				<td class="msglabel">'._L("Theme").':</td>
				<td><select id="'.$n.'theme" name="'.$n.'theme" onchange="loadTheme();" >';
					foreach ($this->args['values'] as $selectvalue => $selectname)
						$str .= '<option value="'.escapehtml($selectvalue).'" '.(($phpvalue->theme == $selectvalue)? "selected" : "").' )>'.escapehtml($selectname['displayname']).'</option>';
					$str .= '</select></td>
			</tr>
			<tr>
				<td class="msglabel">'._L("Primary Color").':</td>
				<td><input id="'.$n.'color" name="'.$n.'color" type="text" value="'.$phpvalue->color.'" onchange="storeTheme();" />
					<img src="img/sel.gif" type="image" onclick="TCP.popup($(\''.$n.'color\')); "/></td>
			</tr>
			<tr>
				<td class="msglabel">'._L("Shader Ratio").':</td>
				<td><input id="'.$n.'ratio" name="'.$n.'ratio" type="text" value="'.$phpvalue->ratio.'" onchange="storeTheme();" /></td>
			</tr>
			</table>
			
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
				// Write values to hidden field and validate. -NRH
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

class ValBrandTheme extends Validator {
	function validate ($value, $args) {
		$checkval = json_decode($value);
		$errortext = "";
		if ($checkval->customize) {
			if (!$checkval->theme)
				$errortext .= " " . _L("Theme must be a valid choice.");
			if (!((strlen($checkval->color) == 6) && is_numeric('0x'.substr($checkval->color, 0, 2)) && is_numeric('0x'.substr($checkval->color, 2, 2)) && is_numeric('0x'.substr($checkval->color, 4, 2))))
				$errortext .= " " . _L("Primary Color must be a valid Hex representation of your color choice.");
			if (!is_numeric($checkval->ratio) && $checkval->ratio + 0 > .5)
				$errortext .= " " . _L("Ratio must be a number and greater than one-half.");
		}
		if ($errortext)
			return $this->label . $errortext;
		else
			return true;
	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				vals = value.evalJSON();
				var errortext = "";
				if (vals.customize) {
					if (!vals.theme)
						errortext += " "+ "'.addslashes(_L("Theme must be a valid choice.")).'";
					if (!(vals.color.length == 6))
						errortext += " "+ "'.addslashes(_L("Primary Color must be a valid Hex representation of your color choice.")).'";
					if (parseFloat(vals.ratio) === false && parseFloat(vals.ratio) > .5)
						errortext += " "+ "'.addslashes(_L("Ratio must be a number and greater than one-half.")).'";
				}
				if (errortext)
					return label + errortext;
				else
					return true;
			}';
	}
}
?>
