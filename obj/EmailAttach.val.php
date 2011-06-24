<?

class ValEmailAttach extends Validator {
	function validate ($value, $args) {
		$max = 3; // default
		if (isset($args['maxattachments']))
			$max = $args['maxattachments'];
			
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if(count($value) > $max)
			return "Max $max attachments allowed. Please remove one attachment.";
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {
				max = 3;
				if (args.maxattachments)
					max = args.maxattachments;
				checkval = value.evalJSON();
				if (Object.keys(checkval).size() > max) {
					return "Max "+max+" attachments allowed. Please remove one attachment.";
				}
				return true;
			}';
	}
}

?>