<?
// get message and pageid for facebook posting

class FacebookPost extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		$fbMaxChars = (isset($this->args['maxchars'])?$this->args['maxchars']:420);
	
		$hidedetails = false;
		if ($value == "disabled")
			$hidedetails = true;
		
		// { message: <text>, page: [ <pageid>, <pageid>, ... ] }
		if (!$value || $value == "disabled")
			$value = '{"message": "", "page": []}';
		
		$message = "";
		if (isset($this->args['message']))
			$message = $this->args['message'];
		
		// keeping track of the authorized pages
		$pages = array("pages" => getFbAuthorizedPages(), "wall" => getSystemSetting("fbauthorizewall"));
		
		// main details div
		$str = '
			<style>
				.fbpagelist {
					width: 40%;
					border: 1px dotted gray;
					padding: 3px;
					float: left;
					height: 150px;
					overflow: auto;
				}
				.fbtextarea {
					float: left;
					margin-right: 15px;
					width: 50%;
				}
				.fbtextarea textarea {
					height: 150px;
					width: 100%;
				}
				.fbname {
					font-weight: bold;
				}
				.fbimg {
					padding: 3px;
					float: left;
				}
			</style>
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
			<input id="'.$n.'authpages" name="'.$n.'authpages" type="hidden" value="'.escapehtml(json_encode($pages)).'" />
			<input id="'.$n.'fbenable" name="'.$n.'fbenable" type="checkbox" '. ($hidedetails?"":"checked") .' /><label for="'.$n.'fbenable">'. _L("Post a message to Facebook") .'</label>
			<div id="'. $n. 'fbdetails" style="display:'. ($hidedetails?"none":"block") .'">
				<div id="fb-root"></div>
				<div id="'.$n.'fbmessage" class="fbtextarea">
					<textarea id="'.$n.'fbmessagetext">'. escapehtml($message). '</textarea>
					<div id="'.$n.'charsleft">'.escapehtml(_L('Characters remaining')). ':&nbsp;'. ( $fbMaxChars - mb_strlen($message)). '</div>
				</div>
				<div id="'. $n. 'fbpages" class="fbpagelist">
					<img src="img/ajax-loader.gif" alt="'. escapehtml(_L("Loading")). '"/>
				</div>
			</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		global $SETTINGS;
		$n = $this->form->name."_".$this->name;
		$fbMaxChars = (isset($this->args['maxchars'])?$this->args['maxchars']:420);
		
		$str = '// Facebook javascript API initialization, pulled from facebook documentation
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
				
				// observe the enable checkbox
				$("'.$n.'fbenable").observe("change", fbEnable.curry("'.$n.'"));
				$("'.$n.'fbenable").observe("blur", fbEnable.curry("'.$n.'"));
				$("'.$n.'fbenable").observe("keyup", fbEnable.curry("'.$n.'"));
				$("'.$n.'fbenable").observe("focus", fbEnable.curry("'.$n.'"));
				$("'.$n.'fbenable").observe("click", fbEnable.curry("'.$n.'"));
				
				// observe changes to the textarea
				$("'.$n.'fbmessagetext").observe("change", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("blur", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("keyup", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("focus", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				$("'.$n.'fbmessagetext").observe("click", fbMessage_storedata.curry("'.$n.'", "'.$fbMaxChars.'"));
				
				var fbMessage_keyupTimer = null;';
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '<script type="text/javascript">
			function fbEnable(formitem, event) {
				var e = event.element();
				if (e.checked) {
					$(formitem + "fbdetails").show();
					handleFbDataChange(formitem, event);
				} else {
					$(formitem + "fbdetails").hide();
					$(formitem).value = "disabled";
					form_do_validation($(formitem).up("form"), $(formitem));
				}
			}
			
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
						handleFbDataChange(formitem, event);
					},
					event.type == "keyup" ? 300 : 100
				);
			}
	
			// when a facebook page is checked/unchecked, update the pageid and access_token used to post to it
			function handleFbDataChange(formitem, event) {
				// get the value of the checked boxs and store in the hidden form item
				var pages = $A();
				
				$$("#" + formitem + "fbpages input").each(function (checkbox) {
					if (checkbox.checked)
						pages[pages.size()] = checkbox.value;
				});
				
				$(formitem).value = Object.toJSON({ message: $(formitem+"fbmessagetext").value, page: pages });
				form_do_validation($(formitem).up("form"), $(formitem));
			}
			
			function updateFbPages(access_token, formitem, container) {
				
				var val = $(formitem).value.evalJSON();
				var pages = $A(val.page);
				
				if (access_token) {
				
					// get the authorized pages
					var authpages = $(formitem + "authpages").value.evalJSON();
					
					$(container).update();
					
					// get users info if wall posting is allowed
					if (authpages.wall) {
						FB.api("/me", { access_token: access_token }, function (res) {
							if (res !== undefined) {
								var checkbox = addFbPageElement(formitem, container, res, true);
									
								// if the pageid is in our currently selected list of pages, check its checkbox
								if (pages.indexOf("me") !== -1)
									checkbox.checked = true;
							}
						}); // end fbapi call
					}
					// get user pages
					FB.api("/me/accounts", { access_token: access_token, type: "page" }, function(res) {
						if (res.data !== undefined) {
							
							res.data.each(function(account) {
								if (authpages.pages.size() == 0 || (authpages.pages.size() > 0 && authpages.pages.indexOf(account.id) !== -1)) {
								
									var checkbox = addFbPageElement(formitem, container, account, false);
									
									// if the pageid is in our currently selected list of pages, check its checkbox
									if (pages.indexOf(account.id) !== -1)
										checkbox.checked = true;
								}
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

			// get an account element with all the facebook page info, returns the checkbox
			function addFbPageElement(e, container, account, iswall) {
				if (iswall) {
					var name = "My Wall";
					var category = "";
					var id = "me";
				} else {
					var name = account.name.escapeHTML();
					var category = account.category.escapeHTML();
					var id = account.id;
				}
				
				var checkbox = new Element("input", { "type": "checkbox", "value": id, "id": id, "name": id });
				var pageimage = new Element("img", { "class": "fbimg", "src": "https://graph.facebook.com/"+ account.id +"/picture?type=square" });
				var accountitem = new Element("div").insert(
						checkbox.setStyle({ "float": "left" })
					).insert(
						new Element("label", { "for": id, title: id }).insert(
							pageimage
						).insert(
							new Element("div").insert(
								new Element("div", { "class": "fbname" }).update(name)
							).insert(
								new Element("div", { "class": "fbcategory" }).update(category))
					));
				$(container).insert(accountitem);
				$(container).insert(new Element("div").setStyle({ "clear": "both"}));
				// observe changes to the checkbox state
				checkbox.observe(
						"change",handleFbDataChange.curry(e)
					).observe(
						"click",handleFbDataChange.curry(e)
					).observe(
						"blur",handleFbDataChange.curry(e)
					).observe(
						"focus",handleFbDataChange.curry(e));
				// IE doesnt work with images in labels, work around that
				if (Prototype.Browser.IE) {
					pageimage.observe("click", function (event) {
						var parentcheck = $(event.element().up().title);
						if (parentcheck.checked)
							parentcheck.checked = false;
						else
							parentcheck.checked = true;
						handleFbDataChange(e,event);
					});
				}
				return checkbox;
			}
			</script>';
		return $str;
	}
}
?>