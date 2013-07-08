<?

class ValEmailAttach extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		$max = 3; // default
		if (isset($args['maxattachments']))
			$max = $args['maxattachments'];
			
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if(count($value) > $max)
			return "Max $max attachments allowed. Please remove one attachment.";
		
		// verify against message group, if possible
		foreach ($value as $cid => $details) {
			if (!contentAllowed($cid))
				return "One or more attachments contains invalid data, or the data is no longer accessible.";
		}
		// TODO: check the database to see that all the content IDs are actually valid (who knows what junk the client sent?)
		// TODO: We only allow attachments up to a certain size
		
		return true;
	}
}
?>