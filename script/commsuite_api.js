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
	var sessionid = false;
	var userid = false;

	/**
	 * Initialize the API by requesting the session information and caching the userid
	 *
	 * @param {Function} callback
	 */
	self.init = function(callback) {
		sessionid = self.getCookie(customer + "_session");
		self._genericRequest(apiRoot + "sessions/" + sessionid, "GET", {}, {}, function(resp, status, headers) {
			if (resp.userId)
				userid = resp.userId;
			if (callback instanceof Function)
				callback(resp, status, headers);
		});
	};

	/**
	 * Create a new list
	 *
	 * @param {Object} data
	 * @param {Function} callback
	 */
	self.createList = function(data, callback) {
		self._genericRequest(apiRoot + "users/" + userid + "/lists", "POST", {}, data, callback);
	};

	/**
	 * Set the manual adds on the specified listId to those in the pkeyList
	 *
	 * @param {number} listId
	 * @param {Object} pkeyList
	 * @param {Function} callback
	 */
	self.setListPkeys = function(listId, pkeyList, callback) {
		var listAdditions = { "additions": [] };
		$.each(pkeyList, function(index, pkey) {
			listAdditions.additions.push({ "pkey": pkey });
		});

		self._genericRequest(apiRoot + "users/" + userid + "/lists/" + listId + "/additions", "PUT", {}, $.toJSON(listAdditions), callback);
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
		if (headers == null || headers == null)
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
			error: function(jqXHR) {
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

	/**
	 * gets the value of the specified cookie
	 *
	 * @param {string} name
	 * @return {string|boolean}
	 */
	self.getCookie = function(name) {
		var data = document.cookie.match ( '(^|;) ?' + name + '=([^;]*)(;|$)' );
		if (data && data[2] && data[2] != "")
			return (data[2]);
		else
			return false;
	}
}
