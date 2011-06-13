<?

class ValMessageGroup extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		$mg = new MessageGroup($value);
		if(!$mg->isValid()) {
			if ($mg->defaultlanguagecode)
				return _L("The default language, %s, for the selected message is missing one or more parts and cannot be used.", Language::getName($mg->defaultlanguagecode));
			else
				return _L("The selected message is missing one or more parts and cannot be used.", Language::getName($mg->defaultlanguagecode));
		}
		return true;
	}
}

?>
