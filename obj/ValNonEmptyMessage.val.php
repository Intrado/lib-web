<?

class ValNonEmptyMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		$messages = QuickQuery("select count(*) from message where messagegroupid = ?", false, array($value));
		if(!$messages) {
			return _L("The selected message is empty");
		}
		return true;
	}
}

?>
