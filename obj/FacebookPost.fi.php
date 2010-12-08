<?
// get access token and pageid for facebook posting

class FacebookPost extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		// { message: <text>, page: { <pageid>: <token>, <pageid>: <token>, ... } }
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />';
		
		$fbMaxChars = 420;
		
		$fb_data = json_decode($value);
		$message = (isset($fb_data->message))?$fb_data->message:$this->args['message'];
		
		// main details div
		$str .= '<div id="'. $n. 'fbdetails">';
		
		// facebook js api loads into this div
		$str .= '<div id="fb-root"></div>';
		
		// show pages div
		$str .= '<div id="'. $n. 'fbpages" class="radiobox">
					<img src="img/ajax-loader.gif" alt="'. escapehtml(_L("Loading")). '"/>
				</div>';
		
		// show text area for post message
		$str .= '<div id="'.$n.'fbmessage">
			<textarea id="'.$n.'fbmessagetext" rows=10 cols=50>'. escapehtml($message). '</textarea>
			<div id="'.$n.'charsleft">'.escapehtml(_L('Characters remaining')). ':&nbsp;'. ( $fbMaxChars - mb_strlen($message)). '</div></div>';
		
		$str .= '</div>';
		
		$str .= '<script type="text/javascript">
		
				// init the value of the hidden form item
				var val = $("'.$n.'").value;
				if (val == "")
					$("'.$n.'").value = Object.toJSON({ message: "", page: {} });

				// Facebook javascript API initialization, pulled from facebook documentation
				window.fbAsyncInit = function() {
					FB.init({appId: "'. $SETTINGS['facebook']['appid']. '", status: true, cookie: false, xfbml: true});
					
					// load the initial list of pages if possible
					updateFbPages("'.$this->args['access_token'].'", "'.$n.'", "'.$n.'fbpages");
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
		
				// when a facebook page is checked/unchecked, update the pageid and access_token used to post to it
				function handleFbPageChange(formitem, event) {
					element = $(event.element());
					
					// get the value of the checked box and store it in the hidden form item
					var val = $(formitem).value.evalJSON();
					var pageinfo = element.value.evalJSON();
					var pages = $H(val.page);
					if (element.checked) {
						pages.set(pageinfo.id, pageinfo.access_token);
					} else {
						pages.unset(pageinfo.id);
					}
					val.page = pages;
					
					// get the text area and store it too
					val.message = $(formitem+"fbmessagetext").value;
					
					$(formitem).value = Object.toJSON(val);
					form_do_validation($(formitem).up("form"), $(formitem));
				}
				
				function updateFbPages(access_token, formitem, container) {
					
					var val = $(formitem).value.evalJSON();
					var pages = $H(val.page);
					
					if (access_token) {
					
						// display loading gif while we get the users accounts
						$(container).update(new Element("img", { src: "img/ajax-loader.gif" }));
						
						// get user pages
						FB.api("/me/accounts", { access_token: access_token, type: "page" }, function(res) {
							if (res.data !== undefined) {
								$(container).update();
								// if there are more than 8 facebook pages in the response data, set the div to scroll
								if (res.data.size() > 8)
									$(container).setStyle({ height: "150px", overflow: "auto" });
								// populate pages selection
								$(container).insert(
										new Element("input", { 
											type: "checkbox", 
											value: Object.toJSON({ id: "me", "access_token": access_token }), 
											name: "me",
											checked: ((pages.get("me"))?true:false) }
										).observe(
											"change",handleFbPageChange.curry(formitem)
										).observe(
											"click",handleFbPageChange.curry(formitem)
										).observe(
											"blur",handleFbPageChange.curry(formitem)
										).observe(
											"focus",handleFbPageChange.curry(formitem))
									).insert(
										new Element("label", { "for": "me" }).insert("'. escapehtml(_L('My Wall')). '")
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
												checked: ((pages.get(account.id))?true:false) }
											).observe(
												"change",handleFbPageChange.curry(formitem)
											).observe(
												"click",handleFbPageChange.curry(formitem)
											).observe(
												"blur",handleFbPageChange.curry(formitem)
											).observe(
												"focus",handleFbPageChange.curry(formitem))
										).insert(
											new Element("label", { "for": account.id }).insert(account.name)
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
				
				</script>';
		return $str;
	}
}
?>