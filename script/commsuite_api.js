/**
 * This is the javascript interface for interacting with the commsuite api
 *
 * NOTE: requires jquery.js jquery.json.js
 *
 * @param {string} host
 * @param {string} customer
 * @constructor
 */
function CommSuiteApi(host, customer) {
	var self = this;

	var apiRoot = "https://" + host + "/" + customer + "/api/2/";
	var sessionid = getCookie(customer + "_session");
	var userid = false;

	/**
	 * Initialize the API by cacheing some session information
	 *
	 * @param {function} callback
	 */
	self.init = function(callback) {
		self._genericRequest(apiRoot + "sessions/" + sessionid, "GET", {}, {}, function(resp, status, headers) {
			if (resp.userId)
				userid = resp.userId;
			if (callback instanceof Function)
				callback(resp, status, headers);
		});
	};

	self.createList = function(callback) {
		// TODO call API to create a list
	};

	self.listPkeys = function(listid, pkeyList, callback) {
		// TODO call API to add pkeys to list
	};

	/**
	 * Make an ajax request
	 *
	 * @param {string} url
	 * @param {string} method
	 * @param {Object} headers
	 * @param {Object} data
	 * @param {function} callback
	 * @private
	 */
	self._genericRequest = function(url, method, headers, data, callback) {
		if (headers == undefined || headers == null)
			headers = {};

		$.ajax({
			url: url,
			type: method,
			headers: headers,
			data: data,
			dataType: "json",
			success: function(resp, textStatus, jqXHR) {
				if (resp == undefined || resp == null)
					resp = {};
				callback(resp, jqXHR.status, jqXHR.getAllResponseHeaders());
			},
			error: function(jqXHR, textStatus, errorThrown) {
				var data = {};
				try {
					data = $.secureEvalJSON(jqXHR.responseText);
				} catch (e) {
					data = {error: jqXHR.responseText};
				}
				if (callback instanceof Function)
					callback(data, jqXHR.status, jqXHR.getAllResponseHeaders());
			}
		});
	};
}

function getCookie (name) {
	var data = document.cookie.match ( '(^|;) ?' + name + '=([^;]*)(;|$)' );
	if (data && data[2] && data[2] != "")
		return (data[2]);
	else
		return false;
}