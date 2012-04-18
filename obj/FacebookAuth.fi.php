<?
// get offline access token for facebook posting

class FacebookAuth extends FormItem {
	function render ($value) {
		global $SETTINGS;
		global $USER;
		
		// NOTE: this form item changes DB values and thus, cannot get it's value from the $value variable
		// if you set value on the form item you will get errors submitting the form
		
		$n = $this->form->name."_".$this->name;
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($USER->getSetting("fb_access_token", false)).'"/>';
		
		// check that the auth token is any good
		$validtoken = fb_hasValidAccessToken();
		
		// These are the required permissions for the app
		$perms = "publish_stream,manage_pages";
		
		$str .= '<div id="'. $n. 'fbdetails">
					<!-- Facebook JS api loads into this div -->
					<div id="fb-root"></div>
					<!-- When connected to facebook, show these options -->
					<div id="'. $n. 'fbconnected" style="border: 1px dotted grey; padding: 5px;'. (($validtoken)? "": "display:none;"). '">
						<div id="'. $n. 'fbuser" style="float:left;"></div>
						<div style="float:left;">
							<!-- Renew button will do a new login request, getting a newer access_token -->'.
							icon_button("Renew this Facebook Authorization", "custom/facebook" ,
								"try { 
									FB.login(handleFbLoginAuthResponse.curry('$n'), {scope: '$perms'});
								} catch (e) { 
									alert('". _L("Could not connect to Facebook:")."' + e); 
								}").
							'<div style="clear: both"></div>
							<!-- Disconnect button will delete the serverside access_token -->'.
							icon_button("Disconnect this Facebook Account", "cross" ,"handleFbLoginAuthResponse('".$n."', null)").'
						</div>
						<div style="clear: both"></div>
					</div>
					<!-- When there is no valid access token, show these options -->
					<div id="'. $n. 'fbdisconnected" style="'. (($validtoken)? "display:none;": ""). '">'.
						icon_button("Connect to Facebook", "custom/facebook", 
							"try { 
								FB.login(handleFbLoginAuthResponse.curry('$n'), {scope: '$perms'});
							} catch (e) { 
								alert('". _L("Could not connect to Facebook:")."' + e); 
							}").'
						<div style="clear: both"></div>
					</div>
					<pre id="'. $n. 'fbdebugdata" style="display:none;"></pre>
				</div>';
		
		return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		global $SETTINGS;
		
		$str = '// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", 
							status: true, 
							cookie: false, 
							xfbml: true,
							oauth: true
					});
					
					// after init, load the user data
					// get current access token details
					new Ajax.Request("ajaxfacebook.php", {
						method:"post",
						parameters: {
							"type": "get",
							"formatdate": "M, jS"},
						onSuccess: function(response) {
							//showDebugData("'.$n.'", response.responseJSON);
							fbLoadUserData("'.$n.'", response.responseJSON.expires_on);
						}
					});
				};
				
				(function() {
					var e = document.createElement("script");
					e.id=\'facebook-jssdk\';
					e.type = "text/javascript";
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("fb-root").appendChild(e);
				}());';
		
		
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '<script type="text/javascript">
				// handle updateing information when the user allows or disallows the facebook application
				function handleFbLoginAuthResponse(formitem, res) {
					//showDebugData(formitem, res);
					var access_token = "";
					var user_id = "";
					if (res != null && res.authResponse) {
						access_token = res.authResponse.accessToken;
						user_id = res.authResponse.userID;
					}
					
					// store access_token value
					var val = $(formitem).value;
					val = access_token;
					$(formitem).value = val;
					
					// if we have an access token. show the appropriate user information
					if (access_token) {
						// ajax request to store data in the db
						new Ajax.Request("ajaxfacebook.php", {
							method:"post",
							parameters: {
								"type": "save",
								"access_token": access_token,
								"fb_user_id": user_id,
								"formatdate": "M, jS"},
							onSuccess: function(response) {
								//showDebugData(formitem, response.responseJSON);
								$(formitem + "fbconnected").setStyle({display: "block"});
								$(formitem + "fbdisconnected").setStyle({display: "none"});
								fbLoadUserData(formitem, response.responseJSON.expires_on);
							}
						});
					} else {
						// no access token, show the connect button
						$(formitem + "fbconnected").setStyle({display: "none"});
						$(formitem + "fbdisconnected").setStyle({display: "block"});
						
						// remove the current user_id/access_token from the db
						new Ajax.Request("ajaxfacebook.php", {
							method:"post",
							parameters: {"type": "delete"}
						});
					}
				}
			
				function fbLoadUserData(formitem, expiresOn) {
					element = $(formitem + "fbuser");
					var access_token = $(formitem).value;
					var loader = new Element("img", { src: "img/ajax-loader.gif" });
					element.update(loader);
					
					// read user data from facebook api
					FB.api("/me", { access_token: access_token }, function(r) {
						//showDebugData(formitem, r);
						if (r && !r.error) {
							var e = new Element("div").insert(
									new Element("div").setStyle({ float: "left" }).insert(
										new Element("img", { 
											src: "https://graph.facebook.com/me/picture?type=square&access_token=" + access_token,
											width: "48",
											height: "48" })
									)
								).insert(
									new Element("div").setStyle({ float: "left", padding: "7px" }).insert(
										new Element("div").setStyle({ "fontWeight": "bold" }).update(r.name.escapeHTML())
									).insert(
										new Element("div", {"id": formitem + "expires"}).update(
											"'._L("Expires:").'&nbsp;" + expiresOn
										)
									)
								);
							
							loader = false;
							element.update(e);
						}
					}); // end facebook api call
					
					// set a timeout (5 sec) to expire the loader gif if the callback never happened
					setTimeout(function(){
						if (loader) {
							$(loader).remove();
							loader = false;
						}
					}, 5000);
				}
				
				function showDebugData(formitem, data) {
					$(formitem + "fbdebugdata").show();
					$(formitem + "fbdebugdata").update(JSON.stringify(data, undefined, 4));
				}
				
				</script>';
		return $str;
	}
}
?>