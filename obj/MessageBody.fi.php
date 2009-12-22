<?

class MessageBody extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;	
	
		$str = '
			<div>
			<table>
				<tr>
					<td valign="top" rowspan="5">
						<textarea id="'.$n.'" name="'.$n.'" rows="12" cols="50" />'.escapehtml($value).'</textarea>	';
	if(!isset($this->args['playbutton']) || $this->args['playbutton'] === true) {
		$str .= 		'<div>' . icon_button(_L("Play"),"fugue/control","var content = $('" . $n . "').getValue();
																	if(content.length > 4000) {
																		alert('The preview will only render audio from the first 4000 characters.');
																		content = content.substr(0,4000);
																	}
																	var language = $('" . $this->form->name . "_language').getValue();
																	var voice = 'Female';
																	if($('" . $this->form->name . "_voice-2').checked) {
																		voice = 'Male';
																	}
																	if(content != '')
																		popup('previewmessage.php?parentfield=" . $n . "&language=' + encodeURIComponent(language) + '&gender=' + encodeURIComponent(voice), 400, 400,'preview');")
						. '</div>';
	}
	
	$str .= '	</td>
				</tr>';
				
if(isset($this->args['audiofiles'])) {			
	$str .=		'<tr>	
					<td valign="top" colspan="2">
						<b>Insert Audio Recording:</b>	
					</td>
				</tr>
				<tr>
					<td valign="top" class="bottomBorder">
						<select id="'.$n.'recording" name="'.$n.'recording">
							<option value="">-- Select an Audio File --</option>';				
		foreach($this->args['audiofiles'] as $audiofile) {
			$str .= '							<option value="' . $audiofile->id . '">' . escapehtml($audiofile->name) . '</option>';
		}					
		$str .= '		</select>'
						. icon_button(_L("Insert"),"fugue/arrow_turn_180","
									var sel = $('" . $n . "recording');							
									if (sel.options[sel.selectedIndex].value > 0) { 
										textInsert('{{' + sel.options[sel.selectedIndex].text + '}}', $('$n'));}") 
						. icon_button(_L("Play"),"fugue/control","
									var sel = $('" . $n . "recording');	
									if(sel.selectedIndex >= 1) { 
										popup('previewaudio.php?close=1&id=' + sel.options[sel.selectedIndex].value, 400, 400);}")						
						. '
					</td>	

				</tr>';
						
}						
	$str .=		'<tr>
					<td valign="top">
						<b>Insert Data Field:</b> 	
					</td>
				</tr>
				<tr>					
					<td valign="top">
						<table border="0" cellpadding="1" cellspacing="0" style="font-size: 9px; margin-top: 5px;">
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
			$str .= '							<option value="' . $field . '">' . $field . '</option>';
		}
		$str .=	'						</select>
								</td>
							</tr>
						</table>	
									'. icon_button(_L("Insert"),"fugue/arrow_turn_180","
												sel = $('" . $n . "datafield');
												if (sel.options[sel.selectedIndex].value != '') { 
													 def = $('" . $n . "datadefault').value; textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n'));
												}") 
									. '								
				</td>
				</tr>
			</table>
		</div>
			<script type="text/javascript">
				function setselection() {
					if(document.selection) this.sel = document.selection.createRange();
				}
				$("' . $n . '").focus();
				$("' . $n . '").observe("keyup",setselection);
				$("' . $n . '").observe("mouseup",setselection);
				
			</script>
		';
		return $str;
	}
}












// TODO: Rename this file to MessageBody.fi.php, replacing the old implementation.

// Todo: set a default language variable instead of checking against 'english' or 'en'

