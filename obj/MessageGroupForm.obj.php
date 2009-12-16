<?php

// current TODO: combine callme, audio upload, and audio library into a single "Audio" accordion section.
// current TODO: audio upload
// next TODO: translation with field inserts.
// next TODO: revise renderFormItems(), refactor so that it's less work for javascript DOM manipulation. (speedup load time in Internet Explorer by utilizing lazy DOM manipulation for messagebody only when the user clicks on a destination or language tab)
// next TODO: alter appearance of data fields in CKEditor to prevent user from interleaving markup in the fieldname, which causes validation errors, but we still want to allow the user to stylize the data field.
// next TODO: auto translation auto creates plain text version if it's not already set, but it does not update the message until the user clicks Refresh Translations in the plain subtab.
// next TODO: if clear button clicked, set greyed-out instructions.
// next TODO: email auto creates plain text version if not already set.
// next TODO: save on tabbing and window close.
// next TODO: implement new formitem's getJSCode() and getJSDependencies()
// next TODO: CKEditor image uploads
// next TODO: strong validation.
// next TODO: restructure tabs to new structure: destination->subtype->languages instead of destination->languages->subtype.
// next TODO: write a function to cause all languages for a certain destination type to be form_validated. (when loading the form).
// next TODO: when saving the form, delete all existing messages?
// next TODO: set a default language variable instead of checking against 'english' or 'en'
// next TODO: set the first destination tab, subtype tab, etc.. depending on permissions and settings.
// delayed TODO: tweak tabs/accordion/splitpane to not wiggle around so much.
// delayed TODO: if there are languages that are not part of the valid set defined in translate.php, do not translate. But, do not assume that the language is valid, explicitly check against an array before sending to translate.php.
// delayed TODO: summary page layout -- no gridlines for SMS, no icons for SMS except for English.
// delayed TODO: for auto-translate, alert if text is longer than 2000 when clicking on Translate or Retranslate.
// delayed TODO: CKEditor custom toolbar
// delayed TODO: don't translate if text is blank.
// delayed TODO: (This should already be done in javascript, but just in case) When saving, make sure if no plain text version is created, auto create one.
// delayed TODO: make sure allowed languages is in translate.php's $supportedlanguages
// delayed TODO: wordsmith Data Field Insert accordion section to "Data Fields"
// delayed TODO: make crossplatform rounded corners for tabs.

/*
 * test case: translations with {{audio}}
 * test case: translations with ckeditor images
 * test case: summary page -- click on a multilingual TD for SMS. Verify that there are no javascript errors; verify that you are not taken to any tab.
 * test case: the langugae tab icon for an email should be accept.gif if either html or plain is a valid message. It does not need both subtypes to be valid.
 * test case: in autotranslate, uncheck lanaguage that has translation. Then go to that language tab and verify that this does not automatically disable translation for the language.
 * test case: if callme to record fials, ui should let the user retry
 * test case: if the english source for a language has been cleared but the translation is not overidden, make sure the translation is also blank.
 * test case: a previously overridden message should no longer keep state/save for overridden once you choose to disable translation alltogether.
 * test case (phone): first go to Chinese with translation on, then go to SPanish with translation off. In spanish, open the Audio Library accordion section, then go to Chinese. Verify that Chinese's audio library does not open and is disabled.
 * test case: when you click refresh translation in autotranslate, reproduce by translating once, then disabling a language's translation, then translate again. notice that the message's text did not get set to the new translation.
 *

GENERAL TEST CASES:
* test case: For spanish phone message, do a callme and also insert from audio library. Then, enable translations. The UI should prevent you from including an audio file in a translation message, perhaps using a validator and making sure there are no message parts of type='A'.
* test case: UTF8 on translations, message body, etc..
* test case: HTML on translations, message body, etc..
* test case: test in Internet Explorer.
* test case: test in Safari, especially CKEDITOR.

FEATURES SUMMARY:
 *
 *  *-- Translation
	* display and buttons.
	*
 * --AutoTranslate
	* consistent with Translation checkbox and editor.
	*
	*
 * --Translation Rules
	* no translation for english (Default).
	* no translation for SMS.
 * --CallMe
	* keep a list in javascript, auto insert into message body.
	* make sure it's not a translated message.
 * CK
	* for email, works with translation message body elements (refreshes correctly, etc).
	* get the cursor position so that data field inserts and callme to record work with ck instead of just the textarea.
	* get ck to update the underlying textarea so that status icons for each language and destination works correctly.
 * Summary
 * Status Images.
 * google translate using INPUT
	* validate against no audio library
	* validate no callme
	* yes language tags [[English]] [[Spanish]]
	* yes data fields.
 * Save, Load.
 * Validation and required fields
 * SMS letter counter.
 *
 * Save on Tabout and window close.
 *
 * Image Uploads

 * Wording
 * Rounded Corners
 * Internet Explorer 6-7-8, Safari
 */

