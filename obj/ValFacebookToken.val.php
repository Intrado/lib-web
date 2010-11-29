<?

class ValFacebookToken extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args, $requiredvalues) {
		$pagesdata = json_decode($requiredvalues['fbpages']);
		
		if (!$pagesdata->access_token || !fb_hasValidAccessToken($pagesdata->access_token))
			return _L("Valid access token not found. Please connect this account with your Facebook account.");
		
		$haspage = false;
		foreach ($pagesdata->page as $pageid => $token) {
			$haspage = true;
			break;
		}
		if (!$haspage)
			return _L("Must select one or more pages to post this message to.");
		
		return true;
	}
}
?>