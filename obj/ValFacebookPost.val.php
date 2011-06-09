<?

class ValFacebookPost extends Validator {
	var $onlyserverside = true;
	
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize('facebookpost'))
			return $this->label. " ". _L("current user is not authorized to post messages.");
		
		$fbdata = json_decode($value);
		
		// get the authorized pages
		$authpages = array();
		$authwall = false;
		if (isset($args['authpages']))
			$authpages = $args['authpages'];
		if (isset($args['authwall']))
			$authwall = $args['authwall'];
		
		// check to see if any pages are selected
		$haspage = false;
		foreach ($fbdata->page as $pageid => $token) {
			$haspage = true;
			// check authorized pages to see if the ones selected are allowed
			if ($pageid == "me" && !$authwall)
				return $this->label. " ". _L("has an invalid selection. Personal wall posting is disabled.");
			else if ($authpages && !in_array($pageid, $authpages))
				return $this->label. " ". _L("has an invalid posting location selected. Page is not authorized.");
		}
		if (!$haspage)
			return $this->label. " ". _L("must have one or more pages to post to.");
		
		// check that there is message text and it is within the allowed length
		if (!$fbdata->message)
			return $this->label. " ". _L("needs a message to post to your Facebook pages.");
		
		if (mb_strlen($fbdata->message) > 420)
			return $this->label. " ". _L("message may not excede 420 characters.");
		
		return true;
	}
	// TODO: javascript validator
}
?>