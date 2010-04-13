<?

class SectionWidget extends FormItem {
	function render($value) {
		global $USER;
		
		$n = $this->form->name . '_' . $this->name;
		
		if (isset($this->args['sectionids']) && is_array($this->args['sectionids']) && count($this->args['sectionids']) > 0)
			$selectedorganizationid = QuickQuery('select organizationid from section where id=?', false, array(reset($this->args['sectionids'])));
		
		$html = '
			<table>
				<tr>
					<th style="text-align:left; vertical-align:top">'.escapehtml(getSystemSetting("organizationfieldname","Organization")).'</th>
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Sections")).'</th>
				</tr>
				<tr>
					<td style="text-align:left; vertical-align:top">
						<select id="'.$n.'organizationselector">
							<option value="">--- ' . escapehtml(_L("Choose a %s",getSystemSetting("organizationfieldname","Organization"))) . ' ---</option>
		';
	
		// Populate the selectbox with authorized organizations.
		$validorgkeys = Organization::getAuthorizedOrgKeys();
		
		foreach ($validorgkeys as $organizationid => $orgkey) {
			$selected = isset($selectedorganizationid) && $selectedorganizationid == $organizationid;
			$html .= '<option value="'.$organizationid.'" '.($selected ? 'selected' : '').'>' . escapehtml($orgkey) . '</option>';
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
			$selectedsectionidsmap = array_fill_keys($this->args['sectionids'], true);
		} else {
			$selectedsectionidsmap = null;
		}
		
		return '
			(function() {
				var sectionwidget = new SectionWidget(
					"'.$n.'",
					"'.$n.'organizationselector",
					"'.$n.'sectionscontainer",
					'.json_encode($selectedsectionidsmap).'
				);
			})();
		';
	}
	
	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/sectionwidget.js.php"></script>';
	}
}

?>