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

	// test that plugin and pkList are set
	if (!container || typeof plugin === 'undefined' || typeof pkeyList === 'undefined') {
		// display an error
		alert("Cannot load application, missing one or more document resources: 'content-msgsndr', 'plugin', 'pkeyList'");
		return;
	}
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
 * @param {Element} container
 * @constructor
 */
function MessageSender_embedded(ssoTarget, pkeyList, container) {
	var $ = jQuery;
	container = $(container);

	// TODO: get appropriate subject
	this.subject = "New Message for...";
	this.baseUrl = false;
	this.iframe = $('<iframe class="embedded" height="1px" width="1px" frameborder="0" scrolling="no">');

	var pmHandler = false;
	var client = false;

	var totalPkeys = pkeyList.length;
	var totalAdded = 0;
	var pkeysPerBatch = 5000;

	var authenticationTimeoutMs = 30000;

	/**
	 * Initialize the container by:
	 * 1. adding progress indication html
	 * 2. begin the single sing on process by following the passed "ssoTarget" url in an iframe
	 * 3. initializing the postMessage handler
	 * 4. initializing the RPC client
	 */
	this.init = function() {
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
			this._showError(['This browser is incompatible with the application being accessed.<br>' +
				'See <a href="http://caniuse.com/#feat=x-doc-messaging">Cross-document messaging compatibility</a> for a browser compatibility list.']);
			return;
		}

		container.append(this.iframe);
		this.updateProgress("authenticate", "trying", "Authenticating...");
		this.iframe.attr("src", ssoTarget);
		var that = this;
		authTimer = setTimeout(function() {
			that._showError("Authentication request timed out after 30 seconds", "Contact your system administrator for assistance");
		}, authenticationTimeoutMs);

		// set up the postMessage handler and rpc client
		pmHandler = new PostMessageHandler(this.iframe[0].contentWindow);
		client = new PmRpcClient(pmHandler);
		client.init();

		// attach a message listener for communication cross domains
		pmHandler.attachListener(function(event) {
			that._onMessage(event);
		});
	};

	/**
	 * handle message events a couple of different ways
	 * 1. if it is a "resize" event, there will be resize data attached. This causes the iframe to resize so it fits the content
	 * 2. if the current information indicates that the user has landed on "start.php" or "dashboard.php", start launching message sender
	 *
	 * @param {event} event
	 * @private
	 */
	this._onMessage = function(event) {
		// TODO: test origin for valid domains
		//if(e.origin !== 'B'){ return; }

		var data = $.secureEvalJSON(event.data);
		if (data.error) {
			// got an error!
			this._showError(data.error);
		} else {
			// resize the iframe
			if (data.resize != undefined && data.resize)
				this._resizeIframe(data.resize);

			// check if we should load a new page
			if (data.custurl != undefined && data.custurl && data.user != undefined && data.user && data.page != undefined && data.page) {
				// update the baseUrl with the origin
				this.baseUrl = event.origin + "/" + data.custurl + "/";

				// we received all the necessary data to indicate pages are loading correctly (and a user is authenticated)
				// if the page loaded is start.php or dashboard.php, precede to the message sender
				if (data.page == "start.php" || data.page == "dashboard.php") {
					clearTimeout(authTimer);

					// Authentication completed
					this.updateProgress("authenticate", "done", "Authentication complete");
					this._launchMessageSender();
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
	this._launchMessageSender = function() {
		var msgsndrUrl = this.baseUrl + "message_sender.php?iframe&template=true&subject=" + encodeURIComponent(this.subject);

		// if the pkey list is not empty, create a list with the rpc client
		if (pkeyList.length > 0) {
			// first, set up the remote rpc provider in the iframe
			this.iframe.attr("src", this.baseUrl + "api/postmessage_rpc.html");

			// then, create a list
			this.updateProgress("createlist", "trying", "Creating list and adding contacts...");
			var that = this;
			client.createList("PowerSchool Selection List", "List created from a PowerSchool selection", true, function(code, data) {
				if (code == 200) {
					totalAdded = 0;
					that._addListPkeys(data.id, pkeyList, function() {
						that.updateProgress("createlist", "done", "List creation complete");
						that.updateProgress("launchmsgsndr", "trying", "Launching application...");
						// load the iframe with message_sender.php (indicate list to add and excluding nav)
						that.iframe.attr("src", msgsndrUrl + "&lists=[" + data.id + "]");
					})(code, data);
				} else {
					that._showError(data.error);
				}
			});
		} else {
			// otherwise, just launch the message sender with no list
			this.updateProgress("createlist", "done", "List creation complete");
			this.updateProgress("launchmsgsndr", "trying", "Launching application...");
			this.iframe.attr("src", msgsndrUrl);
		}
	};

	this._addListPkeys = function(listid, pkeyList, callback) {
		var that = this;
		return function(status, data) {
			if (status == 200) {
				// TODO: test status
				if (pkeyList.length > 0) {
					var partialList = [];
					for (var i = 0; i < pkeysPerBatch && pkeyList.length > 0; i++)
						partialList.push(pkeyList.pop());

					totalAdded += partialList.length;
					// update the status with a counter, shows something is happening
					that.updateProgress("createlist", "trying", "Creating list and adding contacts... (" + totalAdded + " / " + totalPkeys + ")");

					client.addListPkeys(listid, partialList, that._addListPkeys(listid, pkeyList, callback));
				} else {
					callback();
				}
			} else {
				that._showError(data.error);
			}
		}
	};

	/**
	 * display the passed error text in the container
	 *
	 * @param {Object} errorText
	 * @private
	 */
	this._showError = function(errorText) {
		// ensure an error is reported and that the data is an appropriate structure
		if (errorText) {
			if (!(errorText instanceof Array))
				errorText = [errorText];
		} else {
			errorText = ["An error occurred, but the reason was not reported. Seek assistance from your system administrator."];
		}
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
	this._resizeIframe = function(size) {
		if (size > 0) {
			// iframe is taking over the window, remove the loading bits
			$("#loadingmessage").remove();
		}
		// resize the iframe, taking the larger of the left nav, vs the iframe content
		var psNavHeight = $('#nav-main').height();
		this.iframe.attr("width", "98%").attr("height", Math.max(psNavHeight, size) + "px");
	};

	this.updateProgress = function(step, cls, text) {
		$("#" + step).removeClass().addClass(cls).html(text);
	};
}