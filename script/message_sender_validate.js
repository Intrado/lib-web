function ValidationManager() {
	var $ = jQuery;
	var self = this;
	var valTimers = {};
	
	var validationMap = {
		"1": [
			"msgsndr_name",
			"msgsndr_jobtype",
			"msgsndr_addmephone",
			"msgsndr_addmeemail",
			"msgsndr_addmesms",
			"msgsndr_listids"
		],
		"2": [
			"msgsndr_phonemessagetype",
			"msgsndr_phonemessagecallme",
			"msgsndr_phonemessagetext",
			"msgsndr_phonemessagetexttranslate",
			// TODO: phone message translations
			//"msgsndr_emailmessagefromname",
			//"msgsndr_emailmessagefromemail",
			//"msgsndr_emailmessagesubject",
			//"msgsndr_emailmessageattachment",
			//"msgsndr_emailmessagetext",
			// email translations arn't editable...
			//"msgsndr_smsmessagetext",
			//"msgsndr_socialmediafacebookmessage",
			//"msgsndr_socialmediatwittermessage",
			//"msgsndr_socialmediafeedmessage",
			//"msgsndr_socialmediafeedcategory",
			//"msgsndr_optioncallerid"
		],
		"3": [
			
		]
	};
	
	this.init = function() {
		$.each(validationMap, function(vIndex, vItems) {
			$.each(vItems, function(vIndex2, vItem) {
				var e = $('#'+vItem);
				e.on("validation:complete", function(event, memo) {
					switch (memo.style) {
					case "error":
						e.removeClass('ok').addClass('er');
						break;
					case "valid":
						e.removeClass('er').addClass('ok');
						break;
					default:
						e.removeClass('ok er');
					}
				});
			});
		});
	};
	
	this.forceRunValidate = function(step) {
		$.each(validationMap[step], function(vIndex, vItem) {
			self.runValidateById(vItem);
		});
	};
	
	this.bindValidations = function(step) {
		/*
		//GET STEP DIVISION
		var stepArea = stepMap[step];
		if(typeof(stepArea) == "undefined") {
			return false;
		}
		stepArea = $(stepArea);
		
		$.each(validationMap[step], function(vIndex, vItem) {
			var $element = $("[name=" + vItem + "]", stepArea);
			
			var eventtype = "blur.valsys";
			if ($element.is(':checkbox, select, #msgsndr_phonemessagecallme')) {
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
		*/
	};
	
	this.unbindValidations = function(step) {
		/*
		var stepArea = stepMap[step];
		if(typeof(stepArea) == "undefined") {
			return false;
		}
		stepArea = $(stepArea);
		
		$.each(validationMap[step], function(vIndex, vItem) {
			var $element = $("[name=" + vItem + "]", stepArea);
			
			$element.off(".valsys");
		});
		*/
	};
	
	this.setInvalid = function($element, msg) {
		/*
		var elemId = $element.attr('id');

		if($element.next('.error').text() != msg) {
			if($element.is("[name=sms_text], [name=twitter_message], [name=facebook_message]")) {
				$('.characters', $element.next("div")).addClass('error').text(msg);
				$element.removeClass('ok').addClass('er');
				if ($element.is("[name=sms_text]")) {
					$('.btn_save').attr('disabled', 'disabled');
				}
			} else if($element.hasClass('box_validator')) {
				$element.removeClass('ok').addClass('er');
				$($element.next("div")).children(".box_validatorerror").remove();
				$($element.next("div")).append($('<div />', { "class": "box_validatorerror er", "text": msg }));
			} else if ($element.is("#msgsndr_form_body")) {
				//$('#cke_reusableckeditor').removeClass('ok').addClass('er emp');
				$('#msgsndr_form_body').removeClass('ok').addClass('er emp');
			} else {
				$element.removeClass('ok').addClass('er');
				if(typeof(msg) != "undefined" && msg.length > 0) {
					$element.next('.error').fadeIn(300).text(msg);
				} else {
					$element.next('.error').fadeIn(300).text("Unexpected Error!");
				}
			}
		}

		$('label[for='+elemId+']').removeClass('req ok').addClass('er');
		
		obj_stepManager.updateStepStatus();
		obj_contentManager.updateContentStatus();
		*/
	};
	
	this.setValid = function($element) {
		/*
		var elemId = $element.attr('id');

		if($element.is("[name=sms_text], [name=twitter_message], [name=facebook_message]")) {
			$element.removeClass('er emp').addClass('ok');
			$('.characters').removeClass('error');
			if($element.is("[name=sms_text]")) {
				$('.btn_save').removeAttr('disabled');
			}
		} else if($element.hasClass('box_validator')) {
			$element.removeClass('er emp').addClass('ok');
			$($element.next("div")).children(".box_validatorerror").remove();
		} else if ($element.is("#msgsndr_form_body")) {
			// $('#cke_reusableckeditor').removeClass('er emp').addClass('ok');
			$('#msgsndr_form_body').removeClass('er emp').addClass('ok');
		} else {
			$element.removeClass('er emp').addClass('ok').next('.error').fadeOut(300).text("");
		}

		$('label[for='+elemId+']').removeClass('req er').addClass('ok');
		
		obj_stepManager.updateStepStatus();
		obj_contentManager.updateContentStatus();
		*/
	};
	
	this.emptyValidate = function($element) {
		/*
		var elemId = $element.attr('id');

		$element.removeClass('er ok').addClass('emp').next('.error').fadeOut(300).text("");

		$('label[for='+elemId+']').removeClass('ok er').addClass('req');

		obj_stepManager.updateStepStatus();
		obj_contentManager.updateContentStatus();
		*/
	}
	
	//This is to bridge prototype and jquery implementations when force running validation
	this.runValidateById = function(id) {
		var element = $('#' + id);
		this.runValidate(element);
	};

	this.runValidate = function($element) {
		var name = $element.attr('name');
		var form = name.split("_")[0];
		form_do_validation(document.getElementById(form), document.getElementById(name));
		
		/*
		var value = $element.val();
		var validators = document.formvars[form]["validators"][name];
		var requiredFields = document.formvars[form]["formdata"][field]["requires"];

		var requiredValues = {};
		if(requiredFields) {
			for(var i = 0; i < requiredFields.length; i++) {
				var requiredName = requiredFields[i];
				requiredValues[requiredName] = $("#" + name).val();
			}
		}
		
		if (validators == "ajax") {
			if (value == "") {
				self.emptyValidate($element);
			} else {
				var postData = {
					value : value,
					requiredvalues : requiredValues
				};
				$.ajax({
					type : 'POST',
					url : "message_sender.php", //?form=msgsndr&ajaxvalidator=true&formitem=" + name
					data : {
						form: "msgsndr",
						ajaxvalidator: true,
						formitem: name,
						json: $.toJSON(postData)
					},

					success : function(response) {
						if(response.vres != true) {
							self.setInvalid($element, response.vmsg);
						} else {
							self.setValid($element);
						}
					}
				});
			}

		} else { // None AJAX validation
			var response = true;
			
			// Loop validation
			for(var i = 0; i < validators.length; i++) {
				var validator = validators[i];
				response = validator.validate(validator.name, validator.label, value, validator.args, requiredValues);
				if(typeof(response) == "string") {
					break;
				}
			}
			
			if(typeof(response) == "string") {
				if($element.hasClass('required') == false && value.length == 0) {
					self.setValid($element);
				} else if ($element.hasClass('required') == true && value.length == 0) {
					self.emptyValidate($element);
				} else {
					self.setInvalid($element, response);
				}
			} else {
				self.setValid($element);
			}
			
		} // if ajax
		*/
	};
};