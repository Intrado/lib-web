(function ($) {
	$.fn.detachEasyCall = function () {
		return this.each(function () {
			var $this = $(this);
			// if there is easyCall data, then it has been initialized
			if ($this.data('easyCall')) {
				// kill any running timer
				if ($this.data('easyCall').timer)
					$this.data('easyCall').timer.stop();

				// destroy all the easycall DOM containers by removing their parent
				if ($this.data('easyCall').maincontainer)
					$this.data('easyCall').maincontainer.remove();

				$this.data('easyCall', null);
			}
		});
	};

	$.fn.resetEasyCall = function () {
		return this.each(function () {
			var $this = $(this);
			if ($this.data('easyCall')) {
				$this.detachEasyCall();
				$this.attachEasyCall($this.data('easyCall'));
			}
		});
	};

	$.fn.attachEasyCall = function (options) {

		var method = {
			//============================================================================================
			// DOM rendering methods
			// set up the easycall elements, hiding the intial input element
			init:function (options, element) {
				var $this = $(this);
				$this.hide();
				var defaultdata = $.extend({
					"element":$(element),
					"maincontainer":false,
					"subcontainer":{"en":false},
					"languages":{"en":"English"},
					"recording":{"en":false},
					"specialtaskid":false,
					"timer":false,
					"reqcount":0,
					"defaultcode":"en",
					"phonemindigits":10,
					"phonemaxdigits":10,
					"defaultphone":"",
					"emptyphonetext":"Number to Call",
					"emptyextensiontext":"Extension"}, $(element).data('easyCall'));

				var easycalldata = $.extend(defaultdata, options);
				$this.data('easyCall', easycalldata);
				// FIXME: Oops. looks like data is attached to the wrong "this". Add a pointer here for data access on the element (for now).
				$(element).data('easyCall', $this.data('easyCall'));

				// load existing values from attached input
				var elementval = $(element).val();
				var elementdata = {};
				if (elementval !== undefined && elementval !== null && elementval !== "" && elementval !== "undefined")
					elementdata = $.secureEvalJSON(elementval);

				$.each(elementdata, function (code) {
					// do a sanity check, then stuff the value in the recordings list
					if ($this.data('easyCall').languages[code])
						$this.data('easyCall').recording[code] = elementdata[code];
				});

				// destroy all the easycall DOM containers by removing their parent
				if ($this.data('easyCall').maincontainer)
					$this.data('easyCall').maincontainer.remove();

				// remove all existing references to old DOM subcontainers
				$.each($this.data('easyCall').subcontainer, function (code) {
					$this.data('easyCall').subcontainer[code] = false;
				});

				var initdiv = $('<div />', {"class":"easycallmaincontainer easycall-widget easycall"});
				$this.data('easyCall').maincontainer = initdiv;

				// add sub-containers for the pre-recorded languages
				var subcontainer = false;
				var needscallmecontainer = true;
				// always add the default first
				if ($this.data('easyCall').recording[$this.data('easyCall').defaultcode] !== undefined &&
					$this.data('easyCall').recording[$this.data('easyCall').defaultcode] !== false) {
					subcontainer = method.createPreviewContainer($this.data('easyCall').defaultcode);
				} else {
					// add the default callme container
					subcontainer = method.createCallMeContainer(false);
					needscallmecontainer = false;
				}
				$this.data('easyCall').subcontainer[$this.data('easyCall').defaultcode] = subcontainer;
				initdiv.prepend(subcontainer);

				// add all others which have recordings
				$.each($this.data('easyCall').recording, function (code) {
					if (code != $this.data('easyCall').defaultcode && $this.data('easyCall').recording[code] !== false) {
						subcontainer = method.createPreviewContainer(code);
						$this.data('easyCall').subcontainer[code] = subcontainer;
						initdiv.append(subcontainer);
					}
				});

				// add a new call me container with menu
				if (needscallmecontainer) {
					subcontainer = method.createCallMeContainer(true);
					if (subcontainer !== false)
						initdiv.append(subcontainer);
				}

				$this.data('easyCall').extensionValidatorRegex = new RegExp("^" + $this.data('easyCall').emptyextensiontext + "$", "i");

				initdiv.insertAfter($this.data('easyCall').element);
			},

			// create a container for the passed language
			createCallMeContainer:function (hasmenu) {
				var $this = $(this);

				var container = $('<div />', { "class":"easycallcallmecontainer", 'html': '<div class="input-prepend input-append"></div>'});
				var inputContainer = container.find('.input-prepend');
				var phoneinput = $('<input />', { "class":"easycallphoneinput span2", "type":"text", 'placeholder':'Number to Call'});
				var extensioninput = $('<input />', { "class":"easycallextensioninput", "type":"text", 'placeholder':'Extension', 'rel':'tooltip', 'title':"Include extension, ex. 555 (optional)", "data-container": "body"});

				var initInputVal = function(elem, easyCallVal, easyCallEmptyText) {
					if (easyCallVal) {
						elem.val(easyCallVal);
					} else {
						elem.addClass("blank");
						elem.val(easyCallEmptyText);
					}
				};

				initInputVal(phoneinput, $this.data('easyCall').defaultphone, $this.data('easyCall').emptyphonetext);
				initInputVal(extensioninput, $this.data('easyCall').extension, $this.data('easyCall').emptyextensiontext);

				// populates the phone input area with some instructional text when empty
				var blurHandler = function(elem, emptyText) {
					return function () {
						if (elem.val() == "") {
							elem.addClass("blank");
							elem.val(emptyText);
						}
					}
				};

				var focusHandler = function(elem, emptyText) {
					return function () {
						if (elem.val() == emptyText) {
							elem.val("");
							elem.removeClass("blank");
						}
					}
				};

				var keyDownHandler = function(easyCallDataObj, validateFcn, container) {
					$this = $(this);
					return function (e) {
						if (easyCallDataObj.timer)
							easyCallDataObj.timer.stop();
						// enter was pressed?
						if (e.which == 13) {
							e.preventDefault();
							var code = easyCallDataObj.defaultcode;
							if (hasmenu)
								code = selectmenu.val();
							easyCallDataObj.subcontainer[code] = container;
							method.doCall(code);
						} else {
							// set a timer to validate the phone number
							easyCallDataObj.timer = $.timer(function () {
								validateFcn.call($this, container);
							}).set({time:500, autostart:true});
						}
					}
				};

				var phoneBlurHandler 		= blurHandler(phoneinput, $this.data('easyCall').emptyphonetext);
				var extensionBlurHandler 	= blurHandler(extensioninput, $this.data('easyCall').emptyextensiontext);

				var phoneFocusHandler 		= focusHandler(phoneinput, $this.data('easyCall').emptyphonetext);
				var extensionFocusHandler 	= focusHandler(extensioninput, $this.data('easyCall').emptyextensiontext);

				var phoneKeydownHandler 	= keyDownHandler.call(this, $this.data('easyCall'), method.valPhoneField, container);
				var extensionKeydownHandler = keyDownHandler.call(this, $this.data('easyCall'), method.valExtensionField, container);

				phoneinput.blur(phoneBlurHandler)
						  .focus(phoneFocusHandler)
						  .keydown(phoneKeydownHandler);

				extensioninput.blur(extensionBlurHandler)
							  .focus(extensionFocusHandler)
							  .keydown(extensionKeydownHandler);

				var prependSpan = $('<span />', {'class':'add-on'});
				prependSpan.append($('<span class="sprite-glyphicons-phone"></span>'));

				var extensionSpan = $('<span />', {'class':'add-on extension'});
				extensionSpan.text('Optional:');

				// button to start the calling session
				var callbutton = $('<button />', { "class":"record btn btn-success", "value":"Call Now to Record"});
				callbutton.append($('<i />', { "class":"icon-hand-left icon-white" })).append(" &nbsp;Call Now to Record");

				if (hasmenu) {
					// create a multiselect with remaining languages in it.
					var infoalert = $('<div />', { 'class': 'alert alert-info', 'html': '<i class="icon-info-sign"></i> (Optional) Record this message in another language' });
					var selectmenu = $('<select />', { "class":"easycallselectmenu add-on" });
					var hasitems = false;
					selectmenu.append($('<option />', { text: 'Select Language', value: '' }));
					$.each($this.data('easyCall').languages, function (code) {
						if (!$this.data('easyCall').subcontainer[code] && code != $this.data('easyCall').defaultcode) {
							// insert item into selector
							var option = $('<option />', { "value":code, "text":$this.data('easyCall').languages[code]});
							selectmenu.append(option);
							hasitems = true;
						}
					});
					if (!hasitems)
						return false;
					inputContainer.before(infoalert);
					inputContainer.append(selectmenu);
				}

				callbutton.click(function (e) {
					e.preventDefault();
					var code = $this.data('easyCall').defaultcode;
					if (hasmenu) {
						if (selectmenu.val()) {
							code = selectmenu.val();
						} else {
							return;
						}
					}
					$this.data('easyCall').subcontainer[code] = container;
					method.doCall(code);
				});

				inputContainer.append(prependSpan).append(phoneinput).append(extensionSpan).append(extensioninput).append(callbutton);

				return container;
			},

			createPreviewContainer:function (code) {
				var $this = $(this);
				var language = $this.data('easyCall').languages[code];
				var recordingId = $this.data('easyCall').recording[code];

				var container = $('<div />', { "class":"easycallpreviewcontainer"});
				var btnGroupContainer = $('<div />', { "class":"btn-group"});
				var languagetitle = $('<div />', { "class":"easycalllanguagetitle"});
				var languagetitleSpan = $('<span />', { "class":"easycalllanguagetitlewrapper"});
				languagetitle.append('<span class="lighten sprite-pictos-phone padR0"></span>  &nbsp; ').append(languagetitleSpan.append(language));
				var previewbutton = $('<button />', { "class":"easycallpreviewbutton btn", "href":"#ctr_play_audio_modal", "data-language":language, "data-language":code, "data-recordingId":recordingId, "data-toggle":"modal", "title":"Play audio of " + language + " language voice recording", "rel":"tooltip", "data-container": "#callme" });
				previewbutton.html('<span class="sprite-pictos-play padR0"><i></i></span>');
				var removebutton = $('<button />', { "class":"easycallrerecordbutton btn" });

				previewbutton.on('click', function () {
					$this.data('easyCall').element.trigger("easycall:preview", { recordingId: recordingId, languageCode: code, language: language });
				});


				if (code == $this.data('easyCall').defaultcode)
					removebutton.attr({"title":"Re-record " + language + " language voice recording", "rel":"tooltip", "data-container": "#callme"}).html('<span class="sprite-pictos-refresh padR0"><i></i></span>');
				else
					removebutton.attr({"title":"Remove " + language + " language voice recording", "rel":"tooltip", "data-container": "#callme"}).append($('<span />', {"class":"sprite-glyphicons-remove_2 pad0"}));

				removebutton.click(function (e) {
					e.preventDefault();
					// Disallow message deletion if there is a call in progress
					if ($this.data('easyCall').specialtaskid !== false) {
						alert("Cannot remove a recording while a calling session is active.");
						return;
					}
					// Confirm deletion of recording
					var removerecording = confirm("Are you sure you want to delete this recording?");
					if (removerecording)
						method.resetToCallMeContainer(code);
				});

				container.append(languagetitle).append(btnGroupContainer.append(previewbutton).append(removebutton)).append($('<div style="clear:both"/>'));

				return container;
			},

			createProgressContainer:function (code) {
				var $this = $(this);

				var progressBarWrapper = $('<div />', { "class":"progress progress-success progress-striped active"});
				var progressBar = $('<div />', {"class": "bar"}).attr("data-loading-percent","100");
				var container = $('<div />', { "class":"easycallprogresscontainer"});
				var languagetitle = $('<div />', { "class":"easycalllanguagetitle"});
				var languagetitleSpan = $('<span />', { "class":"easycalllanguagetitlewrapper"});
				languagetitle.append('<span class="sprite-pictos-phone padR0"></span>  &nbsp; ').append(languagetitleSpan.append($this.data('easyCall').languages[code]));
				progressBar.append($('<span />', { "class":"easycallprogresstext" }));
				progressBarWrapper.append(progressBar);
				container.append(languagetitle).append(progressBarWrapper);

				return container;
			},

			// create a container for error states with a reset button
			createErrorContainer:function (code, errortext) {
				var $this = $(this);

				var container = $('<div />', { "class":"easycallerrorcontainer alert alert-error"});
				var languagetitle = $('<div />', { "class":"easycalllanguagetitle", "text":$this.data('easyCall').languages[code] });
				var languagetitleSpan = $('<span />', { "class":"easycalllanguagetitlewrapper"});
				var resetbutton = $('<button />', { "class":"easycallerrorbutton btn btn-danger pull-right" });
				resetbutton.append("Retry");
				container.append(languagetitleSpan.append(languagetitle))
					.append($('<span />', { "class":"error-msg" })
					.append('<span class="label label-important"><i class="icon-exclamation-sign icon-white"></i></span> &nbsp;<strong>Error: &nbsp;</strong>')
					.append(errortext))
					.append(resetbutton)
					.append($('<div style="clear:both"/>'));

				resetbutton.click(function (e) {
					e.preventDefault();
					method.resetToCallMeContainer(code);
				});

				return container;
			},

			// intelligently replace the current container with a callme container
			resetToCallMeContainer:function (code) {
				var $this = $(this);

				// clean up the old data for this code
				$this.data('easyCall').subcontainer[code].remove();
				$this.data('easyCall').subcontainer[code] = false;
				$this.data('easyCall').recording[code] = false;
				method.updateParentElement();

				// remove existing call containers, we can only have one shown at a time
				var callmecontainers = $this.data('easyCall').maincontainer.find(".easycallcallmecontainer");
				var hasdefaultcallmecontainer = false;
				$.each(callmecontainers, function (index) {
					// leave behind the default... remember that it exists
					if ($(callmecontainers[index]).find(".easycallselectmenu").length == 0)
						hasdefaultcallmecontainer = true;
					else
						$(callmecontainers[index]).remove();
				});

				// get a new callme container!
				var callmecontainer = false;
				if (code == $this.data('easyCall').defaultcode) {
					callmecontainer = method.createCallMeContainer(false);
					$this.data('easyCall').subcontainer[code] = callmecontainer;
					$this.data('easyCall').maincontainer.prepend(callmecontainer);
				} else {
					if (!hasdefaultcallmecontainer)
						$this.data('easyCall').maincontainer.append(method.createCallMeContainer(true));
				}
			},

			// replace the existing container with the new one
			replaceContainer:function (code, newcontainer) {
				var $this = $(this);

				newcontainer.insertAfter($this.data('easyCall').subcontainer[code]);
				$this.data('easyCall').subcontainer[code].remove();
				$this.data('easyCall').subcontainer[code] = newcontainer;
			},


			//============================================================================================
			// Application logic

			valPhoneField:function (container) {
				container.find(".easycallphoneinvalid").remove();
				var $this = $(this);

				// get the phone number
				var phone = container.find(".easycallphoneinput").val();

				// validate the phone
				var valid = method.validatePhone(phone);
				if (valid !== true) {
					if (phone == "")
						phone = $this.data('easyCall').emptyphonetext;
					container.append($('<div />', { "class":"easycallphoneinvalid easycallerrorcontainer alert alert-error", "html":valid })); //phone + " " + valid }));
					return false;
				}
				return true;
			},

			valExtensionField:function (container) {
				container.find(".easycallextensioninvalid").remove();
				var $this = $(this);

				// get the extension number
				var extension = container.find(".easycallextensioninput").val();

				// validate the extension
				var valid = method.validateExtension(extension);
				if (valid !== true) {
					if (extension == "")
						extension = 'Extension';
					container.append($('<div />', { "class":"easycallextensioninvalid easycallerrorcontainer alert alert-error", "html":valid }));
					return false;
				}
				return true;
			},

			// start the process by making a phone call to the provided number
			doCall:function (code) {
				var $this = $(this);

				var valid = method.valPhoneField($this.data('easyCall').subcontainer[code]);

				if (!valid) {
					// if this isn't the record area for the default, clear the container association
					if (code != $this.data('easyCall').defaultcode)
						$this.data('easyCall').subcontainer[code] = false;
					return;
				}

				// get the phone number
				var phone = $this.data('easyCall').subcontainer[code].find(".easycallphoneinput").val();

				// get the phone extension, if any
				var extension = $this.data('easyCall').subcontainer[code].find(".easycallextensioninput").val();

				// validate the phone
				var validPhone = method.validatePhone(phone);
				var validExtension = method.validateExtension(extension);

				// clean extension of any non-numeric values, namely the possible placeholder text 'Extension'
				extension = method.removeNonNumericValues(extension);

				if (validPhone !== true) {
					$this.data('easyCall').subcontainer[code].find(".easycallphoneinvalid").remove();
					if (phone == "")
						phone = $this.data('easyCall').emptyphonetext;
					$this.data('easyCall').subcontainer[code].append($('<div />', { "class":"easycallphoneinvalid", "html":phone + " " + validPhone }));
					// if this isn't the record area for the default, clear the container association
					if (code != $this.data('easyCall').defaultcode)
						$this.data('easyCall').subcontainer[code] = false;
					return;
				}

				if (validExtension !== true) {
					$this.data('easyCall').subcontainer[code].find(".easycallextensioninvalid").remove();
					$this.data('easyCall').subcontainer[code].append($('<div />', { "class":"easycallextensioninvalid easycallerrorcontainer alert alert-error", "html":extension + " " + validExtension }));
					return;
				}

				$this.data('easyCall').defaultphone = phone;
				$this.data('easyCall').extension = extension;

				//$this.data('easyCall').element.trigger("easycall:startcall", this);

				var progresscontainer = method.createProgressContainer(code);
				var progresstext = progresscontainer.find(".easycallprogresstext");

				progresstext.empty().append("Calling: " + phone);

				method.replaceContainer(code, progresscontainer);

				$.post("ajaxeasycall.php", {"action":"new", "phone":phone, "extension":extension}, function (data) {
					progresstext.empty().append("Initiated...");
					if (!data) {
						$this.data('easyCall').specialtaskid = false;
						method.replaceContainer(code, method.createErrorContainer(code, "An error occured while setting up the call."));
						//$this.data('easyCall').element.trigger("easycall:endcall", this);
					} else {
						$this.data('easyCall').specialtaskid = data.id;
						// on a timer, query the status
						$this.data('easyCall').timer = $.timer(function () {
							$.post("ajaxeasycall.php", {"action":"status", "id":$this.data('easyCall').specialtaskid}, function (data) {
								// get the status
								var status = method.handleStatus(data);
								progresstext.empty().append(status.message + "...");
								if (!status.error && status.complete) {
									$this.data('easyCall').timer.stop();
									//$this.data('easyCall').element.trigger("easycall:endcall", this);
									// save audiofile
									method.doSaveAudioFile(code);
								}
								if (status.error) {
									$this.data('easyCall').specialtaskid = false;
									$this.data('easyCall').timer.stop();
									//$this.data('easyCall').element.trigger("easycall:endcall", this);
									// transition to error mode
									method.replaceContainer(code, method.createErrorContainer(code, status.message));
								}
							}, "json")
								.error(function () {
									$this.data('easyCall').specialtaskid = false;
									$this.data('easyCall').timer.stop();
									//$this.data('easyCall').element.trigger("easycall:endcall", this);

									// transition to error mode
									method.replaceContainer(code, method.createErrorContainer(code, "An error occured while getting the status of a call."));
								})
						}).set({time:2000, autostart:true});
					}
				}, "json")
					.error(function () {
						$this.data('easyCall').specialtaskid = false;
						// transition to error mode
						method.replaceContainer(code, method.createErrorContainer(code, "An error occured while attempting a new call."));
					});
			},

			// save the audiofile and get the audiofileid
			doSaveAudioFile:function (code) {
				var $this = $(this);

				$.post("ajaxeasycall.php", {
						"action":"getaudiofile",
						"id":$this.data('easyCall').specialtaskid,
						"name":$this.data('easyCall').languages[code] + " - " + curDate()},
					function (data) {

						if (data.audiofileid) {
							$this.data('easyCall').recording[code] = data.audiofileid;

							// create and load the preview container
							method.replaceContainer(code, method.createPreviewContainer(code));

							// create a new call me container
							var callmecontainer = method.createCallMeContainer(true);
							if (callmecontainer !== false)
								$this.data('easyCall').maincontainer.append(callmecontainer);

							method.updateParentElement();
						} else {
							// transition to error mode
							method.replaceContainer(code, method.createErrorContainer(code, "An error occurred while attempting to save audio."));
						}
					}, "json")
					.error(function () {
						// transition to error mode
						method.replaceContainer(code, method.createErrorContainer(code, "An error occurred while requesting save audio."));
					});
				$this.data('easyCall').specialtaskid = false;
			},

			// save the current recording data into the parent element
			updateParentElement:function () {
				var $this = $(this);

				// update json data in parent input field
				var itemdata = {};
				// construct some interesting data to return with the event
				var eventData = { recordings: [] };
				$.each($this.data('easyCall').recording, function (languageCode) {
					if ($this.data('easyCall').recording[languageCode] !== false) {
						var recordingId = $this.data('easyCall').recording[languageCode];
						var language = $this.data('easyCall').languages[languageCode];
						itemdata[languageCode] = recordingId;
						eventData.recordings.push({ recordingId: recordingId, languageCode: languageCode, language: language });
					}
				});
				$this.data('easyCall').element.val($.toJSON(itemdata));

				// data was changed. trigger an event on the parent element
				$this.data('easyCall').element.trigger("easycall:update", eventData);
			},

			// read the return status and provide appropriate error handling messages
			handleStatus:function (data) {
				var $this = $(this);

				var complete = false;
				var error = false;
				var message = "";
				switch (data.status) {
					case "new":
						$this.data('easyCall').reqcount++;
						break;
					case "done":
						complete = true;
					default:
						$this.data('easyCall').reqcount = 0;
				}
				switch (data.error) {
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
				// if the specialtask is still "new" after 15 requests (30 seconds), something is wrong
				if ($this.data('easyCall').reqcount > 15) {
					error = true;
					message = "Call setup has timed out. Please try again or contact your system administrator.";
				}
				return {
					"complete":complete,
					"error":error,
					"message":message
				};
			},

			// TODO: Replace with call to Phone Validator
			// validate a phone number
			validatePhone:function (value) {
				var $this = $(this);

				var phone = method.removeNonNumericValues(value);
				if ($this.data('easyCall').phonemindigits == $this.data('easyCall').phonemaxdigits && $this.data('easyCall').phonemaxdigits == 10 && phone.length == 10) {
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
					if ($this.data('easyCall').phonemindigits != 10 || $this.data('easyCall').phonemaxdigits != 10) {
						if (phone.length < $this.data('easyCall').phonemindigits)
							return "is invalid. The phone number or extension must be at least " + $this.data('easyCall').phonemindigits + " digits long.\nYou do not need to include a 1 for long distance.";
						if (phone.length > $this.data('easyCall').phonemaxdigits)
							return "is invalid. The phone number or extension must be no more than " + $this.data('easyCall').phonemaxdigits + " digits long.\nYou do not need to include a 1 for long distance.";
					} else {
						if (phone.length == 0)
							return 'A valid 10-digit phone number is required.<br/> Ex. (555) 123-4567, 555-123-4567, 555.123.4567, 5551234567';
						else
							return 'Please enter a valid 10-digit phone number.<br/>Ex. (555) 123-4567, 555-123-4567, 555.123.4567, 5551234567';
					}
					return true;
				}
			},

			removeNonNumericValues: function(string) {
				return string ? string.replace(/[^0-9]/g, "") : "";
			},

			// Validate extension number. Current validation permits a maximum of 10 digits. All non-numeric characters are parsed out.
			validateExtension: function (value) {
				var $this = $(this);

				var trimmedExtension = $.trim(value);				
				isPlaceholderText = trimmedExtension.search($this.data('easyCall').extensionValidatorRegex) > -1;

				if (trimmedExtension.length > 0) {
					if ((value.search(/\d+/g)) > -1) {
						if ((method.removeNonNumericValues(value)).length <= 10)
							return true;
						else
							return 'Extensions are limited to a maximum of 10 digits. Please enter an extension with 10 or fewer digits.';
					}
					else if (isPlaceholderText)
						return true;
					else
						return 'Extensions require 1 or more digits (0-9), up to a maximum of 10 digits.';
				}
				else if (isPlaceholderText)
					return true;
				else
					return true;
			}
		};

		return this.each(function () {
			method.init(options, this);
		});
	};
})(jQuery);
