<?
class FacebookPage extends FormItem {
	function render ($value) {
		global $SETTINGS;
		
		$n = $this->form->name."_".$this->name;
		
		// [ <pageid>, <pageid>, ... ]
		if (!$value)
			$value = json_encode(array());
		
		// keeping track of the authorized pages
		$pages = array("pages" => getFbAuthorizedPages(), "wall" => getSystemSetting("fbauthorizewall"));
		
		// main details div
		$str = '
			<style>
				.fbpagelist {
					width: 98%;
					border: 1px dotted gray;
					padding: 3px;
					max-height: 250px;
					overflow: auto;
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
			<div id="fb-root"></div>
			<div id="'. $n. 'fbpages" class="fbpagelist">
				<img src="img/ajax-loader.gif" alt="'. escapehtml(_L("Loading")). '"/>
			</div>';
		
		return $str;
	}
	
	function renderJavascript($value) {
		global $SETTINGS;
		$n = $this->form->name."_".$this->name;
		
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
				}());';
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '<script type="text/javascript">
	
			// when a facebook page is checked/unchecked, update the pageid and access_token used to post to it
			function handleFbPageChange(formitem, event) {
				// get the value of the checked boxs and store in the hidden form item
				var pages = $A();
				
				$$("#" + formitem + "fbpages input").each(function (checkbox) {
					if (checkbox.checked)
						pages[pages.size()] = checkbox.value;
				});
				
				$(formitem).value = Object.toJSON(pages);
				form_do_validation($(formitem).up("form"), $(formitem));
			}
			
			function updateFbPages(access_token, formitem, container) {
				
				var pages = $(formitem).value.evalJSON();
				
				if (access_token) {
				
					// get the authorized pages
					var authpages = $(formitem + "authpages").value.evalJSON();
					
					$(container).update();
					
					// add a loading indicator
					$(container).insert(
						new Element("div", { id: formitem + "-pageloading" }).insert(
							new Element("img", { "src": "img/ajax-loader.gif", "alt": "Loading" })
						)
					);
					
					// get users info if wall posting is allowed
					if (authpages.wall) {
						FB.api("/me", { access_token: access_token }, function (res) {
							if (res !== undefined) {
								var checkbox = addFbPageElement(formitem, container, res, true);
									
								// if the pageid is in our currently selected list of pages, check its checkbox
								if (pages.indexOf("me") !== -1)
									checkbox.checked = true;
							} else {
								// no data returned
								$(container).update(
									new Element("div").setStyle({padding: "5px"}).update(
										"'. escapehtml(_L('Error encountered trying to get administered pages')). '"));
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
						// remove the loading icon
						$(formitem + "-pageloading").remove();
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
						"change",handleFbPageChange.curry(e)
					).observe(
						"click",handleFbPageChange.curry(e)
					).observe(
						"blur",handleFbPageChange.curry(e)
					).observe(
						"focus",handleFbPageChange.curry(e));
				// IE doesnt work with images in labels, work around that
				if (Prototype.Browser.IE) {
					pageimage.observe("click", function (event) {
						var parentcheck = $(event.element().up().title);
						if (parentcheck.checked)
							parentcheck.checked = false;
						else
							parentcheck.checked = true;
						handleFbPageChange(e,event);
					});
				}
				return checkbox;
			}
			</script>';
		return $str;
	}
}
?>