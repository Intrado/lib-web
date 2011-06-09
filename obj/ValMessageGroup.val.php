<?

class ValMessageGroup extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		$mg = new MessageGroup($value);
		if(!$mg->isValid()) {
			return _L("The selected message is missing one or more parts and cannot be used.");
		}
		return true;
	}
}

?>
