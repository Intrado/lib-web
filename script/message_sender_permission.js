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
            data :  { "limit" : 1000 },
			dataType : "json",
			async : false,
			success : function(data) {
				// set the orgid from the very first set of role permissions
				orgid = data.roles[0].organization.id;
				orgids = new Array();
				self.configureRoles(data.roles); // Send Data over to the function setUp();
			},
			error: function() {
				$('#error').show();
				$('#loading').hide();
				$('.error_list').append('<li>Unable to get your role data</li>');
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
				},
				error: function() {
					$('#error').show();
					$('#loading').hide();
					$('.error_list').append('<li>Unable to get your organisations options</li>');
				}
			});
		};
		
		function getOrganizationSettingsFeatures(){
			orgFeatures = {};
			$.ajax({
				url : '/'+orgPath+'/api/2/organizations/'+orgid+'/settings/features',
				type : "GET",
				dataType : "json",
				async : false,
				success : function(data) {
					var features = data.features;
					$.each(features, function(fIndex, fItem) {
						orgFeatures[fItem.name] = fItem.isEnabled;
					});
				},
				error: function() {
					$('#error').show();
					$('#loading').hide();
					$('.error_list').append('<li>Unable to get your organisations settings</li>');
				}
			});
		};
		
		function getLanguages() {
			ttslangCodes = [];
			elangCodes = [];
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
						var hasTranslate = (lData.hasTranslate?true:false);
						nLangs[lCodes] = lData.name;
						var voices = lData.voices;

						// If language has translate add to elangCodes and ttslangCodes (if has voice)
						if (lCodes != "en" && hasTranslate) {
							elangCodes.push(lCodes);
							if (typeof (voices) != "undefined")
								ttslangCodes.push(lCodes);
						}
					});

				},
				error: function() {
					$('#error').show();
					$('#loading').hide();
					$('.error_list').append('<li>Unable to get your language options</li>');
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
				async : false,
				success : function(data) {
					$.each(data.preferences, function(uIndex, uPrefs) {
						userPrefs[uPrefs.name] = uPrefs.value;
					});
				},
				error: function() {
					$('#error').show();
					$('#loading').hide();
					$('.error_list').append('<li>Unable to get your user preferences</li>');
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
						userInfo.phoneFormatted = formatPhone(data.phone);
					else
						userInfo.phoneFormatted = "";
				},
				error: function() {
					$('#error').show();
					$('#loading').hide();
					$('.error_list').append('<li>Unable to get your user information</li>');
				}
			});
		};

		// call the functions
		getOptions();
		getLanguages();
		getUserPrefs();
		getUserInfo();
		getOrganizationSettingsFeatures();

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
			orgids.push(rItem.organization.id)
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