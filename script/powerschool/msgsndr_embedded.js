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

	// load all the required javascript libraries and then, once complete, begin the process
	loadScripts([
			"https://heckvm.testschoolmessenger.com/powerschool/script/jquery.json-2.3.min.js",
			"https://heckvm.testschoolmessenger.com/powerschool/script/postmessagehandler.js",
			"https://heckvm.testschoolmessenger.com/powerschool/script/postmessagerpchandler.js"
		],
		function() {
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
		})();
})(jQuery);

function loadScripts(scriptList, callback) {
	return function() {
		if (scriptList.length > 0) {
			var scriptUrl = scriptList.pop();
			jQuery.getScript(scriptUrl, loadScripts(scriptList, callback));
		} else {
			callback();
		}
	}
}

function MessageSender_embedded(ssoTarget, pkeyList, container) {
	var $ = jQuery;
	var self = this;

	// TODO: get appropriate subject
	self.subject = "New Message for...";
	self.baseUrl = false;
	self.iframe = $('<iframe class="embedded" height="1px" width="1px" frameborder="0" scrolling="no">');

	var pmHandler = false;
	var client = false;

	self.init = function() {
		// insert loading content and message area
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
			'			<li id="launchmsgsndr" class="waiting">Launch Application</li>' +
			'		</ul>' +
			'	</div>' +
			'</div>');

		// detect if the browser can use HTML5 window.postMessage API (this is REQUIRED!)
		if (top.postMessage == undefined) {
			self._showError('This browser is incompatible with the application being accessed.<br>' +
				'See <a href="http://caniuse.com/#feat=x-doc-messaging">Cross-document messaging compatibility</a> for a browser compatibility list.');
			return;
		}

		container.append(self.iframe);
		self.updateProgress("authenticate", "trying", "Authenticating...");
		self.iframe.attr("src", ssoTarget);

		// set up the postMessage handler and rpc client
		pmHandler = new PostMessageHandler(self.iframe[0].contentWindow);
		client = new PmRpcClient(pmHandler);
		client.init();

		// attach a message listener for communication cross domains
		pmHandler.attachListener(self._onMessage);
	};

	self._onMessage = function(event) {
		// TODO: test origin for valid domains
		//if(e.origin !== 'B'){ return; }

		var data = $.secureEvalJSON(event.data);
		if (data.error != undefined && data.error) {
			// got an error!
			self._showError(data.error);
		} else {
			// resize the iframe
			if (data.resize != undefined && data.resize)
				self._resizeIframe(data.resize);

			// check if we should load a new page
			if (data.custurl != undefined && data.custurl && data.user != undefined && data.user && data.page != undefined && data.page) {
				// update the baseUrl with the origin
				self.baseUrl = event.origin + "/" + data.custurl + "/";

				// we received all the necessary data to indicate pages are loading correctly (and a user is authenticated)
				// if the page loaded is start.php or dashboard.php, precede to the message sender
				if (data.page == "start.php" || data.page == "dashboard.php") {
					// Authentication completed
					self.updateProgress("authenticate", "done", "Authentication complete");
					self._launchMessageSender();
				}
			}
		}
	};

	self._launchMessageSender = function() {
		var msgsndrUrl = self.baseUrl + "message_sender.php?iframe&template=true&subject=" + encodeURIComponent(self.subject);

		// first, set up the remote rpc provider in the iframe
		self.iframe.attr("src", self.baseUrl + "api/postmessage_rpc.html");

		// then, create a list
		self.updateProgress("createlist", "trying", "Creating list and adding contacts...");
		client.createList("PowerSchool Selection List", "List created from a PowerSchool selection", true, pkeyList, function(code, data) {
			if (code == 200) {
				self.updateProgress("createlist", "done", "List creation complete");
				self.updateProgress("launchmsgsndr", "trying", "Launching application...");
				// load the iframe with message_sender.php (indicate list to add and excluding nav)
				self.iframe.attr("src", msgsndrUrl + "&lists=[" + data.id + "]");
			} else {
				self._showError(data.error);
			}
		});
	};

	self._showError = function(errorText) {
		// update whichever step is "trying" to "failed"
		$("ul.steps .trying").removeClass().addClass("failed");
		// display error message(s)
		$("#errormessage").removeClass("hide");
		$.each(errorText, function(id, error) {
			$("#errormessage .feedback-alert").append("<p>" + error + "</p>");
		});
	};

	self._resizeIframe = function(size) {
		if (size > 0) {
			// iframe is taking over the window, remove the loading bits
			$("#loadingmessage").remove();
		}
		// resize the iframe so the contents will fit, with a little extra...
		self.iframe.attr("width", "98%").attr("height", size + "px");
	};

	self.updateProgress = function(step, cls, text) {
		$("#" + step).removeClass().addClass(cls).html(text);
	};
}