// Translation widget
// $args['subtype']
class MessageBody2 extends FormItem {
	function render ($value) {
		static $renderscript = true;

		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";

		$msgdata = json_decode($value);

		$languageName = $this->args['language'];

		if ($languageName == 'english' || empty($this->args['multilingual'])) {
			$multilingual = false;
		} else {
			$multilingual = true;
		}

		$gender = isset($msgdata->gender)?$msgdata->gender:"female";

		$isphone = !empty($this->args['phone']);

		$cssShowIfMultilingual = "; display:" . ($multilingual ? "block" : "none") . "; ";
		
		$divHtmlEditorContainerIfNeeded = $this->args['subtype'] == 'html' ? '<div class="HtmlEditorContainer"></div>' : '';

		$str = "";

		$str .= '
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="' . escapehtml($value) . '"/>
			<input id="'.$n.'overridesave" type="hidden" value=""></div>

			<div class="TranslationSettingDiv" style="'.$cssShowIfMultilingual.'">
				<input id="'.$n.'translatecheck" class="EnableTranslationCheckbox" name="'.$n.'checkbox" type="checkbox" onclick="toggleTranslation(\''.$n.'\',\''.$languageName.'\');" '.(($msgdata->enabled && $multilingual)?"checked":"").' />
				<b>'._L('Enable Translation').'</b>
			</div>

			<div id="'.$n.'controls" style="">
				<div style="float:left">'.($isphone?icon_button(_L("Play"),"fugue/control","var content = $('" . $n . "text').getValue(); if(content != '') popup('previewmessage.php?parentfield=".$n."text&language=$languageName&gender=$gender" . "', 400, 400,'preview');"):"").'</div>
				<div style="float:right">'. icon_button(_L("Clear"), "fugue/control") .'</div>
				<div style="clear:both"></div>
			</div>

			<div id="'.$n.'textfields" style="padding-right:3px; padding-left: 3px;">
				
				<div id="'.$n.'sourceTextContainer">
					<div class="Translation">
						<textarea id="'.$n.'sourceText" name="'.$n.'sourceText" style="width:98%; '.$cssShowIfMultilingual.'">' . escapehtml($this->args["sourcetext"]) . '</textarea>
						'.$divHtmlEditorContainerIfNeeded.'

						<div style="margin-top: 15px; '.$cssShowIfMultilingual.'">
							<center>' . icon_button(_L("Refresh Translation"), "fugue/magnifier", "getTranslation('$n','$languageName')", null, 'style="float:none;" id="'. $n .'refreshTranslationButton"') . '</center>
							<div style="clear:both"></div>
						</div>
					</div>

					<div class="Translation">
						<div id="'.$n.'textdiv" name="'.$n.'textdiv" style="display: '.((!$msgdata->override && $multilingual)?"block":"none").'; height: 50px; border: 1px solid gray; color: gray; overflow:auto">'.escapehtml($msgdata->text).'</div>
					</div>
				</div>

				<textarea id="'.$n.'text" name="'.$n.'text" style="width:98%;  display: '.(($msgdata->override || !$multilingual)?"block":"none").'; " rows="3" onChange="setTranslationValue(\''.$n.'\');" />'.escapehtml($msgdata->text).'</textarea>
				'.$divHtmlEditorContainerIfNeeded.'

				<div class="Translation">
					<div id="'. $n .'retranslationcontrols" style="clear:both; '.$cssShowIfMultilingual.'">
						<input id="'.$n.'override" name="'.$n.'checkbox" type="checkbox" '.(($msgdata->override)?"checked":"").' onclick="overrideTranslation(\''.$n.'\',\''.$languageName.'\'); $(\''.$n.'\').fire(\'MessageBody:OverrideChanged\');"/>' . _L('Override Translation') . '

						<div id="'.$n.'retranslation" style="margin-top: 15px; clear:both">
							<center>'. icon_button(_L('English Retranslation', $languageName),"fugue/arrow_circle_double_135","submitRetranslation('$n','$languageName')", null, 'style="float:none"') . '</center>
							<div id="'.$n.'retranslationtext" name="'.$n.'retranslation" style="height: 50px; border: 1px solid gray; color: gray; overflow:auto; clear:both"></div>
						</div>
					</div>
				</div>
			</div>
		';

		if($renderscript) {
			$str .= '
			<script>
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
						parameters: {"text": text, "language": language},
						onSuccess: function(result) {
									var data = result.responseJSON;
									if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
										return;
									var dstbox = section + "retranslationtext";
									$(dstbox).innerHTML = data.responseData.translatedText.escapeHTML();
							}
					});
					return false;
				}
				function getTranslation(section, language) {
					var sourceText = $(section+"sourceText");
					var langtext = $(section + "text");
					var overridesave = $(section + "overridesave");
					if (!sourceText.value) {
						langtext.value = overridesave.value;
						return;
					}
					$(section + "textdiv").innerHTML = "<img src=\"img/ajax-loader.gif\" />";
					new Ajax.Request("translate.php", {
						method:"post",
						parameters: {"english": sourceText.value, "languages": language},
						onSuccess: function(transport) {
							var data = transport.responseJSON;
							if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
								return;
							$(section+"textdiv").innerHTML = data.responseData.translatedText.escapeHTML();
							$(section+"text").value = data.responseData.translatedText.escapeHTML();
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
					form_do_validation($(\'' . $this->form->name . '\'), $(section));
				}

				function overrideTranslation(section,language) {
					var langtext = $(section + "text");
					var overridesave = $(section + "overridesave");
					if ($(section+"override").checked) {
						if (!overridesave.value)
							overridesave.value = langtext.value;
						langtext.show();
						$(section + "sourceTextContainer").hide();
					} else {
						if(langtext.value != overridesave.value && !confirm(\'' . _L('The edited text will be removed and set back to the previous translation.') . '\')) {
							$(section + "override").checked = true;
							return;
						}
						getTranslation(section,language);
						overridesave.value = "";
						langtext.hide();
						$(section + "sourceTextContainer").show();
					}
					setTranslationValue(section);
				}
				function toggleTranslation(section,language) {
					var translatecheck = $(section+"translatecheck");
					var settingDiv = translatecheck ? translatecheck.up("div.TranslationSettingDiv") : null;

					if (translatecheck.checked) {
						if ($(section+"text").value == "" && language)
							getTranslation(section, language);

						// TODO: Show appropriate things.
						$(section +"textfields").select(".Translation").invoke("show");
						if (!$(section+"override").checked) {
							$(section +"text").hide();
						}
					} else {
						// TODO: Show appropriate things.
						$(section +"textfields").select(".Translation").invoke("hide");
						$(section +"text").show();
					}
					setTranslationValue(section);

					if (language)
						$(section).fire("MessageBody:TranslationSettingChanged");
				}

			</script>';
			$renderscript = false;
		}
		return $str;
	}
}



?>
