(function($){
	$.fn.attachEasyCall = function(options) {
		var $this = this;
		var easycalldata = $this.data('easyCall');

		// NOTE: re-init with different language list will cause them to be ignored
		if (!easycalldata) {
			easycalldata = {
				"element": this,
				"maincontainer": false,
				"subcontainer": {"en": false},
				"language": {"en": "English"},
				"recording": {"en": false},
				"specialtaskid": false,
				"timer": false,
				"default": "en",
				"phonemindigits": 10,
				"phonemaxdigits": 10};
			if (options.languages) {
				$.each(options.languages, function(code) {
					easycalldata.subcontainer[code] = false;
					easycalldata.language[code] = options.languages[code];
					easycalldata.recording[code] = false;
					easycalldata.specialtaskid[code] = false;
					easycalldata.timer[code] = false;
				});
			}
		}
		// TODO: load existing values from attached input

		var method = {
			//============================================================================================
			// DOM rendering methods
			// set up the easycall elements, hiding the intial input element
			init: function () {
				//$this.hide();
				// destroy all the easycall DOM containers (if there are any)
				if (easycalldata.maincontainer !== false)
					$("." + easycalldata.maincontainer).detach();
				
				// remove all existing references to old DOM subcontainers
				$.each(easycalldata.subcontainer, function (code) {
					easycalldata.subcontainer[code] = false;
				});
				
				var initdiv = $('<div />', { "class": "easycallmaincontainer" });
				easycalldata.maincontainer = initdiv;
				
				// TODO: add sub-containers for pre-recorded languages
				
				// add the default call container
				var subcontainer = method.createCallMeContainer(false);
				easycalldata.subcontainer[easycalldata.default] = subcontainer;
				initdiv.append(subcontainer);
				
				initdiv.insertAfter(easycalldata.element);
			},
			
			// create a container for the passed language
			createCallMeContainer: function(hasmenu) {
				var container = $('<div />', { "class": "easycallcallmecontainer"});
				var phoneinput = $('<input />', { "class": "easycallphoneinput", "type": "text" });
				//var callbutton = $('<a />', { "class": "easycallcallnowbutton", "href": "#", "text": "Call Now to Record" });
				var callbutton = $('#ctrecord');
				
				if (hasmenu) {
					// create a multiselect with remaining languages in it.
					var selectmenu = $('<select />', { "class": "easycallselectmenu" })
					var hasitems = false;
					$.each(easycalldata.subcontainer, function (code) {
						if (easycalldata.subcontainer[code] == false) {
							// insert item into selector
							var option = $('<option />', { "value": code, "text": easycalldata.language[code]});
							selectmenu.append(option);
							hasitems = true;
						}
					});
					if (!hasitems)
						return false;
					container.append(selectmenu);
				}

				callbutton.click(function(e){
					e.preventDefault();
					var code = easycalldata.default;
					if (hasmenu)
						code = selectmenu.val();
					easycalldata.subcontainer[code] = container;
					method.doCall(code);
				});
				
				//container.append(phoneinput).append(callbutton);
				return container;
			},
			
			createPreviewContainer: function(code, audiofileid) {
				var container = $('<div />', { "class": "easycallpreviewcontainer"});
				var languagetitle = $('<div />', { "class": "easycalllanguagetitle", "text": easycalldata.language[code] });
				var previewbutton = $('<a />', { "class": "easycallpreviewbutton", "href": "#", "text": "Preview" });
				var rerecordbutton = $('<a />', { "class": "easycallrerecordbutton", "href": "#", "text": "Re-Record" });

				// TODO: functional preview button
				previewbutton.click(function(){
					alert("AUDIOFILEID " + audiofileid + " PREVIEW!!!");
				});
				
				
				rerecordbutton.click(function(){
					var subcontainer = easycalldata.subcontainer[code];
					easycalldata.subcontainer[code] = false;
					
					// get a new callme container!
					var callmecontainer = false;
					if (code == easycalldata.default)
						callmecontainer = method.createCallMeContainer(false);
					else
						callmecontainer = method.createCallMeContainer(true);
					
					// above HAS to return a container, we just invalidated one!
					callmecontainer.insertAfter(subcontainer);
					
					// clean up the old subcontainers
					subcontainer.remove();

				});
				
				container.append(languagetitle).append(previewbutton).append(rerecordbutton);
				
				return container;
				
			},
			
			createProgressContainer: function() {
				var container = $('<div />', { "class": "easycallprogresscontainer"});
				var progresstext = $('<span />', { "class": "easycallprogresstext" });
				
				container.append(progresstext);
				
				return container;
			},
			
			
			//============================================================================================
			// Application logic
			// start the process by making a phone call to the provided number
			doCall: function (code) {
				var langdiv = easycalldata.subcontainer[code];
				// get the phone number
				//var phone = langdiv.children(".easycallphoneinput").val();
				var phone = $this.val();
				
				// validate the phone
				var valid = method.validatePhone(phone);
				if (valid !== true) {
					langdiv.children(".easycallphoneinvalid").detach();
					langdiv.append($('<div />', { "class": "easycallphoneinvalid", "text": phone + ": " + valid }));
					return;
				}
				var progresscontainer = method.createProgressContainer();
				var progresstext = progresscontainer.children(".easycallprogresstext");
				
				progresstext.empty().append("Calling: " + phone);

				$('#ctrecord').hide();
				
				langdiv.empty();
				langdiv.append(progresscontainer);
				
				$.post("ajaxeasycall.php", {"action":"new","phone":phone}, function(data){
					// TODO: handle returned errors
					progresstext.empty().append("Initiated...");
					
					easycalldata.specialtaskid = data.id;
					// on a timer, query the status
					easycalldata.timer = $.timer(function(){
						$.post("ajaxeasycall.php", {"action":"status","id":easycalldata.specialtaskid}, function(data){
							// get the status
							var status = method.handleStatus(data);
							progresstext.empty().append(status.message);
							if (status.complete) {
								easycalldata.timer.stop();
								// save audiofile
								method.doSaveAudioFile(code);
							}
							if (status.error) {
								easycalldata.timer.stop();
								// TODO: transition to error mode
							}
						},"json");
					}).set({time: 2000, autostart: true});
				},"json");
			},
			
			// save the audiofile and get the audiofileid
			doSaveAudioFile: function (code) {
				$.post("ajaxeasycall.php", {
						"action": "getaudiofile",
						"id": easycalldata.specialtaskid,
						"name": easycalldata.language[code]},
						function(data){
							
					// TODO: handle errors
							
					var audiofileid = data.audiofileid;
					easycalldata.recording[code] = audiofileid;
					
					// TODO: update json data in parent input field
					
					var previewcontainer = method.createPreviewContainer(code, audiofileid);
					previewcontainer.insertAfter(easycalldata.subcontainer[code]);
					easycalldata.subcontainer[code].remove();
					easycalldata.subcontainer[code] = previewcontainer;
					
					// create a new call me container 
					var callmecontainer = method.createCallMeContainer(true);
					if (callmecontainer !== false)
						easycalldata.maincontainer.append(callmecontainer);
				},"json");
			},
			
			// read the return status and provide appropriate error handling messages
			handleStatus: function(data) {
				var complete = false;
				var error = false;
				var message = "";
				switch(data.status) {
					case "done":
						complete = true;
						break;
					case "notask":
						error = true;
						message = "No valid request was found";
						break;
					case "callended":
						error = true;
						message = "Call ended early";
						break;
					case "starterror":
						error = true;
						message = "Couldn't initiate request";
						break;
					case "saveerror":
						error = true;
						message = "There was a problem saving your audio";
						break;
					case "badphone":
						error = true;
						message = "Bad phone number";
						break;
					default:
						message = data.progress;
				}
				return {
					"complete": complete,
					"error": error,
					"message": message
				};
			},
	
			// validate a phone number
			validatePhone: function (value) {
				var phone = value.replace(/[^0-9]/g,"");
				if (easycalldata.phonemindigits == easycalldata.phonemaxdigits && easycalldata.phonemaxdigits == 10 && phone.length == 10) {
					var areacode = phone.substring(0, 3);
					var prefix = phone.substring(3, 6);

					// based on North American Numbering Plan
					// read more at en.wikipedia.org/wiki/List_of_NANP_area_codes

					if ((phone.charAt(0) == "0" || phone.charAt(0) == "1") || // areacode cannot start with 0 or 1
						(phone.charAt(3) == "0" || phone.charAt(3) == "1") || // prefix cannot start with 0 or 1
						(phone.charAt(1) == "1" && phone.charAt(2) == "1") || // areacode cannot be N11
						(phone.charAt(4) == "1" && phone.charAt(5) == "1") || // prefix cannot be N11
						("555" == areacode) || // areacode cannot be 555
						("555" == prefix)    // prefix cannot be 555
						) {
						// check special case N11 prefix with toll-free area codes
						// en.wikipedia.org/wiki/Toll-free_telephone_number
						if ((phone.charAt(4) == "1" && phone.charAt(5) == "1") && (
							("800" == areacode) ||
							("888" == areacode) ||
							("877" == areacode) ||
							("866" == areacode) ||
							("855" == areacode) ||
							("844" == areacode) ||
							("833" == areacode) ||
							("822" == areacode) ||
							("880" == areacode) ||
							("881" == areacode) ||
							("882" == areacode) ||
							("883" == areacode) ||
							("884" == areacode) ||
							("885" == areacode) ||
							("886" == areacode) ||
							("887" == areacode) ||
							("888" == areacode) ||
							("889" == areacode)
							)) {
							return true; // OK special case
						}

						return "seems to be invalid.";
					}
					return true;
				} else {
					if (easycalldata.phonemindigits != 10 || easycalldata.phonemaxdigits != 10) {
						if (phone.length < easycalldata.phonemindigits)
							return label + " is invalid. The phone number or extension must be at least " + minlength + " digits long.\nYou do not need to include a 1 for long distance.";
						if (phone.length > easycalldata.phonemaxdigits)
							return "is invalid. The phone number or extension must be no more than " + maxlength + " digits long.\nYou do not need to include a 1 for long distance.";
					} else {
						return "is invalid. The phone number must be exactly 10 digits long (including area code).\nYou do not need to include a 1 for long distance.";
					}
					return true;
				}
			}
		};
		
		return this.each(function() {
			method.init();
		});
	};
})(jQuery);
