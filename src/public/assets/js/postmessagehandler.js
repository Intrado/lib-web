/**
 * Class for handling html5 postMessage behavior
 *
 * Attaches a listener to the specified window and allows a list of handlers to be called when a message is received
 *
 * @param {window} target
 * @constructor
 */
function PostMessageHandler(target) {

	/**
	 * Attach a message handler to the "message" event
	 * @param {function} handler
	 * @return {PostMessageHandler} this
	 */
	this.attachListener = function(handler) {
		if (window.addEventListener == undefined)
			window.attachEvent("onmessage", handler);
		else
			window.addEventListener("message", handler, false);

		return this;
	};

	/**
	 * Post the json data to all domains
	 * @param {Object} jsonData
	 * @return {PostMessageHandler} this
	 */
	this.postMessageAll = function(jsonData) {
		return this.postMessage(jsonData, '*');
	};

	/**
	 * Post the json data to a specific domain(s)
	 * @param {Object} jsonData
	 * @param {Object} domains
	 * @return {PostMessageHandler} this
	 */
	this.postMessage = function(jsonData, domains) {
		target.postMessage(jQuery.toJSON(jsonData), domains);
		return this;
	};

}