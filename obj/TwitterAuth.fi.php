<?
// get offline access token for twitter posting

class TwitterAuth extends FormItem {
	function render ($value) {
		global $SETTINGS;
		global $USER;
		
		$n = $this->form->name."_".$this->name;
		
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'"/>';
		
		// Page should have checked the auth token already and passed true/false
		$validtoken = $value;
		
		// main details div
		$str .= '<div id="'. $n. 'twdetails">';
		
		// connected options div
		$str .= '<div id="'. $n. 'twconnected" style="border: 1px dotted grey; padding: 5px;'. (($validtoken)? "": "display:none;"). '">';
		
		$str .= '<div id="'. $n. 'twuser"></div>';
		
		// button to remove access_token
		$str .= icon_button("Disconnect this Twitter Account", "twitter" ,"twClearValue('".$n."')");
		
		$str .= '<div style="clear: both"></div></div>';
		
		// disconnected options div
		$str .= '<div id="'. $n. 'twdisconnected" style="'. (($validtoken)? "display:none;": ""). '">';
		
		// Do twitter login to get good auth token
		$thispage = substr($_SERVER["SCRIPT_NAME"], strrpos($_SERVER["SCRIPT_NAME"], "/") + 1);
		$str .= icon_button("Connect to Twitter", "twitter", "", "twitterauth.php/$thispage");
		
		$str .= '<div style="clear: both"></div></div></div>';
		
		$str .= '<script type="text/javascript">
		
			if ('. (($validtoken)?"true":"false"). ') {
				twLoadUserData("'. $n. 'twuser");
			}
		
			function twClearValue(formitem) {
				$(formitem).value = "";
				
				// display the connect button
				$(formitem + "twconnected").setStyle({display: "none"});
				$(formitem + "twdisconnected").setStyle({display: "block"});
				
			}
			
			function twLoadUserData(element) {
				element = $(element);
				element.update(new Element("img", { src: "img/ajax-loader.gif" }));
				
				new Ajax.Request("ajaxtwitter.php", {
					method:"post",
					parameters: {
						"type": "user"
					},
					onSuccess: function(r) {
						var data = r.responseJSON;
						// TODO: image url is http not https, need to convert it
						var imgurlparts = data.profile_image_url.split("://");
						var imgurl = "https://" + imgurlparts[1];
						
						// NOTE: Above doesnt work because twitter is using amazon for storage and the ssl cert doesnt 
						// match the url
						
						var e = new Element("div").insert(
								new Element("div").setStyle({ float: "left" }).insert(
									new Element("img", { 
										src: data.profile_image_url,
										width: "48",
										height: "48" })
								)
							).insert(
								new Element("div").setStyle({ float: "left", padding: "7px" }).insert(
									new Element("div").setStyle({ "fontWeight": "bold" }).update(data.screen_name)
								).insert(
									new Element("div").setStyle({ color: "grey" }).update(data.name)
								)
							);
						
						element.update(e);
					}
				});
			}
			</script>';
		
		return $str;
	}
}

?>