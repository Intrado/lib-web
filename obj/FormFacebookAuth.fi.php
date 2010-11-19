<?
// get access token and pageid for facebook posting

class FacebookAuth extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		$fb_authdata = json_decode($value);
		
		// configure facebook app settings
		$fbconfig = array (
			'appId' => $SETTINGS['facebook']['appid'],
			'cookie' => false,
			'secret' => $SETTINGS['facebook']['appsecret']
		);
		
		$facebook = new Facebook($fbconfig);
		$facebook->getSession();
		
		// check that the auth token is any good
		try {
			// get the user's pages
			$fbaccounts = $facebook->api("/me/accounts", array('access_token' => $fb_authdata->access_token));
			
			$user = $facebook->api("/me", array('access_token' => $fb_authdata->access_token));
			
		} catch (FacebookApiException $e) {
			$fbaccounts = array();
			$user = false;
		}
		
		// main details div
		$str .= '<div id="'. $n. 'fbdetails" style="padding-left: 5px;">';
		
		// connected options div
		$str .= '<div id="'. $n. 'fbconnected" style="'. (($user)? "": "display:none;"). '">';
		$str .= '<div id="'. $n. 'fbgreeting">'. _L("You are currently connected to Facebook."). '</div>';
		
		// button to remove access_token
		$str .= button("Disconnect this Facebook Account", "handleFbLoginResponse(null)"). '<div style="clear: both;"></div>';
		
		$str .= '<div style="padding-top: 5px">'. _L("New posts go to this page:"). "</div>";
		
		// for each accounts, get pages and set active page (defaults to 'me')
		$str .= '<select id="'.$n.'fbpageselect" name="'.$n.'fbpageselect" onchange="handleFbPageChange(this)">';
		$value = json_encode(array("id" => "me", "access_token" => $fb_authdata->access_token));
		$str .= '<option value="'. escapehtml($value). '" >'. escapehtml(_L("My Wall")). "</option>";
		if ($fbaccounts) {
			foreach ($fbaccounts["data"] as $account) {
				if ($account["category"] != "Application") {
					$checked = $fb_authdata->pageid == $account["id"];
					$value = json_encode(array("id" => $account["id"], "access_token" => $account["access_token"]));
					$str .= '<option value="'.escapehtml($value).'" '.($checked ? 'selected' : '').' >'.escapehtml($account["name"]).'</option>
						';
				}
			}
		}
		$str .= '</select></div>';
		
		// disconnected options div
		$str .= '<div id="'. $n. 'fbdisconnected" style="'. (($user)? "display:none;": ""). '">';
		
		// Do facebook login to get good auth token
		$perms = "publish_stream,offline_access,manage_pages";
		$str .= button("Connect to Facebook", "FB.login(handleFbLoginResponse, {perms: '$perms'})");
		
		$str .= '</div>';
		
		$str .= '<script type="text/javascript">
		
				// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $fbconfig['appId']. '", status: true, cookie: false, xfbml: true});
				};
				(function() {
					var e = document.createElement("script");
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("'.$n.'fbdetails").appendChild(e);
				}());
		
				// when the page is changed, update the pageid and access_token used to post to it
				function handleFbPageChange(element) {
					element = $(element);
					if (element.value) {
						var val = $("'.$n.'").value.evalJSON();
						var pageinfo = element.value.evalJSON();
						val["pageid"] = pageinfo.id;
						val["page_access_token"] = pageinfo.access_token;
						$("'.$n.'").value = Object.toJSON(val);
					}
				}
				
				// handle updateing information when the user allows or disallows the facebook application
				function handleFbLoginResponse(res) {
					var access_token = false;
					if (res != null && res.session) {
						if (res.perms) {
							// user is logged in and granted some permissions.
							access_token = res.session.access_token;
						}
					}
					
					// if we have an access token. display the disconnect button and pages selection
					if (access_token) {
						// get user pages
						FB.api("/me/accounts", { access_token: access_token }, function(res) {
							var e = $("'.$n.'fbpageselect").update();
							// populate pages selection
							e.insert(new Element("option", { value: "me" }).update("'. escapehtml(_L("My Wall")). '"));
							for (var i=0, l=res.data.length; i<l; i++) {
								var account = res.data[i];
								if (account.id && account.category != "Application") {
									var val = Object.toJSON({ id: account.id, access_token: account.access_token });
									e.insert(new Element("option", { value: val }).update(account.name));
								}
							}
						});
						
						// show the connected info
						$("'.$n.'fbconnected").setStyle({display: "block"});
						$("'.$n.'fbdisconnected").setStyle({display: "none"});
						
						
					} else {
						// no access token, show the disconnected info
						$("'.$n.'fbconnected").setStyle({display: "none"});
						$("'.$n.'fbdisconnected").setStyle({display: "block"});
					}
					
					// store access_token value and default page values
					var val = $("'.$n.'").value.evalJSON();
					val["access_token"] = access_token;
					if (!val["pageid"]) {
						val["pageid"] = "me";
						val["page_access_token"] = access_token;
					}
					$("'.$n.'").value = Object.toJSON(val);
					
				}
				
				</script>';
		return $str;
	}
}
?>