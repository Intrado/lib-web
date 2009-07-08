<?

class MessageBody extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;	
	
		$str = '
			<div>
			<table>
				<tr>
					<td valign="top" rowspan="5">
						<textarea id="'.$n.'" name="'.$n.'" rows="12" cols="50" />'.escapehtml($value).'</textarea>	'
					 .  icon_button(_L("Play"),"fugue/control","var content = $('" . $n . "').getValue();
																	var language = $('" . $this->form->name . "_language').getValue();
																	var voice = 'Female';
																	if($('" . $this->form->name . "_voice-2').checked) {
																		voice = 'Male';
																	}
																	if(content != '')
																		popup('previewmessage.php?text=' + encodeURIComponent(content) + '&language=' + encodeURIComponent(language) + '&gender=' + encodeURIComponent(voice), 400, 400);")
				. '</td>
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
												sel = $('" . $n . "datafield'); def = $('" . $n . "datadefault').value; textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n'));") 
									. '		
												
				</td>
				</tr>
			</table>
		</div>';
		return $str;
	}
}

class ValMessageBody extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {
		$message = new Message();
		$errors = array();	
		$message->parse($value,$errors);  // Fill in with voice id later
		if (count($errors) > 0)	{			
			//error('There was an error parsing the message', $errors);
			$str = "There was an error parsing the message: ";
			foreach($errors as $error)
			{
				$str .= "\n" . $error;
			}
			
			return $str;
		} else {
			return true;
		}
	}
}

class ValMessageName extends Validator {
	var $onlyserverside = true;
	function validate ($value, $args) {	
		global $USER;
		$existsid = QuickQuery("select id from message where name=? and type=? and userid=? and deleted=0",false,array($value,$args["type"],$USER->id));		
		if($existsid && $existsid != $_SESSION['messageid']) {
			return "A message named $value already exists";
		}
		return true;
	}
}

?>