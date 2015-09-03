TwitterHelper = function () {

	// public
	var TwitterHelper = {
		clearValue: function (formitem) {
			var fi = $(formitem);
			var user_id = fi.value;
			fi.value = "";
			
			// ajax request to remove it from the db
			new Ajax.Request("ajaxtwitter.php",
				{
					method: "post",
					parameters: {
						"type": "delete_access_token",
						"user_id": user_id
					}
				}
			);
			
			// display the connect button
			$(formitem + "twconnected").setStyle({display: "none"});
		},

		loadUserData: function (element, user_id) {
			// NOTE: We are using prototypejs for the DOM work here...
			element = $(element);
			element.update(new Element("img", { src: "img/ajax-loader.gif" }));
			new Ajax.Request("ajaxtwitter.php", {
				method: "get",
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
							}
						);
						var profile_image_box = new Element("div")
							.setStyle({ float: "left" })
							.insert(profile_image);
						var e = new Element("div")
							.setStyle({ width: "250px", height: "50px", float: "left" })
							.insert(profile_image_box);
						var screen_name = new Element("div")
							.setStyle({ "fontWeight": "bold" })
							.update(data.screen_name.escapeHTML());
						var screen_link = new Element("a", {
							href: "http://www.twitter.com/" + data.screen_name.escapeHTML(),
							target: "tw"
						})
							.insert(screen_name);
						var name = new Element("div")
							.setStyle({ color: "grey" })
							.update(data.name.escapeHTML());
						var profile_box = new Element("div")
							.setStyle({ float: "left", padding: "7px" })
							.insert(screen_link)
							.insert(name);
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
	}

	return TwitterHelper;
}();

