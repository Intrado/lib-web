/**
 * application logic for embedded Message Sender, launched via custom page in PowerSchool
 *
 * Requires:
 * 	"ssoUrl" - js list with relative urls for single sign-on redirection locations (only SchoolMessenger plugins are valid!)
 * 	"pkeyList" - js list of pkeys to add to a list
 * 	"content-msgsndr" - container somewhere in the document where the message sender will go
 *
 * 	@author: nrheckman
 */
(function($){
	// TODO: these urls need to be configurable via the plugin or it's going to be a pain for development and QA
	// extend the styles
	$('head').append('<link rel="stylesheet" href="https://heckvm.testschoolmessenger.com/powerschool/themes/powerschool/embedded.css" type="text/css" />');
	// extend jquery with json plugin
	$('head').append('<script type="text/javascript" src="https://heckvm.testschoolmessenger.com/powerschool/script/jquery.json-2.3.min.js"></script>');

	var container = $("#content-msgsndr");
	// detect multiple plugins and present the user with a choice of which to use
	if (ssoUrl.length > 1) {
		container.html(
			'<div id="selectplugin">' +
				'<h1>New Broadcast</h1>' +
				'<div class="box-round">' +
					'<h2>Select a plugin</h2>' +
					'<ul class="plugins">' +
					'</ul>' +
				'</div>' +
			'</div>'
		);
		$.each(ssoUrl, function(id, url) {
			// TODO: also get name and present that instead of the relative url to load
			var li = $("<li><a href='#'>" + url + "</a></li>");
			container.find("ul").append(li);
			li.on("click", function(event) {
				event.preventDefault();
				var msgsndr = new MessageSender_embedded(url, pkeyList, container);
				msgsndr.init();
			})
		})
	} else {
		// initialize the message sender object. It will auto-load into the form
		var msgsndr = new MessageSender_embedded(ssoUrl[0], pkeyList, container);
		msgsndr.init();
	}

})(jQuery);

