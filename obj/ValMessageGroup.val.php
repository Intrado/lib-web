<?

class ValMessageGroup extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args = false) {
		$mg = new MessageGroup($value);
		if(!$mg->isValid()) {
			if ($mg->defaultlanguagecode)
				return _L("The default language, %s, for the selected message is missing one or more parts and cannot be used.", Language::getName($mg->defaultlanguagecode));
			else
				return _L("The selected message is missing one or more parts and cannot be used.", Language::getName($mg->defaultlanguagecode));
		}
                
		// if 'requireemail' profile setting is enabled, make sure message contains an email
		if(isset($args["values"]["USER"])) {

			$USER = $args["values"]["USER"];

			if($USER->authorize('requireemail')) {

				if(! $mg->hasMessage('email')) {
					return _L('Each message is required to include an email', Language::getName($mg->defaultlanguagecode));
				}
			}
		}
		
		return true;
	}
}

?>
