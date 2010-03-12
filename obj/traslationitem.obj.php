<?

// Translation widget
class TranslationItem extends FormItem {
	function render ($value) {
		static $renderscript = true;

		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";

		$usehtmleditor = !empty($this->args['usehtmleditor']) ? 'true' : 'false';
		if ($usehtmleditor === 'true') {
			$escapehtml = 'false';
		} else if (isset($this->args['escapehtml'])) {
			$escapehtml = $this->args['escapehtml'] ? 'true' : 'false';
		} else {
			$escapehtml = 'true';
		}
		
		$language = $this->args['language'];
		$gender = isset($msgdata->gender)?$msgdata->gender:"female";
		
		if (trim($value) == "") {
			$msgdata = (object)array(
				"enabled" => true,
				"text" => isset($this->args['plaintextmessage']) ? $this->args['plaintextmessage'] : "",
				"englishText" => "",
				"override" => false,
				"gender" => $gender,
				"language" => $language
			);
		} else {
			$msgdata = json_decode($value);
		}

		if (isset($this->args['transienttext']))
			$msgdata->text = $this->args['transienttext'];
		
		if ($usehtmleditor === 'true') {
			$msgdata->text = str_replace('<<', '&lt;&lt;', $msgdata->text);
			$msgdata->text = str_replace('>>', '&gt;&gt;', $msgdata->text);
		}
		
		$isphone = !empty($this->args['phone']);

		$translationcheckboxlabel = isset($this->args['translationcheckboxlabel']) ? $this->args['translationcheckboxlabel'] : _L("Translate");
		
		if (isset($this->args['allowoverride']))
			$allowoverride = $this->args['allowoverride'];
		else
			$allowoverride = true;
			
		if (isset($this->args['editwhendisabled']))
			$editwhendisabled = $this->args['editwhendisabled'];
		else
			$editwhendisabled = false;
		
		if (isset($this->args['preferredgenderformitem']))
			$preferredgenderformitem = $this->args['preferredgenderformitem'];
		else
			$preferredgenderformitem = $this->form->name . "_voice";
		
		$str = "";
		
		$hidden = false;
		if (isset($this->args['overrideplaintext'])) {
			$str .= '
				<div id="'.$n.'plaintextpreview" style="display:'.($this->args['overrideplaintext'] ? 'none' : 'block').';">
					<pre style="color:gray; border: solid 1px gray;">' .
					(isset($this->args['plaintextmessage']) && $this->args['plaintextmessage'] != '' ?
						escapehtml($this->args['plaintextmessage']) :
						"<em>" . escapehtml(_L("A plain-text message will be generated from the HTML message.")) . "</em>"
					) .
					'</pre>
				</div>
			';
			
			if (!$this->args['overrideplaintext'])
				$hidden = true;
		}
		
		$str .= '
			<div class="TranslationItemContainer" style="'.($hidden ? 'display:none' : '').'">
			
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="' . escapehtml($value) . '"/>
			<input id="'.$n.'state" type="hidden" value="' . escapehtml(json_encode($msgdata)) . '"/>
			<input id="'.$n.'overridesave" type="hidden" value=""/>
			' . (!empty($this->args['showhr']) ? '<hr>' : '') . '
			
			<div style="'.(!empty($this->args['editenglishtext']) ? '' : 'display:none').'">
				<div class="MessageBodyContainer" style="'.((!$msgdata->enabled || $msgdata->override) ? 'display:none' : '').'">
					<textarea '. (!empty($this->args['editenglishtext']) ? (' onChange="setTranslationValue(\''.$n.'\');" style="display:block; width:99%" ') : ' style="display:none" ') . '  rows="10" class="SourceTextarea" id="'.$n.'englishText">' . escapehtml($msgdata->englishText) . '</textarea>
					' . icon_button(_L("Refresh Translation"),"fugue/arrow_circle_double_135", "getTranslation('$n','$language',$usehtmleditor, $escapehtml);", null, 'id="refreshtranslationbutton"') . '
					<div style="margin-top:20px;clear:both"></div>
				</div>
			</div>
			
			<table width="100%">
				<tr>
					<td '.(!empty($this->args['translationcheckboxnewline']) ? 'colspan=2' : '').' valign="top" width="80px" class="TranslationItemCheckboxTD" style="'.(!empty($this->args['hidetranslationcheckbox']) ? 'display:none' : '').'">
						<input id="'.$n.'translatecheck" name="'.$n.'checkbox" type="checkbox" onclick="toggleTranslation(\''.$n.'\',\''.$language.'\', '.$usehtmleditor.', '.$escapehtml.');" '.(($msgdata->enabled)?"checked":"").' />
						<label for="'.$n.'translatecheck"><b>'.$translationcheckboxlabel.'</b></label>
					</td>
					
				'.(!empty($this->args['translationcheckboxnewline']) ? '<tr>' : '').'
				
					<td valign="top" align="right" width="18px" style="'.(!empty($this->args['hidetranslationlock']) ? 'display:none' : '').'">
						<div id="'.$n.'icons" style="display: '.(($msgdata->enabled)?"block":"none").'">
							<img id="'.$n.'editlock" style="display: '.(($msgdata->override)?"block":"none").';" src="img/padlock.gif">
						</div>
					</td>
					<td valign="top" width="70%">
						<div id="'.$n.'disableinfo" style="display: '.(($msgdata->enabled)?"none":"block").'; width: 100%;">
							'  . (isset($this->args['disabledinfo']) ? $this->args['disabledinfo'] : ('<ul><li> ' . _L('%1$s recipients will now receive the default English message.',ucfirst($language)) . '</li></ul>')) . '
						</div>
						<div id="'.$n.'textfields" style="width: 100%; display: '.(($msgdata->enabled)?"block":"none").'">
							<div id="'.$n.'textdiv" name="'.$n.'textdiv" style="display: '.((!$msgdata->override)?"block":"none").'; width: 99%; '.(!empty($this->args['usehtmleditor']) ? "" : "height: 50px;").' border: 1px solid gray; color: gray; overflow:auto">' .
								((isset($this->args['usehtmleditor']) && $this->args['usehtmleditor']) || (isset($this->args['escapehtml']) && !$this->args['escapehtml']) ?
									$msgdata->text :
									escapehtml($msgdata->text)) .
							'</div>
						</div>
						
						<div class="MessageBodyContainer" style="display: '.(($msgdata->override || !$msgdata->enabled)?"block":"none").'">
							<div style="'.(!$allowoverride ? 'display:none' : '').'">
								<textarea class="MessageTextarea '.($editwhendisabled ? 'EditWhenDisabled' : '').'" id="'.$n.'text" name="'.$n.'text" style="width: 99%;" rows="10" onChange="setTranslationValue(\''.$n.'\');">'.escapehtml($msgdata->text).'</textarea>
							</div>
						</div>
						
						<div id="'. $n .'retranslationcontrols" style="width: 100%; display: '.(($msgdata->enabled)?"block":"none").'">
							
							'. icon_button(_L("Show in English"), "fugue/magnifier", "toEnglishButton('$n','$language',$usehtmleditor, $escapehtml)", null, 'id="'. $n .'showenglish"').'
							'. icon_button(_L("Hide English"), "fugue/magnifier__minus", "toEnglishButton('$n','$language',$usehtmleditor, $escapehtml)", null, 'id="'. $n .'hideenglish" style="display:none"').'
							<span style="'.(!$allowoverride ? 'display:none' : '').'"><input id="'.$n.'override" name="'.$n.'checkbox" type="checkbox" '.(($msgdata->override)?"checked":"").' onclick="overrideTranslation(\''.$n.'\',\''.$language.'\', '.$usehtmleditor.', '.$escapehtml.');"/>' . _L('Override Translation') . '</span>

							<div id="'.$n.'retranslation" style="width: 100%; display: none;margin-top: 15px; clear:both">
								'. icon_button(_L('Refresh %s to English Translation', Language::getName($language)),"fugue/arrow_circle_double_135","submitRetranslation('$n','$language', $usehtmleditor, $escapehtml)", null, 'style="margin-bottom: 12px"') . '
								<div id="'.$n.'retranslationtext" name="'.$n.'retranslation" style="width: 100%; width: 99%; '.(!empty($this->args['usehtmleditor']) ? "" : "height: 50px;").' border: 1px solid gray; color: gray; overflow:auto; clear:both"></div>
							</div>
						</div>
					</td>
					<td valign="top" width="100px" style="'.(!$isphone ? 'display:none' : '').'">
						<div id="'.$n.'controls" style="display: '.(($msgdata->enabled || $editwhendisabled)?"block":"none").'">
							'.($isphone ? icon_button(_L("Play"),"fugue/control","
									var content = $('" . $n . "text').getValue();
									if (content != '') {
										var gender = 'female'; // Default female.
										var preferredgenderdiv = $('$preferredgenderformitem');
										if (preferredgenderdiv) {
											var selectedradio = preferredgenderdiv.down('input:checked');
											if (selectedradio) {
												var value = selectedradio.getValue();
												if (value == 'male' || value == 'Male')
													gender = 'male';
											}
										}
																
										popup('previewmessage.php?parentfield={$n}text&language=$language&gender='+gender, 400, 400,'preview');
									}
								")
								: ""
							).'
						</div>
					</td>
				</tr>
			</table>';

