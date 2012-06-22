var contentMap = {
	"phone" : "#msgsndr_tab_phone",
	"email" : "#msgsndr_tab_email",
	"sms" : "#msgsndr_tab_sms",
	"social" : "#msgsndr_tab_social"
};

var allowControl = {
	"phone" : function() {
		var $ = jQuery;
		// Build up select box based on the maxjobdays user permission
		var daysToRun = userPermissions.maxjobdays;
		$('#msgsndr_form_daystorun').empty();
		for (i = 1; i <= daysToRun; i++) {
			$('#msgsndr_form_days').append('<option value="' + i + '">' + i + '</option>');
		}

		// Hide / Show Translations
		$('#text').on('click', '.toggle-translations', function(event) {
			event.preventDefault();

			var text = $(this).text().split(" ");

			$(this).text(text[0] == 'Show' ? 'Hide ' + text[1] + ' ' + text[2] : 'Show ' + text[1] + ' ' + text[2]);

			var etarget = $(this).attr('data-target');
			$(etarget).slideToggle();
			$(this).toggleClass('active');

			$('#tts_retranslate').remove();

			if(text[0] == 'Show') {
				ttsTranslate(this);
				$(this).parent().append(' <button id="tts_retranslate" data-target="#tts_translate">Re Translate</button>');
			}

		});

		$('#text').on('click', '#tts_retranslate', function() {
			ttsTranslate(this);
		});

		function ttsTranslate(elem) {
			var txtField = $('#msgsndr_tts_message').val();
			var displayArea = $(elem).attr('data-target');
			var msgType = 'tts';

			var ttslangCodes = '';

			var checkTranslations = $('input[name^=tts_override]');
			$.each(checkTranslations, function(tIndex, tData) {
				lCode = $(tData).attr('name').split('_')[2];
				if ($(tData).is(':checked')) {

				} else {
					if (ttslangCodes == '') {
						ttslangCodes = lCode
					} else {
						ttslangCodes = ttslangCodes + '|' + lCode;
					}
				}
			});

			$('#tts_translate fieldset > label[for^=tts_]').append('<img src="img/ajax-loader.gif" class="loading" />');

			doTranslate(ttslangCodes, txtField, displayArea, msgType);
		};

		$('#tts_translate').on('click', '.show_hide_english', function(e) {
			e.preventDefault();

			var langCode = $(this).attr('data-code');
			$('#retranslate_' + langCode).slideToggle('fast');

			if ($(this).text() == "Show In English") {
				reTranslate(this);
			}

			$(this).text($(this).text() == 'Show In English' ? 'Hide English' : 'Show In English');

		});

		$('#tts_translate').on('click', '.retranslate', function(e) {
			e.preventDefault();
			reTranslate(this);
		});

		// Override Translation
		$('#tts_translate').on('click', 'input[name^=tts_override]', function() {
			var langCode = $(this).attr('name').split('_')[2];
			var checkedState = $(this).attr('checked');

			if (typeof (checkedState) != "undefined") {
				$(this).data('translatedtext', $('#tts_translated_' + langCode).val());
				$('#tts_translated_' + langCode).removeAttr('disabled');
			} else {
				var revertTranslation = confirm('The translation will be put back to the previous translation');
				if (!revertTranslation) {
					$(this).attr('checked', 'checked');
					$('#tts_translated_' + langCode).removeAttr('disabled');
				} else {
					// $('#tts_translated_'+langCode)
					$('#tts_translated_' + langCode).attr('disabled', 'disbaled');
					$('#tts_translated_' + langCode).val($(this).data('translatedtext'));
				}
			}
		});

		var splitlangCodes = ttslangCodes.split('|');
		var langCount = splitlangCodes.length;

		if (langCount == 1) {
			$('a[data-target=#tts_translate]').show().text('Show ' + langCount + ' translation');
		} else {
			$('a[data-target=#tts_translate]').show().text('Show ' + langCount + ' translations');
		}

		$.each(splitlangCodes, function(transIndex, transData) {
			var langCode = splitlangCodes[transIndex];

			var ttsTranslate = '<fieldset>';

	        ttsTranslate += '<input type="checkbox" checked="checked" name="save_translation" class="translations" id="tts_'+langCode+'" />';
	        ttsTranslate += '<label for="tts_'+langCode+'">'+nLangs[langCode]+'</label>';
	        ttsTranslate += '<div class="controls">';
	        ttsTranslate += '<textarea id="tts_translated_'+langCode+'" disabled="disabled"></textarea>';
	        ttsTranslate += '<button class="playAudio" data-text="tts_translated_'+langCode+'" data-code="'+langCode+'"><span class="icon play"></span> Play Audio</button>';
	        ttsTranslate += '<button class="show_hide_english" data-text="'+nLangs[langCode]+'" data-code="'+langCode+'">Show In English</button>';
	        ttsTranslate += '<input type="checkbox" name="tts_override_'+langCode+'" id="tts_override_'+langCode+'" /><label for="tts_override_'+langCode+'">Override Translation</label>';
	        ttsTranslate += '</div>';
	        ttsTranslate += '<div class="controls hide" id="retranslate_'+langCode+'">';
	        ttsTranslate += '<button class="retranslate" data-text="'+nLangs[langCode]+'" data-code="'+langCode+'">Refresh '+nLangs[langCode]+' to English Translation</button>';
	        ttsTranslate += '<textarea id="tts_'+nLangs[langCode]+'_to_english" disabled="disabled"></textarea>';
	        ttsTranslate += '</fieldset>';

			$('#tts_translate').append(ttsTranslate);
		});

		// Caller ids
		// //////////////////////////////////

		// determine whether we show or hide the callerId
		function callerIdDisplay() {
			var callerIdnumber = false;

			// If hascallback isn't enabled,
			// check for orgOptions.requiredapprovedcallerid,
			// then subsequently for userPermissions.setcallerid
			if (orgOptions._hascallback == 0) {
				$('#msgsndr_form_callid').on('change', function() {
					if ($('option:selected', this).val() == 'other') {
						$('#callerid_other_wrapper').removeClass('hidden');
						$("#callerid_other").val("");
					} else {
						$('#callerid_other_wrapper').addClass('hidden');
						$("#callerid_other").val($('option:selected', this).val());
					}
				})
				
				if (typeof (orgOptions.requireapprovedcallerid) != 'undefined' && orgOptions.requireapprovedcallerid == 1) {
					// get the users callerid's ...
					var userCallerIds = getUserCallerIds();

					// and append them as options to the select menu ...
					$.each(userCallerIds, function(cIndex, cItem) {
						$('#msgsndr_form_callid').append('<option value="' + cItem + '" >' + formatPhone(cItem) + '</option>');
					});
					
					$('#msgsndr_form_callid').trigger("change");

					// if the users setcallerid permission is defined,
					// add the 'other' option and create a text input for
					// them to add arbitrary value, and validate it.
					if (typeof (userPermissions.setcallerid) != 'undefined' && userPermissions.setcallerid == 1) {
						$('#msgsndr_form_callid').append('<option value="other" >Other</option>');
					}

				} else { // not sure here, set the default callerid and display the select with that as the option?
					var callerIdnumber = getDefaultCallerId();
					$('#msgsndr_form_callid').append('<option value="' + callerIdnumber + '" selected >' + formatPhone(callerIdnumber) + '</option>');
					
					$('#msgsndr_form_callid').trigger("change");
				}

			} else { // the user hascallback so we hide caller id select
						// fieldset from view
				$('#msgsndr_form_callid').closest('fieldset').addClass('hidden');
				// Commented out the following code, I believe there should
				// be no callerid passed to postdata for users with
				// 'hascallback'

				/*
				 * get the default caller id and append it as the selected
				 * option in the hidden callerid select menu var
				 * callerIdnumber = getDefaultCallerId();
				 * $('#msgsndr_form_callid').append('<option
				 * value="'+callerIdnumber+'" selected
				 * >'+formatPhone(callerIdnumber)+'</option>');
				 */
			}
		};

		// call the callerIdDisplay function...
		callerIdDisplay();

		// get the default caller id depending on settings, check the user
		// role permissions first,
		// if that isn't set, then get the callerid from system options.
		function getDefaultCallerId() {
			var userCallerId = userPermissions.callerid;
			var orgCallerId = orgOptions.callerid;

			if (typeof (userCallerId) == 'undefined') {
				return orgCallerId;
			} else {
				return userCallerId;
			}
		};

		// get the users list of caller ids, if the list is empty, return
		// the default caller id...
		function getUserCallerIds() {
			var callerIds = false;
			$.ajax({
				url : '/' + orgPath + '/api/2/users/' + userid + '/roles/' + userRoleId + '/settings/callerids/',
				type : "GET",
				dataType : "json",
				async : false,
				success : function(data) {
					callerIds = data.callerids;
					// if the ajax call returns no numbers or nothing, get
					// the default callerid...
					if (callerIds == false || callerIds.length == 0) {
						callerIds = [ getDefaultCallerId() ];
					}
				}
			});

			return callerIds;
			// return ["8316001090","8316001091","8043810293"]; // some test
			// data...
		};

		// Easy Call jQuery Plugin
		// make "English" into "Default" for easycalls (and make sure it's
		// always set, even when there are no languages)
		easycallLangs = {
			"en" : "Default"
		};
		if (userPermissions.sendmulti) {
			$.each(nLangs, function(code) {
				if (code != "en")
					easycallLangs[code] = nLangs[code];
			});
		}
		$("#msgsndr_form_number").attachEasyCall({
			"languages" : easycallLangs,
			"defaultphone" : userInfo.phoneFormatted
		});
	},
	"email" : function() {
		var $ = jQuery;
		// Hide / Show Translations
		$('#msgsndr_tab_email').on('click', '.toggle-translations', function(event) {
			event.preventDefault();
			var text = $(this).text().split(" ");

			$(this).text(text[0] == 'Show' ? 'Hide ' + text[1] + ' ' + text[2] : 'Show ' + text[1] + ' ' + text[2]);

			var etarget = $(this).attr('data-target');
			$(etarget).slideToggle();
			$(this).toggleClass('active');

			$('#email_retranslate').remove();

			if (text[0] == 'Show') {
				eTranslate(this);

				$(this).parent().append('<button id="email_retranslate" data-target="#email_translate">Re Translate</button>');
			}

		});

		function eTranslate() {

			var txtField = CKEDITOR.instances.reusableckeditor.getData();
			var displayArea = $(this).attr('data-display');
			var msgType = 'email';

			$(this).parent().append('<button id="email_retranslate" data-target="#email_translate">Re Translate</button>');

			$('#email_translate fieldset > label[for^=email_]').append('<img src="img/ajax-loader.gif" class="loading" />');

			doTranslate(elangCodes, txtField, displayArea, msgType);
		}

		$('#msgsndr_tab_email').on('click', '#email_retranslate', function() {
			eTranslate(this);
		});

		var splitlangCodes = elangCodes.split('|');
		var langCount = splitlangCodes.length;

		if (langCount == 1) {
			$('a[data-target=#email_translate]').show().text('Show ' + langCount + ' translation');
		} else {
			$('a[data-target=#email_translate]').show().text('Show ' + langCount + ' translations');
		}

		$.each(splitlangCodes, function(transIndex, transData) {
			var langCode = splitlangCodes[transIndex];
			$('#email_translate').append('<fieldset><input type="checkbox" checked="checked" id="email_'+langCode+'"  name="email_save_translation" class="translations" /><label for="email_'+nLangs[langCode]+'">'+nLangs[langCode]+'</label><div class="controls"><div class="html_translate" id="email_translated_'+langCode+'"></div></div></fieldset>');
		});
	},
	"sms" : function() {
		var $ = jQuery;
		$('li.osms').removeClass('notactive');

		/*$("#msgsndr_form_sms").on({
			keyup : function() {
				charCount(this, '160', '.sms.characters');

				var elem = $(this);
				obj_valManager.runValidate(elem);
				smsChar('set');
			}
		});*/
		
	},
	"facebook" : function() {
		var $ = jQuery;
		$('div[data-social=facebook]').removeClass('hidden');

		// set up the facebook api and any event listeners
		$.when(window.tokenCheck, getFbAuthorizedPages()).done(function() {
			// populate the authorized destinations hidden form item
			$("#msgsndr_fbpageauthpages").val($.toJSON({"pages":facebookPages,"wall":(orgOptions.fbauthorizewall?true:false)}));
			// intialize facebook with the current user's token
			initFacebook(fbToken);
			// listen for clicks to show facebook info
			$("#msgsndr_form_facebook").on('change', function(event) {
				$("#msgsndr_ctrl_social").on('click', function(event) {
					if (fbToken)
					updateFbPages(fbToken, "msgsndr_fbpage", "msgsndr_fbpagefbpages", false);
				});
			});
		});

		// Character Count
		/*$('#msgsndr_form_fbmsg').on('keyup', function() {
			charCount(this, '420', '.fb.characters');
		});*/
		
	},
	"twitter" : function() {
		var $ = jQuery;
		$('div[data-social=twitter]').removeClass('hidden');

		//$('.twit.characters').prepend(twitterCharCount);
		// Character Count
		/*$('#msgsndr_form_tmsg').on('keyup', function() {
			charCount(this, twitterCharCount, '.twit.characters');
		});*/
		
	},
	"feed" : function() {
		var $ = jQuery;
		$('div[data-social=feed]').removeClass('hidden');

		$.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/roles/'+userRoleId+'/settings/feedcategories',
			type: "GET",
			dataType: "json",
			success: function(data) {
				feedCats = data.feedCategories;

				$.each(feedCats, function(index, feedCat) {  
					var name = feedCat.name.toLowerCase().replace(" ","_");
					$('#feed_categories').append('<div class="cf"><input type="checkbox" class="addme" name="" id="'+name+'" /><label class="addme" for="'+name+'">'+feedCat.name+'</label></div>');
				});
			}
		});
	},
};

