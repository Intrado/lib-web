<?

class ValFacebookPost extends Validator {
	
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
			return $this->label. " ". _L("must have one or more pages to post to.");
		
		// check that there is message text and it is within the allowed length
		if (!$fbdata->message)
			return $this->label. " ". _L("needs a message to post to your Facebook pages.");
		
		if (mb_strlen($fbdata->message) > 420)
			return $this->label. " ". _L("message may not excede 420 characters.");
		
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value) {
				var fbdata = value.evalJSON();
				
				// access token can be false if not connected, and that is fine...
				if (!fbdata.access_token)
					return true;
					
				// no syncrhonous way to make sure the access token we have is good in js currently
				
				// check if any pages are selected
				var haspage = false;
				$H(fbdata.page).each(function(page) {
					haspage = true;
				});
				if (!haspage)
					return label + " '. _L("must have one or more pages to post to."). '";
				
				if (!fbdata.message || fbdata.message.length < 1)
					return label + " '. _L("needs a message to post to your Facebook pages."). '";
				
				if (fbdata.message.length > 420)
					return label + " '. _L("message may not excede 420 characters."). '";
				
				return true;
			}';
	}
}
?>