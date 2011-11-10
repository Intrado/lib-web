<?

// The TTS server will crash if a sequence of 141 digits or more are sent.
class ValTtsText extends Validator {
	function validate ($value, $args) {
		if (preg_match("/[0-9]{100,}/",$value))
			return "$this->label cannot contain a sequence of numbers more than 100 digits long";
		return true;
	}

	function getJSValidator () {
		return
		'function (name, label, value, args) {
					var re = /[0-9]{100,}/;
					if (re.test(value))
						return label + " cannot contain a sequence of numbers more than 100 digits long";
					return true;
				}';
	}
}

?>