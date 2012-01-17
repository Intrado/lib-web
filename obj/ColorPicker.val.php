<?
/* ValColorPicker validator
 * 	Used to validate that the data in a text field is a color (hex representation)
 * 
 * Author: Nickolas Heckman
 */

class ValColorPicker extends Validator {
	function validate ($value, $args) {
		if (!strlen($value))
			return $this->label." "._L("cannot be blank.");
		if (!preg_match('/^[0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F]$/', $value))
			return $this->label." "._L("must be a color.");
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (!value.length)
					return label + " '.addslashes(_L('cannot be blank.')).'";
				if (!value.match(/^[0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F][0-9,a-f,A-F]$/))
					return label + " '.addslashes(_L('must be a color.')).'";
				return true;
			}';
	}
}
?>