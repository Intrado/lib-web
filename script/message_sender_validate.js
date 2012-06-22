function ValidationManager() {
	var $ = jQuery;
	var self = this;
	var valTimers = {};
	
	var validationMap = {
		"1" : {
			"broadcast|subject" : [new document.validators["ValRequired"]("broadcast_subject","Subject",{}), new document.validators["ValLength"]("broadcast_subject","Subject",{min:7,max:30})],
			"addme|phone" : [new document.validators["ValPhone"]("addme_phone","My phone",{})],
			"addme|email" : [new document.validators["ValEmail"]("addme_email","My email",{})],
			"addme|sms" : [new document.validators["ValPhone"]("addme_sms","My SMS",{})]
		},
		"2" : {
			"phone|number" : [new document.validators["ValRequired"]("content_phone","Number to Call",{}), new document.validators["ValPhone"]("content_phone","Number to Call",{})],
			"phone|tts" : [new document.validators["ValRequired"]("phone_tts","Phone Message",{}), new document.validators["ValLength"]("phone_tts","Phone Message",{min:1, max:10000}), new document.validators["ValTtsText"]("phone_tts","Phone Message")],
			"email|name" : [new document.validators["ValRequired"]("email_name","Name",{}), new document.validators["ValLength"]("email_name","Name",{min: 1,max:30})],
			"email|address" : [new document.validators["ValRequired"]("email_address","Email Address",{}), new document.validators["ValLength"]("email_address", "Email Address", {max:255}), new document.validators["ValEmail"]("email_address","Email Address",{domain:orgOptions.emaildomain})],
			"email|subject" : [new document.validators["ValRequired"]("email_subject","Subject",{}), new document.validators["ValLength"]("email_subject","Subject",{min:4})],
			"email|body" : [new document.validators["ValRequired"]("email_body","Body",{}), new document.validators["ValLength"]("email_body","Body",{min:4})],
			"sms|text" : [new document.validators["ValRequired"]("sms_text","SMS",{}), new document.validators["ValLength"]("sms_text","SMS",{min:1, max:160}), new document.validators["ValSmsText"]("sms_text","SMS Text")],
			"facebook|message" : [new document.validators["ValRequired"]("facebook_message","Facebook Message",{}), new document.validators["ValLength"]("facebook_message","Facebook Message",{min:4})],
			"twitter|message" : [new document.validators["ValRequired"]("twitter_message","Twitter Message",{}), new document.validators["ValLength"]("twitter_message","Twitter Message",{min:4})],
			"rss|title" : [new document.validators["ValRequired"]("rss_title","Post Title",{}), new document.validators["ValLength"]("rss_title","Post Title",{min:3})],
			"feed|message" : [new document.validators["ValRequired"]("feed_message","Feed Message",{}), new document.validators["ValLength"]("feed_message","Feed Message",{min:4})]
		},
		"3" : {
			"broadcast|schedulecallearly" : [new document.validators["ValTimeCheck"]("schedulecallearly","Early",{min:userPrefs.callearly,max:userPrefs.calllate}), new document.validators["ValTimeWindowCallEarly"]("schedulecallearly")],
			"broadcast|schedulecalllate" : [new document.validators["ValTimeCheck"]("schedulecalllate","Late",{min:userPrefs.callearly,max:userPrefs.calllate}), new document.validators["ValTimeWindowCallEarly"]("schedulecalllate"), new document.validators["ValTimePassed"]("scheduledate")],
			"broadcast|requires|schedulecallearly" : ["schedulecalllate", "scheduledate"],
			"broadcast|requires|schedulecalllate" : ["schedulecallearly", "scheduledate"]
		}
	};
	
	this.forceRunValidate = function(step) {
		var stepArea = stepMap[step];
		if(typeof(stepArea) == "undefined") {
			return false;
		}
		stepArea = $(stepArea);
		
		$.each(validationMap[step], function(vIndex, vItem) {
			var elementLookup = vIndex.split("|");
			var $element = $("[name=" + elementLookup[0] + "_" + elementLookup[1] + "]", stepArea);
			
			if($element) {
				var eventtype = "blur.valsys";
				if ($element.is(':checkbox, select')) {
					eventtype = 'change.valsys';
				} else if ($element.is(':radio')) {
					eventtype = 'click.valsys';
				} else if ($element.is('input[type=text], textarea')) {
					eventtype = 'keydown.valsys';
				}
				
				$element.trigger(eventtype);
			}
		});
	};
	
	this.bindValidations = function(step) {
		//GET STEP DIVISION
		var stepArea = stepMap[step];
		if(typeof(stepArea) == "undefined") {
			return false;
		}
		stepArea = $(stepArea);
		
		$.each(validationMap[step], function(vIndex, vItem) {
			var elementLookup = vIndex.split("|");
			var $element = $("[name=" + elementLookup[0] + "_" + elementLookup[1] + "]", stepArea);
			
			var keyTracker = "";
			
			$.each(elementLookup, function(sIndex, sItem) {
				var getCheck = null;
				
				if(sIndex == 0) {
					getCheck = document.formvars;
				} else {
					var setString = "return document.formvars." + keyTracker + ";";
					getCheck = new Function(setString)();
				}
				
				if(sIndex == elementLookup.length - 1) {
					getCheck[sItem] = vItem;
				} else if(typeof(getCheck[sItem]) == "undefined") {
					getCheck[sItem] = {};
				}
				
				if(keyTracker.length != 0) {
					keyTracker += ".";
				}
				keyTracker += sItem;
			});
			
			var eventtype = "blur.valsys";
			if ($element.is(':checkbox, select')) {
				eventtype = 'change.valsys';
			} else if ($element.is(':radio')) {
				eventtype = 'click.valsys';
			} else if ($element.is('input[type=text], textarea')) {
				eventtype = 'keydown.valsys';
			}

			$element.on(eventtype, function(e) {
				var $elem = $(this);
				var elemId = $(this).attr("id");

				if (typeof (valTimers[elemId]) == "undefined") {
					valTimers[elemId] = null;
				}
				clearTimeout(valTimers[elemId]);
				
				valTimers[elemId] = setTimeout(function() {
					self.runValidate($elem);
				}, 600);
			});
		});
	};
	
	this.unbindValidations = function(step) {
		var stepArea = stepMap[step];
		if(typeof(stepArea) == "undefined") {
			return false;
		}
		stepArea = $(stepArea);
		
		$.each(validationMap[step], function(vIndex, vItem) {
			var elementLookup = vIndex.split("|");
			var $element = $("[name=" + elementLookup[0] + "_" + elementLookup[1] + "]", stepArea);
			
			$element.off(".valsys");
		});
	};
	
	this.setInvalid = function($element, msg) {		
		if($element.next('.error').text() != msg) {
			if($element.is("[name=sms_text], [name=twitter_message], [name=facebook_message]")) {
				$('.characters').addClass('error').text(msg);
				$element.removeClass('ok').addClass('er');
				if ($element.is("[name=sms_text]")) {
					$('.btn_save').attr('disabled', 'disabled');
				}
			} else {
				$element.removeClass('ok').addClass('er');
				if(typeof(msg) != "undefined" && msg.length > 0) {
					$element.next('.error').fadeIn(300).text(msg);
				} else {
					$element.next('.error').fadeIn(300).text("Unexpected Error!");
				}
			}
		}
		
		obj_stepManager.updateStepStatus();
		obj_contentManager.updateContentStatus();
	};
	
	this.setValid = function($element) {
		if($element.is("[name=sms_text], [name=twitter_message], [name=facebook_message]")) {
			$element.removeClass('er').addClass('ok');
			$('.characters').removeClass('error');
			if($element.is("[name=sms_text]")) {
				$('.btn_save').removeAttr('disabled');
			}
		} else {
			$element.removeClass('er').addClass('ok').next('.error').fadeOut(300).text("");
		}
		
		obj_stepManager.updateStepStatus();
		obj_contentManager.updateContentStatus();
	};
	
	this.runValidate = function($element) {
		var name = $element.attr('name');
		var form = name.split("_")[0];
		var field = name.split("_")[1];
		
		var value = $element.val();
		var ajax = $element.attr('data-ajax');
		var validators = document.formvars[form][field];

		if(typeof document.formvars[form]['requires'] != "undefined") {
			var requiredFields = document.formvars[form]['requires'][field];
		} else {
			requiredFields = false;
		}

		requiredValues = {};
		if(requiredFields) {
			for(var i = 0; i < requiredFields.length; i++) {
				var requiredName = requiredFields[i];
				requiredValues[requiredName] = $("#" + requiredName).val();
			}
		}
		
		
		if (ajax == 'true') {
			var postData = {
				value : value,
				requiredvalues : requiredValues
			};

			var ajaxurl = "message_sender.php?form=broadcast&ajaxvalidator=true&formitem=" + name;

			$.ajax({
				type : 'POST',
				url : ajaxurl,
				data : {
					json : $.toJSON(postData)
				},

				success : function(response) {
					if(response.vres != true) {
						self.setInvalid($element, response.vmsg);
					} else {
						self.setValid($element);
					}
				}
			});

		} else { // None AJAX validation
			requiredvalues = [];
			var response = true;
			
			// Loop validation
			for(var i = 0; i < validators.length; i++) {
				var validator = validators[i];
				response = validator.validate(validator.name, validator.label, value, validator.args, requiredvalues);
				if(typeof(response) == "string") {
					break;
				}
			}
			
			if(typeof(response) == "string") {
				if($element.hasClass('required') == false && value.length == 0) {
					self.setValid($element);
				} else {
					self.setInvalid($element, response);
				}
			} else {
				self.setValid($element);
			}
			
		} // if ajax
		
	};
};