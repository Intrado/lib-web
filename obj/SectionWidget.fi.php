<?php

// The post-data value from this formitem is a comma-separated list of sectionids.
class SectionWidget extends FormItem {
	// $value is an array of $sectionid => $skey pairs.
	function render($value) {
		global $USER;
		
		$n = $this->form->name . '_' . $this->name;
		$selectedsectionids = array_keys($value);
		$selectmultipleorganizations = isset($this->args["selectmultipleorganizations"]) && $this->args["selectmultipleorganizations"];
		
		$html = '<input name="'.$n.'" id="'.$n.'" value="'.implode(',', $selectedsectionids).'" type="hidden"/>';
		
		if ($selectmultipleorganizations) {
			$html .= '<ul id="'.$n.'selectedsectionscontainer" style="margin:2px; padding-left: 0; list-style:inside"></ul>';
		} else if (count($selectedsectionids) > 0) {
			$selectedorganizationid = QuickQuery("select organizationid from section where id=?", false, array($selectedsectionids[0]));
		}
		
		$html .= '
			<table>
				<tr>
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Organization")).'</th>
					<th style="text-align:left; vertical-align:top">'.escapehtml(_L("Sections")).'</th>
				</tr>
				<tr>
					<td style="text-align:left; vertical-align:top">
						<select id="'.$n.'organizationselectbox">
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
				where ua.userid = ? and ua.type = "organization"
				order by o.orgkey',
				true, false, array($USER->id)
			);
			
			$validorgkeys += QuickQueryList('
				select distinct o.id, o.orgkey
				from userassociation ua
					inner join section s
						on (ua.sectionid = s.id)
					inner join organization o
						on (s.organizationid = o.id)
				where ua.userid = ? and ua.type = "section"
				order by o.orgkey',
				true, false, array($USER->id)
			);
		} else {
			$validorgkeys = QuickQueryList('select id, orgkey from organization where not deleted', true, false);
		}
		
		foreach ($validorgkeys as $organizationid => $orgkey) {
			$validorgkeys = QuickQueryList('select id, orgkey from organization where not deleted', true, false);
			
			$selected = isset($selectedorganizationid) && $selectedorganizationid == $organizationid;
			
			$html .= '<option value="'.$organizationid.'" '.($selected ? "selected" : "").'>' . escapehtml($orgkey) . '</option>';
		}
		
		$html .= '
						</select>
					</td>
					<td id="'.$n.'sectioncheckboxescontainer" style="text-align:left; vertical-align:top"></td>
		';
		
		if ($selectmultipleorganizations) {
			$html .= '<td id="'.$n.'addbuttoncontainer" style="text-align:left; vertical-align:top"></td>';
		}
		
		$html .= '
				</tr>
			</table>
		';
		
		return $html;
	}
	
	// $value is an array of $sectionid => $skey pairs.
	function renderJavascript($value) {
		$n = $this->form->name . '_' . $this->name;
		
		// The javascript SectionWidget expects an object literal indexed by $sectionid pairs.
		$selectedsectionidsmap = count($value) > 0 ? json_encode((object)$value) : 'null';

		$selectmultipleorganizations = isset($this->args["selectmultipleorganizations"]) && $this->args["selectmultipleorganizations"];
		
		return '
			document.observe("dom:loaded", function() {
				var sectionwidget = new SectionWidget(
					"'.$n.'",
					' . ($selectmultipleorganizations ? '"'.$n.'selectedsectionscontainer"' : 'null') . ',
					"'.$n.'organizationselectbox",
					"'.$n.'sectioncheckboxescontainer",
					' . ($selectmultipleorganizations ? '"'.$n.'addbuttoncontainer"' : 'null') . ',
					'.$selectedsectionidsmap.'
				);
			});
		';
	}
	
	function renderJavascriptLibraries() {
		return '<script type="text/javascript" src="script/sectionwidget.js.php"></script>';
	}
}

?>