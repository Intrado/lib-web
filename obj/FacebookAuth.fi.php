<?
// get access token and pageid for facebook posting

class FacebookAuth extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		// check that the auth token is any good
		if ($value && fb_hasValidAccessToken($value)) {
			$validtoken = true;
		} else {
			$validtoken = false;
		}
		
		// main details div
		$str .= '<div id="'. $n. 'fbdetails" style="padding-left: 5px;">';
		
		// facebook js api loads into this div
		$str .= '<div id="fb-root"></div>';
		
		// connected options div
		$str .= '<div id="'. $n. 'fbconnected" style="float: left;'. (($validtoken)? "": "display:none;"). '">';
		$str .= '<div id="'. $n. 'fbgreeting">'. _L("You are currently connected to Facebook."). '</div>';
		
		// button to remove access_token
		$str .= icon_button("Disconnect this Facebook Account", "facebook" ,"handleFbLoginAuthResponse('".$n."', null)");
		
		$str .= "</div>";
		
		// disconnected options div
		$str .= '<div id="'. $n. 'fbdisconnected" style="float: left;'. (($validtoken)? "display:none;": ""). '">';
		
		// Do facebook login to get good auth token
		$perms = "publish_stream,offline_access,manage_pages";
		$str .= icon_button("Connect to Facebook", "facebook", 
			"try { 
				FB.login(handleFbLoginAuthResponse.curry('$n'), {perms: '$perms'});
			} catch (e) { 
				alert('". _L("Could not connect to Facebook")."'); 
			}");
			
		$str .= '</div></div>';
		
		$str .= '<script type="text/javascript">
		
				// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
				};
				(function() {
					var e = document.createElement("script");
					e.type = "text/javascript";
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("fb-root").appendChild(e);
				}());
				
				// handle updateing information when the user allows or disallows the facebook application
				function handleFbLoginAuthResponse(formitem, res) {
					var access_token = false;
					if (res != null && res.session) {
						if (res.perms) {
							// user is logged in and granted some permissions.
							access_token = res.session.access_token;
						}
					}
					
					// if we have an access token. display the pages selection
					if (access_token) {
						$(formitem + "fbconnected").setStyle({display: "block"});
						$(formitem + "fbdisconnected").setStyle({display: "none"});
					} else {
						// no access token, show the connect button
						$(formitem + "fbconnected").setStyle({display: "none"});
						$(formitem + "fbdisconnected").setStyle({display: "block"});
					}
					
					// store access_token value
					var val = $(formitem).value;
					val = access_token;
					$(formitem).value = val;
					
				}
				
				</script>';
		return $str;
	}
}
?>