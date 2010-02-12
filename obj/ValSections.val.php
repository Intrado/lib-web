<?

class ValSections extends Validator {
	var $onlyserverside = true;
	
	function validate($value) {
		global $USER;
		
		// TODO: Validate against $USER->sections()
		
		return true;
	}
}

?>