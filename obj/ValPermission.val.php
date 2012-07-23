<?
//FIXME since arg is known, can check upfront and return a js validator that always fails or always passes, no need to serverside check
//FIXME also this shouldn't ever be needed, the form items for these shouldn't exist, and form handling code shouldn't try to read values for features the user is not permitted to access
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