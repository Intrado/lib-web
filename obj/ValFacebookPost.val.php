<?

class ValFacebookPost extends Validator {
	
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize('facebookpost'))
			return $this->label. " ". _L("current user is not authorized to post messages.");
		
		$fbdata = json_decode($value);
		
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