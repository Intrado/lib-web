function PermissionManager() {
	var $ = jQuery;
	var self = this;
	
	var eventManager = {
		onPermissionsLoaded : []
	};
	
	this.onPermissionsLoaded = function(callback) {
		//callback(lastcontent, nextcontent)
		eventManager.onPermissionsLoaded.push(callback);
		return eventManager.onPermissionsLoaded.length - 1;
	};
	
	this.unbindOnPermissionsLoaded = function(id) {
		eventManager.onPermissionsLoaded.splice(id, 1);
	};
	
	this.getRoles = function() {
		$.ajax({
			url : '/' + orgPath + '/api/2/users/' + userid + '/roles',
			type : "GET",
			dataType : "json",
			async : false,
			success : function(data) {
				// set the orgid from the very first set of role permissions
				orgid = data.roles[0].organization.id;

				self.configureRoles(data.roles); // Send Data over to the function setUp();
			}
		});
	};
	
	this.configureRoles = function(roleData) {
		if(!$.isArray(roleData)) {
			alert("error");
			return false;
		}

		// get the organization settings options
		function getOptions() {
			orgOptions = {};
			$.ajax({
				url : '/' + orgPath + '/api/2/organizations/' + orgid + '/settings/options',
				type : "GET",
				dataType : "json",
				async : false,
				success : function(data) {
					var options = data.options;
					$.each(options, function(oIndex, oItem) {
						orgOptions[oItem.name] = oItem.value;
					});
				}
			});
		};

		function getLanguages() {
			ttslangCodes = "";
			elangCodes = "";
			nLangs = {};

			$.ajax({
				url : '/' + orgPath + '/api/2/organizations/' + orgid + '/languages',
				type : "GET",
				dataType : "json",
				async : false,
				success : function(data) {
					languages = data.languages;

					$.each(languages, function(lIndex, lData) {
						var lCodes = lData.code;
						nLangs[lCodes] = lData.name;
						var voices = lData.voices;

						/*
						  If languages has voices then add code to ttlangcodes as well as elangcodes
						  if voices is undefined only add code to elangcodes
						 */

						if (lCodes != "en") {
							if (typeof (voices) != "undefined") {
								if (ttslangCodes == "") {
									ttslangCodes = lCodes;
									if (elangCodes == "") {
										elangCodes = lCodes;
									}
								} else {
									ttslangCodes = ttslangCodes + '|' + lCodes;
									elangCodes = elangCodes + '|' + lCodes;
								}

							} else {

								if (elangCodes == "") {
									elangCodes = lCodes;
								} else {
									elangCodes = elangCodes + '|' + lCodes;
								}

							}
						}
					});

				}
			});

		};

		// get user preferences ...
		function getUserPrefs() {
			userPrefs = {};

			$.ajax({
				url : '/' + orgPath + '/api/2/users/' + userid + '/preferences',
				type : "GET",
				dataType : "json",
				success : function(data) {
					$.each(data.preferences, function(uIndex, uPrefs) {
						userPrefs[uPrefs.name] = uPrefs.value;
					});
				}
			});
		};

		// get user information ...
		function getUserInfo() {
			userInfo = false;

			$.ajax({
				url : '/' + orgPath + '/api/2/users/' + userid,
				type : "GET",
				dataType : "json",
				async : false,
				success : function(data) {
					userInfo = data;
					// format the phone number and add the new formatted version to the userInfo object
					if (data.phone)
						userInfo.phoneFormatted = global.formatPhone(data.phone);
					else
						userInfo.phoneFormatted = "";
				}
			});
		};

		// call the functions
		getOptions();
		getLanguages();
		getUserPrefs();
		getUserInfo();

		userPermissions = {};
		userRoleId = false;
		$.each(roleData, function(rIndex, rItem) {
			//ideally match organization for rols here
			//if(rItem.organization.id == [orgVariable]) {
			userRoleId = rItem.id;
			$.each(rItem.accessProfile.permissions, function(pIndex, pItem) {
				userPermissions[pItem.name] = pItem.value;
			});
			//}
		});

		if (userRoleId == false) {
			alert("error: user doesn't have permissions for current organization");
			return false;
		}
		
		//RUN ON SAVE EVENTS
		$.each(eventManager.onPermissionsLoaded, function(eIndex, eEvent) {
			eEvent();
		});
	};
};