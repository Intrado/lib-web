<?

class RenderedListCM extends RenderedList2 {
	
	function getPageData($iscsv = false) {
		//if we called setPageOffset, we cleared the cache, so no infinite loop on csv
		if ($this->pagedata !== false)
			return $this->pagedata;
		
		$this->loadPagePersonIds();
		
		if (count($this->pagepersonids) == 0)
			return; //nothing more to do!
		
		$pagepidcsv = implode(",", $this->pagepersonids);
		
		$result = Query("select personid, portaluserid from portalperson where personid in ($pagepidcsv) order by personid, portaluserid");
		$portaluserids = array();
		$personportalusers = array();
		while ($row = DBGetRow($result)) {
			$portaluserids[] = $row[1];
			if (!isset($personportalusers[$row[0]]))
				$personportalusers[$row[0]] = array();
			$personportalusers[$row[0]][] = $row[1];
		}
		$portalusers = getPortalUsers($portaluserids);

		$extrafields = array();
		
		//get list of orgs first
		$extrafields[] = "(select group_concat(org.orgkey separator ', ') from organization org "
					."inner join personassociation pa on (pa.organizationid = org.id and pa.type='organization') "
					."where pa.personid = p.id) as organization";
		//then find F and G fields
		foreach (FieldMap::getAuthorizedFieldMapsLike('f') as $field) {
			if ($field->fieldnum == 'f01' || $field->fieldnum == 'f02')
				continue;
			$extrafields[] = "p.$field->fieldnum";
		}
		foreach (FieldMap::getAuthorizedFieldMapsLike('g') as $field) {
			$i = substr($field->fieldnum, 1) + 0;
			$extrafields[] = "(select group_concat(value separator ', ') from groupdata where fieldnum=$i and personid=p.id) as $field->fieldnum";
		}
		
		$extrafieldsql = ", " . implode(',', $extrafields);
		
		//load all of the person, f, and g fields
		$query = "select p.id, p.pkey, p.f01, p.f02, ppt.token, ppt.expirationdate
				$extrafieldsql
				from person p
				left join portalpersontoken ppt on (ppt.personid = p.id)
				where p.id in ($pagepidcsv)
				";

//error_log("final query ".$query);	
				
		//note: we'll want to keep the ordering of the returned list of person ids as they are already presorted
		//array_fill_keys will give us a new array indexed by personid in the same order, then we just fill in the data
		$persondata = array_fill_keys($this->pagepersonids,array());
		$res = Query($query);
		while ($row = DBGetRow($res)) {
			$persondata[$row[0]] = $row;
		}
		
		// now create return array, could have duplicate rows for persons with multiple contact manager accounts
		$this->pagedata = array();
		
		foreach ($persondata as $id => $person) {
			// csv skips the account user info
			if ($iscsv || !isset($personportalusers[$id])) {
				array_splice($person, 6, 0, array(""));
				$this->pagedata[] = $person;
			} else {
				foreach($personportalusers[$id] as $portaluserid) {
					if (isset($portalusers[$portaluserid])) {
						$portaluser = $portalusers[$portaluserid];
						$portaluserinfo = $portaluser['portaluser.firstname'] . " " . $portaluser['portaluser.lastname'] . " (" . $portaluser['portaluser.username'] . ")";
						array_splice($person, 6, 0, array($portaluserinfo));
						$this->pagedata[] = $person;
					}
				}
			}
			
		}

		return $this->pagedata;
	}

}

?>
