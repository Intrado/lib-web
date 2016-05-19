<?

include_once(dirname(__FILE__) . "/../includes/content.inc.php");

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
		
		// Verify against message group, if possible
		foreach ($value as $cid => $details) {
            // API clients are stateless and hence no content will be in session scope.
            // Manually check for existence of uploaded content before rejecting...
            //
			if (!contentAllowed($cid)) {
                if (isset($_GET['api'])) {
                    if (!contentGet($cid)) {
                        return "One or more attachments contains invalid data, or the data is no longer accessible.";
                    } else {
                        permitContent($cid);
                    }
                } else {
                    return "One or more attachments contains invalid data, or the data is no longer accessible.";
                }
            }
		}
		// TODO: check the database to see that all the content IDs are actually valid (who knows what junk the client sent?)
		// TODO: We only allow attachments up to a certain size
		
		return true;
	}
}
?>
