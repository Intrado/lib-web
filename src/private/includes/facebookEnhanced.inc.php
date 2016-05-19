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
			error_log("FacebookEnhanced error getting extended access token:". $e);
			return array(false, false);
		}

		if (empty($access_token_response)) {
			error_log("FacebookEnhanced error getting extended access token: Empty response.");
			return array(false, false);
		}

		$response_params = array();
		parse_str($access_token_response, $response_params);
		if (!isset($response_params['access_token'])) {
			error_log("FacebookEnhanced error getting extended access token: No token set in response.");
			return array(false, false);
		}
		
		if (!isset($response_params['expires'])) {
			#error_log("FacebookEnhanced error getting extended access token: No expires set in response. RAW response=[" . $access_token_response . "]");
			// assume 60day extended access token (though it appears this token doesn't expire!)
			$response_params['expires'] = 5184000;
		}
		
		return array($response_params['access_token'], $response_params['expires']);
	}
	
}
