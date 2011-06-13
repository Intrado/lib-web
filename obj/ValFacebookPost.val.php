<?

class ValFacebookPost extends Validator {
	
	function validate ($value, $args) {
		global $USER;
		if (!$USER->authorize('facebookpost'))
			return $this->label. " ". _L("current user is not authorized to post messages.");
		
		$fbdata = json_decode($value);
		
		// check that there is message text and it is within the allowed length
		if (!$fbdata->message)
			return $this->label. " ". _L("needs a message to post to your Facebook pages.");
		
		if (mb_strlen($fbdata->message) > 420)
			return $this->label. " ". _L("message may not excede 420 characters.");
		
		// get the authorized pages
		// don't trust args, look up the authorized pages
		$authpages = getFbAuthorizedPages();
		$authwall = getSystemSetting("fbauthorizewall");
		
		// check to see if any pages are selected
		$haspage = false;
		foreach ($fbdata->page as $pageid => $token) {
			error_log($pageid);
			$haspage = true;
			// check authorized pages to see if the ones selected are allowed
			if ($pageid == "me") {
				if (!$authwall)
					return $this->label. " ". _L("has an invalid selection. Personal wall posting is disabled.");
			} else if ($authpages && !in_array($pageid, $authpages)) {
				return $this->label. " ". _L("has an invalid posting location selected. Page is not authorized.");
			}
		}
		if (!$haspage)
			return $this->label. " ". _L("must have one or more pages to post to.");
		
		
		return true;
	}

	function getJSValidator () {
		return
			'function (name, label, value, args) {
				var authpages = args.authpages;
				var authwall = (args.authwall == 1);
				var pages = value.evalJSON();
				
				if (pages.message == "")
					return label + " '. _L("needs a message to post to your Facebook pages.") .'";
				if (pages.message.length > 420)
					return label + " '. _L("message may not excede 420 characters.") .'";
				if ($H(pages.page).size() == 0)
					return label + " '. _L("must have one or more pages to post to.") .'";
				
				var validatormessage = true;
				$H(pages.page).keys().each(function (pageid) {
					if (pageid == "me") {
						if (!authwall)
							validatormessage = label + " '. _L("has an invalid selection. Personal wall posting is disabled.") .'";
					} else {
						var validpage = false;
						$A(authpages).each(function (authpageid) {
							if (pageid == authpageid)
								validpage = true;
						});
						if (!validpage)
							validatormessage = label + " '. _L("has an invalid posting location selected. Page is not authorized.") .'";
					}
				});
				return validatormessage;
			}';
	}
}
?>