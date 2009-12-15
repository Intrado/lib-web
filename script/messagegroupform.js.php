<?php
require_once("../inc/subdircommon.inc.php");
require_once("../inc/html.inc.php");

//set expire time to + 1 hour so browsers cache this file
header("Expires: " . gmdate('D, d M Y H:i:s', time() + 60*60) . " GMT"); //exire in 1 hour, but if theme changes so will hash pointing to this file
header("Content-Type: text/javascript");
header("Cache-Control: private");
?>
// delayed TODO: whenever using _L(), must also strip tags.

// done: Intenret explorer, chekcbox setting to true might be problematic, need to see how to do it in the dom.

var AutoTranslate = Class.create({

	// done: Make onclick handlers for each language checkbox, adding or removing from this.translationLanguages
	
	initialize: function(container, messageGroupForm, type, subtype) {
		
		this.container = container;
		this.messageGroupForm = messageGroupForm;
		this.type = type;
		this.subtype = subtype;
		this.destinationInfo = messageGroupForm.destinationInfos[type];
		this.languages = this.destinationInfo.languages;
		this.fieldareas = this.destinationInfo.fieldareas;
		this.translationDivs = {};
		this.retranslationDivs = {};
		this.languageCheckboxes = {};
		
		// Holds a list of languages to be auto-translated.
		// Updated whenever user clicks on a language's checkbox.
		// this.translationLanguages[languageCode] = languageName;
		this.translationLanguages = {};
		
		if (!messageGroupForm.autotranslates)
			messageGroupForm.autotranslates = {};
		messageGroupForm.autotranslates[type + subtype] = this;

		// Create the ui.
		this.sourceTextarea = new Element('textarea', {'style':'width:99%; height:100px'});
		var clearButton = new Element('button', {'type':'button'}).update('Clear');
		var translateButton = new Element('button', {'type':'button'}).update('Refresh Translations');

		this.container.insert(new Element('div', {'class':'MessageContentHeader'}).update('Auto-Translate'));
		this.container.insert(new Element('div', {'style':'text-align:right'}).insert(clearButton));
		this.container.insert(new Element('div').insert(this.sourceTextarea));
		this.container.insert(new Element('div', {'style':'text-align:left;'}).insert(translateButton));

		clearButton.observe('click', this.on_click_clear.bindAsEventListener(this));
		translateButton.observe('click', this.on_click_translate.bindAsEventListener(this));

		for (var languageCode in this.languages) {
			if (languageCode == 'en') // TODO: only autotranslate valid languages defined in translate.php
				continue;

			var playButton = new Element('button', {'type':'button'}).update('Play');
			var retranslateButton = new Element('button', {'type':'button'}).update('English Retranslation');
			this.translationDivs[languageCode] = new Element('div', {'style':'padding:2px; height:100px; border: dashed 1px rgb(220,220,220)'});
			this.retranslationDivs[languageCode] = new Element('div', {'style':'padding:2px; border: dashed 1px rgb(220,220,220)'});
			this.languageCheckboxes[languageCode] = new Element('input', {'type':'checkbox', 'style':'margin-right:10px', 'checked':true});
			var languageCheckbox = this.languageCheckboxes[languageCode];
			languageCheckbox.setAttribute('defaultChecked', true); // Workaround for Internet Explorer.
			languageCheckbox.observe('click', function (event, languageCode) {
				var checkbox = event.element();
				if (checkbox.checked) {
					this.translationLanguages[languageCode] = this.languages[languageCode];
				} else {
					delete this.translationLanguages[languageCode];
				}
			}.bindAsEventListener(this, languageCode));

			var headerDiv = new Element('div', {'style':'font-size:125%'});
			var label = new Element('label', {'style':'margin-right: 10px;'}).insert(this.languages[languageCode]);
			
			var translationContainer = new Element('div', {'style':'margin-top: 15px; border: solid 1px rgb(210,210,210); padding: 5px'});
			translationContainer.insert(headerDiv.insert(this.languageCheckboxes[languageCode]).insert(label).insert(playButton));
			translationContainer.insert(this.translationDivs[languageCode]);
			translationContainer.insert(new Element('div', {'style':'margin-top:10px'}).insert(retranslateButton));
			translationContainer.insert(this.retranslationDivs[languageCode]);
			this.container.insert(translationContainer);

			playButton.observe('click', this.on_click_play.bindAsEventListener(this));
			
			retranslateButton.observe('click', this.on_click_retranslate.bindAsEventListener(this, languageCode));
			
			this.languageCheckboxes[languageCode].observe('click', this.on_toggle_language.bindAsEventListener(this));
			
			// By default, let all languages be translated.
			this.translationLanguages[languageCode] = this.languages[languageCode];
		}
	},

	get_message_prefix: function(languageCode) {
		return this.messageGroupForm.formName + '_' + this.type + this.subtype + languageCode;
	},

	get_message_element: function(languageCode, suffix) {
		return $(this.get_message_prefix(languageCode) + suffix);
	},

	on_click_translate: function() {
		// Loop over list of languages to translate (languages that the user has checked), adding to the translationLanguageNames array.
		var translationLanguageNames = []; // List of language names to be sent via ajax to translate.php.
		var willOverwrite = false; // Indicates if any messages will get overwritten.
		for (var languageCode in this.translationLanguages) {
			translationLanguageNames.push(this.languages[languageCode]);
			
			// Indicate if any message will get overwritten.
			if (this.get_message_element(languageCode, 'text').getValue().strip())
				willOverwrite = true;
		}
		
		// USER HAS NOT SELECTED ANY LANGUAGES TO TRANSLATE
		if (translationLanguageNames.size() < 1) {
			alert('<?= _L("You have not selected any languages to translate.") ?>'); // TODO: Better error message.
			return;
		}
		
		// WARN THE USER
		if (willOverwrite && !confirm('<?= _L("Are you sure?") ?>')) { // TODO: Better confirm message.
			return;
		}
		
		var sourceText = this.sourceTextarea.value;
		// Loop over list of languages to translate (languages that the user has checked), updating the DOM.
		for (var languageCode in this.translationLanguages) {
			// Show swirly ajax loader.
			this.translationDivs[languageCode].update('<img src=\"img/ajax-loader.gif\" />');

			// Clear retranslations.
			this.retranslationDivs[languageCode].update();
				
			// In the message's tab: update sourceText.
			this.get_message_element(languageCode, 'sourceText').update(sourceText);
			
			// In the message's tab: enable translations for this message (checkbox).
			this.get_message_element(languageCode, 'translatecheck').checked = true; // TODO: This may be buggy in Internet Explorer.
		}
		
		new Ajax.Request('translate.php', {
			method:'post',
			parameters: {'english': sourceText, 'languages': translationLanguageNames.join(';')},
			onSuccess: function(transport, translationCount) {
				var data = transport.responseJSON;

				if (!data || !data.responseData || !data.responseStatus || data.responseStatus != 200 || (translationCount > 1 && translationCount != data.responseData.length)) {
					alert('<?= _L('Sorry') ?>'); // TODO: Better error message.
					return;
				}

				var dataResponseData = data.responseData;	
			
				var i = 0;
				for (var languageCode in this.translationLanguages) {
					if (translationCount == 1) {
						this.update_message_translation(languageCode, dataResponseData.translatedText);
						break;
					}
					
					var response = dataResponseData[i];
					var responseData = response.responseData;
					i++;
					
					if (response.responseStatus != 200 || !responseData)
						continue; // TODO: alert with an error message?
						
					this.update_message_translation(languageCode, responseData.translatedText);
				}
			}.bindAsEventListener(this, translationLanguageNames.length)
		});
	},

	update_message_translation: function(languageCode, translatedText) {
		// TODO: escape HTML.
		this.translationDivs[languageCode].update(translatedText);
		this.get_message_element(languageCode, 'text').update(translatedText);
		this.get_message_element(languageCode, 'textdiv').update(translatedText);
		
		// In the message's tab: show translation-related DOM elements.
		toggleTranslation(this.get_message_prefix(languageCode), null);
	},
	
	on_click_play: function(event, languageCode) {
	},

	on_click_retranslate: function(event, languageCode) {
		var retranslationDiv = this.retranslationDivs[languageCode].update('<img src=\"img/ajax-loader.gif\" />');

		new Ajax.Request('translate.php', {
			method:'post',
			parameters: {'text': this.translationDivs[languageCode].innerHTML, 'language': this.languages[languageCode]},
			onSuccess: function(result, retranslationDiv) {
				var data = result.responseJSON;
				if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
					return; // TODO: alert error message.
				retranslationDiv.update(data.responseData.translatedText.escapeHTML());
			}.bindAsEventListener(this, retranslationDiv)
		});
	},

	on_click_clear: function() {
	},
	
	on_toggle_language: function(event, languageCode) {
	}
});

