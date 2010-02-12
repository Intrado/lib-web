<?php

// First fix organizations for the rulewidget.
// Next finish up SectionWidget, just spit out table html in the FormItem rather than building it in the javascript class.

class SectionWidget extends FormItem {
	function render($value) {
		global $USER;
		
		$n = $this->form->name . '_' . $this->name;
		
		if (isset($this->args['sectionids']) && is_array($this->args['sectionids']) && count($this->args['sectionids']) > 0) {
			$organizationid = QuickQuery('select organizationid from section where id=?', false, array(reset($this->args['sectionids'])));
		}
		
		$html = '
			<table>
				<tr>
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Organization")).'</th>
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Sections")).'</th>
				</tr>
				<tr>
					<td style="text-align:left; vertical-align:top">
						<select id="'.$n.'organizationselector">
							<option value="">' . escapehtml(_L("Choose an Organization")) . '</option>
		';
	
		// Loop through organizations.
		foreach ($USER->organizations() as $organization) {
			$selected = isset($organizationid) && $organizationid === $organization->id;
			$html .= '<option value="'.$organization->id.'" '.($selected ? 'selected' : '').'>' . escapehtml($organization->orgkey) . '</option>';
		}
		
		$html .= '
						</select>
					</td>
					<td id="'.$n.'sectionscontainer" style="text-align:left; vertical-align:top">
						<!-- This is necessary for form_make_validators() to actually instantiate the validator for this form item. -->
						<!-- This hidden input will get deleted when the section widget loads new content. -->
						<input id="'.$n.'" type="hidden" value=""/>
					</td>
				</tr>
			</table>
		';
		
		return $html;
	}
	
	function renderJavascript() {
		$n = $this->form->name . '_' . $this->name;
		
		if (isset($this->args['sectionids']) && is_array($this->args['sectionids']) && count($this->args['sectionids']) > 0) {
			// The javascript SectionWidget expects an object literal of sectionid => true pairs.
			$sectionids = array_fill_keys($this->args['sectionids'], true);
		} else {
			$sectionids = null;
		}
		
		return '
			(function() {
				var sectionwidget = new SectionWidget(
					"'.$n.'",
					"'.$n.'organizationselector",
					"'.$n.'sectionscontainer",
					'.json_encode($sectionids).'
				);
			})();
		';
	}
	
	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/sectionwidget.js.php"></script>';
	}
}

?>