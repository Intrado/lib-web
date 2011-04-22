<?
class ValEasycall extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize("starteasy"))
			return "$this->label "._L("is not allowed for this user account");
		$values = json_decode($value);
		if ($values == json_decode("{}"))
			return "$this->label "._L("has messages that are not recorded");
		foreach ($values as $langcode => $afid) {
			$audiofile = DBFind("AudioFile", "from audiofile where id = ? and userid = ?", false, array($afid, $USER->id));
			if (!$audiofile)
				return "$this->label "._L("has invalid or missing messages");
		}
		return true;
	}
}
?>