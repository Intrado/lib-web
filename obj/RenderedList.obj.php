<?

class RenderedList2 {
	
	var $mode = false; //list,search,individual
	var $owneruser;
	
	//vars to hold search criteria (or list search contents)
	var $rules = array();
	var $organizationids = array();
	var $sectionids = array();
	//extra search criteria for finding individual people
	var $searchpkey = false;
	var $searchphone = false;
	var $searchemail = false;
	
	var $quickaddsearch = false;//quick add search string
	
	var $listid = false; //used to filter out any skips, or to get manual adds.
	
	var $pageinlistmap = array(); // array of personids in this page (from search) that match a list to type of entry (rule or add)
	
	
	//vars for paginating and sorting
	var $pageoffset = 0;
	var $pagelimit = 100;
	var $orderby = array(array ("f02", false), array("f01",false));// key is field, value if true is descending order
	
	//data for this page
	var $pagepersonids = false; //list of personids from main person query
	var $pagedata = false; //all display data for this page
	var $pageinlistpersonids = array(); //when doing a search for an active list, any personpageids that are also in the list
	
	//statistics
	var $total = 0; //total people on list = (rules/org/section - skips) + adds
	
	function RenderedList2 () {
		global $USER;
		$this->owneruser = $USER; //default to global user unless we are set to use a list with a different user
	}
	
	
	function initWithList ($list) {
		global $USER;
		$this->mode = "list";
		//load the list rules, orgs, sections, etc
		$this->listid = $list->id;
		//TODO load list userid as owner so we can check user restrictions on them instead of logged in global (ie for list sharing)
		
		$this->rules = $list->getListRules();
		foreach ($list->getOrganizations() as $org)
			$this->organizationids[] = $org->id;
		foreach ($list->getSections() as $section)
			$this->sectionids[] = $section->id;
		
		if ($list->userid != $USER->id)
			$this->owneruser = new User($list->userid);
	}
	
	function initWithSearchCriteria ($rules = array(), $organizationids = array(), $sectionids = array()) {
		$this->mode = "search";
		//use params to find people
		$this->rules = $rules;
		$this->organizationids = $organizationids;
		$this->sectionids = $sectionids;
	}
	
	function initWithIndividualCriteria ($searchpkey, $searchphone, $searchemail) {
		$this->mode = "individual";
		$this->searchpkey = $searchpkey;
		$this->searchphone = $searchphone;
		$this->searchemail = $searchemail;
	}
	
