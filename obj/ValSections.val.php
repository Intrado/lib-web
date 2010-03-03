<?

class ValSections extends Validator {
	var $onlyserverside = true;
	
	// Error if any one of the $sectionids is not valid.
	// $value may either be an array of sectionid => skey pairs or a comma-separated string of sectionids.
	function validate($value) {
		global $USER;
		
		$msgInvalidSections = _L("%s contains unauthorized sections.", $this->label);
		
		if (!is_array($value))
			$sectionids = explode(',', $value);
		else
			$sectionids = array_keys($value);
		
		// If the user is unrestricted, $validsectionids = all sections.
		// Otherwise get a union of associated sections and sections that are part of the user's associated organization.
		// $validsectionids is indexed by sectionid for checking isset($validsectionids[$id])
		if (QuickQuery('select 1 from userassociation where userid = ? limit 1', false, array($USER->id))) {
			$validsectionids = array_flip(QuickQueryList('
				select distinct s.id
				from userassociation ua
					inner join section s
						on (ua.sectionid = s.id or ua.organizationid = s.organizationid)
				where ua.userid = ? and ua.type in ("section", "organization")',
				false, false, array($USER->id)
			));
		} else {
			$validsectionids = array_flip(QuickQueryList('select id from section', false, false));
		}
		
		foreach ($sectionids as $id) {
			if ($id > 0 && !isset($validsectionids[$id])) {
				return $msgInvalidSections;
			}
		}
		
		return true;
	}
}

?>
