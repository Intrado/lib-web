<?

class RenderedListCM extends RenderedList2 {

	function RenderedListCM () {
		global $USER;
		$this->owneruser = $USER; //default to global user unless we are set to use a list with a different user
	}
	
	function loadPagePersonIds() {
		$personsql = $this->getPersonSql(true);
		if ($personsql != "") {
			$this->pagepersonids = QuickQueryList($personsql);
			$this->total = QuickQuery("select found_rows()");
		} else {
			$this->pagepersonids = array();
			$this->total = 0;
		}
	}

	/**
	 * Generates a query to select personids matching criteria set up with one of the init functions.
	 * Only minimal column data is returned: personid and any fields used in the orderby. (ie to use query as subquery/union/etc)
	 * @param $addorderlimit set to false to avoid appending "order by" or "limit" clauses
	 * @param $calctotal adds SQL_CALC_FOUND_ROWS to the select statement so that "select round_rows()" can be called later.
	 * @return query to select ids from the person table
	 */
	function getPersonSql ($addorderlimit = true, $calctotal = true) {
		
		$fields = array("p.id");
		
		$ordersql = "";
		$limitsql = "";
		$sqlflags = $calctotal ? "SQL_CALC_FOUND_ROWS" : "";
		
		if ($addorderlimit) {
			if (count($this->orderby) > 0) {
				$orderbits = array();
				foreach ($this->orderby as $orderopts) {
					list($field,$desc) = $orderopts;
					$orderbits[] = $field . ($desc ? " desc " : " ");
					$fields[] = "p.".$field; //add to list of fields also, so that unions can still sort
				}
				$ordersql = "order by " . implode(",",$orderbits);
			}
			$limitsql = $this->pagelimit >= 0 ? "limit $this->pageoffset,$this->pagelimit" : "";
		}
		
		$hideactivecodes = "";
		$hideassociated = "";
		if (isset($_SESSION['hideactivecodes']) && $_SESSION['hideactivecodes']) {
			$hideactivecodes = " and (ppt.token is null or ppt.expirationdate < curdate()) ";
		}
		if (isset($_SESSION['hideassociated']) && $_SESSION['hideassociated']) {
			$hideassociated = " and not exists(select count(*) from portalperson pp2 where pp2.personid = p.id group by pp2.personid) ";
		}
		
		$fieldsql = implode(",",$fields);
		
		$query = "";
		switch ($this->mode) {
			case "search": 
				$joinsql = $this->owneruser->getPersonAssociationJoinSql($this->organizationids, $this->sectionids, "p");
				$rulesql = $this->owneruser->getRuleSql($this->rules,"p");
				
				$query = "select SQL_CALC_FOUND_ROWS distinct $fieldsql from person p \n"
						." left join portalpersontoken ppt on (ppt.personid = p.id) \n"
						."	$joinsql \n"
						."	where not p.deleted and p.userid is null \n"
						." $rulesql \n"
						."$hideactivecodes $hideassociated "
						."$ordersql $limitsql";
				
				break;
			case "individual":
				$joinsql = $this->owneruser->getPersonAssociationJoinSql(array(), array(), "p");
				$rulesql = $this->owneruser->getRuleSql(array(), "p");
				
				$contactjoinsql = "";
				$contactwheresql = "";
				
				if ($this->searchphone !== false) {
					$phone = DBSafe($this->searchphone);
					$contactjoinsql .= "left join phone ph on (ph.personid = p.id and ph.phone like '$phone') \n";
					$contactjoinsql .= "left join sms s on (s.personid = p.id and s.sms like '$phone') \n";
					$contactwheresql .= "and (ph.id is not null or sms.id is not null) ";
				}
				
				if ($this->searchemail !== false) {
					$email = DBSafe($this->searchemail);
					$contactjoinsql .= "inner join email e on (e.personid = p.id and e.email like '$email') \n";
				}
				
				if ($this->searchpkey !== false) {
					$pkey = DBSafe($this->searchpkey);
					$contactwheresql = " and p.pkey='$pkey' ";
				}
				
				
				$query = "select SQL_CALC_FOUND_ROWS distinct $fieldsql from person p \n"
						."	$joinsql \n"
						."	$contactjoinsql "
						."	where not p.deleted and p.userid is null \n"
						." $rulesql $contactwheresql \n"
						."$ordersql $limitsql ";
				
				break;
		}
//error_log($query);
		return $query;
	}
	
	function getPageData() {
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

		$hideactivecodes = "";
		$hideassociated = "";
		if (isset($_SESSION['hideactivecodes']) && $_SESSION['hideactivecodes']) {
			$hideactivecodes = " and (ppt.token is null or ppt.expirationdate < curdate()) ";
		}
		if (isset($_SESSION['hideassociated']) && $_SESSION['hideassociated']) {
			$hideassociated = " and not exists(select count(*) from portalperson pp2 where pp2.personid = p.id group by pp2.personid) ";
		}
		
		//load all of the person, f, and g fields
		$query = "select p.id, p.pkey, p.f01, p.f02, ppt.token, ppt.expirationdate
				$extrafieldsql
				from person p
				left join portalpersontoken ppt on (ppt.personid = p.id)
				where p.id in ($pagepidcsv)
				$hideactivecodes
				$hideassociated
				";

//error_log("final query ".$query);	
		
		//note: we'll want to keep the ordering of the returned list of person ids as they are already presorted
		//array_fill_keys will give us a new array indexed by personid in the same order, then we just fill in the data
		$persondata = array_fill_keys($this->pagepersonids,array());

		$res = Query($query);
	
		while ($row = DBGetRow($res)) {
			if (!isset($personportalusers[$row[0]])) {
				array_splice($row, 6, 0, array(""));
				$persondata[$row[0]] = $row;
			} else {
				foreach($personportalusers[$row[0]] as $portaluserid){
					if(isset($portalusers[$portaluserid])){
						$portaluser = $portalusers[$portaluserid];
						$portaluserinfo = $portaluser['portaluser.firstname'] . " " . $portaluser['portaluser.lastname'] . " (" . $portaluser['portaluser.username'] . ")";
						array_splice($row, 6, 0, array($portaluserinfo));
						$persondata[$row[0]] = $row;
					}
				}
			}
		}
		
		return $persondata;
	}

}

?>