class CallMe extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		$nophone = _L("Phone Number");
		$defaultphone = escapehtml((isset($this->args['phone']) && $this->args['phone'])?Phone::format($this->args['phone']):$nophone);
		if (!$value)
			$value = '{}';
		// Hidden input item to store values in
		$str = '<input id="'.$n.'" name="'.$n.'" type="hidden" value="'.escapehtml($value).'" />
		<div>
			<div id="'.$n.'_messages" style="padding: 6px; white-space:nowrap"></div>
			<div id="'.$n.'_altlangs" style="clear: both; padding: 5px; display: none"></div>
		</div>
		';
		// include the easycall javascript object and setup to record
		$str .= '<script type="text/javascript" src="script/easycall.js.php"></script>
			<script type="text/javascript">
			document.observe("dom:loaded", function() {
				//return;
				var msgs = '.$value.';
				// Load default. it is a special case
				new Easycall(
					"'.$this->form->name.'",
					"'.$n.'",
					"Default",
					"'.((isset($this->args['min']) && $this->args['min'])?$this->args['min']:"10").'",
					"'.((isset($this->args['max']) && $this->args['max'])?$this->args['max']:"10").'",
					"'.$defaultphone.'",
					"'.$nophone.'",
					"audio",
					"callme"
				).load();
			});
			</script>';
		return $str;
	}
}

////////////////////////////////////////////////////////////////////////////////
// Custom Validators
////////////////////////////////////////////////////////////////////////////////
class ValCallMeMessage extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		global $USER;

		if (!$USER->authorize("starteasy"))
			return "$this->label "._L("is not allowed for this user account");


		$values = json_decode($value, true);
		if (isset($values['Default'])) {
			$audioFile = new AudioFile($values['Default'] +0);
			if ($audioFile->userid !== $USER->id)
				return "$this->label "._L("has invalid audio file values");
		}
		return true;
	}
}

class MessageGroupForm extends Form {
	//////////////////////////////
	// Gather Fields.
	// $messages = array( $type . $subtype . $language . $autotranslate => new Message2() );
	var $messages;
	var $destinationInfos;

