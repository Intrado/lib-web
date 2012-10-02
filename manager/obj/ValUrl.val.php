<?

class ValUrl extends Validator {
	var $urlregexp = "(http|https)\://[a-zA-Z0-9\-]+\.[a-zA-Z]{2,3}(:[a-zA-Z0-9]*)?/?([a-zA-Z0-9\-\._\'/\\\+&amp;%\$#\=~])*";

	function validate ($value, $args) {
		if (!preg_match("!^{$this->urlregexp}$!", $value))
		return "$this->label is not a valid url format";

		return true;
	}

	function getJSValidator () {
		return
		'function (name, label, value, args) {
			var urlregexp = "^' . addslashes($this->urlregexp) . '$";
			var reg = new RegExp(urlregexp);
			if (!reg.test(value))
				return label + " is not a valid url format";
			return true;
		}';
	}
}
?>