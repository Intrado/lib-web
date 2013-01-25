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
		switch (data.type) {
			case "createPkeyList":
				methods.createList(data);
				break;
			default:
				pmHandler.postMessageAll({ "status": "error", "error": "Unknown request type" });
		}
	};

	/**
	 * Create a list and populate it with the list of pkeys
	 *
	 * @param {{requestBody: Object, pkeyList: Object, requestId: string}} data
	 */
	methods.createList = function(data) {
		csApi.createList(data.requestBody, function(createRespData, createStatus, createHeaders) {
			if (createStatus == 200) {
				csApi.setListPkeys(createRespData.id, data.pkeyList, function(addRespData, addStatus, addHeaders) {
					if (addStatus == 200) {
						pmHandler.postMessageAll(self._createMessageFromResponse(data, addStatus, createRespData));
					} else {
						pmHandler.postMessageAll(self._createMessageFromResponse(data, addStatus, addRespData));
					}
				});
			} else {
				pmHandler.postMessageAll(self._createMessageFromResponse(data, createStatus, createRespData));
			}
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
			"requestId": requestData.requestId.toString()
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
	var reqId = 0;

	// for storing requests before the remote api is ready
	// TODO: Fail all queued requests if we never receive "ready" from the remote system
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
				if (callback != null)
					delete callbacks[data.requestId];
				if (callback instanceof Function)
					callback(data.responseCode, data.responseData);
		}
	};

	/**
	 * Create a list and populate it with the provided pkeys
	 *
	 * @param {string} name
	 * @param {string} desc
	 * @param {string} isDeleted
	 * @param {Object} pkeyList
	 * @param {function({string}responseCode, {Object}responseData)} callback
	 */
	self.createList = function(name, desc, isDeleted, pkeyList, callback) {
		self._doRequest({
			"type": "createPkeyList",
			"requestBody": $.toJSON({
				"name": name,
				"description": desc,
				"type": "person",
				"isDeleted": isDeleted
			}),
			"pkeyList": pkeyList
		}, callback);
	};

	/**
	 * Execute request or queue it if we have not yet received a "ready" response from the provider
	 *
	 * @param {Object} request
	 * @param {function} callback
	 * @private
	 */
	self._doRequest = function(request, callback) {
		request["requestId"] = reqId++;
		// keep track of which callbacks belong to which requests so we can execute them when the response comes back
		callbacks[request.requestId] = callback;
		if (!ready) {
			queuedRequests.push(request);
		} else {
			pmHandler.postMessageAll(request);
		}
	}
}