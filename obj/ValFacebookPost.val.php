<?

class ValFacebookPost extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$fbdata = json_decode($value);
		
		// it's ok to not have an access_token, that just means facebook will be "skipped"
		if (!$fbdata->access_token)
			return true;
		
		// if we have an access token, be sure it's a good one
		if (!fb_hasValidAccessToken($fbdata->access_token))
			return _L("Valid access token not found. Please connect this account with your Facebook account.");
	
		// check to see if any pages are selected
		$haspage = false;
		foreach ($fbdata->page as $pageid => $token) {
			$haspage = true;
			break;
		}
		if (!$haspage)
			return _L("Must select one or more pages to post to.");
		
		// check that there is message text and it is within the allowed length
		if (!$fbdata->message)
			return _L("You must enter a message to post to your Facebook pages.");
		
		if (mb_strlen($fbdata->message) > 420)
			return _L("The message may not excede 420 characters.");
		
		return true;
	}
}
?>