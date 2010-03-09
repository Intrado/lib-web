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
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Organization")).'</th>
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Sections")).'</th>
				</tr>
				<tr>
					<td style="text-align:left; vertical-align:top">
						<select id="'.$n.'organizationselector">
							<option value="">' . escapehtml(_L("Choose an Organization")) . '</option>
		';
	
		// Populate the selectbox with organizations.
		// If the user has section/organization associations,
		// get a union of his associated organizations and the organizations of his associated sections.
		// Otherwise, get all organizations.
		if (QuickQuery('select 1 from userassociation where userid = ? limit 1', false, array($USER->id))) {
			$validorgkeys = QuickQueryList('
				select o.id, o.orgkey
				from userassociation ua
					inner join organization o
						on (ua.organizationid = o.id)
				where ua.userid = ? and ua.type = "organization"',
				true, false, array($USER->id)
			);
			
			$validorgkeys += QuickQueryList('
				select distinct o.id, o.orgkey
				from userassociation ua
					inner join section s
						on (ua.sectionid = s.id)
					inner join organization o
						on (s.organizationid = o.id)
				where ua.userid = ? and ua.type = "section" and ua.sectionid != 0',
				true, false, array($USER->id)
			);
		} else {
			$validorgkeys = QuickQueryList('select id, orgkey from organization where not deleted', true, false);
		}
		
		foreach ($validorgkeys as $organizationid => $orgkey) {
			$validorgkeys = QuickQueryList('select id, orgkey from organization where not deleted', true, false);
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