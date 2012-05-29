<?
class ValPermission extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $ACCESS;
		$name = $args["name"];
		
		if ($value) {
			$access_allowed = $ACCESS->getPermission($name);
			if (!$ACCESS->getPermission($name))
				return "$this->label: ". escapehtml(_L('access profile permission denied.'));
		}
		
		return true;
	}
}

?>