// facebook page script
/////////////////////////

// Facebook javascript API initialization, pulled from facebook documentation
window.fbAsyncInit = function() {
	FB.init({appId: "'+fbAppId+'",
			status: true,
			cookie: false,
			xfbml: true,
			oauth: true
	});
};

(function() {
	var e = document.createElement("script");
	e.type = "text/javascript";
	e.async = true;
	e.src = document.location.protocol + "//connect.facebook.net/en_US/all.js";
	document.getElementById("fb-root").appendChild(e);
}());

function initFacebook(accesstoken){
		if (accesstoken) {
			$("msgsndr_socialmediafacebookpagerenew").removeClassName("hidden");
		} else {
			$("msgsndr_socialmediafacebookpageconnect").removeClassName("hidden");
		}
		// Observe an authentication update on the document (the auth popup fires this event)
		document.observe("FbAuth:update", function (res) {
			updateFbPages(res.memo.access_token, "msgsndr_socialmediafacebookpage", "msgsndr_socialmediafacebookpagefbpages", false);
		});
		
		// Observe a click on the action links
		$("msgsndr_socialmediafacebookpageall").observe("click", handleActionLink.curry("msgsndr_socialmediafacebookpage", true));
		$("msgsndr_socialmediafacebookpagenone").observe("click", handleActionLink.curry("msgsndr_socialmediafacebookpage", false));
		// Observe event indicating page loading has completed
		$("msgsndr_socialmediafacebookpages").observe("FbPages:update", function (res) {
			if (res.memo.pagesloaded == 0) {
				$("msgsndr_socialmediafacebookpagefbpages").update("There were no authorized posting locations found!<br>Contact your system administrator for assistance.");
				$("msgsndr_socialmediafacebookpageactionlinks").addClassName("hidden");
			} else {
				$("msgsndr_socialmediafacebookpageactionlinks").removeClassName("hidden");
			}
		});
};

// action link all clicked
function handleActionLink(formitem, checkval, event) {
	$$("#" + formitem + "fbpages input").each(function (checkbox) {
		checkbox.checked = checkval;
	});
	handleFbPageChange(formitem, null);
}

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

function updateFbPages(access_token, formitem, container, showrenew) {
	
	var pages = $(formitem).value;
	if (!pages)
		pages = [];
	else
		pages = pages.evalJSON();
	container = $(container);
	connectdiv = $(formitem + "connect");
	renewdiv = $(formitem + "renew");
	actionlinks = $(formitem + "actionlinks");
	
	if (access_token) {
	
		container.removeClassName("hidden");
		actionlinks.removeClassName("hidden");
		connectdiv.addClassName("hidden");
		renewdiv.addClassName("hidden");
		
		// get the authorized pages
		var authpages = $(formitem + "authpages").value
		if (!authpages)
			authpages = {"pages":[], "wall": true};
		else
			authpages = authpages.evalJSON();
		
		container.update();
		
		// add a loading indicator
		$(container).insert(
			new Element("div", { id: formitem + "-pageloading" }).insert(
				new Element("img", { "src": "img/ajax-loader.gif", "alt": "Loading" })
			)
		);
		
		// get user pages
		FB.api("/me/accounts", { access_token: access_token, type: "page" }, function(res) {
			var availablepages = 0;
			if (res.data !== undefined) {
				
				res.data.each(function(account) {
					if (authpages.pages.size() == 0 || (authpages.pages.size() > 0 && authpages.pages.indexOf(account.id) !== -1)) {
						availablepages++;
						var checkbox = addFbPageElement(formitem, container, account, false);
						
						// if the pageid is in our currently selected list of pages, check its checkbox
						if (pages.indexOf(account.id) !== -1)
							checkbox.checked = true;
					}
				});
			} else {
				// no data returned
				container.update(
					new Element("div").setStyle({padding: "5px"}).update(
						"Error encountered trying to get administered pages"));
			}
			
			// get users info if wall posting is allowed
			if (authpages.wall) {
				FB.api("/me", { access_token: access_token }, function (res) {
					if (res !== undefined) {
						availablepages++;
						var checkbox = addFbPageElement(formitem, container, res, true);
							
						// if the pageid is in our currently selected list of pages, check its checkbox
						if (pages.indexOf("me") !== -1)
							checkbox.checked = true;
					} else {
						// no data returned
						container.update(
							new Element("div").setStyle({padding: "5px"}).update(
								"Error encountered trying to get administered pages"));
					}
					// remove the loading icon
					$(formitem + "-pageloading").remove();
					$(formitem).fire("FbPages:update", { pagesloaded: availablepages });
				}); // end fbapi call
			} else {
				// remove the loading icon
				$(formitem + "-pageloading").remove();
				$(formitem).fire("FbPages:update", { pagesloaded: availablepages });
			}
		});
	} else {
		container.addClassName("hidden");
		actionlinks.addClassName("hidden");
		connectdiv.removeClassName("hidden");
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
};

