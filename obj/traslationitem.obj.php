<? 

// Translation widget
class TranslationItem extends FormItem {
	function render ($value) {
		static $renderscript = true;
		
		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";
			
		$msgdata = json_decode($value);
		
		$language = $this->args['language'];
		$gender = isset($msgdata->gender)?$msgdata->gender:"female";
		
		$isphone = isset($this->args['phone']);

		$str = "";
		
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
					var urllang = encodeURIComponent(language);
					var request = "translate.php?text=" + encodeURIComponent(text) + "&language=" + urllang;
					cachedAjaxGet(
							request,
							function(result) {	
									var data = result.responseJSON;
									if(data.responseStatus != 200 || data.responseData.translatedText == undefined)
										return;
									var dstbox = section + "retranslationtext";
									$(dstbox).value = data.responseData.translatedText;
							}
					);
					return false;
				}
				function setTranslationValue(section) {
					$(section).value = Object.toJSON({
							"enabled": $(section + "translatecheck").checked,
							"text": $(section + "text").value.toString(),
							"override": $(section + "override").checked	
					});
					form_do_validation($(\'' . $this->form->name . '\'), $(section)); 
				} 
				function overrideTranslation(section) {
					var langtext = $(section + "text");
					if(langtext.disabled) {
						$(section + "text").innerHTML = langtext.getValue();
					} else {
						if(langtext.value != $(section + "text").innerHTML && !confirm(\'' . _L('The edited text will be removed and set back to the generated translation.') . '\')) {
							$(section + "override").checked = true;
							return;
						}
						langtext.value = $(section + "text").innerHTML;
					}
					langtext.disabled = !langtext.disabled;
					$(section + "editlock").toggle();
					setTranslationValue(section);
				}
				function toggleTranslation(section) {
					$(section +"icons").toggle(); 
					$(section +"textfields").toggle();
					$(section +"disableinfo").toggle();
					$(section +"controls").toggle();
					setTranslationValue(section);
				}
				
			</script>';
			$renderscript = false;
		}
		
		$str .= '
			<input id="'.$n.'" name="'.$n.'" type="hidden" size="70" value="' . escapehtml($value) . '"/>
			<hr>
			<table>
				<tr>
					<td valign="top">
						<input id="'.$n.'translatecheck" name="'.$n.'checkbox" type="checkbox" onchange="toggleTranslation(\''.$n.'\');" checked /> 
						<b>Translate</b>
					</td>
					<td valign="top" align="right" width="40px">
						<div id="'.$n.'icons">
							<div id="'.$n.'editlock" style="display: none;">
								<img src="img/padlock.gif">
							</div>
							<img src="img/pixel.gif" width="10" height="1">
						</div>
					</td>
					<td valign="top">
						<div id="'.$n.'disableinfo" style="display: none;">
							<ul><li> '  . _L('%1$s recipients will now receive the default English message.',$language) . '<ul>
						</div>
						<div id="'.$n.'textfields">
							<textarea id="'.$n.'text" name="'.$n.'text" rows="3" cols="50" onChange="setTranslationValue(\''.$n.'\');" disabled />'.escapehtml($msgdata->text).'</textarea>	
							<br />
							<div id="'.$n.'overridesave" style="display:none;"></div>
							<input id="'.$n.'override" name="'.$n.'checkbox" type="checkbox"
												 onchange="overrideTranslation(\''.$n.'\');"/>
							' . _L('Override Translation') . 
							'<div id="'.$n.'retranslation" style="display: none;margin-top: 30px;">
								<table>
									<tr>
										<td>' 
											. icon_button(_L('Refresh %1$s to English Translation', $language),"fugue/arrow_circle_double_135","submitRetranslation('$n','$language')") . 
										'</td>
									</tr>
									<tr>
										<td>
											<textarea id="'.$n.'retranslationtext" name="'.$n.'retranslation" rows="3" cols="50"/></textarea>
										</td>
									</tr>
								</table>
							</div>
						</div>
					</td>
					<td valign="top">
						<div id="'.$n.'controls">'
							. ($isphone?icon_button(_L("Play"),"fugue/control","
									var content = $('" . $n . "text').getValue();
										if(content != '')
											popup('previewmessage.php?text=' + encodeURIComponent(content) + '&language=$language&gender=$gender" . "', 400, 400);"):"")
							. '<div id="'. $n .'showenglish">'
								. icon_button(_L("Show in English"),"fugue/magnifier","
									$('" . $n . "retranslation').toggle();submitRetranslation('$n','$language');$('" . $n . "hideenglish').show();$('" . $n . "showenglish').hide();")
							. '</div>
							    <div id="'. $n .'hideenglish" style="display: none;">'
								. icon_button(_L("Hide English"),"fugue/magnifier__minus","
									$('" . $n . "retranslation').toggle();$('" . $n . "hideenglish').hide();$('" . $n . "showenglish').show();") 
							. '</div>
						</div>
					</td>
				</tr>
			</table>';
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
