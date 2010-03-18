<?

class ValNonEmptyMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value) {
		if(!QuickQuery("select 1 from message where messagegroupid = ? limit 1", false, array($value))) {
			return _L("The selected message is empty");
		}
		return true;
	}
}

?>
