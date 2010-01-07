<?

// Translation widget
class TranslationItem extends FormItem {
	function render ($value) {
		static $renderscript = true;

		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";

		$usehtmleditor = !empty($this->args['usehtmleditor']) ? 'true' : 'false';
		
		$msgdata = json_decode($value);

		$language = $this->args['language'];
		$gender = isset($msgdata->gender)?$msgdata->gender:"female";

		$isphone = !empty($this->args['phone']);

		$translationcheckboxlabel = isset($this->args['translationcheckboxlabel']) ? $this->args['translationcheckboxlabel'] : _L("Translate");
		
		$str = "";

		$str .= '
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="' . escapehtml($value) . '"/>
			<input id="'.$n.'englishText" name="'.$n.'englishText" type="hidden" value="' . escapehtml($this->args["englishText"]) . '"/>
			<input id="'.$n.'overridesave" type="hidden" value=""></div>
			' . (!empty($this->args['showhr']) ? '<hr>' : '') . '
			<table width="100%">
				<tr>
					<td '.(!empty($this->args['translationcheckboxnewline']) ? 'colspan=2' : '').' valign="top" width="80px" class="TranslationItemCheckboxTD" style="'.(!empty($this->args['hidetranslationcheckbox']) ? 'display:none' : '').'">
						<input id="'.$n.'translatecheck" name="'.$n.'checkbox" type="checkbox" onclick="toggleTranslation(\''.$n.'\',\''.$language.'\', '.$usehtmleditor.');" '.(($msgdata->enabled)?"checked":"").' />
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
							<fieldset>
								<div style="width: 100%;">
									<div id="'.$n.'textdiv" name="'.$n.'textdiv" style="display: '.((!$msgdata->override)?"block":"none").'; width: 99%; height: 50px; border: 1px solid gray; color: gray; overflow:auto">'.escapehtml($msgdata->text).'</div>
									<textarea id="'.$n.'text" name="'.$n.'text" style="display: '.(($msgdata->override)?"block":"none").'; width: 99%;" rows="3" onChange="setTranslationValue(\''.$n.'\');" />'.escapehtml($msgdata->text).'</textarea>
									<br>
								</div>
							</fieldset>
							<div id="'. $n .'retranslationcontrols" style="width: 100%">
								'. icon_button(_L("Show in English"), "fugue/magnifier", "toEnglishButton('$n','$language')", null, 'id="'. $n .'showenglish"').'
								'. icon_button(_L("Hide English"), "fugue/magnifier__minus", "toEnglishButton('$n','$language')", null, 'id="'. $n .'hideenglish" style="display:none"').'
								<span style="'.(empty($this->args['allowoverride']) ? 'display:none' : '').'"><input id="'.$n.'override" name="'.$n.'checkbox" type="checkbox" '.(($msgdata->override)?"checked":"").' onclick="overrideTranslation(\''.$n.'\',\''.$language.'\', '.$usehtmleditor.');"/>' . _L('Override Translation') . '</span>

								<div id="'.$n.'retranslation" style="width: 100%; display: none;margin-top: 15px; clear:both">
									'. icon_button(_L('Refresh %1$s to English Translation', $language),"fugue/arrow_circle_double_135","submitRetranslation('$n','$language')", null, 'style="margin-bottom: 12px"') . '
									<div id="'.$n.'retranslationtext" name="'.$n.'retranslation" style="width: 100%; width: 99%; height: 50px; border: 1px solid gray; color: gray; overflow:auto; clear:both"></div>
								</div>
							</div>
						</div>
					</td>
					<td valign="top" width="100px" style="'.(!$isphone ? 'display:none' : '').'">
						<div id="'.$n.'controls" style="display: '.(($msgdata->enabled)?"block":"none").'">
							'.($isphone?icon_button(_L("Play"),"fugue/control","var content = $('" . $n . "text').getValue(); if(content != '') popup('previewmessage.php?parentfield=".$n."text&language=$language&gender=$gender" . "', 400, 400,'preview');"):"").'
						</div>
					</td>
				</tr>
			</table>';

		if (isset($this->args['fields'])) {
			// Data Fields.
			$str .= '
				<table border="0" cellpadding="1" cellspacing="0" style="display:none; font-size: 9px; margin-top: 5px;" class="DataFieldsTable" id="'.$n.'datafieldstable">
					<tr>
						<td>
							<span style="font-size: 9px;">Default&nbsp;Value:</span><br />
							<input id="'.$n.'datadefault" name="'.$n.'datavalue" type="text" size="10" value=""/>
						</td>
						<td>
							<span style="font-size: 9px;">Data&nbsp;Field:</span><br />
							<select id="'.$n.'datafield" name="'.$n.'language">
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
							'. icon_button(_L("Insert"),"fugue/arrow_turn_180","
										sel = $('" . $n . "datafield');
										if (sel.options[sel.selectedIndex].value != '') {
											 def = $('" . $n . "datadefault').value;
											 textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('{$n}text'));
										}")
							. '					
						</td>
					</tr>
				</table>';
		}
			
			
		if($renderscript || isset($this->args['reload'])) {
			$renderscript = false;
		}
		return $str;
	}
	
	function renderJavascriptLibraries() {
		$str = '
			<script type="text/javascript">
				function toEnglishButton(section,language) {
					if ($(section+"showenglish").visible()) {
						$(section+"retranslation").show();
						$(section+"showenglish").hide();
						$(section+"hideenglish").show();
						submitRetranslation(section,language);
					} else {
						$(section+"retranslation").hide();
						$(section+"hideenglish").hide();
						$(section+"showenglish").show();
					}
				}
				function submitRetranslation(section,language) {
					var srcbox = section + "text";
					
					var text = $(srcbox).getValue();
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
									$(dstbox).innerHTML = data.responseData.translatedText.replace(/<</, "&lt;&lt;").replace(/>>/, "&gt;&gt;");//.escapeHTML();
							}
					});
					return false;
				}
				function getTranslation(section, language) {
					var englishText = $(section+"englishText");
					var langtext = $(section + "text");
					var overridesave = $(section + "overridesave");
					if (!englishText.value) {
						langtext.value = overridesave.value;
						return;
					}
					$(section + "textdiv").innerHTML = "<img src=\"img/ajax-loader.gif\" />";
					new Ajax.Request("translate.php", {
						method:"post",
						parameters: {"english": makeTranslatableString(englishText.value), "languages": language},
						onSuccess: function(transport) {
							var data = transport.responseJSON;
							if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
								return;
							$(section+"textdiv").innerHTML = data.responseData.translatedText.replace(/<</, "&lt;&lt;").replace(/>>/, "&gt;&gt;");//.escapeHTML();
							$(section+"text").value = data.responseData.translatedText;//.escapeHTML();
							englishText.value = "";
							setTranslationValue(section);
						}
					});
				}
				function setTranslationValue(section) {
					var curVal = $(section).value.evalJSON();
					$(section).value = Object.toJSON({
						"enabled": $(section + "translatecheck").checked,
						"text": (($(section + "translatecheck").checked)?$(section + "text").value.toString():""),
						"override": (($(section + "translatecheck").checked)?$(section + "override").checked:false),
						"gender": curVal.gender
					});
					form_do_validation($(section).up("form"), $(section));
				}

				function overrideTranslation(section,language, usehtmleditor, nowarning) {
					var langtext = $(section + "text");
					var overridesave = $(section + "overridesave");
					
					saveHtmlEditorContent();
					
					if ($(section+"override").checked) {
						$(section).fire("TranslationItem:OverrideToggled", {"override": $(section+"override").checked});
						
						if (!overridesave.value)
							overridesave.value = langtext.value;
						langtext.show();
						$(section + "editlock").show();
						$(section + "textdiv").hide();
						
						if (usehtmleditor)
							applyHtmlEditor(langtext);
					} else {
						if(!nowarning && langtext.value != overridesave.value && !confirm(\'' . _L('The edited text will be removed and set back to the previous translation.') . '\')) {
							$(section + "override").checked = true;
							return;
						}
						
						$(section).fire("TranslationItem:OverrideToggled", {"override": $(section+"override").checked});
						
						getTranslation(section,language);
						overridesave.value = "";
						langtext.hide();
						$(section + "editlock").hide();
						$(section + "textdiv").show();
					}
					setTranslationValue(section);
				}
				
				function toggleTranslation(section,language, usehtmleditor) {
					$(section).fire("TranslationItem:TranslationToggled", {"enabled": $(section+"translatecheck").checked});
					
					if ($(section+"translatecheck").checked) {
						if ($(section+"text").value == "")
							getTranslation(section, language);
						$(section +"icons").show();
						$(section +"textfields").show();
						$(section +"disableinfo").hide();
						$(section +"controls").show();
						
						if ($(section+"override").checked && usehtmleditor)
							applyHtmlEditor(section+"text");
					} else {
						$(section +"icons").hide();
						$(section +"textfields").hide();
						$(section +"disableinfo").show();
						$(section +"controls").hide();
					}
					setTranslationValue(section);
					
				}
			</script>';
			return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		$usehtmleditor = !empty($this->args['usehtmleditor']) ? 'true' : 'false';
		
		$str = "
			(function() {
				if ($usehtmleditor) {
					var textarea = $('{$n}text');
					if (textarea.visible())
						applyHtmlEditor(textarea);
				}
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
