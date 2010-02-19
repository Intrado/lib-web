<?

class ValSections extends Validator {
	var $onlyserverside = true;
	
	function validate($sectionids) {
		global $USER;
		
		$msgInvalidSections = _L("%s contains unauthorized sections.", $this->label);
		
		if (!is_array($sectionids))
			return $msgInvalidSections;
		
		// All these sections should belong to the same organization.
		$organizationid = QuickQuery('select organizationid from section where id = ?', false, array(reset($sectionids)));
		
		if (!$USER->authorizeOrganization($organizationid))
			return $msgInvalidSections;
		
		// If the user is associated with this organization, return all sections from this organization.
		// If the user has section associations belonging to this organization, return those sections.
		// If the user has no associations, retrieve all sections from this organization.
		if (QuickQuery('select count(*) from userassociation where userid = ? and type = "organization" and organizationid=?', false, array($USER->id, $organizationid)) > 0) {
			$authorizedsectionids = QuickQueryList("
				select id
				from section
				where organizationid = ?",
				false, false, array($organizationid)
			);
		}
		
		if ((!isset($authorizedsectionids) || count($authorizedsectionids) < 1) && QuickQuery('select count(*) from userassociation where userid = ? and type = "section"', false, array($USER->id)) > 0) {
			$authorizedsectionids = QuickQueryList("
				select section.id
				from section
					inner join userassociation
						on (userassociation.sectionid = section.id)
				where userid=? and section.organizationid = ?",
				false, false, array($USER->id, $organizationid)
			);
		} else {
			$authorizedsectionids = QuickQueryList("
				select id
				from section
				where organizationid = ?",
				false, false, array($organizationid)
			);
		}
		
		foreach ($sectionids as $id) {
			if (!in_array($id, $authorizedsectionids))
				return $msgInvalidSections;
		}
		
		return true;
	}
}

?>
