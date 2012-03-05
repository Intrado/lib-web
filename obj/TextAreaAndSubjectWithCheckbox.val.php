<?
/* Validator for TextAreaAndSubjectWithCheckbox form item
 *  You can have an empty value and it will be valid
 *  non-empty value is of format:
 *    {"subject":<subject text>,"message":<message text>}
 *  
 * Possible args
 *  requiresubject - subject must be filled out if a value is present
 *  
 * Nickolas Heckman
 */
class ValTextAreaAndSubjectWithCheckbox extends Validator {
	function validate ($value, $args) {
		// if the value is blank, that's fine. ValRequired should prevent this when it's required.
		if ($value == "")
			return true;
		
		// get the data
		$jsvalue = json_decode($value, true);
		
		if (isset($args['requiresubject']) && $args['requiresubject'] && !$jsvalue['subject'])
			return "$this->label: ". escapehtml(_L('subject is required'));
		
		if (!$jsvalue['message'])
			return "$this->label: ". escapehtml(_L('message text is required'));
		
		return true;
	}
	
	function getJSValidator () {
		return '
			function (name, label, value, args) {
				// if blank, no problem
				if (value == "")
					return true;
				
				// parse the data
				var jsvalue = value.evalJSON(true);
				
				if (args.requiresubject && !jsvalue.subject)
					return label + " " +"'.escapehtml(_L('subject is required')).'";
				
				if (!jsvalue.message)
					return label + " " +"'.escapehtml(_L('message text is required')).'";
				
				return true;
			}
		';
	}
}

?>