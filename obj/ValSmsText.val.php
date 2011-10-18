<?
class ValSmsText extends Validator {
	function validate ($value, $args) {
		if (!preg_match("/^[a-zA-Z0-9\x20\x09\x0a\x0b\x0C\x0d\x2a\<\>\?\,\.\/\|\!\@\#\$\%\&\(\)\_\+\'\"\:\;\=\-]*$/", $value))
		return _L("Invalid character in ")." ".$this->label;
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var reg = /^[a-zA-Z0-9\x20\x09\x0a\x0b\x0C\x0d\x2a\<\>\?\,\.\/\|\!\@\#\$\%\&\(\)\_\+\'\"\:\;\=\-]*$/;
				if (!value.match(reg))
					return "' . _L("Invalid character in ")  . '" + label ;
				return true;
			}';
	}
}