	function initWithQuickAddSearch ($quickaddsearch) {
		$this->mode = "quickaddsearch";
		$this->quickaddsearch = $quickaddsearch;
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
		
		$fieldsql = implode(",",$fields);
		
		$query = "";
		switch ($this->mode) {
			case "list": 
				
				//in list mode, having no rules, no sections, and no orgs means empty list
				if (count($this->organizationids) > 0 || count($this->sectionids) > 0 || count($this->rules) > 0) {
					$joinsql = $this->owneruser->getPersonAssociationJoinSql($this->organizationids, $this->sectionids, "p");
					$rulesql = $this->owneruser->getRuleSql($this->rules,"p");
					
					$query = "(select $sqlflags distinct $fieldsql from person p \n"
							."	$joinsql \n"
							."	left join listentry le on \n"
							."		(p.id = le.personid and le.listid=" . $this->listid . ") \n" //skip anyone that is directly referenced, add or negate
							."	where not p.deleted and p.userid is null and le.type is null \n"
							." $rulesql ) \n"
							." UNION ALL \n"
							."(select $fieldsql from person p \n"
							."	inner join listentry le on \n"
							."		(p.id = le.personid and le.listid=" . $this->listid . " and le.type='add') \n"
							."where not p.deleted )\n"
							."$ordersql $limitsql ";
				} else {
					//with no rules/orgs/sections, just use manual adds, if any
					$query = "select $sqlflags $fieldsql from person p \n"
							."	inner join listentry le on \n"
							."		(p.id = le.personid and le.listid=" . $this->listid . " and le.type='add') \n"
							."where not p.deleted\n"
							."$ordersql $limitsql ";
				}
				
				break;
			case "search": 
				$joinsql = $this->owneruser->getPersonAssociationJoinSql($this->organizationids, $this->sectionids, "p");
				$rulesql = $this->owneruser->getRuleSql($this->rules,"p");
				
				$query = "select $sqlflags distinct $fieldsql from person p \n"
						."	$joinsql \n"
						."	where not p.deleted and p.userid is null \n"
						." $rulesql \n"
						."$ordersql $limitsql ";
				
				break;
			case "individual":
				$joinsql = $this->owneruser->getPersonAssociationJoinSql(array(), array(), "p");
				$rulesql = $this->owneruser->getRuleSql(array(), "p");
				
				$contactjoinsql = "";
				$contactwheresql = "";
				
				if ($this->searchphone !== false) {
					$phone = DBSafe($this->searchphone);
					$contactjoinsql .= "left join phone ph on (ph.personid = p.id and ph.phone like '".Phone::parse($phone)."') \n";
					$contactjoinsql .= "left join sms s on (s.personid = p.id and s.sms like '".Phone::parse($phone)."') \n";
					$contactwheresql .= " and (ph.id is not null or s.id is not null) ";
				}
				
				if ($this->searchemail !== false) {
					$email = DBSafe($this->searchemail);
					$contactjoinsql .= "inner join email e on (e.personid = p.id and e.email like '$email') \n";
					$contactwheresql .= " and e.id is not null ";
				}
				
				if ($this->searchpkey !== false) {
					$pkey = DBSafe($this->searchpkey);
					$contactwheresql = " and p.pkey='$pkey' ";
				}
				
				// if searching without any criteria, fetch empty set
				if ("" == $contactwheresql)
					$contactwheresql = "and p.id=0";

				
				$query = "select $sqlflags distinct $fieldsql from person p \n"
						."	$joinsql \n"
						."	$contactjoinsql "
						."	where not p.deleted and p.userid is null \n"
						." $rulesql $contactwheresql \n"
						."$ordersql $limitsql ";
				
				break;
			case "quickaddsearch":
				
				$digits = Phone::parse($this->quickaddsearch); //get any digits out of the string
				$searchstring = DBEscapeLikeWildcards(DBSafe($this->quickaddsearch));
				
				//if not enough search data, dont search
				if (strlen($searchstring) < 2) {
					return "select $fieldsql from person p where 0";
				}
				
				//because mysql doesn't optimize OR and LIKE very well at all, it is more efficient to search
				//each condition individually and union them together, assuming we didn't find a very large
				//dataset in each query
				$queries = array();
				
				//remains the same for all queries
				$joinsql = $this->owneruser->getPersonAssociationJoinSql(array(), array(), "p");
				$rulesql = $this->owneruser->getRuleSql(array(), "p");
				
				//add the person search criteria first
				foreach (array("p.pkey","p.f01","p.f02") as $personfield) {
					
					$query = "select $sqlflags distinct $fieldsql from person p \n"
						."	$joinsql \n"
						."	where not p.deleted and p.userid is null \n"
						." $rulesql and $personfield like '$searchstring%' \n";
					
					$queries[] = $query;
					
					//avoid setting SQL_CALC_FOUND_ROWS on subsequent queries in the union
					//http://dev.mysql.com/doc/refman/5.0/en/information-functions.html#function_found-rows
					//The SQL_CALC_FOUND_ROWS keyword must appear in the first SELECT of the UNION.
					$sqlflags = ""; 
				}
				
				//if there are two words separated by a spaces or commas, do a first, last and last, first search
				$words = preg_split("/[\s,]+/", $this->quickaddsearch, -1, PREG_SPLIT_NO_EMPTY);
				if (count($words) == 2) {
					//if using a comma, do "last, first" otherwise "first last"
					if (strpos($this->quickaddsearch, ",") === false) {
						$word1 = DBEscapeLikeWildcards(DBSafe($words[0]));
						$word2 = DBEscapeLikeWildcards(DBSafe($words[1]));
					} else {
						$word1 = DBEscapeLikeWildcards(DBSafe($words[1]));
						$word2 = DBEscapeLikeWildcards(DBSafe($words[0]));
					}
					
					$query = "select distinct $fieldsql from person p \n"
						."	$joinsql \n"
						."	where not p.deleted and p.userid is null \n"
						." $rulesql and p.f01 like '$word1%' and p.f02 like '$word2%'\n";
					$queries[] = $query;
				}
				
				
				
				//add phone, sms if we have at least 4 digits
				//less than 4 seems to not use any index, and would get entire area codes anyhow
				if (strlen($digits) >= 4) {
					foreach (array("phone","sms") as $type) {
						$query = "select distinct $fieldsql from person p \n"
							."	$joinsql \n"
							." inner join $type x on (x.personid = p.id) \n"
							."	where not p.deleted and p.userid is null \n"
							." $rulesql and x.$type like '$digits%' \n";
						$queries[] = $query;
					}
				}
				
				//add email if we have at least 3 chars
				if (strlen($searchstring) >= 3) {
					$query = "select distinct $fieldsql from person p \n"
						."	$joinsql \n"
						." inner join email x on (x.personid = p.id) \n"
						."	where not p.deleted and p.userid is null \n"
						." $rulesql and x.email like '$searchstring%' \n";
					$queries[] = $query;
				}
				
				$query = "(\n" . implode("\n ) \n union \n ( \n",$queries) . "\n ) \n"
						."$ordersql $limitsql ";
								
				break;
		}
		return $query;
	}
	
