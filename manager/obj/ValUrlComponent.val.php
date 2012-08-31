<?
class ValUrlComponent extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		// Check alphanumeric
		if(!preg_match("/^[a-zA-Z0-9]*$/", $value)) {
			return 'Can only use letters and numbers';
		}

		// Allow legacy urlcomponents to be sorter than 5 characters but all new ones should be 5 or more
		if (($args["urlcomponent"] && strlen($args["urlcomponent"]) >= 5 && strlen($value) < 5) ||
		(!$args["urlcomponent"] && strlen($value) < 5)) {
			return 'URL path must be 5 or more characters';
		}

		$query = "select count(*) from customer where urlcomponent=?";
		if (($args["customerid"] && QuickQuery($query . " and id!=?",false,array($value,$args["customerid"]))) ||
		(!$args["customerid"] && QuickQuery($query,false,array($value)))) {
			return 'URL path is already in use';
		}
		return true;
	}
}

?>