	// $settings['readonly'] = boolean.
	// $settings['disablephone'] = boolean.
	// $settings['disableemail'] = boolean.
	// $settings['disablesms'] = boolean.
	// $settings['disablesummary'] = boolean.
	// $settings['disablemultilingual'] = boolean.
	function MessageGroupForm ($name, $helpsteps, $buttons, $messageGroup, $settings) {
		/////////////////////////////////////////////
		// Authorization and Customizations.
		/////////////////////////////////////////////
		global $USER; // Used for authorization checks.

		/////////////////////////////////////////////
		// Data Gathering
		/////////////////////////////////////////////
		$this->messageGroup = $messageGroup;
		$this->destinationInfos = array();
		$this->messages = array();
		$this->audiofiles = DBFindMany('AudioFile', "from audiofile where userid = $USER->id and deleted != 1 order by name");
		$this->dataFields = FieldMap::getAuthorizedMapNames();

		if (empty($settings['disablePhone'])) {
			$this->destinationInfos['phone'] = array();
			$this->destinationInfos['phone']['subtypes'] = array('voice');

			// Todo: check for multilingual privilege.
			$this->destinationInfos['phone']['languages'] = array('en' => 'English', 'vt' => 'Chinese', 'vt' => 'Chinese', 'es' => 'Spanish');
			$preferredVoice = "female";
		}

		if (empty($settings['disableEmail'])) {
			$this->destinationInfos['email'] = array();
			$this->destinationInfos['email']['subtypes'] = array('html', 'plain');

			// Todo: check for multilingual privilege.
			$this->destinationInfos['email']['languages'] = array('en' => 'English', 'vt' => 'Chinese', 'vt' => 'Chinese', 'es' => 'Spanish');
		}

		if (empty($settings['disableSMS'])) {
			$this->destinationInfos['sms'] = array();
			$this->destinationInfos['sms']['subtypes'] = array('plain');
			$this->destinationInfos['sms']['languages'] = array('en' => 'English');
		}

		/////////////////////////////////////////////
		// Form Initialization.
		/////////////////////////////////////////////
		/* Necessary form items:
		 ** New TranslationItem for each destination-language.
		 *** Each TranslationItem must keep track of sourceMessage and translatedMessage.
		 *** Each TranslationItem must indicate if it has been overridden.
		 *** Each TranslationItem can be used as a manually typed message; just set overridden=true.
		 *** TODO: If TranslationItem does not meet requirements, make a subclass.
		 */
		$this->emailItems = array();
		$this->attachmentItems = array();
		$this->messageItems = array();
		$this->translationSettingItems = array();
		$this->advancedItems = array();
		$this->callMeItems = array();
		$this->audioLibraryItems = array();

		$formdata = array();

		foreach ($this->destinationInfos as $type => $destination) {
			// Store the fieldareas for easy DOM manipulation into $destinationTabs.
			$this->destinationInfos[$type]['fieldareas'] = array();

			foreach ($destination['subtypes'] as $subtype) {
				foreach ($destination['languages'] as $languageCode => $languageName) {

					$messageKey = $type . $subtype . $languageCode;
					$messageBody = '';
					$sourceText = '';
					$autotranslate = 'none';

					$message = new Message2();
					$message->type = $type;
					$message->subtype = $subtype;
					$message->languagecode = $languageCode;
					$message->autotranslate = $autotranslate;
					$this->messages[$messageKey] = $message;

					$formdata[$messageKey] = array(
						"label" => ucfirst($languageName),
						"value" => json_encode(array(
							"enabled" => true,
							"text" => $messageBody,
							"override" => false,
							"gender" => 'female'
						)),
						"validators" => array(array("ValTranslation")),
						"control" => array("MessageBody2",
							"phone" => $type == 'phone',
							"language" => strtolower($languageName),
							"sourceText" => $sourceText,
							"multilingual" => $type != 'sms',
							"subtype" => $subtype
						),
						"transient" => false,
						"helpstep" => 2
					);
					$this->messageItems[] = $messageKey;
					$this->destinationInfos[$type]['fieldareas'][$subtype . $languageCode] = "{$name}_{$messageKey}_fieldarea";
				}
			}
		}

		/////////////////////////////////////
		// Message Group Settings
		/////////////////////////////////////
		$formdata['name'] = array(
			"label" => _L('Message Group Name'),
			"value" => $messageGroup->name,
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata['description'] = array(
			"label" => _L('Message Group Description'),
			"value" => $messageGroup->description,
			"validators" => array(),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata['autoexpire'] = array(
			"label" => _L('Auto Expire'),
			"value" => 0,
			"validators" => array(),
			"control" => array("RadioButton", "values" => array(0 => "Yes (Keep for ". getSystemSetting('softdeletemonths', "6") ." months)",1 => "No (Keep forever)")),
			"helpstep" => 1
		);

		$this->advancedItems[] = 'autoexpire';

		/////////////////////////////////////
		// Phone Message Settings
		/////////////////////////////////////
		$formdata['preferredVoice'] = array(
			"label" => _L('Preferred Voice'),
			"value" => 'Female',
			"validators" => array(array("ValRequired")),
			"control" => array("RadioButton","values" => array ("Female" => "Female","Male" => "Male")),
			"helpstep" => 2
		);
		$this->advancedItems[] = 'preferredVoice';

		$formdata["callme"] = array(
			"label" => _L('Voice Recording'),
			"value" => "",
			"validators" => array(
				array("ValCallMeMessage")
			),
			"control" => array(
				"CallMe",
				"phone" => Phone::format($USER->phone),
				"max" => getSystemSetting('easycallmax',10),
				"min" => getSystemSetting('easycallmin',10)
			),
			"helpstep" => 1
		);
		$this->callMeItems[] = 'callme';

		/////////////////////////////////////
		// Phone Message Settings
		/////////////////////////////////////
		$formdata['subject'] = array(
			"label" => _L('Subject'),
			"value" => '',
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);
		$this->emailItems[] = 'subject';

		$formdata['fromname'] = array(
			"label" => _L('From Name'),
			"value" => '',
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);
		$this->emailItems[] = 'fromname';

		$formdata['fromemail'] = array(
			"label" => _L('From Email'),
			"value" => '',
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);
		$this->emailItems[] = 'fromemail';

		$attachvalues = array();
		$formdata["attachments"] = array(
			"label" => _L('Attachments'),
			"value" => $attachvalues,
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
			"helpstep" => 3
		);
		$this->attachmentItems[] = 'attachments';

		// Extend MessageBody.
		// TODO: Is MessageBody formitem used anywhere other than the advanced message editor?
		// If so, rethink extending it.
		// But if it's just used in the advanced message editor, then enhance it with ckeditor and plain text view if email,
		// and use DOM manipulation to move the tools into the accordion.
		// Also enhance MessageBody with call-me-to-record.
		// Also enhance with Translations.
		// Need to register click handlers and accordion handlers on the MessageBody's elements to do extra things like AutoTranslate.

		parent::Form($name, $formdata, $helpsteps, $buttons);
	}

	function render () {
		global $USER;

		// HTML
		$audioFileOptions = array();
		foreach($this->audiofiles as $audiofile) {
			$audioFileOptions[] = "<option value='$audiofile->id'>" . escapehtml($audiofile->name) . "</option>";
		}

		$dataFieldOptions = array();
		foreach($this->dataFields as $field) {
			$dataFieldOptions[] = "<option value='$field'>$field</option>";
		}

		$summaryHeaders = "";
		$summaryLanguageRows = "";
		$languageNames = array();
		foreach ($this->destinationInfos as $type => $destination) {
			foreach ($destination['subtypes'] as $subtype) {
				$subtypeHtml = count($destination['subtypes']) > 1 ? (" (" . ucfirst($subtype) . ") ") : "";
				$summaryHeaders .= "<th class='Destination'>" . ucfirst($type) . $subtypeHtml . "</th>";

				$languageNames = array_merge($languageNames, $destination['languages']);
			}
		}
		foreach ($languageNames as $languageCode => $languageName) {
			$summaryLanguageRows .= "<tr><th class='Language'>" . ucfirst($languageNames[$languageCode]) . "</th>";
			foreach ($this->destinationInfos as $type => $destination) {
				foreach ($destination['subtypes'] as $subtype) {
					$icon = 'img/icons/diagona/16/160.gif';
					$summaryLanguageRows .= "<td class='StatusIcon'><img class='StatusIcon' id='summary_{$type}_{$subtype}_{$languageCode}' src='$icon'/></td>";
				}
			}
			$summaryLanguageRows .= "</tr>";
		}

		$bareboneItems = array_merge($this->attachmentItems, $this->messageItems, $this->translationSettingItems, $this->advancedItems, $this->callMeItems, $this->audioLibraryItems);

		$str = "
			<!-- FORM -->
			<div class='newform_container' style='clear:both'>

				<form class='newform' id='{$this->name}' name='{$this->name}' method='POST' action='" . $this->get_post_url() . "'>

					<div id='renderedFormItems'>
							" . $this->renderFormItems($bareboneItems) . "
					</div>

					<!-- Initially Offscreen Form Items -->
					<table style='display:none'>
						<tbody>
							" . $this->renderFormItemsControl($this->messageItems) . "
						</tbody>
					</table>

					<!-- Initially Offscreen Premade Accordion Content -->
					<div style='display:none'>
						<div id='toolsAccordionContainer'></div>

						<table id='attachmentSection' style='width:100%; border-collapse:collapse'>
							<tbody>".$this->renderFormItemsControl($this->attachmentItems)."</tbody>
						</table>

						<div id='callMeSection' style='width:100%'>
							<table style='width:100%; border-collapse:collapse'><tbody class='AudioFiles'></tbody></table>
							<table style='width:100%; border-collapse:collapse'><tbody class='EasycallFormItem'>".$this->renderFormItemsControl($this->callMeItems)."</tbody></table>
						</div>

						<div id='audioLibrarySection'>
							<table style='width:100%; border-collapse:collapse'>
								<tr>
									<td valign='top' colspan='2'>
										<b>Insert Audio Recording:</b>
									</td>
								</tr>
								<tr>
									<td valign='top' class='bottomBorder'>
										<select id='audioLibrarySelect'>
											<option value=''>-- Select an Audio File --</option>
											" . implode('', $audioFileOptions) . "
										</select>
										" . icon_button(_L('Insert'),'fugue/arrow_turn_180', null, null, "id='insertAudio'") . "
										" . icon_button(_L('Play'),'fugue/control', null, null, "id='playAudio'") ."
									</td>
								</tr>
							</table>
						</div>

						<div id='dataFieldSection'>
							<table style='width:100%; border-collapse:collapse'>
								<tr>
									<td valign='top'>
										<b>Insert Data Field:</b>
									</td>
								</tr>
								<tr>
									<td valign='top'>
										<table border='0' cellpadding='1' cellspacing='0' style='font-size: 9px; margin-top: 5px;width:100%; border-collapse:collapse'>
											<tr>
												<td>
													<span style='font-size: 9px;'>Default&nbsp;Value:</span><br />
													<input id='dataFieldDefault' type='text' size='10' value=''/>
												</td>
												<td>
													<span style='font-size: 9px;'>Data&nbsp;Field:</span><br />
													<select id='dataFieldSelect' >
														<option value=''>-- Select a Field --</option>;
														" . implode('', $dataFieldOptions) . "
													</select>
												</td>
											</tr>
										</table>

										" . icon_button(_L('Insert'),'fugue/arrow_turn_180',null, null, "id='insertDataField'") . "
								</td>
								</tr>
							</table>
						</div>

						<div id='translationSection'>
							<table style='width:100%; border-collapse:collapse'><tbody>".$this->renderFormItemsControl($this->translationSettingItems)."</tbody></table>
						</div>

						<div id='advancedSection'>
							<table style='width:100%; border-collapse:collapse'><tbody>".$this->renderFormItemsControl($this->advancedItems)."</tbody></table>
						</div>
					</div>

					<!-- Initially Offscreen Language Tabs -->
					<div style='display:none'>
						<div id='phoneContainer'></div>
						<div id='emailContainer'><table style='width:100%; border-collapse:collapse'><tbody></tbody></table></div>
						<div id='smsContainer'></div>
						<div id='summaryContainer'>
							<table style='border-collapse: collapse'>
								<tr>
									<th></th>
									$summaryHeaders
								</tr>
								$summaryLanguageRows
							</table>
						</div>
					</div>

					<!-- Main Tabs -->
					<fieldset>
						<div id='destinationTabsContainer'></div>
					</fieldset>

					<!-- Buttons -->
					<div style='clear:both; margin-top: 20px;'>
						".implode('', $this->buttons)."
					</div>

					" . $this->render_hidden_serialnum() . "
				</form>
			</div>
		";

		// JAVASCRIPT
		$str .= "
			<script type='text/javascript' src='script/ckeditor/ckeditor_basic.js'></script>
			<script type='text/javascript' src='script/datepicker.js'></script> <!-- Needed for data-field-insert Date fields. -->
			<script type='text/javascript' src='script/accordion.js'></script>
			<script type='text/javascript' src='script/messagegroupform.js.php'></script>
			<script type='text/javascript'>
				document.observe('dom:loaded', function() {
					var formName = '{$this->name}';
					form_load(formName,
						'". $this->get_post_url() ."',
						".json_encode($this->formdata).",
						".json_encode($this->helpsteps).",
						".($this->ajaxsubmit ? "true" : "false")."
					);
					var destinationInfos = " . json_encode($this->destinationInfos) . ";

					var destinationTabs = new Tabs($('destinationTabsContainer'), {'showDuration':0, 'hideDuration':0});
					var toolsAccordion = new Accordion('toolsAccordionContainer');
					var messageGroupForm = new MessageGroupForm (formName, destinationTabs, destinationInfos, toolsAccordion);
					
					// Accordion.
					toolsAccordion.container.setStyle({'paddingLeft':'10px'});
					toolsAccordion.add_section('attachment');
					toolsAccordion.add_section('callMe');
					toolsAccordion.add_section('audioLibrary');
					toolsAccordion.add_section('dataField');
					toolsAccordion.add_section('translation');
					toolsAccordion.add_section('advanced');
					toolsAccordion.update_section('attachment', {
						'title': 'Attachments',
						'icon': 'img/icons/accept.gif',
						'content': $('attachmentSection')
					});
					toolsAccordion.update_section('callMe', {
						'title': 'Call Me to Record',
						'icon': 'img/icons/accept.gif',
						'content': $('callMeSection')
					});
					toolsAccordion.update_section('audioLibrary', {
						'title': 'Audio Library',
						'icon': 'img/icons/accept.gif',
						'content': $('audioLibrarySection')
					});
					toolsAccordion.update_section('dataField', {
						'title': 'Insert Data Fields',
						'icon': 'img/icons/accept.gif',
						'content': $('dataFieldSection')
					});
					toolsAccordion.update_section('translation', {
						'title': 'Translation',
						'icon': 'img/icons/accept.gif',
						'content': $('translationSection')
					});
					toolsAccordion.update_section('advanced', {
						'title': 'Advanced Options',
						'icon': 'img/icons/accept.gif',
						'content': $('advancedSection')
					});

					// Vertical Tabs.
					for (var type in destinationInfos) {

						var destination = destinationInfos[type];

						var subtypes = destination['subtypes'];
						var languages = destination['languages'];
						var fieldareas = destination['fieldareas'];
						destination.translationSettingDivs = {};
						destination.subtypeTabs = {};
						destination.hasValidMessages = {};
						destination.languageHasValidMessages = {};

						var languageTabs =  (\$H(languages).size() > 1) ? new Tabs($(type + 'Container'), {'vertical':true, 'showDuration':0, 'hideDuration':0}) : null;



						if (languageTabs) {
							if (type == 'email')
								languageTabs.splitContainer.setStyle({'marginTop':'10px'});

							languageTabs.panelsPane.setStyle({'padding':'10px'});

							var splitPane = make_split_pane(true);
							splitPane.down('.SplitPane', 0).addClassName('ForAutoTranslate');
							splitPane.down('.SplitPane', 1).addClassName('ForAccordion');

							languageTabs.add_section('autotranslate');
							languageTabs.update_section('autotranslate', {
								'title': 'Auto-Translate',
								'icon': 'img/icons/transmit.gif',
								'content': splitPane
							});

							languageTabs.sections['autotranslate'].titleDiv.setStyle({'paddingTop':'10px'});
							languageTabs.sections['autotranslate'].titleDiv.setStyle({'paddingBottom':'10px'});
							languageTabs.sections['autotranslate'].titleDiv.setStyle({'marginBottom':'10px'});

							languageTabs.container.observe('Tabs:ClickTitle', function(event, type) {
								if (event.memo.section == 'html' || event.memo.section == 'plain')
									return;

								this.refresh_accordion(type, event.memo.section);
							}.bindAsEventListener(messageGroupForm, type));
						}

						for (var languageCode in languages) {
							var contentDiv = new Element('div');

							var subtypeTabs = subtypes.length > 1 ? new Tabs(contentDiv, {'showDuration':0, 'hideDuration':0}) : null;

							for (var i = 0, count = subtypes.length; i < count; i++) {
								var subtype = subtypes[i];

								if (subtypeTabs) {
									subtypeTabs.add_section(subtype);
								}

								var tbody = new Element('tbody');

								var fieldarea = $(fieldareas[subtype + languageCode]);


								var formItem = $(messageGroupForm.formName + '_' + type + subtype + languageCode);
								formItem.observe('MessageBody:OverrideChanged', function(event, type, subtype, languageCode) {
									var currentEditor = this.get_current_editor(type, subtype, languageCode);
									if (currentEditor)
										this.refresh_html_editor(currentEditor);
								}.bindAsEventListener(messageGroupForm, type, subtype, languageCode));

								formItem.observe('Form:ValidationDisplayed', function(event, type, subtype, languageCode) {
									var destination = this.destinationInfos[type];
									var destinationTabs = this.destinationTabs;
									var subtypes = destination.subtypes;
									var subtypeTabs = destination.subtypeTabs[languageCode];
									var languages = destination.languages;
									var hasValidMessages = destination.hasValidMessages;
									var languageTabs = destination.languageTabs;
									var languageHasValidMessages = destination.languageHasValidMessages;

									// Store the updated status.
									var messageValid = (event.memo.style == 'valid') && (this.get_message_element(type, subtype, languageCode, 'text').value.strip() != '');
									hasValidMessages[subtype + languageCode] = messageValid;

									if (messageValid) {
										if (subtypeTabs)
											subtypeTabs.sections[subtype].titleIcon.src = 'img/icons/accept.gif';
									}
									
									// If the status of any subtype message for this language is valid, then indicate that the language is also.
									var languageHasValidMessage = messageValid;
									if (!messageValid) {
										if (subtypeTabs)
										subtypeTabs.sections[subtype].titleIcon.src = 'img/icons/diagona/16/160.gif';
										
										for (var i = 0, count = subtypes.length; i < count; i++) {
											if (hasValidMessages[subtypes[i] + languageCode]) {
												languageHasValidMessage = true;
												languageHasValidMessages[languageCode] = true;
												break;
											}
										}
									}

									if (languageHasValidMessage) {
										if (languageTabs) {
											languageTabs.sections[languageCode].titleIcon.src = 'img/icons/accept.gif';
										}

										destinationTabs.sections[type].titleIcon.src = 'img/icons/accept.gif';

									} else {
										languageHasValidMessages[languageCode] = false;
										
										if (languageTabs) {
											languageTabs.sections[languageCode].titleIcon.src = 'img/icons/diagona/16/160.gif';
										}

										var destinationHasValidMessage = false;
										// If the status of any language for this destination type is valid, then the indicate that destination type is also valid.
										for (var languageCode in languages) {
											if (languageHasValidMessages[languageCode]) {
												destinationHasValidMessage = true;
												break;
											}
										}
										
										if (destinationHasValidMessage) {
											destinationTabs.sections[type].titleIcon.src = 'img/icons/accept.gif';
										} else {
											destinationTabs.sections[type].titleIcon.src = 'img/icons/diagona/16/160.gif';
										}
									}

								}.bindAsEventListener(messageGroupForm, type, subtype, languageCode));


								formItem.observe('MessageBody:TranslationSettingChanged', function(event, type, languageCode, subtype) {
									this.refresh_accordion(type, languageCode, subtype);
								}.bindAsEventListener(messageGroupForm, type, languageCode, subtype));

								var settingDiv = fieldarea.down('.TranslationSettingDiv');
								destination.translationSettingDivs[subtype + languageCode] = settingDiv;

								tbody.insert(fieldarea);

								var content = new Element('table', {'style':'width:100%; border-collapse:collapse'}).insert(tbody);

								if (subtypeTabs) {
									subtypeTabs.update_section(subtype, {
										'title': subtype,
										'icon': 'img/icons/diagona/16/160.gif', // TODO: Show accept.gif if valid.
										'content': content
									});
								} else {
									contentDiv.insert(content);
								}

							}

							if (subtypeTabs) {
								subtypeTabs.show_section(subtypes[0]);


								destination.subtypeTabs[languageCode] = subtypeTabs;



								subtypeTabs.container.observe('Tabs:ClickTitle', function(event, type, languageCode) {
									if (event.memo.section != 'html' && event.memo.section != 'plain')
										return;
									this.refresh_accordion(type, languageCode, event.memo.section);
								}.bindAsEventListener(messageGroupForm, type, languageCode));

							} else if (type == 'email') {

							}

							var splitPane = make_split_pane(true);
							splitPane.down('.SplitPane', 1).addClassName('ForAccordion');
							splitPane.down('.SplitPane', 0).insert(contentDiv);

							if (languageTabs) {
								languageTabs.add_section(languageCode);
								languageTabs.update_section(languageCode, {
									'title': languages[languageCode],
									'icon': 'img/icons/diagona/16/160.gif',
									'content': splitPane
								});

							} else {
								$(type + 'Container').insert(splitPane);
							}
						}

						if (languageTabs) {
							languageTabs.show_section('en'); // TODO: use defualt language variable.
							destination.languageTabs = languageTabs;

							var autotranslateSection = languageTabs.sections['autotranslate'];
							var forAutoTranslate = autotranslateSection.contentDiv.down('.ForAutoTranslate');
							var autotranslateSubtypeTabs = subtypes.length > 1 ? new Tabs(forAutoTranslate, {'showDuration':0, 'closeDuration':0}) : null;

							for (var i = 0, count = subtypes.length; i < count; i++) {
								var subtype = subtypes[i];
								var contentDiv = autotranslateSubtypeTabs ? new Element('div') : forAutoTranslate;

								if (autotranslateSubtypeTabs) {
									autotranslateSubtypeTabs.add_section(subtype);
									autotranslateSubtypeTabs.update_section(subtype, {
										'title': subtype,
										'icon': 'img/pixel.gif',
										'content': contentDiv
									});
								}

								// NOTE: AutoTranslate's constructor automatically attaches the new instance to messageGroupForm.autotranslates.
								var autotranslate = new AutoTranslate(contentDiv, messageGroupForm, type, subtype);
							}

							if (autotranslateSubtypeTabs) {
								autotranslateSubtypeTabs.show_section(subtypes[0]);
								messageGroupForm.autotranslateSubtypeTabs[type] = autotranslateSubtypeTabs;
							}
						}
					}

					// Main Tabs.
					destinationTabs.add_section('phone');
					destinationTabs.add_section('email');
					destinationTabs.add_section('sms');
					destinationTabs.add_section('summary');
					destinationTabs.update_section('phone', {
						'title': 'Phone',
						'icon': 'img/icons/diagona/16/160.gif', // TODO: Show accept.gif if valid.
						'content': $('phoneContainer')
					});

					$('emailContainer').down('tbody').insert($('messagegroupform_subject_fieldarea')).insert($('messagegroupform_fromname_fieldarea')).insert($('messagegroupform_fromemail_fieldarea'));
					destinationTabs.update_section('email', {
						'title': 'Email',
						'icon': 'img/icons/diagona/16/160.gif', // TODO: Show accept.gif if valid.
						'content': $('emailContainer')
					});

					destinationTabs.update_section('sms', {
						'title': 'SMS',
						'icon': 'img/icons/diagona/16/160.gif', // TODO: Show accept.gif if valid.
						'content': $('smsContainer')
					});
					destinationTabs.update_section('summary', {
						'title': 'Summary',
						'icon': 'img/icons/sum.gif',
						'content': $('summaryContainer')
					});

					destinationTabs.container.observe('Tabs:ClickTitle', function(event) {
						var section = event.memo.section;

						if (section == 'summary') {
							// Update Summary.
							for (var type in this.destinationInfos) {
								var destination = this.destinationInfos[type];
								var hasValidMessages = destination.hasValidMessages;
								var languages = destination.languages;
								var subtypes = destination.subtypes;

								for (var languageCode in languages) {
									for (var i = 0, count = subtypes.length; i < count; i++) {
										var subtype = subtypes[i];

										var image = $('summary_' + type + '_' + subtype + '_' + languageCode);

										if (hasValidMessages[subtype + languageCode]) {
											image.src = 'img/icons/accept.gif';
										} else {
											image.src = 'img/icons/diagona/16/160.gif';
										}
									}
								}
							}
							return;
						} else if (section != 'phone' && section != 'email' && section != 'sms') {
							return;
						}
						this.refresh_accordion(section);
					}.bindAsEventListener(messageGroupForm));
					$('summaryContainer').observe('click', function(event) {
						var element = event.element();
						if (!element.match('img.StatusIcon'))
							return;
						var idSplit = element.identify().split('_');
						var type = idSplit[1];
						var subtype = idSplit[2];
						var languageCode = idSplit[3];
						this.destinationTabs.show_section(type);
						var languageTabs = this.destinationInfos[type].languageTabs;
						if (languageTabs)
							languageTabs.show_section(languageCode);
						var subtypeTabs = this.destinationInfos[type].subtypeTabs;
						if (subtypeTabs) {
							var subtypeTabsForLanguage = subtypeTabs[languageCode];
							if (subtypeTabsForLanguage)
								subtypeTabsForLanguage.show_section(subtype);
						}
					}.bindAsEventListener(messageGroupForm));

					destinationTabs.show_section('phone'); // TODO: Show the first, depending on allowMultilingual, allow phone, etc..
					messageGroupForm.refresh_accordion('phone'); // TODO: Show the first, depending on allowMultilingual, allow phone, etc..

					// Show remaining top-level form items.

					// Register event handlers.


					$('insertDataField').observe('click', function(event) {
						var select = $('dataFieldSelect');
						if (select.getValue()) {
							var defaultValue = $('dataFieldDefault').getValue().strip();
							var defaultString = defaultValue != '' ? ':' + defaultValue : '';

							this.textInsert('<<' + select.options[select.selectedIndex].text + defaultString + '>>');
							// todo: update ckeditor.
						}
					}.bindAsEventListener(messageGroupForm));

					$('insertAudio').observe('click', function(event) {
						var select = $('audioLibrarySelect');
						if (select.getValue()) {
							this.textInsert('{{' + select.options[select.selectedIndex].text + '}}');
							// todo: update ckeditor.
						}
					}.bindAsEventListener(messageGroupForm));

					$('messagegroupform_callme').observe('Easycall:RecordingDone', function(event) {
						cachedAjaxGet('ajax.php?type=AudioFile&id=' + event.memo.Default, function(result, audioFileID) {
							var audioFile = result.responseJSON;

							if (!audioFile.name)
								return;

							var playButton = new Element('button', {'type':'button'}).update('Play');

							playButton.observe('click', function(event, audioFileID) {

							}.bindAsEventListener(playButton, audioFileID));

							var insertButton = new Element('button', {'type':'button'}).update('Insert');

							insertButton.observe('click', function(event, audioFileID, audioFile) {
								this.textInsert('{{' + audioFile.name + '}}');
							}.bindAsEventListener(this, audioFileID, audioFile));

							var tr = new Element('tr');
							tr.insert(new Element('td').insert(audioFile.name.escapeHTML()));
							tr.insert(new Element('td').insert(playButton));
							tr.insert(new Element('td').insert(insertButton));

							$('callMeSection').down('tbody.AudioFiles').insert(tr);

							// TODO: Insert the audio file name into the current message body.
						}.bindAsEventListener(this), event.memo.Default);
					}.bindAsEventListener(messageGroupForm));

				});
			</script>";
		return $str;
	}

	function render_hidden_serialnum() {
		return "<input name='{$this->name}-formsnum' type='hidden' value='{$this->serialnum}'/>";
	}

	function save() {
		global $USER;

		$postdata = $this->getData();

		$this->messageGroup->userid = $USER->id;
		$this->messageGroup->name = $postdata['name'];
		$this->messageGroup->description = $postdata['description'];
		$this->messageGroup->permanent = $postdata["autoexpire"]!=1?0:1;
		$this->messageGroup->modified = QuickQuery("select now()");
		$this->messageGroup->update();

		////////////////////////////////
		// Messages Autotranslations Notes
		// * autotranslate: source (no audio inserts)
		// * autotranslate: translation (no audio inserts)
		// * autotranslate: override
		// * autotranslate: none
		//////////////////////////////
		foreach ($this->messages as $messageKey => $message) {
			if (!isset($postdata[$messageKey]))
				continue;

			/*if (!empty($message->id) && trim($postdata[$messageKey]) == '') {
				// Message content is blank, so delete this message, its parts, and attachments.
				QuickUpdate('delete from messageattachment where messageid=?',false,array($message->id));
				QuickUpdate('delete from messagepart where messageid=?', false, array($message->id));
				QuickUpdate('delete from message where id=?', false, array($message->id));
				continue;
			}*/

			// Common Message Info.
			$message->name = $this->messageGroup->name;
			$message->messagegroupid = $this->messageGroup->id;
			$message->description = $this->messageGroup->description;
			$message->modifydate = $this->messageGroup->modified;
			$message->userid = $USER->id;

			// Email Headers.
			if ($message->type == 'email') {
				$message->subject = trim($postdata["subject"]);
				$message->fromname = trim($postdata["fromname"]);
				$message->fromemail = trim($postdata["fromemail"]);
			}

			// Body and Message Parts.
			$body = trim($postdata[$messageKey]);
			$voiceid = ($message->type == 'phone') ? Voice::getPreferredVoice($message->languagecode, $postdata['preferredVoice']) : null;
			$message->update_with_parts($body, $voiceid);

			// Email Attachments.
			if ($message->type == 'email' && isset($postdata['attachments']))
				$message->reset_attachments(json_decode($postdata['attachments'], true));
		}
	}

	function get_post_url() {
		$posturl = $_SERVER['REQUEST_URI'];
		$posturl .= mb_strpos($posturl,"?") !== false ? "&" : "?";
		$posturl .= "form=". $this->name;
		return $posturl;
	}
}
?>