	function loadPagePersonIds() {
		if ($this->pagepersonids === false) {
			$personsql = $this->getPersonSql(true);
			$this->pagepersonids = QuickQueryList($personsql);
			$this->total = QuickQuery("select found_rows()");
		}
	}
	
	function getTotal() {
		$this->loadPagePersonIds(); //also gets total from found_rows
		return $this->total;
	}
	
	function getPageData() {
		if ($this->pagedata !== false)
			return $this->pagedata;
		
		$this->loadPagePersonIds();
		
		if (count($this->pagepersonids) == 0)
			return; //nothing more to do!
		
		$pagepidcsv = implode(",", $this->pagepersonids);

		$extrafields = array();
		
		//get list of orgs first
		$extrafields[] = "(select group_concat(org.orgkey separator ', ') from organization org "
					."inner join personassociation pa on (pa.organizationid = org.id and pa.type='organization') "
					."where pa.personid = p.id) as orgnaization";
		//then find F and G fields
		foreach (FieldMap::getAuthorizedFieldMapsLike('f') as $field)
			$extrafields[] = "p.$field->fieldnum";
		foreach (FieldMap::getAuthorizedFieldMapsLike('g') as $field) {
			$i = substr($field->fieldnum, 1) + 0;
			$extrafields[] = "(select group_concat(value separator ', ') from groupdata where fieldnum=$i and personid=p.id) as $field->fieldnum";
		}
		
		$extrafieldsql = ", " . implode(',', $extrafields);
		
		//load all of the person, f, and g fields
		$query = "select p.id, pkey, 0 as sequence_placeholder, 0 as destination_placeholder, 0 as destinationtype_placeholder, 0 as editlock_placeholder
					$extrafieldsql
				from person p
				where p.id in ($pagepidcsv)";
		
		//note: we'll want to keep the ordering of the returned list of person ids as they are already presorted
		//array_fill_keys will give us a new array indexed by personid in the same order, then we just fill in the data
		$persondata = array_fill_keys($this->pagepersonids,array());
		$res = Query($query);
		while ($row = DBGetRow($res)) {
			$persondata[$row[0]] = $row;
		}
		
		//now we need to get all of the destination data for these people
		
		$destinationQuery =
			"(select personid as pid,
				phone as destination,
				sequence as sequence,
				'phone' as type,
				editlock as editlock,
				'1' as ordering
				from phone ph
				where
				personid in ($pagepidcsv)
				)
			union
			(select personid as pid2,
				email as destination,
				sequence as sequence,
				'email' as type,
				editlock as editlock,
				'2' as ordering
				from email
				where
				personid in ($pagepidcsv)
				)";
		if (getSystemSetting("_hassms", false)) {
			$destinationQuery .= " union
				(select personid as pid3,
					sms as destination,
					sequence as sequence,
					'sms' as type,
					editlock as editlock,
					'3' as ordering
					from sms
					where
					personid in ($pagepidcsv)
					) ";
		}
			
