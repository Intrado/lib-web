<?

class ValSections extends Validator {
	var $onlyserverside = true;
	
	// Error if any one of the $sectionids is not valid.
	function validate($sectionids) {
		global $USER;
		
		//if user is restricted to one or more sections and/or organizations
		if (QuickQuery("select 1 from userassociation where type in ('section', 'organization') and userid = ? limit 1", false, array($USER->id))) {
			// first argument first query
			$args = array($USER->id);
			// additional arguments first query
			foreach ($sectionids as $id)
				$args[] = $id;
			
			// first argument second query
			$args[] = $USER->id;
			// additional arguments second query
			foreach ($sectionids as $id)
				$args[] = $id;
			
			//get sectionids valid for user associations
			$validsections = QuickQueryList("
				select s.id, 1
				from section s
					inner join userassociation ua on
						(s.id = ua.sectionid and ua.type = 'section')
				where ua.userid = ?
					and s.id in (". DBParamListString(count($sectionids)). ")
				union
				select s.id, 1
				from section s
					inner join organization o on
						(s.organizationid = o.id)
					inner join userassociation ua on
						(o.id = ua.organizationid and ua.type = 'organization')
				where ua.userid = ?
					and s.id in (". DBParamListString(count($sectionids)). ")", true, false, $args);
		} else {
			// if user has no section or organization restrictions
			// search for the requested sectionids in the sections table
			$validsections = QuickQueryList("select id, 1 from section where id in (". DBParamListString(count($sectionids)). ")", true, false, $sectionids);
		}
		
		$msgInvalidSections = _L("%s contains unauthorized sections.", $this->label);
		
		// if no valid sections were returned
		if (!$validsections)
			return $msgInvalidSections;
		
		foreach ($sectionids as $id) {
			if (!isset($validsections[$id]))
				return $msgInvalidSections;
		}
		
		return true;
	}
}

?>