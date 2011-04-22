<?

class PhoneMessageRecorderValidator extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;


		if (!$USER->authorize("starteasy"))
			return _L('%1$s is not allowed for this user account',$this->label);
		$values = json_decode($value);

		if ($values == null || (!isset($values->m) && !isset($values->af) && !isset($values->delete)))
			return _L('%1$s does not have a message recorded', $this->label);

		//special allow for delete
		if (isset($values->delete))
			return true;

		if (isset($values->m)) {
			if (!QuickQuery("select count(*) from message where id=? and userid=?",false,array($values->m,$USER->id)))
				return _L('%1$s has an invalid or missing message', $this->label);
		}

		if (isset($values->af)) {
			if (!QuickQuery("select count(*) from audiofile where id=? and userid=?",false,array($values->af,$USER->id)))
				return _L('%1$s has an invalid or missing message', $this->label);
		}

		return true;
	}
}
?>