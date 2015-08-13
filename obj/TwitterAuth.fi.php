<?
// get offline access token for twitter posting

class TwitterAuth extends FormItem {
	function render ($value) {
		
		// NOTE: this form item changes DB values and thus, cannot get it's value from the $value variable
		// if you set value on the form item you will get errors submitting the form
		
		$n = $this->form->name . '_' . $this->name;

		$str = $scriptStr = '';
		$twitterTokens = new TwitterTokens();
		$accessTokens = $twitterTokens->getAllAccessTokens();
		if (is_array($accessTokens)) {
			for ($xx = 0; $xx < count($accessTokens); $xx++) {

				// Get the one we're working with; xx is our enumerator for DHTML operations...
				$dn = $n . "_{$xx}";
				$twitter = new Twitter($accessTokens[$xx], false);
				$validToken = $twitter->hasValidAccessToken();

				// Per-twitter account containers
				$str .= '<input id="' . $dn . '" name="' . $dn . '" type="hidden" value="' . escapehtml($accessTokens[$xx]->user_id) . '"/>';
				$str .= '<div id="' . $dn . 'twdetails">';
				$str .= '<div id="' . $dn . 'twconnected" style="border: 1px dotted grey; padding: 5px;' . (($validToken) ? "" : "display:none;") . '">';
				$str .= '<div id="' . $dn . 'twuser"></div>';
				
				// button to remove access_token
				$str .= icon_button("Disconnect this Twitter Account", "custom/twitter" ,"twClearValue('" . $dn . "')");
				$str .= '<div style="clear: both"></div></div>';
			}
		}


		// Do twitter login to get good auth token
		$str .= submit_button(_L('Connect to Twitter'), 'twitterauth', 'custom/twitter');

		$str .= '<div style="clear: both"></div></div></div>';
		return $str;
	}

	function renderJavascript($value) {
		$n = $this->form->name . '_' . $this->name;

		$str = '';
		$twitterTokens = new TwitterTokens();
		$accessTokens = $twitterTokens->getAllAccessTokens();
		if (is_array($accessTokens)) {
			for ($xx = 0; $xx < count($accessTokens); $xx++) {

				// Get the one we're working with; xx is our enumerator for DHTML operations...
				$dn = $n . "_{$xx}";
				$str .= 'twLoadUserData("' . $dn . 'twuser", "' . escapehtml($accessTokens[$xx]->user_id) . '");' . "\n";
			}
		}
		return $str;
	}

	function renderJavascriptLibraries() {
		return '
			<script type="text/javascript">
			function twClearValue(formitem) {
				var fi = $(formitem);
				var user_id = fi.value;
				fi.value = "";
				
				// ajax request to remove it from the db
				new Ajax.Request("ajaxtwitter.php",
					{
						method:"post",
						parameters: {
							"type": "delete_access_token",
							"user_id": user_id
						}
					}
				);
				
				// display the connect button
				$(formitem + "twconnected").setStyle({display: "none"});
			}
			
			function twLoadUserData(element, user_id) {
				element = $(element);
				element.update(new Element("img", { src: "img/ajax-loader.gif" }));
				new Ajax.Request("ajaxtwitter.php", {
					method:"get",
					parameters: {
						"type": "user",
						"user_id": user_id
					},
					onSuccess: function(r) {
						var data = r.responseJSON;
						if (data) {
							var profile_image = new Element("img", { 
								src: data.profile_image_url_https,
								width: "48",
								height: "48"
							})
							var profile_image_box = new Element("div").setStyle({ float: "left" }).insert(profile_image);
							var e = new Element("div").insert(profile_image_box);
							var screen_name = new Element("div").setStyle({ "fontWeight": "bold" }).update(data.screen_name.escapeHTML());
							var name = new Element("div").setStyle({ color: "grey" }).update(data.name.escapeHTML());
							var profile_box = new Element("div").setStyle({ float: "left", padding: "7px" }).insert(screen_name).insert(name);
							e.insert(profile_box);
							element.update(e);
						} 
						else {
							element.update();
						}
					},
					onFailure: function() {
						element.update();
					}
				});
			}
		</script>';
	}
}

?>
