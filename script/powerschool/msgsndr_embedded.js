/**
 * application logic for embedded Message Sender, launched via custom page in PowerSchool
 *
 * Requires:
 * 	"plugin" - js object {ssoLink: {string}, registrationUrl: {string}, name: {string}} (only SchoolMessenger plugins are valid!)
 * 	"pkeyList" - js list of pkeys to add to a list
 * 	"content-msgsndr" - container somewhere in the document where the message sender will go
 *
 * 	@author: nrheckman
 */
(function($){
	var container = $("#content-msgsndr");
	var appUrl = plugin.registrationUrl.replace(/\/api\/.*$/g, "") + "/";

	// extend the styles
	$('head').append('<link rel="stylesheet" href="' + appUrl + "themes/powerschool/embedded.css" + '" type="text/css" />');

	// load all the required javascript libraries and then, once complete, begin the process
	loadScripts([
		appUrl + "script/jquery.json-2.3.min.js",
		appUrl + "script/postmessagehandler.js",
		appUrl + "script/postmessagerpchandler.js"
	],
		function() {
			// initialize the message sender object. It will auto-load into the form
			var msgsndr = new MessageSender_embedded(plugin.ssoLink, pkeyList, container);
			msgsndr.init();
		}
	)();
})(jQuery);

/**
 * Loads requested list of script files sequentially, and then executes the callback
 *
 * @param {string[]} scriptList
 * @param {function} callback
 * @return {function}
 */
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

/**
 * Instance of an embedded message sender
 * This will take over part of the dom (referenced by "container") and insert the message sender into it after:
 * 1. Initiating a single sing on request via the "ssoTarget" url
 * 2. Creating a list which contains the student/staff pkeys from "pkeyList"
 *
 * @param {string} ssoTarget
 * @param {string[]} pkeyList
 * @param {element} container
 * @constructor
 */
function MessageSender_embedded(ssoTarget, pkeyList, container) {
	var $ = jQuery;
	var self = this;

	// TODO: get appropriate subject
	self.subject = "New Message for...";
	self.baseUrl = false;
	self.iframe = $('<iframe class="embedded" height="1px" width="1px" frameborder="0" scrolling="no">');

	var pmHandler = false;
	var client = false;

	/**
	 * Initialize the container by:
	 * 1. adding progress indication html
	 * 2. begin the single sing on process by following the passed "ssoTarget" url in an iframe
	 * 3. initializing the postMessage handler
	 * 4. initializing the RPC client
	 */
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

	/**
	 * handle message events a couple of different ways
	 * 1. if it is a "resize" event, there will be resize data attached. This causes the iframe to resize so it fits the content
	 * 2. if the current information indicates that the user has landed on "start.php" or "dashboard.php", start launching message sender
	 *
	 * @param {event} event
	 * @private
	 */
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

	/**
	 * Launch the message sender into the iframe
	 * 1. create a new list and add the pkeys to it
	 * 2. navigate the iframe to the message sender
	 *
	 * @private
	 */
	self._launchMessageSender = function() {
		var msgsndrUrl = self.baseUrl + "message_sender.php?iframe&template=true&subject=" + encodeURIComponent(self.subject);

		// if the pkey list is not empty, create a list with the rpc client
		if (pkeyList.length > 0) {
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
		} else {
			// otherwise, just launch the message sender with no list
			self.updateProgress("createlist", "done", "List creation complete");
			self.updateProgress("launchmsgsndr", "trying", "Launching application...");
			self.iframe.attr("src", msgsndrUrl);
		}
	};

	/**
	 * display the passed error text in the container
	 *
	 * @param {string} errorText
	 * @private
	 */
	self._showError = function(errorText) {
		// update whichever step is "trying" to "failed"
		$("ul.steps .trying").removeClass().addClass("failed");
		// display error message(s)
		$("#errormessage").removeClass("hide");
		$.each(errorText, function(id, error) {
			$("#errormessage .feedback-alert").append("<p>" + error + "</p>");
		});
	};

	/**
	 * resize the iframe to the specified height.
	 * any height > 0 causes it to expand it's width to fill the container
	 *
	 * @param {number} size
	 * @private
	 */
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