<?php

// next task: summary page.
// next task: summary page -- onclick event handlers for each cell.
// next task: status images for each destination.
// next task: correctly find out the current editor for use with data field inserts and call me to record.
// next task: write a function to cause all languages for a certain destination type to be form_validated. (for use in autotranslate and when loading the form).
// next task: replace the subtype icons with the actual form validation icon.
// next task: when saving the form, delete all existing messages?
// next task: when clicking on override translation, disable the sourceTextarea.

/* // QUESTION: it may help usability to use an exclamation point for languageTabIcons if there are errors. likewise for the destination tabs.
 * // test case: test in Internet Explorer.
 *
 * // TODO: if there are languages that are not part of the valid set defined in translate.php, do not translate. But, do not assume that the language is valid, explicitly check against an array before sending to translate.php.
 * // test caes: the langugae tab icon for an email should be accept.gif if either html or plain is a valid message. It does not need both subtypes to be valid.
 * // test case: if the english source for a language has been cleared but the translation is not overidden, make sure the translation is also blank.
 * // current task: status images for each language.
 * // test case: a previously overridden message should no longer keep state/save for overridden once you choose to disable translation alltogether.
 * // test case (phone): first go to Chinese with translation on, then go to SPanish with translation off. In spanish, open the Audio Library accordion section, then go to Chinese. Verify that Chinese's audio library does not open and is disabled.
 * // current task: When you click 'refresh translation' in autotranslate, check the Translation Option's 'Enable Translation' checkbox, and call the formitem's toggleTranslation function.
 * // test case: when you click refresh translation in autotranslate, reproduce by translating once, then disabling a language's translation, then translate again. notice that the message's text did not get set to the new translation.
 * // current task: When you click 'refresh translation' in autotranslate, update the sourceText and translationText for each affected Language.
 * // TODO: no need to confirm() when clicking 'refrehs translation' in autotranslate if you are not actually overwriting any messages.
 * // TODO: take out error_log
 * // TODO: take out 
 * // TODO: for auto-translate, alert if text is longer than 2000 when clicking on Translate or Retranslate.
 * // TODO: don't translate if text is blank.
 * // Todo: set a default language variable instead of checking against 'english' or 'en'
 * // TODO: set the first destination tab, subtype tab, etc.. depending on permissions and settings.
 * // TODO: do not translate default language. (autotranslate tab, and in any google translations).
 * // TODO: does callme to record ui correctly display errors?
 * // TODO: if clear, set greyed-out instructions.
 * // TODO: update get_current_editor for use in autotranslate tab due to data field inserts for phone.
 * // TODO: disable callme, translation, and audiolibrary (accordion sections) for autotranslate phone.
 * // TODO: disable translation (accordion section0 for autotranslate email.
 * // TODO: auto translation auto creates plain text version if it's not already set.
 * // TODO: email auto creates plain text version if not already set.
 * // TODO: Accordion needs to correctly get the current editor for translation messages.
 * // TODO: make sure allowed languages is in translate.php's $supportedlanguages
 *
/// TEST CASE:
* For spanish phone message, do a callme and also insert from audio library. Then, enable translations. The UI should prevent you from including an audio file in a translation message, perhaps using a validator and making sure there are no message parts of type='A'.
* UTF8 on translations, message body, etc..
* HTML on translations, message body, etc..
*
*
*
 * TODO:
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
	// $messages = array( $type . $subtype . $language . $autotranslate => new Message() );
	var $messages;
	var $allowedDestinations;

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
		$this->allowedDestinations = array();
		$this->messages = array();
		$this->audiofiles = DBFindMany('AudioFile', "from audiofile where userid = $USER->id and deleted != 1 order by name");
		$this->dataFields = FieldMap::getAuthorizedMapNames();

		if (empty($settings['disablePhone'])) {
			$this->allowedDestinations['phone'] = array();
			$this->allowedDestinations['phone']['subtypes'] = array('voice');

			// Todo: check for multilingual privilege.
			$this->allowedDestinations['phone']['languages'] = array('en' => 'English', 'vt' => 'Chinese', 'vt' => 'Chinese', 'es' => 'Spanish');
			$preferredVoice = "female";
		}

		if (empty($settings['disableEmail'])) {
			$this->allowedDestinations['email'] = array();
			$this->allowedDestinations['email']['subtypes'] = array('html', 'plain');

			// Todo: check for multilingual privilege.
			$this->allowedDestinations['email']['languages'] = array('en' => 'English', 'vt' => 'Chinese', 'vt' => 'Chinese', 'es' => 'Spanish');
		}

		if (empty($settings['disableSMS'])) {
			$this->allowedDestinations['sms'] = array();
			$this->allowedDestinations['sms']['subtypes'] = array('plain');
			$this->allowedDestinations['sms']['languages'] = array('en' => 'English');
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
		$formdata = array();

		foreach ($this->allowedDestinations as $type => $destination) {
			// Store the fieldareas for easy DOM manipulation into $destinationTabs.
			$this->allowedDestinations[$type]['fieldareas'] = array();

			foreach ($destination['subtypes'] as $subtype) {
				foreach ($destination['languages'] as $languageCode => $languageName) {

					$messageKey = $type . $subtype . $languageCode;
					$messageBody = '';
					$sourceText = '';
					$autotranslate = 'none';

					$message = new Message();
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
						"control" => array("MessageBody",
							"phone" => $type == 'phone',
							"language" => strtolower($languageName),
							"sourceText" => $sourceText,
							"multilingual" => $type != 'sms'
						),
						"transient" => false,
						"helpstep" => 2
					);
					$this->allowedDestinations[$type]['fieldareas'][$subtype . $languageCode] = "{$name}_{$messageKey}_fieldarea";
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

		$formdata['fromname'] = array(
			"label" => _L('From Name'),
			"value" => '',
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$formdata['fromemail'] = array(
			"label" => _L('From Email'),
			"value" => '',
			"validators" => array(
				array("ValRequired","ValLength","min" => 3,"max" => 50)
			),
			"control" => array("TextField","size" => 30, "maxlength" => 51),
			"helpstep" => 1
		);

		$attachvalues = array();
		$formdata["attachments"] = array(
			"label" => _L('Attachments'),
			"value" => $attachvalues,
			"validators" => array(array("ValEmailAttach")),
			"control" => array("EmailAttach","size" => 30, "maxlength" => 51),
			"helpstep" => 3
		);

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
		//var td = $('summary_' + type + subtype + languageCode);
		foreach ($this->allowedDestinations as $type => $destination) {
			foreach ($destination['subtypes'] as $subtype) {
				$subtypeHtml = count($destination['subtypes']) > 1 ? (" (" . ucfirst($subtype) . ") ") : "";
				$summaryHeaders .= "<th class='Destination'>" . ucfirst($type) . $subtypeHtml . "</th>";

				$languageNames = array_merge($languageNames, $destination['languages']);
			}
		}
		foreach ($languageNames as $languageCode => $languageName) {
			$summaryLanguageRows .= "<tr><th class='Language'>" . ucfirst($languageNames[$languageCode]) . "</th>";
			foreach ($this->allowedDestinations as $type => $destination) {
				foreach ($destination['subtypes'] as $subtype) {
					$summaryLanguageRows .= "<td class='StatusIcon' id='summary_{$type}{$subtype}{$languageCode}'></td>";
				}
			}
			$summaryLanguageRows .= "</tr>";
		}

		$str = "
			<!-- FORM -->
			<div class='newform_container' style='clear:both'>

				<form class='newform' id='{$this->name}' name='{$this->name}' method='POST' action='" . $this->get_post_url() . "'>

					<!-- Initially Offscreen Form Items -->
					<div id='renderedFormItems' style='display:none'>
							" . $this->renderFormItems() . "
					</div>

					<!-- Initially Offscreen Premade Accordion Content -->
					<div style='display:none'>
						<div id='toolsAccordionContainer'></div>

						<table id='attachmentSection' style='width:100%; border-collapse:collapse'>
							<tbody></tbody>
						</table>

						<div id='callMeSection' style='width:100%'>
							<table style='width:100%; border-collapse:collapse'><tbody class='AudioFiles'></tbody></table>
							<table style='width:100%; border-collapse:collapse'><tbody class='EasycallFormItem'></tbody></table>
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

						<div id='translationSection'></div>

						<div id='advancedSection'>
							<table style='width:100%; border-collapse:collapse'><tbody></tbody></table>
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
			<div id='ckarea' style='clear:both; display:none'>
				<div>
					<textarea id='forck'> hello </textarea>
				</div>
			</div>
		";

		// JAVASCRIPT
		$str .= "
			
			<script type='text/javascript' src='script/datepicker.js'></script> <!-- Needed for data-field-insert Date fields. -->
			<script type='text/javascript' src='script/accordion.js'></script>
			<script type='text/javascript'>
				document.observe('dom:loaded', function() {

					var formName = '{$this->name}';
					form_load(formName,
						'". $this->get_post_url() ."',
						".json_encode($this->formdata).",
						".json_encode($this->helpsteps).",
						".($this->ajaxsubmit ? "true" : "false")."
					);

					var allowedDestinations = " . json_encode($this->allowedDestinations) . ";

					var AutoTranslate = Class.create({
						initialize: function(container, messageGroupForm, type, subtype) {
							
							

							this.container = container;
							this.messageGroupForm = messageGroupForm;
							this.type = type;
							this.subtype = subtype;
							this.destination = messageGroupForm.allowedDestinations[type];

							
							

							this.languages = this.destination.languages;
							this.fieldareas = this.destination.fieldareas;

							
							

							this.translationDivs = {};
							this.retranslationDivs = {};
							this.languageCheckboxes = {};

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

								
								

								var translationContainer = new Element('div', {'style':'margin-top: 15px; border: solid 1px rgb(210,210,210); padding: 5px'});
								var headerDiv = new Element('div', {'style':'font-size:125%'});
								
								
								var label = new Element('label', {'style':'margin-right: 10px;'}).insert(this.languages[languageCode]);
								
								
								translationContainer.insert(headerDiv.insert(this.languageCheckboxes[languageCode]).insert(label).insert(playButton));
								translationContainer.insert(this.translationDivs[languageCode]);
								
								
								translationContainer.insert(new Element('div', {'style':'margin-top:10px'}).insert(retranslateButton));
								
								
								translationContainer.insert(this.retranslationDivs[languageCode]);
								this.container.insert(translationContainer);

								
								

								playButton.observe('click', this.on_click_play.bindAsEventListener(this));
								
								
								retranslateButton.observe('click', this.on_click_retranslate.bindAsEventListener(this, languageCode));
								
								
								this.languageCheckboxes[languageCode].observe('click', this.on_toggle_language.bindAsEventListener(this));
								
								
							}

							
						},

						get_message_prefix: function(languageCode) {
							return this.messageGroupForm.formName + '_' + this.type + this.subtype + languageCode;
						},

						get_message_element: function(languageCode, suffix) {
							
							
							
							
							return $(this.get_message_prefix(languageCode) + suffix);
						},

						on_click_translate: function() {
							var willOverwrite = true;

							if (!willOverwrite || confirm('Are you sure?')) {
								var sourceText = this.sourceTextarea.value;


							


								var translateLanguages = {};
								
								for (var languageCode in this.languageCheckboxes) {
									if (this.languageCheckboxes[languageCode].checked) {
										translateLanguages[languageCode] = this.languages[languageCode];

										this.translationDivs[languageCode].update('<img src=\"img/ajax-loader.gif\" />');

										// Clear english retranslations.
										this.retranslationDivs[languageCode].update();

										
										this.get_message_element(languageCode, 'sourceText').update(sourceText);
										
										if (this.get_message_element(languageCode, 'translatecheck'))
											this.get_message_element(languageCode, 'translatecheck').checked = true;
										toggleTranslation(this.get_message_prefix(languageCode), null);
									}
								}

								
								

								new Ajax.Request('translate.php', {
									method:'post',
									parameters: {'english': sourceText, 'languages': \$H(translateLanguages).values().join(';')},
									onSuccess: function(transport, translateLanguages) {
										
										var languageCodes = \$H(translateLanguages).keys();
										
										// TODO: ajax; and onsuccess, update the Languages/Messages in languageTabs.

										var data = transport.responseJSON;
										

										if (!data || !data.responseData || !data.responseStatus || data.responseStatus != 200)
											return;
										if (languageCodes.length == 1 && data.responseData.length < 1)
											return;

										var responses = data.responseData;
										
										

										
										for (var i = 0; i < languageCodes.length; i++) {
											var languageCode = languageCodes[i];

											var response = languageCodes.length > 1 ? responses[i] : responses;

											var translatedText;
											if (languageCodes.length > 1) {
												if (response.responseStatus != 200 || !response.responseData)
													continue;
												translatedText = response.responseData.translatedText;
											} else {
												translatedText = response.translatedText;
											}

											
											// TODO: escape HTML.
											this.translationDivs[languageCode].update(translatedText);

											// TODO: escape HTML.
											
											this.get_message_element(languageCode, 'text').update(translatedText);
											this.get_message_element(languageCode, 'textdiv').update(translatedText);
										}

										
										//if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
										//	return;
										//$(section+'textdiv').innerHTML = data.responseData.translatedText.escapeHTML();
										//$(section+'text').value = data.responseData.translatedText.escapeHTML();
										//setTranslationValue(section);
									}.bindAsEventListener(this, translateLanguages)
								});
							}
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
										return;
									retranslationDiv.update(data.responseData.translatedText.escapeHTML());
								}.bindAsEventListener(this, retranslationDiv)
							});
						},

						on_click_clear: function() {
							//if (confirm('Are you sure?')) {
								// TODO: Does this clear each selected language's translation?
							//}
						},
						on_toggle_language: function(event, languageCode) {
							// Does unchecking the checkbox clear this language's translation?
						}
					});

					var MessageGroupForm = Class.create({
						initialize: function(formName, destinationTabs, allowedDestinations, toolsAccordion) {
							this.formName = formName;
							this.destinationTabs = destinationTabs;
							this.allowedDestinations = allowedDestinations;
							this.toolsAccordion = toolsAccordion;
						},

						get_message_element: function(type, subtype, languageCode, suffix) {
							return $(this.formName + '_' + type + subtype + languageCode + suffix);
						},

						get_current_message_info: function(type, destination) {
							var info = {'type': type};

							var languageTabs = destination.languageTabs;
							info.languageCode = languageTabs ? languageTabs.currentSection : 'en';

							var subtypeTabs = destination.subtypeTabs[info.languageCode];
							
							if (subtypeTabs)
								info.subtype = subtypeTabs.currentSection;
							else if (type == 'phone')
								info.subtype = 'voice';
							else if (type == 'sms')
								info.subtype = 'plain';
							

							

							info.control = $(this.formName + '_' + info.type + info.subtype + info.languageCode);
							

							return info;
						},

						get_current_editor: function() {
							var type = this.destinationTabs.currentSection;
							if (type != 'phone' && type != 'email' && type != 'sms')
								return null;
							var destination =  this.allowedDestinations[type];

							var info = this.get_current_message_info(type, destination);

							// TODO: Depending on the state of info.control, set destination.currentEditor accordingly.
							destination.currentEditor = $(info.control.identify() + 'text');

							return destination.currentEditor;
						},

						refresh_accordion: function(type, verticalSection, subtypeSection) {
							this.toolsAccordion.enable_section('callMe');
							this.toolsAccordion.enable_section('audioLibrary');
							this.toolsAccordion.enable_section('dataField');
							this.toolsAccordion.enable_section('attachment');
							this.toolsAccordion.enable_section('translation');
							this.toolsAccordion.unlock_section('callMe');
							this.toolsAccordion.unlock_section('audioLibrary');

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

							var destination = this.allowedDestinations[type];
							var languageTabs = destination.languageTabs;
							if (languageTabs) {
								var languageCode = verticalSection || languageTabs.currentSection;
								

								languageTabs.sections[languageCode].contentDiv.down('.ForAccordion').insert(this.toolsAccordion.container);

								if (languageCode == 'en') {
									this.toolsAccordion.disable_section('translation');
								} else if (languageCode == 'autotranslate') {
									
								} else {
									var subtypeTabs = destination.subtypeTabs[languageCode];
									var subtype;
									if (subtypeTabs)
										subtype = subtypeSection || subtypeTabs.currentSection;
									else
										subtype = 'voice';
									if (destination.translationSettingDivs) {
										var settingDiv = destination.translationSettingDivs[subtype+languageCode];
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
						}
					});

					var destinationTabs = new Tabs($('destinationTabsContainer'), {'showDuration':0, 'hideDuration':0});
					var toolsAccordion = new Accordion('toolsAccordionContainer');
					toolsAccordion.container.setStyle({'paddingLeft':'10px'});

					var messageGroupForm = new MessageGroupForm (formName, destinationTabs, allowedDestinations, toolsAccordion);

					



					// Move type-specific form items.
					$('emailContainer').down('tbody').insert($('messagegroupform_subject_fieldarea')).insert($('messagegroupform_fromname_fieldarea')).insert($('messagegroupform_fromemail_fieldarea'));

					// Accordion.

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
					$('attachmentSection').down('tbody').insert($('messagegroupform_attachments_fieldarea'));
					$('messagegroupform_attachments_fieldarea').down('.formtableheader').hide();
					$('messagegroupform_attachments_fieldarea').down('.formtableicon').hide();

					toolsAccordion.update_section('callMe', {
						'title': 'Call Me to Record',
						'icon': 'img/icons/accept.gif',
						'content': $('callMeSection')
					});
					$('callMeSection').down('tbody.EasycallFormItem').insert($('messagegroupform_callme_fieldarea'));
					$('messagegroupform_callme_fieldarea').down('.formtableheader').hide();

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
					$('advancedSection').down('tbody').insert($('messagegroupform_autoexpire_fieldarea')).insert($('messagegroupform_preferredVoice_fieldarea'));

					// Vertical Tabs.
					for (var type in allowedDestinations) {
						var destination = allowedDestinations[type];
						var subtypes = destination['subtypes'];
						var languages = destination['languages'];
						var fieldareas = destination['fieldareas'];
						destination.translationSettingDivs = {};
						destination.subtypeTabs = {};
						destination.hasValidMessages = {};

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
								fieldarea.down('.formtableicon').hide();
								fieldarea.down('.formtableheader').hide();
								var formItem = $(messageGroupForm.formName + '_' + type + subtype + languageCode);
								formItem.observe('Form:ValidationDisplayed', function(event, type, subtype, languageCode) {
									
									
									
									
									
									
									
									
									
									
									
									var destination = this.allowedDestinations[type];
									var subtypes = destination.subtypes;
									var hasValidMessages = destination.hasValidMessages;
									var languageTabs = destination.languageTabs;

									// Store the updated status.
									var messageValid = (event.memo.style == 'valid') && (this.get_message_element(type, subtype, languageCode, 'text').value.strip() != '');
									hasValidMessages[subtype + languageCode] = messageValid;

									// If the status of any subtype for this language is specified==true.
									var languageHasValidMessage = messageValid;
									if (!messageValid) {
										for (var i = 0, count = subtypes.length; i < count; i++) {
											if (hasValidMessages[subtypes[i] + languageCode]) {
												languageHasValidMessage = true;
												break;
											}
										}
									}

									if (languageHasValidMessage) {
										if (languageTabs) {
											languageTabs.sections[languageCode].titleIcon.src = 'img/icons/accept.gif';
										}

										// TODO: Update the Phone,Email,SMS mainTab icons.

									} else {
										if (languageTabs) {
											languageTabs.sections[languageCode].titleIcon.src = 'img/icons/diagona/16/160.gif';
										}

										// TODO: Update the Phone,Email,SMS mainTab icons. If any language has a valid message.
									}

								}.bindAsEventListener(messageGroupForm, type, subtype, languageCode));

								var formtablecontrol = fieldarea.down('.formtablecontrol');
								var label = fieldarea.down('.formtableheader').down('label');
								formtablecontrol.insert({'top':label.addClassName('MessageContentHeader')});
								if (subtype == 'html')
									formtablecontrol.insert(new Element('div', {'class':'ForCK'}).update('forck'));

								var settingDiv = fieldarea.down('.TranslationSettingDiv');

								settingDiv.observe('MessageBody:TranslationSettingChanged', function(event, type, languageCode, subtype) {
									
									
									
									
									
									
									
									
									
									
									
									
									
									
									
									
									
									
									this.refresh_accordion(type, languageCode, subtype);
								}.bindAsEventListener(messageGroupForm, type, languageCode, subtype));

								destination.translationSettingDivs[subtype + languageCode] = settingDiv;

								tbody.insert(fieldarea);

								var content = new Element('table', {'style':'width:100%; border-collapse:collapse'}).insert(tbody);

								if (subtypeTabs) {
									
									subtypeTabs.update_section(subtype, {
										'title': subtype,
										'icon': 'img/icons/accept.gif',
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
										'icon': 'img/icons/accept.gif',
										'content': contentDiv
									});
								}

								// NOTE: AutoTranslate's constructor automatically attaches the new instance to messageGroupForm.autotranslates.
								var autotranslate = new AutoTranslate(contentDiv, messageGroupForm, type, subtype);
							}

							if (autotranslateSubtypeTabs) {
								autotranslateSubtypeTabs.show_section(subtypes[0]);
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
						'icon': 'img/icons/telephone.gif',
						'content': $('phoneContainer')
					});
					destinationTabs.update_section('email', {
						'title': 'Email',
						'icon': 'img/icons/email.gif',
						'content': $('emailContainer')
					});

					destinationTabs.update_section('sms', {
						'title': 'SMS',
						'icon': 'img/icons/box.gif',
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
							for (var type in this.allowedDestinations) {
								var destination = this.allowedDestinations[type];
								var hasValidMessages = destination.hasValidMessages;
								var languages = destination.languages;
								var subtypes = destination.subtypes;

								for (var languageCode in languages) {
									for (var i = 0, count = subtypes.length; i < count; i++) {
										var subtype = subtypes[i];

										
										var td = $('summary_' + type + subtype + languageCode);
										
										td.update();
										if (hasValidMessages[subtype + languageCode]) {
											td.update(new Element('img', {'src':'img/icons/accept.gif'}));
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

					destinationTabs.show_section('phone'); // TODO: Show the first, depending on allowMultilingual, allow phone, etc..
					messageGroupForm.refresh_accordion('phone'); // TODO: Show the first, depending on allowMultilingual, allow phone, etc..

					// Show remaining top-level form items.

					// Register event handlers.


					$('insertDataField').observe('click', function(event) {
						var currentEditor = this.get_current_editor();
						

						var select = $('dataFieldSelect');
						if (select.getValue()) {
							var defaultValue = $('dataFieldDefault').getValue().strip();
							var defaultString = defaultValue != '' ? ':' + defaultValue : '';

							textInsert('<<' + select.options[select.selectedIndex].text + defaultString + '>>', currentEditor);
							// todo: update ckeditor.
						}
					}.bindAsEventListener(messageGroupForm));

					$('insertAudio').observe('click', function(event) {
						var currentEditor = this.get_current_editor();
						

						var select = $('audioLibrarySelect');
						if (select.getValue()) {
							textInsert('{{' + select.options[select.selectedIndex].text + '}}', currentEditor);
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
								var currentEditor = this.get_current_editor();
								textInsert('{{' + audioFile.name + '}}', currentEditor);
							}.bindAsEventListener(this, audioFileID, audioFile));

							

							var tr = new Element('tr');
							tr.insert(new Element('td').insert(audioFile.name.escapeHTML()));
							tr.insert(new Element('td').insert(playButton));
							tr.insert(new Element('td').insert(insertButton));

							

							

							$('callMeSection').down('tbody.AudioFiles').insert(tr);

							

							// TODO: Insert the audio file name into the current message body.
						}.bindAsEventListener(this), event.memo.Default);
					}.bindAsEventListener(messageGroupForm));

					$('renderedFormItems').show();
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
