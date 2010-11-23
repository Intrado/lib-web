<?
// get access token and pageid for facebook posting

class FacebookPages extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
		
		$fb_data = json_decode($value);
		
		// main details div
		$str .= '<div id="'. $n. 'fbdetails" style="padding-left: 5px;">';
		
		// facebook js api loads into this div
		$str .= '<div id="fb-root"></div>';
		
		// check that the auth token is any good
		if (fb_hasValidAccessToken()) {
			$validtoken = true;
		} else {
			$validtoken = false;
		}
		
		// show pages div
		$str .= '<div id="'. $n. 'fbpages" class="radiobox" style="'. (($validtoken)? "": "display:none;"). '">
					<img src="img/ajax-loader.gif" alt="'. escapehtml(_L("Loading")). '"/>
				</div>';
	
		// show connect button div
		$str .= '<div id="'. $n. 'fbconnect" style="float: left;'. (($validtoken)? "display:none;": ""). '">';
		$perms = "publish_stream,offline_access,manage_pages";
		$str .= button("Connect to Facebook", "FB.login(handleFbLoginPagesResponse.curry('".$n."'), {perms: '$perms'})");
		$str .= '</div></div>';
		
		$str .= '<script type="text/javascript">
		
				// init the value of the hidden form item
				var val = $("'.$n.'").value.evalJSON();
				if (!val.page || val.page.size() == 0) {
					val.page = new Hash({});
					$("'.$n.'").value = Object.toJSON(val);
				}
				
				// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
					
					// load the initial list of pages if possible
					updateFbPages("'.$n.'", "'.$n.'fbpages", "'. $fb_data->access_token. '");
				};
				(function() {
					var e = document.createElement("script");
					e.type = "text/javascript";
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("fb-root").appendChild(e);
				}());
		
				// when the page is changed, update the pageid and access_token used to post to it
				function handleFbPageChange(formitem, element) {
					element = $(element);
					
					var val = $(formitem).value.evalJSON();
					var pageinfo = element.value.evalJSON();
					var pages = $H(val.page);
					if (element.checked) {
						pages.set(pageinfo.id, pageinfo.access_token);
					} else {
						pages.unset(pageinfo.id);
					}
					val.page = pages;
					$(formitem).value = Object.toJSON(val);
				}
				
				function updateFbPages(formitem, container, access_token) {
					
					if (access_token) {
						
						// display loading gif while we get the users accounts
						$(container).update(new Element("img", { src: "img/ajax-loader.gif" }));
						
						// get user pages
						try {
							FB.api("/me/accounts", { access_token: access_token, type: "page" }, function(res) {
								if (res.data != undefined) {
									$(container).update();
									// populate pages selection
									$(container).insert(
											new Element("input", { 
												type: "checkbox", 
												value: Object.toJSON({ id: "me", access_token: access_token }), 
												name: "me",
												onchange: "handleFbPageChange(\'"+formitem+"\', this);" })
										).insert(
											new Element("label", { for: "me" }).insert("'. escapehtml(_L('My Wall')). '")
										).insert(
											new Element("br")
									);
									res.data.each(function(account) {
										var val = Object.toJSON({ id: account.id, access_token: account.access_token });
										$(container).insert(
												new Element("input", { 
													type: "checkbox", 
													value: val, 
													name: account.id,
													onchange: "handleFbPageChange(\'"+formitem+"\', this);" })
											).insert(
												new Element("label", { for: account.id }).insert(account.name)
											).insert(
												new Element("br")
										);
									});
								}
							});
						} catch (e) {
							// do nothing
						}
					}
				}
				
				// handle updateing information when the user allows or disallows the facebook application
				function handleFbLoginPagesResponse(formitem, res) {
					var access_token = false;
					if (res != null && res.session) {
						if (res.perms) {
							// user is logged in and granted some permissions.
							access_token = res.session.access_token;
						}
					}
					
					// if we have an access token. display the pages selection
					if (access_token) {
						$(formitem + "fbpages").setStyle({display: "block"});
						$(formitem + "fbconnect").setStyle({display: "none"});
						updateFbPages(formitem, formitem + "fbpages", access_token);
					} else {
						// no access token, show the connect button
						$(formitem + "fbpages").setStyle({display: "none"});
						$(formitem + "fbconnect").setStyle({display: "block"});
					}
					
					// store access_token value
					var val = $(formitem).value.evalJSON();
					val.access_token = access_token;
					$(formitem).value = Object.toJSON(val);
					
				}
				
				</script>';
		return $str;
	}
}
?>