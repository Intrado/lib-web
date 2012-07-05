function ValidationManager() {
	var $ = jQuery;
	var self = this;
	var pending = 0;
	
	var validationMap = {
		"1": {"all": [
				"msgsndr_name",
				"msgsndr_jobtype",
				"msgsndr_addmephone",
				"msgsndr_addmeemail",
				"msgsndr_addmesms",
				"msgsndr_listids"
				]},
		"2": {"phone": [
				"msgsndr_phonemessagetype",
				"msgsndr_phonemessagecallme",
				"msgsndr_phonemessagetext",
				"msgsndr_phonemessagetexttranslate",
				"msgsndr_optioncallerid",
				"msgsndr_optionmaxjobdays",
				"msgsndr_optionleavemessage",
				"msgsndr_optionmessageconfirmation"
				],
			"email": [
				// TODO: phone message translations
				"msgsndr_emailmessagefromname",
				"msgsndr_emailmessagefromemail",
				"msgsndr_emailmessagesubject",
				"msgsndr_emailmessageattachment",
				"msgsndr_emailmessagetext"
				// email translations arn't editable...
				],
			"sms": [
				"msgsndr_smsmessagetext"
				],
			"social": [
				"msgsndr_phonemessagepost",
				"msgsndr_hasfacebook",
				"msgsndr_socialmediafacebookmessage",
				"msgsndr_socialmediafacebookpage",
				"msgsndr_hastwitter",
				"msgsndr_socialmediatwittermessage",
				"msgsndr_hasfeed",
				"msgsndr_socialmediafeedmessage"
				//"msgsndr_socialmediafeedcategory" // TODO: can't call validate on a div...
				]},
		"3": {"all": [
				"msgsndr_optionautoreport",
				"msgsndr_optionskipduplicate",
				"msgsndr_optionsavemessage",
				"msgsndr_optionsavemessagename",
				"msgsndr_scheduledate",
				"msgsndr_schedulecallearly",
				"msgsndr_schedulecalllate"
				]}
	};
	
	this.init = function() {
		$.each(validationMap, function(step, substeps) {
			$.each(substeps, function(substep, elements) {
				$.each(elements, function(index, element) {
					var e = $('#'+element);
					self.preValidate(e);
					e.on("validation:complete", function(event, memo) {
						switch (memo.style) {
						case "error":
							self.setInvalid(e);
							break;
						case "valid":
							self.setValid(e);
							break;
						default:
							self.setUnknown(e);
						}
					});
				});
			});
		});
	};
	
	this.getElements = function (step, substep) {
		var elements = [];
		if (substep) {
			$.each(validationMap[step][substep], function (index, element) {
				elements.push(element);
			});
		} else {
			$.each(validationMap[step], function (substep, stepelements) {
				$.each(stepelements, function (index, element) {
					elements.push(element);
				});
			});
		}
		return elements;
	};
	
	// set the prevalidate status on all form items (adds default required style)
	this.preValidate = function(e) {
		if (!e)
			return;
		var name = e.attr('name');
		var field = name.split('_')[1];
		var validators = document.formvars["msgsndr"].formdata[field].validators;
		// get the presense of the required validator
		var isRequired = false;
		$.each(validators, function (index, validator) {
			if (validator && (validator[0] == "ValRequired" || validator[0] == "ValConditional" || validator[0] == "ValConditionallyRequired")) {
				isRequired = true;
				return false;
			}
		});
		if (isRequired) {
			$("#"+name+"_icon").attr("src","img/icons/error.gif");
			self.setPreValidate(e);
		}
	};
	
	this.validateStep = function(step, substep, callback) {
		var elements = self.getElements(step, substep);
		self.forceRunValidate(elements, callback);
	};
	
	this.forceRunValidate = function(elements, callback) {
		if (elements.length == 0) {
			callback();
			return;
		}
		pending = elements.length;
		$.each(elements, function(vIndex, vItem) {
			var e = $('#'+vItem);
			e.on("validation:complete.forced", function(event, memo) {
				e.off("validation:complete.forced");
				pending--;
				// NOTE: this might run the callback early if another form item is doing validation...
				if (callback && pending == 0)
					callback();
			});
			self.runValidateById(vItem);
		});
	};
	
	this.stepIsValid = function(step, substep) {
		var elements = self.getElements(step, substep);
		var passed = true;
		$.each(elements, function(vIndex, vItem) {
			var e = $('#'+vItem);
			if (!e.hasClass("ok")) {
				passed = false;
				return false;
			}
		});
		return passed;
	};
	
	this.setInvalid = function(e) {
		e.removeClass('pre ok').addClass('er');
	};
	
	this.setValid = function(e) {
		e.removeClass('pre er').addClass('ok');
	};
	
	this.setPreValidate = function(e) {
		e.removeClass('ok er').addClass("pre");
	}
	
	//This is to bridge prototype and jquery implementations when force running validation
	this.runValidateById = function(id) {
		var element = $('#' + id);
		this.runValidate(element);
	};

	this.runValidate = function(e) {
		self.setPreValidate(e);
		// FIXME: probably a better way to do this...
		if (e.val() == "[]" || e.val() == "{}")
			e.val("");
		var name = e.attr('name');
		var form = name.split("_")[0];
		form_do_validation(document.getElementById(form), document.getElementById(name));
	};
};