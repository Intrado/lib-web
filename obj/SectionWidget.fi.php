<?php

// First fix organizations for the rulewidget.
// Next finish up SectionWidget, just spit out table html in the FormItem rather than building it in the javascript class.

class SectionWidget extends FormItem {
	function render($value) {
		global $USER;
		
		$n = $this->form->name . '_' . $this->name;
		
		$html = '
			<table>
				<tr>
					<th>Organization</th>
					<th>Section</th>
				</tr>
				<tr>
					<td>
						<select id="'.$n.'organizationselector">
		';
	
		// Loop through organizations.
		foreach ($USER->organizations() as $organization) {
			$html .= '<option value="'.$organization->id.'">' . escapehtml($organization->orgkey) . '</option>';
		}
		
		$html .= '
						</select>
					</td>
					<td id="'.$n.'sectionscontainer">
					</td>
				</tr>
			</table>
		';
		
		return $html;
	}
	
	function renderJavascript() {
		$n = $this->form->name . '_' . $this->name;
		
		return '
			(function() {
				var sectionwidget = new SectionWidget(
					"'.$n.'",
					"'.$n.'organizationselector",
					"'.$n.'sectionscontainer"
				);
			})();
		';
	}
	
	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/sectionwidget.js.php"></script>';
	}
}

?>