<?
class SMSAggregator extends FormItem {
	
	// flag required by FormItem
	function render ($selectvalue) {
		
		$formData = $this->args['values']['form'];
		
		// code copied from SelectMenu form item in Form.obj.php
		$n = $this->form->name."_".$this->name;
		$size = isset($this->args['size']) ? 'size="'.$this->args['size'].'"' : "";
		
		$str = '<select id='.$n.' name="'.$n.'" '.$size .' >';
		foreach ($formData as $key => $groupName) {
			$str .= '<option value="'.escapehtml($key).'" '.($key  == $selectvalue ? 'selected' : '').' >'.escapehtml($groupName).'</option>';
		}
		$str .= '</select>';
		
		// then we add a couple divs for displaying vendor and short code information
		$str .='<div style="padding: 4px;margin: 3px;" id="smsShortCodeData"></div>';
		return $str;
	}
	
	function renderJavascript () {
		
		$jsData = json_encode($this->args['values']['js']);
		
		$str = "
			// create a namespace; give a hoot.
			var smsFunctions = {
				data: $jsData
			};
			
			smsFunctions.showData = function() {
				var sf = smsFunctions;
				
				// get currently selected option
				var selected = jQuery( 'select[id*=_shortcodegroup]' ).val();
				
				// find the matching codeGroup data via selected value
				var htmlData =  '<div>';
				
				// create header line
				htmlData += '<div>' + 
								'<strong>vendor</strong> - <strong>shortcode</strong>' + 
							'</div>';
				
				jQuery.each( sf.data, function( key, data ) {
					if( selected === data.shortcodeid ) {
						htmlData += '<div>' + 
										data.smsaggregatorname + 
										' - ' + 
										data.shortcode + 
									'</div>';
					}
				});
				htmlData += '</div>'

				jQuery( '#smsShortCodeData' ).html( htmlData );
			};
			
			smsFunctions.showData();
		";		
		
		return $str;
	}
}
?>