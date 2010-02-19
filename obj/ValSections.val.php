<?

class ValSections extends Validator {
	var $onlyserverside = true;
	
	function validate($sectionids) {
		global $USER;
		
		$msgInvalidSections = _L("%s contains unauthorized sections.", $this->label);
		
		if (!is_array($sectionids))
			return $msgInvalidSections;
		
		$organizationid = QuickQuery('select organizationid from section where id = ?', false, array(reset($sectionids)));
		
		if (!$USER->authorizeOrganization($organizationid))
			return $msgInvalidSections;
		
		// If the user has section associations, only retrieve those sections that also belong to this organization.
		// Otherwise retrieve all sections from this organization.
		if (QuickQuery('select count(*) from userassociation where userid = ? and type = "section" limit 1', false, array($USER->id)) > 0) {
			// Return an array of id=>skey pairs.
			$authorizedsectionids = QuickQueryList("
				select section.id
				from section
					inner join userassociation
						on (userassociation.sectionid = section.id)
				where userid=? and section.organizationid = ?",
				false, false, array($USER->id, $organizationid)
			);
		} else {
			// Return an array of id=>skey pairs.
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
