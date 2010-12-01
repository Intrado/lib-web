<?
// get access token and pageid for facebook posting

class FacebookPost extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		// { access_token: <token>, message: <text>, page: { <pageid>: <token>, <pageid>: <token>, ... } }
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
		
		$fbMaxChars = 420;
		
		$fb_data = json_decode($value);
		
		// main details div
		$str .= '<div id="'. $n. 'fbdetails" style="padding-left: 5px;">';
		
		// facebook js api loads into this div
		$str .= '<div id="fb-root"></div>';
		
		// check that the auth token is any good
		if ($fb_data->access_token && fb_hasValidAccessToken($fb_data->access_token)) {
			$validtoken = true;
		} else {
			$validtoken = false;
		}
		
		// show pages div
		$str .= '<div id="'. $n. 'fbpages" class="radiobox" style="'. (($validtoken)? "": "display:none;"). '">
					<img src="img/ajax-loader.gif" alt="'. escapehtml(_L("Loading")). '"/>
				</div>';
	
		// show connect button div
		$str .= '<div id="'. $n. 'fbconnect" style="'. (($validtoken)? "display:none;": ""). '">';
		$perms = "publish_stream,offline_access,manage_pages";
		$str .= button("Connect to Facebook", 
			"try { 
				FB.login(handleFbLoginPagesResponse.curry('$n'), {perms: '$perms'});
			} catch (e) { 
				alert('". _L("Could not connect to Facebook")."');
			}");
		
		$str .= '</div>';
		
		// show text area for post message
		$str .= '<div id="'.$n.'fbmessage" style="'. (($validtoken)? "": "display:none;"). '">
			<textarea id="'.$n.'fbmessagetext" rows=10 cols=50>'. escapehtml($fb_data->message). '</textarea>
			<div id="'.$n.'charsleft">'.escapehtml(_L('Characters remaining')). ':&nbsp;'. ( $fbMaxChars - mb_strlen($fb_data->message)). '</div></div>';
		
		$str .= '</div>';
		
		$str .= '<script type="text/javascript">
		
				// init the value of the hidden form item
				var val = $("'.$n.'").value.evalJSON();
				if (Object.isArray(val.page)) {
					val.page = new Hash({});
					$("'.$n.'").value = Object.toJSON(val);
				}

				// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
					
					// load the initial list of pages if possible
					updateFbPages("'.$n.'", "'.$n.'fbpages");
				};
				(function() {
					var e = document.createElement("script");
					e.type = "text/javascript";
					e.async = true;
					e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
					document.getElementById("fb-root").appendChild(e);
				}());
				
				// observe changes to the textarea
				$("'.$n.'fbmessagetext").observe("change", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("blur", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("keyup", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("focus", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("click", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));

				var fbMessage_keyupTimer = null;
				function fbMessage_storedata(formitem, maxchars, event) {
					var form = event.findElement("form");
					
					// if there is a running timer for storing the message text, clear it.
					if (fbMessage_keyupTimer) {
						window.clearTimeout(fbMessage_keyupTimer);
					}
					
					// update the character counter
					form_count_field_characters(maxchars, formitem + "charsleft", event);
					
					// set a timer to store the message text
					fbMessage_keyupTimer = window.setTimeout(function () {
							var val = $(formitem).value.evalJSON();
							val.message = $(formitem+"fbmessagetext").value;
							$(formitem).value = Object.toJSON(val);
							form_do_validation(form, $(formitem));
						},
						event.type == "keyup" ? 300 : 100
					);
				}
		
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
					form_do_validation($(formitem).up("form"), $(formitem));
				}
				
				function updateFbPages(formitem, container) {
					
					var val = $(formitem).value.evalJSON();
					var pages = $H(val.page);
					
					if (val.access_token) {
					
						// display loading gif while we get the users accounts
						$(container).update(new Element("img", { src: "img/ajax-loader.gif" }));
						
						// get user pages
						FB.api("/me/accounts", { access_token: val.access_token, type: "page" }, function(res) {
							if (res.data != undefined) {
								$(container).update();
								// populate pages selection
								$(container).insert(
										new Element("input", { 
											type: "checkbox", 
											value: Object.toJSON({ id: "me", access_token: val.access_token }), 
											name: "me",
											onchange: "handleFbPageChange(\'"+formitem+"\', this);",
											checked: ((pages.get("me"))?true:false) })
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
												onchange: "handleFbPageChange(\'"+formitem+"\', this);",
												checked: ((pages.get(account.id))?true:false) })
										).insert(
											new Element("label", { for: account.id }).insert(account.name)
										).insert(
											new Element("br")
									);
								});
							} else {
								// no data returned
								$(container).update(
									new Element("div").setStyle({padding: "5px"}).update(
										"'. escapehtml(_L('Error encountered trying to get administered pages')). '"));
							}
						});
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
					
					// store access_token value
					var val = $(formitem).value.evalJSON();
					val.access_token = access_token;
					$(formitem).value = Object.toJSON(val);
					
					// if we have an access token. display the pages selection
					if (access_token) {
						$(formitem + "fbpages").setStyle({display: "block"});
						$(formitem + "fbconnect").setStyle({display: "none"});
						updateFbPages(formitem, formitem + "fbpages");
						$(formitem + "fbmessage").setStyle({display: "block"});
						
					} else {
						// no access token, show the connect button
						$(formitem + "fbpages").setStyle({display: "none"});
						$(formitem + "fbconnect").setStyle({display: "block"});
						$(formitem + "fbmessage").setStyle({display: "none"});
					}
					
				}
				
				</script>';
		return $str;
	}
}
?>