<? 

class ValFacebookAuth extends Validator {
	var $onlyserverside = true;

	function validate ($value) {
		if (fb_hasValidAccessToken())
			return true;
		
		return _L('Facebook authorization failed. Please check your account link and connect to Facebook.');
	}
}
?>
