<?
/**
 * This whacky custom validator is made necessary by the fact that PHP cannot
 * see the file field in the POST data since it goes into the multi-part
 * section. Therefore we'll fake out the server-side part of the validator by
 * always making it pass, but on the client side still show that the field is
 * required since JavaScript CAN see the form field's "value" which would
 * normally have the filename of the selected file in it.
 *
 * Note that if the user touches the field, but makes no file selection, the
 * server will mark the field as valid, and then the vlient side will not re-
 * check because it trusts the server as the source of truth. So it will be
 * incorrectly marked as valid even though it is not.
 */
class ValClientOnlyRequired extends Validator {
	var $isrequired = true;

	function validate ($value, $args) {
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (value.length == 0)
					return label + " is required";
				return true;
			}';
	}
}
?>