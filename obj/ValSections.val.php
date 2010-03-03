<?

class ValSections extends Validator {
	var $onlyserverside = true;
	
	// Error if any one of the $sectionids is not valid.
	// NOTE: Assume that all these sections belong to the same organization.
	function validate($sectionids) {
		global $USER;
		
		$msgInvalidSections = _L("%s contains unauthorized sections.", $this->label);
		
		if (!is_array($sectionids))
			return $msgInvalidSections;
		
		$organizationid = QuickQuery('select organizationid from section where id = ?', false, array(reset($sectionids)));

		// If the user is unrestricted or is associated with this organization, $validsectionids = all sections for this organization.
		// Otherwise if the user is associated to sections, $validsectionids = associated sections that are part of this organization.
		if (QuickQuery('select 1 from userassociation where userid = ? limit 1', false, array($USER->id))) {
			if (QuickQuery('select 1 from userassociation where userid = ? and organizationid = ? and type = "organization" limit 1', false, array($USER->id, $organizationid))) {
				$validorganizationsectionids = QuickQueryList('select id from section where organizationid = ?', false, false, array($organizationid));
			} else {
				$validorganizationsectionids = QuickQueryList('
					select s.id
					from userassociation ua
						inner join section s on (ua.sectionid = s.id)
					where ua.userid = ? and ua.type = "section" and ua.sectionid != 0 and s.organizationid = ?',
					false, false, array($USER->id, $organizationid)
				);
			}
		} else {
			$validorganizationsectionids = QuickQueryList('select id from section where organizationid = ?', false, false, array($organizationid));
		}
		
		foreach ($sectionids as $id) {
			if (!in_array($id, $validorganizationsectionids))
				return $msgInvalidSections;
		}
		
		return true;
	}
}

?>