<? 


// Translation widget
class TranslationItem extends FormItem {
	function render ($value) {
		static $renderscript = true;
		
		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";
			
		$language = "english";
		$gender = "female";	
			
		if(is_array($value)) {
			$language = $value["language"];
			$gender = $value["gender"];
		}

		$displaylanguage = ucfirst($language);
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
						alert("The message is too long. Only the first 2000 characters are submitted for translation.");
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
			</script>';
			$renderscript = false;
		}
		
		$str .= '
			<input id="'.$n.'" name="'.$n.'" type="hidden" value="' . escapehtml(json_encode($value)) . '"/>
		
			<table>
				<tr>
					<td valign="top">
						<input id="'.$n.'translatecheck" name="'.$n.'checkbox" type="checkbox"
							 onchange=" $(\''.$n.'icons\').toggle(); 
							 			$(\''.$n.'textfields\').toggle();
										$(\''.$n.'controls\').toggle();" 
										checked /> 
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
						<div id="'.$n.'textfields">
							<textarea id="'.$n.'text" name="'.$n.'text" rows="3" cols="50" disabled />'.escapehtml($value["text"]).'</textarea>	
							<br />
							<input id="'.$n.'override" name="'.$n.'checkbox" type="checkbox"
												 onchange="$(\''.$n.'editlock\').toggle();
												 			var langtext = $(\''.$n.'text\');
												 			langtext.disabled = !langtext.disabled;
												 "/>
							Override Translation 
							
							<div id="'.$n.'retranslation" style="display: none;margin-top: 30px;">
								<table>
									<tr>
										<td>' 
											. icon_button(_L('Refresh %1$s to English Translation', $displaylanguage),"fugue/arrow_circle_double_135","submitRetranslation('$n','$language')") . 
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
							. icon_button(_L("Play"),"fugue/control","
									var content = $('" . $n . "text').getValue();
										if(content != '')
											popup('previewmessage.php?text=' + encodeURIComponent(content) + '&language=$language&gender=$gender', 400, 400);")
											
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
			</table>
			<hr>
		';				
				
		return $str;
	}
}

class ValTranslation extends Validator {
	function validate ($value, $args) {
		if(is_array($value)) {
			return true;
		}
		if (!$value)	
			return $this->label . " is Required";
		else
			return true;

	}
	function getJSValidator () {
		return 
			'function (name, label, value, args) {			
				checkval = value.evalJSON();
				if (value == "")
					return label + " is Required";
				return true;
			}';
	}
}


?>