function ContentManager() {
	var $ = jQuery;
	var self = this;
	var currentContent = "";
	
	var saveManager = new ContentSaveManager();
	
	var eventManager = {
		onContentStart: [],
		onContentSave: []
	};
	
	this.onContentStart = function(callback) {
		//callback(lastcontent, nextcontent)
		eventManager.onContentStart.push(callback);
		return eventManager.onContentStart.length - 1;
	};
	
	this.unbindOnContentStart = function(id) {
		eventManager.onContentStart.splice(id, 1);
	};
	
	this.onContentSave = function(callback) {
		//callback(lastcontent, nextcontent)
		eventManager.onContentSave.push(callback);
		return eventManager.onContentSave.length - 1;
	};
	
	this.unbindOnContentSave = function(id) {
		eventManager.onContentSave.splice(id, 1);
	};
	
	this.updateContentStatus = function() {
		var readyForSave = true;
		
		if(currentContent == "social") {
			if($("input.social:checked").size() == 0) {
				readyForSave = false;
			} else if($("#msgsndr_form_feed").is(":checked") && $("input:checked", "#feed_categories").size() == 0) {
				readyForSave = false;
			}
			
		}
		
		if($(".er:visible").size() > 0) {
			readyForSave = false;
		}
		
		if(readyForSave) {
			$("button.btn_save", contentMap[currentContent]).removeAttr('disabled');
		} else {
			$('button.btn_save', contentMap[currentContent]).attr('disabled','disabled');
		}
	};
	
	this.gotoContent = function(mode) {
		//DONT SWITCH IF ALREADY ADJUSTING CONTENT
		if(currentContent.length > 0) {
			return false;
		}
		
		//RUN ON CHANGE EVENTS
		$.each(eventManager.onContentStart, function(eIndex, eEvent) {
			eEvent(mode);
		});
		
		$.each(autoCharCount, function(index, item) {
			clearTimeout(item);
		});
		if(mode == "sms") {
			autoUpdateCharCount($("#msgsndr_form_sms"), 160, $(".sms.characters"));
		} else if(mode == "social") {
			autoUpdateCharCount($("#msgsndr_form_fbmsg"), 420, $(".fb.characters"));
			var twitterCharCount = 140 - twitterReservedChars;
			autoUpdateCharCount($("#msgsndr_form_tmsg"), twitterCharCount, $(".twit.characters"));
		}
		
		//SWITCH STEP
		$(".msg_content_nav > li").not(".o" + mode).addClass("lighten");
		currentContent = mode;
		$(contentMap[mode]).show();
		$(".o" + currentContent, ".msg_content_nav").addClass('active');
		
		self.updateContentStatus();
	};
	
	this.allowContent = function(type) {
		if(typeof(allowControl[type]) != "undefined") {
			$('li.o' + type + ", div[data-social=" + type + "]").removeClass('notactive hidden');
			
			allowControl[type]();
		}
	};
	
	this.cancelContent = function() {
		$("[id^=msgsndr_tab_]").hide();
		$('.msg_content_nav > li').removeClass('active').removeClass('lighten');
		
		$('.msg_content_nav .o' + currentContent).removeClass('complete');
		$('#msgsndr_review_' + currentContent).parent().removeClass('complete');
		
		currentContent = "";
		obj_stepManager.updateStepStatus();
	};
	
	this.saveContent = function($button) {
		//RUN ON SAVE EVENTS
		$.each(eventManager.onContentSave, function(eIndex, eEvent) {
			eEvent(currentContent);
		});
		
		var doWait = null;
		
		if($button.attr('data-tts') == "true" && $('#msgsndr_form_phonetranslate').is(':checked')) {
			$button.next('img').removeClass('hide');
			doWait = saveManager.save("translation");
		} else {
			if(currentContent == "email") {
				$button.next('img').removeClass('hide');
				doWait = saveManager.save("email");
			}
		}
		
		$.when(doWait).done(function() {
			$('#msgsndr_tab_' + currentContent).hide();

			$('.msg_content_nav li').removeClass('lighten');
			$('.msg_content_nav ' + $button.attr("data-nav")).removeClass('active').addClass('complete');

			$('input[name=has_' + currentContent + ']').attr('checked', 'checked');

			// Set Message tabs on review tab
			$('#msgsndr_review_' + currentContent).parent().addClass('complete');
			
			currentContent = "";
			obj_stepManager.updateStepStatus();
		});
	};
	
	// BIND CONTENT BUTTONS
	$('.msg_content_nav button').on('click', function(event) {
		event.preventDefault();
		//NEXT CONTENT ID
		var getContentId = $.trim($(this).attr("id").match(/msgsndr_ctrl_([a-z]*)/i)[1]);
		//SWITCH STEP
		self.gotoContent(getContentId);
	});
	
	//BIND CONTENT CANCEL
	$(".btn_cancel", "[id^=msgsndr_tab_]").on("click", function() {
		self.cancelContent();
	});
	
	//BIND CONTENT CANCEL
	$(".btn_save", "[id^=msgsndr_tab_]").on("click", function() {
		self.saveContent($(this));
	});
	
	// BIND SOCIAL BUTTONS
	// Social Input Buttons
	$('input.social').on('click', function() {
		var itemName = $(this).attr('id').split('_')[2];

		$('.' + itemName).slideToggle('slow', function() {
			if (itemName == 'feed') { // if Post to Feeds set focus to the
				// Post title input
				$('#msgsndr_form_rsstitle').focus();
			} else { // Set focus to the textarea
				$('.' + itemName + ' textarea').focus();
			}
			
			self.updateContentStatus();
		});
	});
	
	//BIND FEED CHECKBOXES FOR VALIDATING
	$("#feed_categories").on("click change", function() {
		self.updateContentStatus();
	});
	
	// SET CONTENT ALLOWANCES
	if (userPermissions.sendphone == 1) {
		self.allowContent("phone");
	}

	if (userPermissions.sendemail == 1) {
		self.allowContent("email");
	}

	if (userPermissions.sendsms == 1) {
		self.allowContent("sms");
	}

	if (userPermissions.facebookpost == 1 || userPermissions.twitterpost == 1 || userPermissions.feedpost == 1) {
		$('li.osocial').removeClass('notactive');

		if (userPermissions.facebookpost == 1) {
			self.allowContent("facebook");
		}

		if (userPermissions.twitterpost == 1) {
			self.allowContent("twitter");
		}

		if (userPermissions.feedpost == 1) {
			self.allowContent("feed");
		}
	}
	
	// if the user can "leavemessage" aka voice replies
    if (userPermissions.leavemessage == 1) {
    	$("#msgsndr_leavemessage").removeClass("hide");
    	$("#msgsndr_leavemessage").children("input").attr("checked", "checked");
    }
    
    // if the user can "messageconfirmation" aka request confirmation of outbound messages
    if (userPermissions.messageconfirmation == 1) {
    	$("#msgsndr_messageconfirmation").removeClass("hide");
    }
    
    if (userPermissions.sendmulti == 1) {
    	$("#msgsndr_form_phonetranslate").parent().removeClass("hide");
    	$("#msgsndr_form_emailtranslate").parent().parent().removeClass("hide");
    }
};