		if (!empty($this->args['fields'])) {
			// Data Fields.
			$str .= '
				<table border="0" cellpadding="1" cellspacing="0" style="display:block; font-size: 9px; margin-top: 5px;" class="DataFieldsTable" id="'.$n.'datafieldstable">
					<tr>
						<td>
							<span style="font-size: 9px;">Default&nbsp;Value:</span><br />
							<input class="DataFieldDefaultValue" type="text" size="10" value=""/>
						</td>
						<td>
							<span style="font-size: 9px;">Data&nbsp;Field:</span><br />
							<select class="DataFieldSelect">
								<option value="">-- Select a Field --</option>';								
			foreach($this->args['fields'] as $field)
			{
				$str .= '		<option value="' . $field . '">' . $field . '</option>';
			}
			$str .=	'		</select>
						</td>
					</tr>
					<tr>
						<td colspan=2>
							'. icon_button(_L("Insert"),"fugue/arrow_turn_180")
							. '
						</td>
					</tr>
				</table>';
		}
			
		
		$str .= '</div>';
		
		if($renderscript || isset($this->args['reload'])) {
			$renderscript = false;
		}
		
		
		return $str;
	}
	
	function renderJavascriptLibraries() {
		
		$str = '
			<script type="text/javascript">
				function toEnglishButton(section,language,usehtmleditor, escapehtml) {
					if (usehtmleditor)
						saveHtmlEditorContent();
					
					if ($(section+"showenglish").visible()) {
						$(section+"retranslation").show();
						$(section+"showenglish").hide();
						$(section+"hideenglish").show();
						submitRetranslation(section,language,usehtmleditor, escapehtml);
					} else {
						$(section+"retranslation").hide();
						$(section+"hideenglish").hide();
						$(section+"showenglish").show();
					}
				}
				function submitRetranslation(section,language,usehtmleditor, escapehtml) {
					if (usehtmleditor)
						saveHtmlEditorContent();
					
					var srcbox = section + "text";
					
					var text = $(srcbox).getValue().strip();
					if(text == "")
						return;
					if(text != text.substring(0, 2000)){
						text = text.substring(0, 2000);
						alert("' . _L('The message is too long. Only the first 2000 characters are submitted for translation.') . '");
					}
					$(section + "retranslationtext").innerHTML = "<img src=\"img/ajax-loader.gif\" />";
					new Ajax.Request("translate.php", {
						method:"post",
						parameters: {"text": makeTranslatableString(text), "language": language},
						onSuccess: function(result) {
									var data = result.responseJSON;
									if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
										return;
									var dstbox = section + "retranslationtext";
									var translatedtext = data.responseData.translatedText;
									if (escapehtml)
										translatedtext = translatedtext.escapeHTML();
									else
										translatedtext = translatedtext.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
										
									$(dstbox).innerHTML = translatedtext;
							}
					});
					return false;
				}
				function getTranslation(section, language, usehtmleditor, escapehtml) {
					if (usehtmleditor)
						saveHtmlEditorContent();
					var englishText = $(section+"englishText");
					var langtext = $(section + "text");
					var overridesave = $(section + "overridesave");
					if (!englishText.value.strip()) {
						langtext.value = overridesave.value;
						return;
					}
					$(section + "textdiv").innerHTML = "<img src=\"img/ajax-loader.gif\" />";
					new Ajax.Request("translate.php", {
						method:"post",
						parameters: {"english": makeTranslatableString(englishText.value.strip()), "languages": language},
						onSuccess: function(transport) {
							var data = transport.responseJSON;
							if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
								return;
								
							var translatedtext = data.responseData.translatedText;
							if (escapehtml)
								translatedtext = translatedtext.escapeHTML();
							else
								translatedtext = translatedtext.replace(/<</g, "&lt;&lt;").replace(/>>/g, "&gt;&gt;");
							
							$(section+"textdiv").innerHTML = translatedtext;
							$(section+"text").value = data.responseData.translatedText;
							setTranslationValue(section);
						}
					});
				}
				// Returns the revised state object.
				function setTranslationValue(section) {
					var state = {};
					var formitemelement = $(section);
					if (formitemelement.value.strip() != "") {
						state = formitemelement.value.evalJSON();
					}
					
					var mainTextarea = $(section+"text");
					
					// NOTE: Edit the existing value instead of overwriting it completely because there may be some properties that we want to keep, like state.language.
					state.enabled = $(section + "translatecheck").checked;
					state.englishText = $(section + "englishText").value;
					state.override = (($(section + "translatecheck").checked)?$(section + "override").checked:false);
					state.text = mainTextarea.value;
					
					var statejson = Object.toJSON(state);
					
					if ((mainTextarea.hasClassName("EditWhenDisabled") &&
							state.enabled &&
							!state.override &&
							state.englishText.strip() == "") ||
						((!state.enabled || state.override) &&
							state.text.strip() == "")
					) {
						formitemelement.value = "";
					} else {
						formitemelement.value = statejson;
					}
					
					// Keep the json value in $(section + "state") so that we can keep track of override/enabled states in case formitemelement.value is blank.
					$(section + "state").value = statejson;
					
					form_do_validation(formitemelement.up("form"), formitemelement);
					
					return state;
				}
				
				function overrideTranslation(section,language, usehtmleditor, escapehtml, nowarning) {
					var langtext = $(section + "text");
					var overridesave = $(section + "overridesave");
					
					if (usehtmleditor)
						saveHtmlEditorContent();
					
					if ($(section+"override").checked) {
						$(section).fire("TranslationItem:OverrideToggled", {"override": $(section+"override").checked});
						
						if (!overridesave.value)
							overridesave.value = langtext.value;
						langtext.up(".MessageBodyContainer").show();
						$(section + "editlock").show();
						$(section + "textdiv").hide();
						
						if (usehtmleditor)
							applyHtmlEditor(langtext);
							
						$(section + "englishText").up(".MessageBodyContainer").hide();
					} else {
						if(!nowarning && langtext.value != overridesave.value && !confirm(\'' . _L('The edited text will be removed and set back to the previous translation.') . '\')) {
							$(section + "override").checked = true;
							return;
						}
						
						$(section).fire("TranslationItem:OverrideToggled", {"override": $(section+"override").checked});
						
						$(section + "englishText").up(".MessageBodyContainer").show();
						
						getTranslation(section,language,usehtmleditor, escapehtml);
						overridesave.value = "";
						langtext.up(".MessageBodyContainer").hide();
						$(section + "editlock").hide();
						$(section + "textdiv").show();
						if (usehtmleditor)
							applyHtmlEditor($(section+"englishText"));
					}
					setTranslationValue(section);
				}
				
				function toggleTranslation(section,language, usehtmleditor, escapehtml) {
					if (usehtmleditor)
						saveHtmlEditorContent();
					
					$(section).fire("TranslationItem:TranslationToggled", {"enabled": $(section+"translatecheck").checked});
					
					if ($(section+"translatecheck").checked) {
						if ($(section+"text").value == "")
							getTranslation(section, language,usehtmleditor, escapehtml);
						$(section +"icons").show();
						$(section +"textfields").show();
						$(section +"retranslationcontrols").show();
						$(section +"disableinfo").hide();
						$(section +"controls").show();
						
						if ($(section+"override").checked) {
							if (usehtmleditor)
								applyHtmlEditor(section+"text");
							else
								$(section+"text").up(".MessageBodyContainer").show();
						} else {
							$(section+"englishText").up(".MessageBodyContainer").show();
							$(section+"text").up(".MessageBodyContainer").hide();
							if (usehtmleditor)
								applyHtmlEditor($(section+"englishText"));
						}
					} else {
						$(section +"icons").hide();
						$(section +"textfields").hide();
						$(section +"retranslationcontrols").hide();
						$(section +"disableinfo").show();
						$(section+"englishText").up(".MessageBodyContainer").hide();
						
						var mainTextarea = $(section+"text");
						if (mainTextarea.hasClassName("EditWhenDisabled")) {
							mainTextarea.up(".MessageBodyContainer").show();
						} else {
							$(section +"controls").hide();
							mainTextarea.up(".MessageBodyContainer").hide();
						}
						
						if (usehtmleditor)
							applyHtmlEditor($(section+"text"));
					}
					setTranslationValue(section);
				}
				
			</script>';
			return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;

		$str = "
			(function() {
				var formitemelement = $('$n');
				var stateelement = $('{$n}state');
				var formitemcontainer = formitemelement.up('.TranslationItemContainer');
				var curVal = stateelement.value.evalJSON();
				var textarea = ((curVal.enabled && !curVal.override)) ? formitemcontainer.down('.SourceTextarea') : formitemcontainer.down('.MessageTextarea');
		";
		
		if (isset($this->args['usehtmleditor']) && $this->args['usehtmleditor']) {
			$str .= "
				applyHtmlEditor(textarea);
				
				formitemcontainer.observe('HtmlEditor:SavedContent', function(event) {
					setTranslationValue(this.identify());
				}.bindAsEventListener(formitemelement));
			";
		}
		
		if (isset($this->args['fields']) && $this->args['fields']) {
			$str .= "
				var datafieldstable = $('{$n}datafieldstable');
				var datafieldinsertbutton = datafieldstable.down('button');
				datafieldinsertbutton.stopObserving('click');
				datafieldinsertbutton.observe('click', function(event) {
					var curVal = stateelement.value.evalJSON();
					var textarea = (curVal.enabled && !curVal.override) ? formitemcontainer.down('.SourceTextarea') : formitemcontainer.down('.MessageTextarea');
					
					var sel = datafieldstable.down('.DataFieldSelect');
					if (sel.options[sel.selectedIndex].value != '') {
						 def = datafieldstable.down('.DataFieldDefaultValue').value;
						 textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', textarea);
						 setTranslationValue(formitemelement.identify());
					}
				}.bindAsEventListener(datafieldinsertbutton));
			";
		}
		
		if (isset($this->args['overrideplaintext'])) {
			$str .= "
					var form = $('{$this->form->name}');
					var plaintextpreview = $('{$n}plaintextpreview');
					form.observe('PlainEmailCheckbox:OverridePlainText', function(event) {
						if (event.memo.override) {
							formitemcontainer.show();
							plaintextpreview.hide();
							if (formitemelement.value.strip() == '') {
								formitemelement.value = '';
							}
						} else {
							formitemcontainer.hide();
							plaintextpreview.show();
						}
					});
			";
		}
		
		$str .= "
				// Set the textareas' selection for use with textInsert().
				var setselection = function () {
					if(document.selection)
						this.sel = document.selection.createRange();
				};
				var sourceTextarea = $('{$n}englishText');
				var mainTextarea = $('{$n}text');
				sourceTextarea.observe('keyup',setselection.bindAsEventListener(textarea));
				sourceTextarea.observe('mouseup',setselection.bindAsEventListener(textarea));
				mainTextarea.observe('keyup',setselection.bindAsEventListener(textarea));
				mainTextarea.observe('mouseup',setselection.bindAsEventListener(textarea));
			})();
		";
		
		return $str;
	}
}

class ValTranslation extends Validator {
	function validate ($value, $args) {
		if(!is_array($value)) {
			$value = json_decode($value,true);
		}
		if (!isset($value["enabled"]))
			return _L('Validation error. Please check or uncheck ') . $this->label;
		if($value["enabled"] == true && (!isset($value["text"]) || trim($value["text"] == ""))) {
			return $this->label . " " . _L('message can not be empty if translation checkbox is checked');
		}else
			return true;

	}
	function getJSValidator () {
		return
			'function (name, label, value, args) {
				if (value.strip() == "")
					return label + " " + "' . _L('message can not be empty if translation checkbox is checked') . '";
				checkval = value.evalJSON();
				if (checkval.enabled == true && (checkval.text == undefined || checkval.text.strip() == ""))
					return label + " " + "' . _L('message can not be empty if translation checkbox is checked') . '";
				return true;
			}';
	}
}


?>