function MessageSender_embedded(ssoTarget, pkeyList, container) {
	var $ = jQuery;
	var self = this;

	// TODO: get appropriate subject
	self.subject = "New Message for...";
	self.baseUrl = false;
	self.iframe = $('<iframe class="embedded" height="1px" width="1px" frameborder="0" scrolling="no">');

	self.init = function() {
		// detect if the browser can use HTML5 window.postMessage API (this is REQUIRED!)
		if (top.postMessage == undefined) {
			self.showError('This browser is incompatible with the application being accessed.<br>' +
				'See <a href="http://caniuse.com/#feat=x-doc-messaging">Cross-document messaging compatibility</a> for a browser compatibility list.');
			return;
		}

		// insert fancy loading content and message area
		container.html(
			'<div id="loadingmessage">' +
			'	<h1>New Broadcast</h1>' +
			'	<div id="errormessage" class="hide">' +
			'		<div class="feedback-alert"></div>' +
			'	</div>' +
			'	<div class="box-round progress">' +
			'		<h2>Progress</h2>' +
			'		<ul class="steps">' +
			'			<li id="authenticate" class="waiting">Authenticate</li>' +
			'			<li id="createlist" class="waiting">Create List</li>' +
			'			<li id="uploadpkeys" class="waiting">Upload Selections</li>' +
			'			<li id="launchmsgsndr" class="waiting">Launch Application</li>' +
			'		</ul>' +
			'	</div>' +
			'</div>');

		container.append(self.iframe);
		self.updateProgress("authenticate", "trying", "Authenticating...");
		self.iframe.attr("src", ssoTarget);

		// attach a message listener for communication cross domains
		if (window.addEventListener == undefined)
			window.attachEvent("onmessage", self.onMessage);
		else
			window.addEventListener("message", self.onMessage, false);
	};

	self.onMessage = function(event) {
		// TODO: test origin for valid domains
		//if(e.origin !== 'B'){ return; }

		var data = $.secureEvalJSON(event.data);
		if (data.error != undefined && data.error) {
			// got an error!
			self.showError(data.error);
		} else {
			// resize the iframe
			if (data.resize != undefined && data.resize)
				self.resizeIframe(data.resize);

			// check if we should load a new page
			if (data.custurl != undefined && data.custurl && data.user != undefined && data.user && data.page != undefined && data.page) {
				// update the baseUrl with the origin
				self.baseUrl = event.origin + "/" + data.custurl + "/";

				// we received all the necessary data to indicate pages are loading correctly (and a user is authenticated)
				// if the page loaded is start.php or dashboard.php, precede to the message sender
				if (data.page == "start.php" || data.page == "dashboard.php") {
					// Authentication completed
					self.updateProgress("authenticate", "done", "Authentication complete");
					self.launchMessageSender();
				}
			}
		}
	};

	self.launchMessageSender = function() {
		var msgsndrUrl = self.baseUrl + "message_sender.php?nonav&template=true&subject=" + encodeURIComponent(self.subject);
		// first, create a list
		self.createList(function(listId) {
			self.updateProgress("launchmsgsndr", "trying", "Launching application...");
			// load the iframe with message_sender.php (indicate list to add and excluding nav)
			self.iframe.attr("src", msgsndrUrl + "&lists=[" + listId + "]");
		});
	};

	self.createList = function(callback) {
		// List creation completed
		self.updateProgress("createlist", "trying", "Creating list...");
		// create a new list
		self.doAjax(
			"ajaxlistform.php",
			{	"type": "createlist",
				"name": "PowerSchool selection",
				"description": "A list created via user selection in PowerSchool"
			},
			function(listId) {
				// List creation completed
				self.updateProgress("createlist", "done", "List created");
				// add all the people into it
				self.addIdsToList(listId, callback)(0);
			}
		);
	};

	self.addIdsToList = function(listId, callback) {
		return function(data) {
			self.updateProgress("uploadpkeys", "trying", "Uploading contacts... (" + data + ") uploaded");
			// if there are people left who need to be added...
			if (pkeyList.length > 0) {
				// build up a list of pkeys to inject into the list.
				// NOTE: this has to use GET due to X-domain requests so limit the request to something reasonable (Less than 2kb, MSIE)
				var reqUrl = "ajaxlistform.php?type=addpkeys&listid=" + listId + "&pkeys=%5B";
				var addMore = true;
				while (addMore) {
					var pkey = pkeyList[0];
					pkeyList.splice(0,1);
					reqUrl += pkey;
					// reserve some space for jquery and jsonp stuff
					if (reqUrl.length > 2000 || pkeyList.length == 0) {
						// all full for this request, add the ']' and return
						reqUrl += "%5D";
						addMore = false;
					} else {
						// still room for more! add a ','
						reqUrl += "%2C";
					}
				}
				self.doAjax(reqUrl, {}, self.addIdsToList(listId, callback));
			} else {
				self.updateProgress("uploadpkeys", "done", "All contacts uploaded");
				callback(listId);
			}
		}
	};

	self.doAjax = function(page, data, callback) {
		$.ajax({
			"url": self.baseUrl + page,
			"data": data,
			"dataType": "jsonp",
			"xhrFields": {
				"withCredentials": true
			},
			"success": function(data) {
				callback(data);
			},
			"error": function(jqXHR, textStatus, errorThrown) {
				self.showError("There was an unexpected error attempting to communicate with the target application. Please try again.<br>" +
					"If the problem continues, seek assistance from your system administrator.")
			}
		});
	};

	self.showError = function(errorText) {
		// update whichever step is "trying" to "failed"
		$("ul.steps .trying").removeClass().addClass("failed");
		// display error message(s)
		$("#errormessage").removeClass("hide");
		$.each(errorText, function(id, error) {
			$("#errormessage .feedback-alert").append("<p>" + error + "</p>");
		});
	};

	self.resizeIframe = function(size) {
		if (size > 0) {
			// iframe is taking over the window, remove the loading bits
			$("#loadingmessage").remove();
		}
		// resize the iframe so the contents will fit, with a little extra...
		self.iframe.attr("width", "100%").attr("height", (size + 20) + "px");
	};

	self.updateProgress = function(step, cls, text) {
		$("#" + step).removeClass().addClass(cls).html(text);
	};
}