<?

class MessageBody extends FormItem {
	function render ($value) {
		$n = $this->form->name."_".$this->name;	
		
		if (isset($this->args['preferredgenderformitem']))
			$preferredgenderformitem = $this->args['preferredgenderformitem'];
		else
			$preferredgenderformitem = $this->form->name . "_voice";
		
		if (isset($this->args['language']))
			$language = $this->args['language'];
			
		$str = '';
		
		$hidden = isset($this->args['hidden']) ? $this->args['hidden'] : false;
		
		if (isset($this->args['overrideplaintext'])) {
			$str .= '
				<div id="'.$n.'plaintextpreview" style="display:'.($this->args['overrideplaintext'] ? 'none' : 'block').';">
					<pre style="white-space:normal;color:gray; border: solid 1px gray;">' .
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
		
		$str .= '<div id="'.$n.'messagebodycontainer" class="MessageBodyContainer" style="'.($hidden ? 'display:none' : '').'">
			<table style="width:100%">
				<tr>
					<td valign="top" rowspan="5">
						<textarea id="'.$n.'" name="'.$n.'" rows="12" cols="35">'.escapehtml($value).'</textarea>	';
						
		if(!isset($this->args['playbutton']) || $this->args['playbutton'] === true) {
			$str .= 		'<div>' . icon_button(_L("Play"),"fugue/control","var content = $('" . $n . "').getValue();
																		if(content.length > 4000) {
																			alert('The preview will only render audio from the first 4000 characters.');
																			content = content.substr(0,4000);
																		}
																		var languageselection = $('" . $this->form->name . "_language');
																		var language;
																		if (languageselection) {
																			language = $('" . $this->form->name . "_language').getValue();
																		} else {
																			language = '$language';
																		}
																		
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
																		
																		if(content != '')
																			popup('previewmessage.php?parentfield=" . $n . "&language=' + encodeURIComponent(language) + '&gender=' + encodeURIComponent(gender), 400, 400,'preview');")
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
			<table border="0" cellpadding="1" cellspacing="0" style="' . (!empty($this->args['hidedatafieldsonload']) ? 'display:none' : '') . '; font-size: 9px; margin-top: 5px;" class="DataFieldsTable" id="'.$n.'datafieldstable">
				<tr>
					<td>
						<span style="font-size: 9px;">Default&nbsp;Value:</span><br />
						<input id="'.$n.'datadefault" type="text" size="10" value=""/>
					</td>
					<td>
						<span style="font-size: 9px;">Data&nbsp;Field:</span><br />
						<select id="'.$n.'datafield">
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
			
		$str .= '</div>'; // MessageBodyContainer
	
		return $str;
	}
	
	function renderJavascript() {
		$n = $this->form->name."_".$this->name;
		
		$str = "
			(function() {
				var setselection = function () {
					if(document.selection)
						this.sel = document.selection.createRange();
				};

				var textarea = $('$n');
				textarea.observe('keyup',setselection.bindAsEventListener(textarea));
				textarea.observe('mouseup',setselection.bindAsEventListener(textarea));
				
		";
		
		if ((isset($this->args['usehtmleditor']) && $this->args['usehtmleditor']) &&
			(!isset($this->args['hidden']) || !$this->args['hidden'])) {
			$str .= "
					if (textarea.visible())
						applyHtmlEditor(textarea);
			";
		}
		
		if (isset($this->args['overrideplaintext'])) {
			$str .= "
					var form = $('{$this->form->name}');
					var messagebodycontainer = $('{$n}messagebodycontainer');
					var plaintextpreview = $('{$n}plaintextpreview');
					form.observe('PlainEmailCheckbox:OverridePlainText', function(event) {
						if (event.memo.override) {
							messagebodycontainer.show();
							plaintextpreview.hide();
							if (textarea.value.strip() == '') {
								textarea.value = '';
							}
						} else {
							messagebodycontainer.hide();
							plaintextpreview.show();
						}
					});
			";
		}
		
		$str .= "
			})();
		";
		
		return $str;
	}
}

?>
