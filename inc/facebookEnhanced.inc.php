<?

class FacebookEnhanced extends Facebook {
	
	// taken and slightly modified from http://stackoverflow.com/questions/8982025/how-to-extend-access-token-validity-since-offline-access-deprecation
	public function getExtendedAccessToken($accessToken){
		try {
			// need to circumvent json_decode by calling _oauthRequest
			// directly, since response isn't JSON format.
			$access_token_response =
			$this->_oauthRequest(
			$this->getUrl('graph', '/oauth/access_token'), array(
					'client_id' => $this->getAppId(),
					'client_secret' => $this->getApiSecret(),
					'grant_type'=>'fb_exchange_token',
					'fb_exchange_token'=>$accessToken));
	
		} catch (FacebookApiException $e) {
		// most likely that user very recently revoked authorization.
		// In any event, we don't have an access token, so say so.
			return array(false, false);
		}

		if (empty($access_token_response)) {
			return array(false, false);
		}

		$response_params = array();
		parse_str($access_token_response, $response_params);
		if (!isset($response_params['access_token'])) {
			return array(false, false);
		}
		
		return array($response_params['access_token'], $response_params['expires']);
	}
	
}