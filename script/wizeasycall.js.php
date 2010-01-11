<?
require_once("../inc/utils.inc.php");
require_once("../inc/html.inc.php");
require_once("../inc/locale.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>
// keep track of how many audiofiles we have
var totalaudiofiles = 0;

// remember phone number the user enters
var msgphone = null;

function insertNewWizEasyCall(formname, formitemname, maincontainer, langselect, langcode) {

	// insert a new container for the language at the end of the content element
	$(maincontainer).insert({ "bottom":
		new Element("div",{ id: maincontainer+"_"+langcode, style: "padding: 0px; margin: 0px; white-space:nowrap" })
	});

	// set the selector to index 0
	$(langselect).value = 0;

	// hide the language from the selector
	if ($(langselect+"_"+langcode))
		$(langselect+"_"+langcode).hide();

	new WizEasyCall(
		formname,
		formitemname,
		maincontainer+"_"+langcode,
		languages[langcode]+" - "+curDate(),
		langcode
	);
}

// extend the EasyCall class
var WizEasyCall = Class.create(EasyCall,{

	// override the initializeation to add langcode
	initialize: function($super, formname, formitemname, container, name, langcode) {

		// keep track of the langcode for this easycall
		this.langcode = langcode;

		// increment the number of audiofiles
		totalaudiofiles++;

		// call super constructor
		$super(formname, formitemname, container, msgphone, name);

		// check if form data for this langcode already exists.
		var audiofiles = $(this.formitemname).value.evalJSON();
		if (typeof(audiofiles[this.langcode]) !== "undefined" && audiofiles[this.langcode] !== null) {
			this.audiofileid = audiofiles[this.langcode];
			this.createPlayReRecordRemoveButtons();
		}
	},

	// override the setupRecord function to add the audiofile language name
	setupRecord: function ($super) {

		// call the super function
		$super();

		// add the language name and an action element
		$(this.container+"_callcontrol").insert({ "before":
			new Element("div",{id: this.container+"_action", style: "width: 80%; float: right; margin-bottom: 5px;"})
		});

		// if there is more than 1 audofile, display the title
		if (totalaudiofiles > 1) {
			$(this.container+"_action").insert({ "before":
				new Element("div", {id: this.container+"_lang", style: "font-size: large; float: left; margin-bottom: 3px"}).update(
					(this.langcode == "en")?"Default":languages[this.langcode]
				)
			});
		}

		// if this is not en (Default), allow a delete button
		if (this.langcode != "en")
			$(this.container+"_action").insert(this.getRemoveButton());

		$(this.container+"_action").insert({ "after":
			new Element("div",{style: "clear: both;"})
		});

		// hide the language selector box element
		$(this.formitemname+"_altlangs").hide();

	},

	// override the completeSession function call
	completeSession: function () {
		// store the audiofile id in the parent form value
		var audiofiles = $(this.formitemname).value.evalJSON();
		audiofiles[this.langcode] = this.audiofileid;
		$(this.formitemname).value = Object.toJSON(audiofiles);


		// validate the form
		form_do_validation($(this.formname), $(this.formitemname));

		// change the contents of the container element to a re-record and play button
		this.createPlayReRecordRemoveButtons();
	},

	// create the form items after the message is recorded
	createPlayReRecordRemoveButtons: function () {
		// display the language and add an action div
		$(this.container).update();
		$(this.container).insert(
				new Element("div", {id: this.container+"_lang", style: "font-size: large; float: left;"}).update(
					(this.langcode == "en")?"Default":languages[this.langcode]
				)
			).insert(
				new Element("div", {id: this.container+"_action", style: "width: 80%; float: right; margin-bottom: 5px;"}).insert(
					icon_button("<?=escapehtml(_L("Play"))?>", "fugue/control", this.container+"_play").setStyle({float: "left"})
				).insert(
					icon_button("<?=escapehtml(_L('Re-record'))?>", "diagona/16/118", this.container+"_rerecord").setStyle({float: "left"})
				)
			);

		// if this is not en (Default), allow a delete button
		if (this.langcode != "en")
			$(this.container+"_action").insert(this.getRemoveButton());

		// JobWizard EasyCall gets a bottom border
		$(this.container).insert(new Element("div", {style: "padding-top: 3px; margin-bottom: 5px; border-bottom: 1px solid gray; clear: both"}));

		// listen for clicks on the re-record button
		$(this.container+"_rerecord").observe("click", function (event) {
			if (confirm("<?=escapehtml(_L('This will delete the current recording. Are you sure you want to do this?'))?>")) {

				// remove the audiofile from form data
				this.removeAudioFile();

				// reset the record elements
				this.setupRecord();
			}
		}.bind(this));

		// listen for clicks on the play button
		$(this.container+"_play").observe("click", function (event) {
			popup("previewaudio.php?close=1&id="+this.audiofileid, 400, 500);
		}.bind(this));

		// show the language select element
		$(this.formitemname+"_altlangs").show();
	},

	// returns a remove button element
	getRemoveButton: function() {
		rembutton = icon_button("<?=escapehtml(_L("Remove"))?>", "cross", this.container+"_delete").setStyle({float:"left"});

		// listen for clicks on the remove button
		rembutton.observe("click", function (event) {
			// remove the audiofile if it exists in form data
			this.removeAudioFile();

			// decrement the total audiofiles
			totalaudiofiles--;

			// remove the container
			$(this.container).remove();

			// unhide the alt language selector
			$(this.formitemname+"_altlangs").show();

			// unhide the langcode from the selector
			if ($(this.formitemname+"_select_"+this.langcode))
				$(this.formitemname+"_select_"+this.langcode).show();
		}.bind(this));

		return rembutton;
	},

	// remove the audiofile (if it exists) from the form data.
	removeAudioFile: function() {
		var audiofiles = $(this.formitemname).value.evalJSON();
		delete audiofiles[this.langcode];
		$(this.formitemname).value = Object.toJSON(audiofiles);
	}

});
