<?
// get offline access token for twitter posting

class TwitterAuth extends FormItem {
	function render ($value) {
		global $SETTINGS;
		global $USER;
		
		// NOTE: this form item changes DB values and thus, cannot get it's value from the $value variable
		// if you set value on the form item you will get errors submitting the form
		
		$n = $this->form->name."_".$this->name;
		
		$twitter = new Twitter($USER->getSetting("tw_access_token", false));
		$validToken = $twitter->hasValidAccessToken();
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($validToken).'"/>';
		
		// main details div
		$str .= '<div id="'. $n. 'twdetails">';
		
		// connected options div
		$str .= '<div id="'. $n. 'twconnected" style="border: 1px dotted grey; padding: 5px;'. (($validToken)? "": "display:none;"). '">';
		
		$str .= '<div id="'. $n. 'twuser"></div>';
		
		// button to remove access_token
		$str .= icon_button("Disconnect this Twitter Account", "custom/twitter" ,"twClearValue('".$n."')");
		
		$str .= '<div style="clear: both"></div></div>';
		
		// disconnected options div
		$str .= '<div id="'. $n. 'twdisconnected" style="'. (($validToken)? "display:none;": ""). '">';
		
		// Do twitter login to get good auth token
		if (isset($this->args["submit"])) {
			$str .= submit_button(_L("Connect to Twitter"), "twitterauth", "custom/twitter");
		} else {
			$thispage = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);
			$str .= icon_button(_L("Connect to Twitter"), "custom/twitter", "", "twitterauth.php/$thispage");
		}
		
		$str .= '<div style="clear: both"></div></div></div>';
		
		$str .= '<script type="text/javascript">
		
			if ('. (($validToken)?"true":"false"). ') {
				twLoadUserData("'. $n. 'twuser");
			}
		
			function twClearValue(formitem) {
				$(formitem).value = "";
				
				// ajax request to remove it from the db
				new Ajax.Request("ajaxtwitter.php", {
					method:"post",
					parameters: {
						"type": "store_access_token",
						"access_token": false}});
				
				// display the connect button
				$(formitem + "twconnected").setStyle({display: "none"});
				$(formitem + "twdisconnected").setStyle({display: "block"});
				
			}
			
			function twLoadUserData(element) {
				element = $(element);
				element.update(new Element("img", { src: "img/ajax-loader.gif" }));
				
				new Ajax.Request("ajaxtwitter.php", {
					method:"get",
					parameters: {
						"type": "user"
					},
					onSuccess: function(r) {
						var data = r.responseJSON;
						if (data) {
							var e = new Element("div").insert(
									new Element("div").setStyle({ float: "left" }).insert(
										new Element("img", { 
											src: data.profile_image_url_https,
											width: "48",
											height: "48" })
									)
								).insert(
									new Element("div").setStyle({ float: "left", padding: "7px" }).insert(
										new Element("div").setStyle({ "fontWeight": "bold" }).update(data.screen_name.escapeHTML())
									).insert(
										new Element("div").setStyle({ color: "grey" }).update(data.name.escapeHTML())
									)
								);
							
							element.update(e);
						} else {
							element.update();
						}
					},
					onFailure: function() {
						element.update();
					}
				});
			}
			</script>';
		
		return $str;
	}
}

?>