/**
 * postMessage requests, sent to commsuite rest api
 *
 * @param {PostMessageHandler} pmHandler
 * @param {CommSuiteApi} csApi
 * @constructor
 */
function PmRpcProvider(pmHandler, csApi) {
	var self = this;
	var methods = {};

	/**
	 * initialize the listener and the commsuite api, will emit a message when everything is ready, or an error
	 */
	self.init = function() {
		// attach the message listener for rpc methods
		pmHandler.attachListener(self._onMessage);

		// initialize the commsuite api, this will emit a message to the parent indicating it's ready for rpc requests
		csApi.init(function(data, status, headers) {
			pmHandler.postMessageAll({
				"status": (status == 200? "ready": "error"),
				"responseCode": status,
				"responseData": data
			});
		});
	};

	/**
	 * Handle new requests by filtering them on "type"
	 *
	 * @param {Event} event
	 * @private
	 */
	self._onMessage = function(event) {
		// TODO: check event.origin to be sure we are communicating with allowed domains?

		var data = $.secureEvalJSON(event.data);
		// only certain request types are currently supported
		if (methods[data.type] instanceof Function) {
			methods[data.type](data);
		} else {
			pmHandler.postMessageAll({ "status": "error", "error": "Unknown request type" });
		}
	};

	/**
	 * Create a list
	 *
	 * @param {{requestBody: Object, requestId: string}} data
	 */
	methods.createList = function(data) {
		csApi.createList(data.requestBody, function(respData, status, headers) {
			pmHandler.postMessageAll(self._createMessageFromResponse(data, status, respData));
		});
	};

	/**
	 * Add pkeys to a list
	 *
	 * @param {{listId: number, pkeyList: string[], requestId: {string}}} data
	 */
	methods.addListPkeys = function(data) {
		csApi.addListPkeys(data.listId, data.pkeyList, function(respData, status, headers) {
			pmHandler.postMessageAll(self._createMessageFromResponse(data, status, respData));
		});
	};

	/**
	 * Create a message object to send back to the parent
	 *
	 * @param {Object} requestData
	 * @param {number} responseStatus
	 * @param {Object} responseData
	 * @return {{status: string, responseCode: number, responseData: Object, requestId: string}}
	 * @private
	 */
	self._createMessageFromResponse = function(requestData, responseStatus, responseData) {
		var response = {
			"status": "complete",
			"responseCode": responseStatus,
			"responseData": responseData,
			"requestId": requestData.requestId
		};
		if (responseStatus == 200)
			response["status"] = "complete";
		else
			response["status"] = "error";
		return response;
	};
}

/**
 * Client for interacting with a remote api via postMessage
 * @param {PostMessageHandler} pmHandler
 * @constructor
 */
function PmRpcClient(pmHandler) {
	var $ = jQuery;
	var self = this;
	var reqId = 1;

	var requestTimeoutMs = 60000;

	// global for storing request timers
	pmRpcClientTimers = {};

	// for storing requests before the remote api is ready
	var ready = false;
	var queuedRequests = [];
	// callback methods stored for async requests (key is requestid)
	var callbacks = {};

	self.init = function() {
		pmHandler.attachListener(self._onMessage);
	};

	/**
	 * Handle response messages by checking the "status" and executing the requests callback
	 *
	 * @param event
	 * @private
	 */
	self._onMessage = function(event) {
		var data = $.secureEvalJSON(event.data);
		switch (data.status) {
			case "ready":
				ready = true;
				// execute any queued requests
				$.each(queuedRequests, function(index, request) {
					pmHandler.postMessageAll(request);
				});
				break;
			case "error":
			case "complete":
			default:
				// execute the callback for this requestid (if its a function)
				var callback = callbacks[data.requestId];
				if (callback != null) {
					clearTimeout(pmRpcClientTimers[data.requestId]);
					delete pmRpcClientTimers[data.requestId];
					delete callbacks[data.requestId];
				}
				if (callback instanceof Function)
					callback(data.responseCode, data.responseData);
		}
	};

	/**
	 * Create a list
	 *
	 * @param {string} name
	 * @param {string} desc
	 * @param {string} isDeleted
	 * @param {function(number, Object)} callback
	 */
	self.createList = function(name, desc, isDeleted, callback) {
		self._doRequest({
			"type": "createList",
			"requestBody": $.toJSON({
				"name": name,
				"description": desc,
				"type": "person",
				"isDeleted": isDeleted
			})
		}, callback);
	};

	/**
	 * Add pkeys to list with id of listId
	 *
	 * @param {number} listId
	 * @param {string[]} pkeyList
	 * @param {function(number, Object)} callback
	 */
	self.addListPkeys = function(listId, pkeyList, callback) {
		self._doRequest({
			"type": "addListPkeys",
			"listId": listId,
			"pkeyList": pkeyList
		}, callback);
	};

	/**
	 * Execute request or queue it if we have not yet received a "ready" response from the provider
	 *
	 * @param {Object} request
	 * @param {function(number, Object)} callback
	 * @private
	 */
	self._doRequest = function(request, callback) {
		var requestId = reqId++;
		request["requestId"] = requestId;
		// keep track of which callbacks belong to which requests so we can execute them when the response comes back
		callbacks[request.requestId] = callback;

		// add a timeout for this request, fail after requestTimeoutMs
		pmRpcClientTimers[reqId] = setTimeout(function() {
			delete pmRpcClientTimers[requestId];
			delete callbacks[requestId];
			callback(408, { "error": ["Request timeout"] });
		}, requestTimeoutMs);

		// if the provider isn't ready yet, queue the request
		if (!ready) {
			queuedRequests.push(request);
		} else {
			// otherwise, send it immediately
			pmHandler.postMessageAll(request);
		}
	}
}