		$result = Query($destinationQuery . " order by pid, ordering, sequence");
		$destinationData = array();
		while ($row = DBGetRow($result)) {
			$personid = $row[0];
			if (!isset($destinationData[$personid]))
				$destinationData[$personid] = array();
			$destinationData[$personid][] = $row;
		}
		

		$this->pagedata = array();
		foreach ($persondata as $id => $person) {
			$allBlank = true;
			if (isset($destinationData[$id])) {
				foreach($destinationData[$id] as $destination) {
					if (!empty($destination[1])) {
						$person[2] = $destination[2];
						$person[3] = $destination[1];
						$person[4] = $destination[3];
						$person[5] = $destination[4];
						$this->pagedata[] = $person;
						$allBlank = false;
					}
				}
			}
			if ($allBlank) {
				$person[2] = _L("--None--");
				$person[3] = "";
				$person[4] = "";
				$person[5] = "";
				$this->pagedata[] = $person;
			}
		}
		return $this->pagedata;
	}
	
	
	function getPageInListMap($list) {
		global $USER;
		
		//index cache on listid since we can't control future invocations could pass a diff list
		if (isset($this->pageinlistmap[$list->id]))
			return $this->pageinlistmap[$list->id];
		
		$this->loadPagePersonIds();
		
		if (count($this->pagepersonids) == 0)
			return array(); //nothing more to do!
		
		$pagepidcsv = implode(",", $this->pagepersonids);
			
		$rules = $list->getListRules();
		$organizationids = array();
		$sectionids = array();
		foreach ($list->getOrganizations() as $org)
			$organizationids[] = $org->id;
		foreach ($list->getSections() as $section)
			$sectionids[] = $section->id;
		
		if ($list->userid == $USER->id)
			$owneruser = $USER;
		else
			$owneruser = new User($list->userid);
		
		
		
		if (count($organizationids) > 0 || count($sectionids) > 0 || count($rules) > 0) {
			$joinsql = $owneruser->getPersonAssociationJoinSql($organizationids, $sectionids, "p");
			$rulesql = $owneruser->getRuleSql($rules, "p");
			$query = "(select p.id, 'rule' as entrytype from person p \n"
					."	$joinsql \n"
					."	left join listentry le on \n"
					."		(p.id = le.personid and le.listid=" . $list->id . ") \n" //skip anyone that is directly referenced, add or negate
					."	where not p.deleted and p.userid is null and le.type is null \n"
					."	and p.id in ($pagepidcsv) \n"
					." $rulesql ) \n"
					." UNION ALL \n"
					."(select p.id, 'add' as entrytype from person p \n"
					."	inner join listentry le on \n"
					."		(p.id = le.personid and le.listid=" . $list->id . " and le.type='add') \n"
					."where not p.deleted and p.id in ($pagepidcsv) )\n";
		} else {
			$query = "select p.id, 'add' as entrytype from person p \n"
					."	inner join listentry le on \n"
					."		(p.id = le.personid and le.listid=" . $list->id . " and le.type='add') \n"
					."where not p.deleted and p.id in ($pagepidcsv)\n";
		}
		
		return $this->pageinlistmap[$list->id] = QuickQueryList($query,true);
	}
	
}

?>
