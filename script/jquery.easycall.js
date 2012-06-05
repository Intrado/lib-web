(function($){
	$.fn.attachEasyCall = function(options) {
		var $this = this;
		var easycalldata = $this.data('easyCall');

		// NOTE: re-init with different options will ignore new options
		if (!easycalldata) {
			easycalldata = {
				"element": this,
				"maincontainer": false,
				"subcontainer": {"en": false},
				"language": {"en": "English"},
				"recording": {"en": false},
				"specialtaskid": false,
				"timer": false,
				"defaultcode": "en",
				"phonemindigits": 10,
				"phonemaxdigits": 10,
				"defaultphone": "",
				"emptyphonetext": "Number to Call"};
			if (options.languages) {
				$.each(options.languages, function(code) {
					easycalldata.subcontainer[code] = false;
					easycalldata.language[code] = options.languages[code];
					easycalldata.recording[code] = false;
					easycalldata.specialtaskid[code] = false;
					easycalldata.timer[code] = false;
				});
			}
			if (options.phonemindigits)
				easycalldata.phonemindigits = options.phonemindigits;
			if (options.phonemaxdigits)
				easycalldata.phonemaxdigits = options.phonemaxdigits;
			if (options.defaultphone)
				easycalldata.defaultphone = options.defaultphone;
		}
		
		// load existing values from attached input
		var elementval = this.val();
		if (elementval == "")
			elementdata = {};
		else
			elementdata = $.secureEvalJSON(elementval);
		$.each(elementdata, function (code) {
			// do a sanity check, then stuff the value in the recordings list
			if (easycalldata.language[code])
				easycalldata.recording[code] = elementdata[code];
		});

		var method = {
			//============================================================================================
			// DOM rendering methods
			// set up the easycall elements, hiding the intial input element
			init: function () {
				$this.hide();
				// destroy all the easycall DOM containers (if there are any)
				if (easycalldata.maincontainer !== false)
					$("." + easycalldata.maincontainer).detach();
				
				// remove all existing references to old DOM subcontainers
				$.each(easycalldata.subcontainer, function (code) {
					easycalldata.subcontainer[code] = false;
				});
				
				var initdiv = $('<div />', { "class": "easycallmaincontainer" });
				easycalldata.maincontainer = initdiv;

				// add sub-containers for the pre-recorded languages
				var subcontainer = false;
				var needscallmecontainer = true;
				// always add the default first
				if (easycalldata.recording[easycalldata.defaultcode] !== false) {
					subcontainer = method.createPreviewContainer(easycalldata.defaultcode);
				} else {
					// add the default callme container
					subcontainer = method.createCallMeContainer(false);
					needscallmecontainer = false;
				}
				easycalldata.subcontainer[easycalldata.defaultcode] = subcontainer;
				initdiv.prepend(subcontainer);
				
				// add all others which have recordings
				$.each(easycalldata.recording, function (code) {
					if (code != easycalldata.defaultcode && easycalldata.recording[code] !== false) {
						subcontainer = method.createPreviewContainer(code);
						easycalldata.subcontainer[code] = subcontainer;
						initdiv.append(subcontainer);
					}
				});

				// add a new call me container with menu
				if (needscallmecontainer) {
				subcontainer = method.createCallMeContainer(true);
					if (subcontainer !== false)
						initdiv.append(subcontainer);
				}
				
				initdiv.insertAfter(easycalldata.element);
			},
			
			// create a container for the passed language
			createCallMeContainer: function(hasmenu) {
				var container = $('<div />', { "class": "easycallcallmecontainer"});
				
				// phone number input field
				var phoneinput = $('<input />', { "class": "easycallphoneinput small", "type": "text" });
				if (easycalldata.defaultphone) {
					phoneinput.val(easycalldata.defaultphone);
				} else {
					phoneinput.addClass("blank");
					phoneinput.val(easycalldata.emptyphonetext);
				}
				// populate the phone input area with some instructional text when empty
				phoneinput.blur(function(e) {
					if (phoneinput.val() == "") {
						phoneinput.addClass("blank");
						phoneinput.val(easycalldata.emptyphonetext);
					}
				});
				phoneinput.focus(function(e) {
					if (phoneinput.val() == easycalldata.emptyphonetext) {
						phoneinput.val("");
						phoneinput.removeClass("blank");
					}
				});
				// observe the enter keypress inside the phone input field.
				phoneinput.keydown(function(e) {
					// enter was pressed?
					if (e.which == 13) {
						e.preventDefault();
						var code = easycalldata.defaultcode;
						if (hasmenu)
							code = selectmenu.val();
						easycalldata.subcontainer[code] = container;
						method.doCall(code);
					}
				});
				
				// button to start the calling session
				var callbutton = $('<button />', { "class": "easycallcallnowbutton record", "value": "Call Now to Record" });
				callbutton.append($('<span />', { "class": "icon" })).append("Call Now to Record");
				
				if (hasmenu) {
					// create a multiselect with remaining languages in it.
					var selectmenu = $('<select />', { "class": "easycallselectmenu" })
					var hasitems = false;
					$.each(easycalldata.subcontainer, function (code) {
						if (easycalldata.subcontainer[code] == false && code != easycalldata.defaultcode) {
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
					var code = easycalldata.defaultcode;
					if (hasmenu)
						code = selectmenu.val();
					easycalldata.subcontainer[code] = container;
					method.doCall(code);
				});
				
				container.append(phoneinput).append(callbutton).append($('<div style="clear:both"/>'));
				return container;
			},
			
			createPreviewContainer: function(code) {
				var container = $('<div />', { "class": "easycallpreviewcontainer"});
				var languagetitle = $('<div />', { "class": "easycalllanguagetitle", "text": easycalldata.language[code] });
				var previewbutton = $('<button />', { "class": "easycallpreviewbutton" });
				previewbutton.append($('<span />', { "text": "Preview" }))
				var removebutton = $('<button />', { "class": "easycallrerecordbutton" });
				
				var audiofileid = easycalldata.recording[code];
				
				if (code == easycalldata.defaultcode)
					removebutton.append($('<span />', { "text": "Re-record" }));
				else
					removebutton.append($('<span />', { "text": "Remove" }));
				
				previewbutton.click(function(e){
					e.preventDefault();
					// Old school preview, loads in a popup
					popup("previewaudio.php?close=1&id="+audiofileid, 400, 500);
				});
				
				
				removebutton.click(function(e){
					e.preventDefault();
					// Disallow message deletion if there is a call in progress
					if (easycalldata.specialtaskid !== false) {
						alert("Cannot remove a recording while a calling session is active.");
						return;
					}
					// Confirm deletion of recording
					var removerecording = confirm("Are you sure you want to delete this recording?");
					if (removerecording)
						method.resetToCallMeContainer(code);
				});
				
				container.append(languagetitle).append(previewbutton).append(removebutton).append($('<div style="clear:both"/>'));
				
				return container;
			},
			
			createProgressContainer: function(code) {
				var container = $('<div />', { "class": "easycallprogresscontainer"});
				var languagetitle = $('<div />', { "class": "easycalllanguagetitle", "text": easycalldata.language[code] });
				var progresstext = $('<div />', { "class": "call-progress" });
				progresstext.append($('<span />', { "class": "icon" })).append($('<span />', { "class": "easycallprogresstext" }))
				
				container.append(languagetitle).append(progresstext).append($('<div style="clear:both"/>'));
				
				return container;
			},
			
			// create a container for error states with a reset button
			createErrorContainer: function(code, errortext) {
				var container = $('<div />', { "class": "easycallerrorcontainer"});
				var languagetitle = $('<div />', { "class": "easycalllanguagetitle", "text": easycalldata.language[code] });
				var resetbutton = $('<button />', { "class": "easycallerrorbutton" });
				resetbutton.append($('<span />', { "class": "icon" })).append($('<span />').append("Retry"));
				container.append(languagetitle)
						.append($('<span />', { "class": "easycallerrortext" }).append(errortext))
						.append(resetbutton)
						.append($('<div style="clear:both"/>'));
				
				resetbutton.click(function (e) {
					e.preventDefault();
					method.resetToCallMeContainer(code);
				});
				
				return container;
			},
			
			// intelegently replace the current container with a callme container
			resetToCallMeContainer: function(code) {
				// clean up the old data for this code
				easycalldata.subcontainer[code].remove();
				easycalldata.subcontainer[code] = false;
				easycalldata.recording[code] = false;
				method.updateParentElement();
				
				// remove existing call containers, we can only have one shown at a time
				var callmecontainers = easycalldata.maincontainer.children(".easycallcallmecontainer");
				var hasdefaultcallmecontainer = false;
				$.each(callmecontainers, function (index) {
					// leave behind the default... remember that it exists
					if ($(callmecontainers[index]).children(".easycallselectmenu").length == 0)
						hasdefaultcallmecontainer = true;
					else
						$(callmecontainers[index]).remove();
				});
				
				// get a new callme container!
				var callmecontainer = false;
				if (code == easycalldata.defaultcode) {
					callmecontainer = method.createCallMeContainer(false);
					easycalldata.subcontainer[code] = callmecontainer;
					easycalldata.maincontainer.prepend(callmecontainer);
				} else {
					if (!hasdefaultcallmecontainer)
						easycalldata.maincontainer.append(method.createCallMeContainer(true));
				}
			},
			
			// replace the existing container with the new one
			replaceContainer: function(code, newcontainer) {
				newcontainer.insertAfter(easycalldata.subcontainer[code]);
				easycalldata.subcontainer[code].remove();
				easycalldata.subcontainer[code] = newcontainer;
			},
			
			
			//============================================================================================
			// Application logic
			// start the process by making a phone call to the provided number
			doCall: function (code) {
				// get the phone number
				var phone = easycalldata.subcontainer[code].children(".easycallphoneinput").val();
				
				// validate the phone
				var valid = method.validatePhone(phone);
				if (valid !== true) {
					easycalldata.subcontainer[code].children(".easycallphoneinvalid").remove();
					if (phone == "")
						phone = easycalldata.emptyphonetext;
					easycalldata.subcontainer[code].append($('<div />', { "class": "easycallphoneinvalid", "text": phone + " " + valid }));
					// if this isn't the record area for the default, clear the container association
					if (code != easycalldata.defaultcode)
						easycalldata.subcontainer[code] = false;
					return;
				}
				easycalldata.defaultphone = phone;
				
				var progresscontainer = method.createProgressContainer(code);
				var progresstext = progresscontainer.find(".easycallprogresstext");
				
				progresstext.empty().append("Calling: " + phone);
				
				method.replaceContainer(code, progresscontainer);
				
				$.post("ajaxeasycall.php", {"action":"new","phone":phone}, function(data){
					progresstext.empty().append("Initiated...");
					
					easycalldata.specialtaskid = data.id;
					// on a timer, query the status
					easycalldata.timer = $.timer(function(){
						$.post("ajaxeasycall.php", {"action":"status","id":easycalldata.specialtaskid}, function(data){
							// get the status
							var status = method.handleStatus(data);
							progresstext.empty().append(status.message);
							if (!status.error && status.complete) {
								easycalldata.timer.stop();
								// save audiofile
								method.doSaveAudioFile(code);
							}
							if (status.error) {
								easycalldata.specialtaskid = false;
								easycalldata.timer.stop();
								
								// transition to error mode
								method.replaceContainer(code, method.createErrorContainer(code, status.message));
							}
						},"json")
						.error(function() {
							easycalldata.specialtaskid = false;
							easycalldata.timer.stop();
							// transition to error mode
							method.replaceContainer(code, method.createErrorContainer(code, "An error occured while getting the status of a call."));
						});
					}).set({time: 2000, autostart: true});
				},"json")
				.error(function() {
					easycalldata.specialtaskid = false;
					// transition to error mode
					method.replaceContainer(code, method.createErrorContainer(code, "An error occured while attempting a new call."));
				});
			},
			
			// save the audiofile and get the audiofileid
			doSaveAudioFile: function (code) {
				$.post("ajaxeasycall.php", {
						"action": "getaudiofile",
						"id": easycalldata.specialtaskid,
						"name": easycalldata.language[code]},
						function(data){
					
					if (data.audiofileid) {
						easycalldata.recording[code] = data.audiofileid;
						
						// create and load the preview container
						method.replaceContainer(code, method.createPreviewContainer(code));
						
						// create a new call me container 
						var callmecontainer = method.createCallMeContainer(true);
						if (callmecontainer !== false)
							easycalldata.maincontainer.append(callmecontainer);
						
						method.updateParentElement();
					} else {
						// transition to error mode
						method.replaceContainer(code, method.createErrorContainer(code, "An error occured while attempting to save audio."));
					}
				},"json")
				.error(function() {
					// transition to error mode
					method.replaceContainer(code, method.createErrorContainer(code, "An error occured while requesting save audio."));
				});
				easycalldata.specialtaskid = false;
			},
			
			// save the current recording data into the parent element
			updateParentElement: function () {
				// update json data in parent input field
				var itemdata = {};
				$.each(easycalldata.recording, function (code) {
					if (easycalldata.recording[code] !== false)
						itemdata[code] = easycalldata.recording[code]
				});
				easycalldata.element.val($.toJSON(itemdata));
				
				// data was changed. trigger a change event on the parent element
				var updateevent = jQuery.Event("change");
				easycalldata.element.trigger(updateevent);
			},
			
			// read the return status and provide appropriate error handling messages
			handleStatus: function(data) {
				var complete = false;
				var error = false;
				var message = "";
				if (data.status == "done")
					complete = true;
				switch(data.error) {
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