var MessageGroupForm = Class.create({

	// todo: when in email and sms, hide the preferred voice advanced option.
	// todo: disable certain accordion sections for autotranslate.
	
	initialize: function (formName, destinationTabs, destinationInfos, toolsAccordion) {
		this.formName = formName;
		this.destinationTabs = destinationTabs;
		this.destinationInfos = destinationInfos;
		this.toolsAccordion = toolsAccordion;
		
		// Load CKEditor; use this.htmlEditorWrapper to easily move the html editor around without fiddling with the actual html editor's DOM structure.
		// NOTE: CKEditor throws javascript errors if it is inserted inside of the form.
		// NOTE: CKEditor throws javascript errors if the textarea that it is trying to replace is not already in-page.
		this.htmlEditorWrapper = new Element('div', {'style':'clear:both'}).hide();
		this.htmlEditorTextarea = new Element('textarea');
		this.htmlEditorWrapper.insert(this.htmlEditorTextarea);
		$(this.formName).insert({after:this.htmlEditorWrapper});
		this.htmlEditor = CKEDITOR.replace(this.htmlEditorTextarea, {
			'toolbar': [
				['Styles', 'Format'],
				['Bold', 'Italic', '-', 'NumberedList', 'BulletedList', '-', 'Link', '-']
			],
			'resize_enabled': false,
			'width': '100%'
		});
	},
	
	get_message_element: function(type, subtype, languageCode, suffix) {
		return $(this.formName + '_' + type + subtype + languageCode + suffix);
	},

	get_current_message_info: function(type, subtype, languageCode, destinationInfo) {
		var info = {'type': type};

		if (languageCode) {
			info.languageCode = languageCode;
		} else {
			var languageTabs = destinationInfo.languageTabs;
			info.languageCode = languageTabs ? languageTabs.currentSection : 'en';
		}

		if (subtype) {
			info.subtype = subtype;
		} else {
			var subtypeTabs = destinationInfo.subtypeTabs[info.languageCode];
				
			if (subtypeTabs)
				info.subtype = subtypeTabs.currentSection;
			else if (type == 'phone')
				info.subtype = 'voice';
			else if (type == 'sms')
				info.subtype = 'plain';
		}
		
		// TODO: Get the correct control.
		info.control = $(this.formName + '_' + info.type + info.subtype + info.languageCode);
		
		return info;
	},

	get_current_editor: function(typeSection, subtypeSection, languageCodeSection) {
		var type = typeSection || this.destinationTabs.currentSection;
		if (type != 'phone' && type != 'email' && type != 'sms')
			return null;
		var destinationInfo =  this.destinationInfos[type];

		var info = this.get_current_message_info(type, subtypeSection, languageCodeSection, destinationInfo);

		// TODO: Depending on the state of info.control, set destinationInfo.currentEditor accordingly.
		destinationInfo.currentEditor = $(info.control.identify() + 'text');

		return destinationInfo.currentEditor;
	},

	refresh_html_editor: function(textarea) {
		var container = textarea.next('.HtmlEditorContainer');
		if (!container)
			return;

		// TODO: May need to parse the value for data field insert tags, etc..
		textarea.hide();
		this.htmlEditor.setData(textarea.getValue());
		container.insert(this.htmlEditorWrapper.show());
	},
	
	// TODO: Rename to refresh_display() because this also refresh the ckeditor.
	refresh_accordion: function(type, verticalSection, subtypeSection) {
		this.toolsAccordion.enable_section('callMe');
		this.toolsAccordion.enable_section('audioLibrary');
		this.toolsAccordion.enable_section('dataField');
		this.toolsAccordion.enable_section('attachment');
		this.toolsAccordion.enable_section('translation');
		this.toolsAccordion.unlock_section('callMe');
		this.toolsAccordion.unlock_section('audioLibrary');

		var subtype;
		var languageCode;

		if (type != 'phone') {
			this.toolsAccordion.disable_section('callMe');
			this.toolsAccordion.disable_section('audioLibrary');
		}
		if (type != 'email') {
			this.toolsAccordion.disable_section('attachment');
		}
		if (type == 'sms') {
			this.toolsAccordion.disable_section('dataField');
		}

		var destinationInfo = this.destinationInfos[type];
		var languageTabs = destinationInfo.languageTabs;
		if (languageTabs) {
			languageCode = verticalSection || languageTabs.currentSection;
			
			languageTabs.sections[languageCode].contentDiv.down('.ForAccordion').insert(this.toolsAccordion.container);

			if (languageCode == 'en') {
				this.toolsAccordion.disable_section('translation');
			} else if (languageCode == 'autotranslate') {
				this.toolsAccordion.disable_section('callMe');
				this.toolsAccordion.disable_section('audioLibrary');
				this.toolsAccordion.disable_section('translation');
			} else {
				var subtypeTabs = destinationInfo.subtypeTabs[languageCode];
				if (subtypeTabs)
					subtype = subtypeSection || subtypeTabs.currentSection;
				else
					subtype = 'voice';
				if (destinationInfo.translationSettingDivs) {
					var settingDiv = destinationInfo.translationSettingDivs[subtype+languageCode];
					var checkbox = settingDiv.down('input.EnableTranslationCheckbox');

					if (checkbox.checked) {
						this.toolsAccordion.lock_section('callMe');
						this.toolsAccordion.lock_section('audioLibrary');
					}

					var translationSection = $('translationSection');
					
					translationSection.select('.TranslationSettingDiv').invoke('hide');
					translationSection.insert(settingDiv.show());
				} else {
					this.toolsAccordion.disable_section('translation');
				}
			}
		} else {
			this.toolsAccordion.disable_section('translation');
			$(type + 'Container').down('.ForAccordion').insert(this.toolsAccordion.container);
		}

		var currentEditor = this.get_current_editor(type, subtype, languageCode);
		if (currentEditor)
			this.refresh_html_editor(currentEditor);
	}
});

