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
			"msgsndr_optioncallerid",
			"msgsndr_optionmaxjobdays",
			"msgsndr_optionleavemessage",
			"msgsndr_optionmessageconfirmation",
			// TODO: phone message translations
			"msgsndr_emailmessagefromname",
			"msgsndr_emailmessagefromemail",
			"msgsndr_emailmessagesubject",
			"msgsndr_emailmessageattachment",
			"msgsndr_emailmessagetext",
			// email translations arn't editable...
			"msgsndr_smsmessagetext",
			"msgsndr_phonemessagepost",
			"msgsndr_hasfacebook",
			"msgsndr_socialmediafacebookmessage",
			"msgsndr_socialmediafacebookpage",
			"msgsndr_hastwitter",
			"msgsndr_socialmediatwittermessage",
			"msgsndr_hasfeed",
			"msgsndr_socialmediafeedmessage",
			"msgsndr_socialmediafeedcategory"
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
	};
	
	this.unbindValidations = function(step) {
	};
	
	this.setInvalid = function($element, msg) {
	};
	
	this.setValid = function($element) {
	};
	
	this.emptyValidate = function($element) {
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
	};
};