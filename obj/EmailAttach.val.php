<?

class ValEmailAttach extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if(count($value) > 3)
			return "Max 3 attachments allowed. Please remove one attachment.";
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				checkval = value.evalJSON();
				if(Object.keys(checkval).size() > 3) {
					return "Max 3 attachments allowed. Please remove one attachment.";
				}
				return true;
			}';
	}
}

?>