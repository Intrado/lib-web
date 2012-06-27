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
			var selectedTtslangCodes = [];

			var checkTranslations = $('input[name^=tts_override]');
			$.each(checkTranslations, function(tIndex, tData) {
				lCode = $(tData).attr('name').split('_')[2];
				if ($(tData).is(':checked')) {
					// Nothing... (Overridden)
				} else {
					// add loading icon to label
					$('#tts_translate fieldset > label[for^=tts_'+ lCode+ ']').append('<img src="img/ajax-loader.gif" class="loading" />');
					selectedTtslangCodes.push(lCode);
				}
			});

			$.translate(txtField, selectedTtslangCodes, function(data) {
				$.each(data.responseData, function(transIndex, transData) {
					var e = $('#tts_translated_' + transData.code);
					e.val(transData.translatedText);
					// remove loading icon from label
					e.parent().parent().find("img.loading").remove();
				});
			});
		};

		$('#tts_translate').on('click', '.show_hide_english', function(e) {
			e.preventDefault();

			var langCode = $(this).attr('data-code');
			$('#retranslate_' + langCode).slideToggle('fast');

			if ($(this).text() == "Show In English") {
				$(this).after('<img src="img/ajax-loader.gif" class="loading" />');
				reTranslate(this);
			}

			$(this).text($(this).text() == 'Show In English' ? 'Hide English' : 'Show In English');

		});

		$('#tts_translate').on('click', '.retranslate', function(e) {
			e.preventDefault();
			$(this).after('<img src="img/ajax-loader.gif" class="loading" />');
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

		var langCount = ttslangCodes.length;

		if (langCount == 1) {
			$('a[data-target=#tts_translate]').show().text('Show ' + langCount + ' translation');
		} else {
			$('a[data-target=#tts_translate]').show().text('Show ' + langCount + ' translations');
		}

		$.each(ttslangCodes, function(transIndex, transData) {
			var langCode = ttslangCodes[transIndex];

			var ttsTranslate = '<fieldset>';

	        ttsTranslate += '<input type="checkbox" checked="checked" name="save_translation" class="msgdata translations" id="tts_'+langCode+'" />';
	        ttsTranslate += '<label for="tts_'+langCode+'">'+nLangs[langCode]+'</label>';
	        ttsTranslate += '<div class="controls">';
	        ttsTranslate += '<input type="hidden" name="phone_translate_' + langCode + '">';
	        ttsTranslate += '<textarea id="tts_translated_'+langCode+'" class="msgdata" disabled="disabled"></textarea>';
	        ttsTranslate += '<button class="playAudio" data-text="tts_translated_'+langCode+'" data-code="'+langCode+'"><span class="icon play"></span> Play Audio</button>';
	        ttsTranslate += '<button class="show_hide_english" data-text="'+nLangs[langCode]+'" data-code="'+langCode+'">Show In English</button>';
	        ttsTranslate += '<input type="checkbox" name="tts_override_'+langCode+'" class="msgdata" id="tts_override_'+langCode+'" /><label for="tts_override_'+langCode+'">Override Translation</label>';
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
		var mindigits = (orgOptions.easycallmin?orgOptions.easycallmin:10);
		var maxdigits = (orgOptions.easycallmax?orgOptions.easycallmax:10);
		$("#msgsndr_form_number").attachEasyCall({
			"languages" : easycallLangs,
			"phonemindigits": mindigits,
			"phonemaxdigits": maxdigits,
			"defaultphone" : userInfo.phoneFormatted
		});
	},
	"email" : function() {
		var $ = jQuery;
		
		// Add users' email address to the from email field
		if(userInfo.email != '') {
        	$('#msgsndr_form_email').attr('value', userInfo.email);
		}
		
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
			var txtField = $("#msgsndr_form_body").val();

			$(this).parent().append('<button id="email_retranslate" data-target="#email_translate">Re Translate</button>');

			// add loading icon to label
			$('#email_translate fieldset > label[for^=email_]').append('<img src="img/ajax-loader.gif" class="loading" />');

			$.translate(txtField, elangCodes, function(data) {
				$.each(data.responseData, function(transIndex, transData) {
					var e = $('#email_translated_' + transData.code);
					e.html(transData.translatedText.replace(/<</g, "&lt;&lt;").replace(/>>/g,"&gt;&gt;"));
					// remove loading icon from label
					e.parent().parent().find("img.loading").remove();
				});
			});
		}

		$('#msgsndr_tab_email').on('click', '#email_retranslate', function() {
			eTranslate(this);
		});

		var langCount = elangCodes.length;

		if (langCount == 1) {
			$('a[data-target=#email_translate]').show().text('Show ' + langCount + ' translation');
		} else {
			$('a[data-target=#email_translate]').show().text('Show ' + langCount + ' translations');
		}

		$.each(elangCodes, function(transIndex, transData) {
			var langCode = elangCodes[transIndex];
			$('#email_translate').append(
				'<fieldset>'+
					'<input type="checkbox" class="msgdata" checked="checked" id="email_'+langCode+'"  name="email_save_translation" class="translations" />'+
					'<label for="email_'+langCode+'">'+nLangs[langCode]+'</label>'+
					'<div class="controls">'+
						'<input type="hidden" name="email_translate_' + langCode + '">'+
						'<div class="msgdata html_translate" id="email_translated_'+langCode+'"></div>'+
					'</div>'+
				'</fieldset>');
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
		//$('div[data-social=facebook]').removeClass('hidden');
		
		// set up the facebook api and any event listeners
		$.when(window.tokenCheck, getFbAuthorizedPages()).done(function() {
			// populate the authorized destinations hidden form item
			$("#msgsndr_fbpageauthpages").val($.toJSON({"pages":facebookPages,"wall":(orgOptions.fbauthorizewall?true:false)}));
			// intialize facebook with the current user's token
			initFacebook(fbToken);
			// listen for clicks to show facebook info
			$("#msgsndr_ctrl_social").on('click', function(event) {
				if (fbToken)
				updateFbPages(fbToken, "msgsndr_fbpage", "msgsndr_fbpagefbpages", false);
			});
		});

		// Character Count
		/*$('#msgsndr_form_fbmsg').on('keyup', function() {
			charCount(this, '420', '.fb.characters');
		});*/
		
	},
	"twitter" : function() {
		var $ = jQuery;
		
		//$('div[data-social=twitter]').removeClass('hidden');
		//$('.twit.characters').prepend(twitterCharCount);
		// Character Count
		/*$('#msgsndr_form_tmsg').on('keyup', function() {
			charCount(this, twitterCharCount, '.twit.characters');
		});*/
		
	},
	"feed" : function() {
		var $ = jQuery;
		//$('div[data-social=feed]').removeClass('hidden');

		$.ajax({
			url: '/'+orgPath+'/api/2/users/'+userid+'/roles/'+userRoleId+'/settings/feedcategories',
			type: "GET",
			dataType: "json",
			success: function(data) {
				feedCats = data.feedCategories;

				$.each(feedCats, function(index, feedCat) {  
					var name = feedCat.name.toLowerCase().replace(" ","_");
					$('#feed_categories').append('<div class="cf"><input type="checkbox" class="addme" name="feed_categories" id="'+name+'" value="'+feedCat.id+'" /><label class="addme" for="'+name+'">'+feedCat.name+'</label></div>');
				});
			}
		});
	}
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

		if(currentContent == "phone") {
			var voiceType = $('#switchaudio button.active').attr('data-type');
			if($(".er:visible, .required.er", '#'+voiceType).size() > 0) {
				readyForSave = false;
			}
		} else {
			if($(".er:visible").size() > 0) {
				readyForSave = false;
			}
		}
		
		// if($(".er:visible, .required.er", contentMap[currentContent]).size() > 0) {
		// 	readyForSave = false;
		// }
		
		if(readyForSave) {
			$("button.btn_save", contentMap[currentContent]).removeAttr('disabled');
		} else {
			$('button.btn_save', contentMap[currentContent]).attr('disabled','disabled');
		}
	};
	
	this.resetContentStatus = function() {
		currentContent = "";
	}
	
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
			
			//IF NO PHONE MESSAGE IS SAVED, HIDE THE LINK TO AUDIO FILE
			if($(".ophone", ".msg_content_nav").hasClass('complete')){
				var fieldinsertcheck = $('#msgsndr_tts_message').val();
				if (fieldinsertcheck.indexOf('<<') == -1) {
					$('#audiolink').removeClass('hidden');
				} else {
					$('#audiolink').addClass('hidden');
				}
			}
			
		}
		
		//SWITCH STEP
		$(".msg_confirm").hide();
		
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
		var remove = confirm("Warning, this will remove the message from your broadcast. Are you sure you wish to do this?");
		if (remove) {
			$("[id^=msgsndr_tab_]").hide();
			$('.msg_content_nav > li').removeClass('active').removeClass('lighten');
			
			$('input[name=has_' + currentContent + ']').removeAttr('checked');
		
			$('.msg_content_nav .o' + currentContent).removeClass('complete');
			$('#msgsndr_review_' + currentContent).parent().removeClass('complete');
			
			self.resetContentStatus();
			obj_stepManager.updateStepStatus();
			
			$(".msg_confirm").show();
		}
	};
	
	this.saveContent = function($button) {
		// disable save and cancel buttons
		var oldBtnText = $button.text();
		$button.text("Saving content...");
		$button.attr("disabled","disabled");
		$button.parent().find("button.btn_cancel").attr("disabled","disabled");
		//RUN ON SAVE EVENTS
		$.each(eventManager.onContentSave, function(eIndex, eEvent) {
			eEvent(currentContent);
		});
		
		var doWait = null;
		
		if($button.attr('data-tts') == "true") {
			$button.next('img').removeClass('hide');
			doWait = saveManager.save("translation");
		} else {
			if(currentContent == "email") {
				$button.next('img').removeClass('hide');
				doWait = saveManager.save("email");
			} else if(currentContent == "social") {
				$button.next('img').removeClass('hide');
				doWait = saveManager.save("feed");
			}
		}
		
		$.when(doWait).done(function() {
			// enable save and cancel buttons
			$button.text(oldBtnText);
			$button.removeAttr("disabled");
			$button.parent().find("button.btn_cancel").removeAttr("disabled");
			$button.next('img').addClass('hide');
			$('#msgsndr_tab_' + currentContent).hide();

			$('.msg_content_nav li').removeClass('lighten');
			$('.msg_content_nav ' + $button.attr("data-nav")).removeClass('active').addClass('complete');

			$('input[name=has_' + currentContent + ']').attr('checked', 'checked');

			// Set Message tabs on review tab
			$('#msgsndr_review_' + currentContent).parent().addClass('complete');
			
			self.resetContentStatus();
			obj_stepManager.updateStepStatus();
			
			$(".msg_confirm").show();
		});
	};
	
	this.isEditing = function() {
		return (currentContent.length > 0);
	}
	
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
		getTokens();

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
    if (userPermissions.leavemessage) {
    	$("#msgsndr_leavemessage").removeClass("hide");
    	$("#msgsndr_voice_response").attr("checked","checked");
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