<?

class PhoneMessageRecorderValidator extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;


		if (!$USER->authorize("starteasy"))
			return _L('%1$s is not allowed for this user account',$this->label);
		$values = json_decode($value);
		if ($values == null || $value == '{}')
			return _L('%1$s does not have a message recorded', $this->label);

		foreach ($values as $langCode => $audiofileId) {
			$languages = Language::getLanguageMap();
			if (!isset($languages[$langCode]) || !userCanSee("audiofile", $audiofileId))
				return _L('%1$s has an invalid or missing message', $this->label);
		}

		return true;
	}
}
?>