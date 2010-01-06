<?

class MessageBody extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;	
	
		$str = '
			<div class="MessageBodyContainer" style="'.(!empty($this->args['hidden']) ? 'display:none' : '').'">
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
		$str .=		'</table>';
			
	
		// Data Fields.
		$str .= '
			<table border="0" cellpadding="1" cellspacing="0" style="font-size: 9px; margin-top: 5px;" class="DataFieldsTable" id="'.$n.'datafieldstable">
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
										 textInsert('<<' + sel.options[sel.selectedIndex].text + (def ? ':' : '') + def + '>>', $('$n'));
									}") 
						. '					
					</td>
				</tr>
			</table>';
			
		$str .= '</div>';
	
		return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		$usehtmleditor = !empty($this->args['usehtmleditor']) ? 'true' : 'false';
		$hidden = !empty($this->args['hidden']) ? 'true' : 'false';
		$str = "
			(function() {
				var setselection = function () {
					if(document.selection) this.sel = document.selection.createRange();
				};
				$('$n').focus();
				$('$n').observe('keyup',setselection);
				$('$n').observe('mouseup',setselection);
				
				if ($usehtmleditor && !$hidden) {
					var textarea = $('$n');
					if (textarea.visible())
						applyHtmlEditor(textarea);
				}
			})();
		";
		
		return $str;
	}
}

?>
