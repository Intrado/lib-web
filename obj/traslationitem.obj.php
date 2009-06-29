<? 


// Translation widget
class TranslationItem extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;
		if($value == null)
			$value == "";
			
		$language = "english";
		$gender = "female";	
			
		if(is_array($value)) {
			$language = $value["language"];
			$gender = $value["gender"];
			$value = $value["value"];
		}

		$displaylanguage = ucfirst($language);
		
		
		$str = '
			<table>
				<tr>
					<td valign="top">
						<input id="'.$n.'translatecheck" name="'.$n.'checkbox" type="checkbox"
							 onchange=" $(\''.$n.'icons\').toggle(); 
							 			$(\''.$n.'textfields\').toggle();
										$(\''.$n.'controls\').toggle();" 
										checked/> 
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
							<textarea id="'.$n.'text" name="'.$n.'text" rows="3" cols="50" disabled />'.escapehtml($value).'</textarea>	
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
											. icon_button(_L('Refresh %1$s to English Translation', $displaylanguage),"fugue/arrow_circle_double_135","") . 
										'</td>
									</tr>
									<tr>							
										<td>
											<textarea id="'.$n.'retranslation" name="'.$n.'retranslation" rows="3" cols="50"/></textarea>
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
							. icon_button(_L("Show in English"),"fugue/magnifier","
									$('" . $n . "retranslation').toggle();
							") .
						'</div>
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