<?

/**
 * Specialized version of ValLengh for translation purposes
 *
 * see: UNITTEST/PHPUnit/application/ValTranslationLengthTest.php
 */
class ValTranslationLength extends Validator {
	function validate ($value, $args) {
		if (mb_strlen($value) > 5000)
			return "Translated messages can not be more than 5000 characters long";
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (value.length > 5000)
					return "Translated messages can not be more than 5000 characters long";
				return true;
			}';
	}
}

?>
