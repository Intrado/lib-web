<?
// get offline access token for twitter posting

class TwitterAuth extends FormItem {
	function render ($value) {
		
		// NOTE: this form item changes DB values and thus, cannot get it's value from the $value variable
		// if you set value on the form item you will get errors submitting the form
		
		$n = $this->form->name . '_' . $this->name;

		$str = $scriptStr = '';
		$twitterTokens = new TwitterTokens();
		$accessTokens = $twitterTokens->getAllAccessTokens();
		if (is_array($accessTokens)) {
			for ($xx = 0; $xx < count($accessTokens); $xx++) {

				// Get the one we're working with; xx is our enumerator for DHTML operations...
				$dn = $n . "_{$xx}";
				
				// Per-twitter account containers
				$str .= '<input id="' . $dn . '" name="' . $dn . '" type="hidden" value="' . escapehtml($accessTokens[$xx]->user_id) . '"/>';
				$str .= '<div id="' . $dn . 'twdetails">';
				$str .= '<div id="' . $dn . 'twconnected" style="border: 1px dotted grey; padding: 5px;margin-bottom:3px;">';
				$str .= '<div id="' . $dn . 'twuser"></div>';
				
				// button to remove access_token
				$str .= icon_button("Disconnect this Twitter Account", "cross" ,"if(confirm('Are you sure you wish to disconnect this account?')) { TwitterHelper.clearValue('" . $dn . "') }");
				$str .= '<div style="clear: both"></div></div>';
			}
		}


		// Do twitter login to get good auth token
		$str .= submit_button(_L('Connect to Twitter'), 'twitterauth', 'custom/twitter');

		$str .= '<div style="clear: both"></div>';
		return $str;
	}

	function renderJavascript($value) {
		$n = $this->form->name . '_' . $this->name;
		$str = '';
		$twitterTokens = new TwitterTokens();
		$accessTokens = $twitterTokens->getAllAccessTokens();
		if (! is_array($accessTokens)) return '';
		for ($xx = 0; $xx < count($accessTokens); $xx++) {

			// Get the one we're working with; xx is our enumerator for DHTML operations...
			$dn = $n . "_{$xx}";
			$str .= 'TwitterHelper.loadUserData("' . $dn . 'twuser", "' . escapehtml($accessTokens[$xx]->user_id) . '");' . "\n";
		}
		return $str;
	}

	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/TwitterHelper.js"></script>';
	}
}

