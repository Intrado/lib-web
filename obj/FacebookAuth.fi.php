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
		
		// main details div
		$str .= '<div id="'. $n. 'fbdetails">';
		
		// facebook js api loads into this div
		$str .= '<div id="fb-root"></div>';
		
		// connected options div
		$str .= '<div id="'. $n. 'fbconnected" style="border: 1px dotted grey; padding: 5px;'. (($validtoken)? "": "display:none;"). '">';
		
		$str .= '<div id="'. $n. 'fbuser"></div>';
		
		// button to remove access_token
		$str .= icon_button("Disconnect this Facebook Account", "custom/facebook" ,"handleFbLoginAuthResponse('".$n."', null)");
		
		$str .= '<div style="clear: both"></div></div>';
		
		// disconnected options div
		$str .= '<div id="'. $n. 'fbdisconnected" style="'. (($validtoken)? "display:none;": ""). '">';
		
		// Do facebook login to get good auth token
		$perms = "publish_stream,offline_access,manage_pages";
		$str .= icon_button("Connect to Facebook", "custom/facebook", 
			"try { 
				FB.login(handleFbLoginAuthResponse.curry('$n'), {perms: '$perms'});
			} catch (e) { 
				alert('". _L("Could not connect to Facebook")."'); 
			}");
			
		$str .= '<div style="clear: both"></div></div></div>';
		
		return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		global $SETTINGS;
		
		$str = '// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
					
					// after init, load the user data
					fbLoadUserData("'.$n.'");
				};
				(function() {
					var e = document.createElement("script");
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
					var access_token = "";
					if (res != null && res.session) {
						if (res.perms) {
							// user is logged in and granted some permissions.
							access_token = res.session.access_token;
						}
					}
					
					// store access_token value
					var val = $(formitem).value;
					val = access_token;
					$(formitem).value = val;
					
					// ajax request to store data in the db
					new Ajax.Request("ajaxfacebook.php", {
						method:"post",
						parameters: {
							"type": "store_access_token",
							"access_token": access_token}});
					
					// if we have an access token. show the appropriate user information
					if (access_token) {
						$(formitem + "fbconnected").setStyle({display: "block"});
						$(formitem + "fbdisconnected").setStyle({display: "none"});
						fbLoadUserData(formitem);
						// do a request to get the userid and store it in our db
						FB.api("/me", { access_token: access_token }, function(r) {
							if (r && !r.error) {
								// store the current userid in the db
								new Ajax.Request("ajaxfacebook.php", {
									method:"post",
									parameters: {
										"type": "store_user_id",
										"fb_user_id": r.id}
								});
							}
						}); // end facebook api call
					} else {
						// no access token, show the connect button
						$(formitem + "fbconnected").setStyle({display: "none"});
						$(formitem + "fbdisconnected").setStyle({display: "block"});
						// remove the current userid from the db
						new Ajax.Request("ajaxfacebook.php", {
							method:"post",
							parameters: {
								"type": "store_user_id",
								"fb_user_id": ""}
						});
					}
					
					
				}
			
				function fbLoadUserData(formitem) {
					element = $(formitem + "fbuser");
					var access_token = $(formitem).value;
					element.update(new Element("img", { src: "img/ajax-loader.gif" }));
					
					// read user data from facebook api
					var fbLoaded = false;
					FB.api("/me", { access_token: access_token }, function(r) {
						if (r && !r.error) {
							fbLoaded = true;
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
									)
								);
							
							element.update(e);
						}
					}); // end facebook api call
					
					// set a timeout (5 sec) to expire the loader gif if the callback never happened
					setTimeout(function(){
						if (!fbLoaded)
							element.update();
					}, 5000);
				}
				
				</script>';
		return $str;
	}
}
?>