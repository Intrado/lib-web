/**
 * Class for handling html5 postMessage behavior
 *
 * Attaches a listener to the specified window and allows a list of handlers to be called when a message is received
 *
 * @param {window} target
 * @constructor
 */
function PostMessageHandler(target) {
	var self = this;

	self.target = target;

	/**
	 * Attach a message handler to the "message" event
	 * @param {function} handler
	 * @return {PostMessageHandler} this
	 */
	self.attachListener = function(handler) {
		if (window.addEventListener == undefined)
			window.attachEvent("onmessage", handler);
		else
			window.addEventListener("message", handler, false);

		return self;
	};

	/**
	 * Post the json data to all domains
	 * @param {Object} jsonData
	 * @return {PostMessageHandler} this
	 */
	self.postMessageAll = function(jsonData) {
		return self.postMessage(jsonData, '*');
	};

	/**
	 * Post the json data to a specific domain(s)
	 * @param {Object} jsonData
	 * @param {Object} domains
	 * @return {PostMessageHandler} this
	 */
	self.postMessage = function(jsonData, domains) {
		self.target.postMessage(jQuery.toJSON(jsonData), domains);
		return self;
	};

}