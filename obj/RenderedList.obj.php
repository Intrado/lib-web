<?

class RenderedList {
	var $list;
	var $data = array();

	var $firstname;
	var $lastname;
	var $language;

	var $searchrules = array();
	var $searchorganizationids = array();
	var $searchpeople;

	var $mode = "rules"; // possible modes: add,remove,rules,people
	var $pageoffset = 0;
	var $pagelimit = 100;
	var $orderby = "";

	var $hasstats = false;
	var $total = 0;
	var $totalrule = 0;
	var $totalremoved = 0;
	var $totaladded = 0;

	var $pageids = array();
	var $pageruleids = array();
	var $pageaddids = array();
	var $pageremoveids = array();

	function RenderedList ($list) {
		global $USER;
		$this->list = $list;
		$this->firstname = FieldMap::getFirstNameField();
		$this->lastname = FieldMap::getLastNameField();
		$this->language = FieldMap::getLanguageField();
		$this->orderby =  $this->lastname . "," . $this->firstname;
	}

	function preparePeopleMode ($pagelimit, $pkey=false, $phone=false, $email=false) {
		$this->searchpeople = array('pkey' => $pkey, 'phone' => $phone, 'email' => $email, 'sms' => $phone);
		$this->mode = "people";
		$this->pagelimit = $pagelimit;
	}
	// $rules, an array of rules; can be set to false to retrieve all contacts
	function prepareRulesMode ($pagelimit, $rules, $organizations = false) {
		$this->searchorganizations = $organizations;
		$this->searchrules = $rules;
		$this->mode = "rules";
		$this->pagelimit = $pagelimit;
	}

	function prepareAdditionsMode ($pagelimit) {
		$this->mode = "add";
		$this->pagelimit = $pagelimit;
	}
	
	function prepareSkipsMode ($pagelimit) {
		$this->mode = "remove";
		$this->pagelimit = $pagelimit;
	}

	function getPage ($offset, $limit) {
		$this->pageoffset = $offset;
		$this->pagelimit = $limit;
		$this->render();

		return $this->data;
	}

	//generates the data
	function render () {
		$this->pageids = array();
		$this->pageruleids = array();
		$this->pageaddids = array();
		$this->pageremoveids = array();
		$this->data = array();
		if (!$this->hasstats) {
			$this->total = 0;
			$this->totalrule = 0;
			$this->totalremoved = 0;
			$this->totaladded = 0;
		}
		
		if ($this->mode == "rules") {
			$result = $this->renderSearch(false, $this->searchrules, $this->searchorganizations, false);
			if (!empty($result) && !$this->hasstats)
				$this->total = QuickQuery("SELECT FOUND_ROWS()");
		} else if ($this->mode == "people") {
			$personids = false;
			if (!empty($this->searchpeople['pkey'])) {
				$id = QuickQuery("SELECT id from person where not deleted and pkey=?", false, array($this->searchpeople['pkey']));
				if ($id)
					$personids = array($id);
			}
			if (!empty($this->searchpeople['phone'])) {
				$phoneids = $this->peopleWithDestination('phone', $personids);
				
				if (!empty($this->searchpeople['sms']) && getSystemSetting('_hassms', false))
					$smsids = $this->peopleWithDestination('sms', $personids);
					
				$personids = isset($smsids) ? array_merge($phoneids, $smsids) : $phoneids;
			}
			if (!empty($this->searchpeople['email']))
				$personids = $this->peopleWithDestination('email', $personids);
			
			if (!empty($personids))
				$result = $this->renderSearch($personids);
			if (!empty($result) && !$this->hasstats)
				$this->total = QuickQuery("SELECT FOUND_ROWS()");
		} else if ($this->mode == "add" || $this->mode == "remove") {
			if (!$this->hasstats)
				$this->calcStats();
			$result = $this->renderManualEntries();
		}
			
		if (empty($result))
			return;
		
		while ($row = DBGetRow($result)) {
			$this->data[] = $row;
			$this->pageids[] = $row[1];
			
			if ($row[0] == 'rule')
				$this->pageruleids[] = $row[1];
			else if ($row[0] == 'add')
				$this->pageaddids[] = $row[1];
			else {
				$this->pageruleids[] = $row[1];
				$this->pageremoveids[] = $row[1];
			}
		}
		$this->hasstats = true;
		
		
		// Get a list of the people in the manual list entries, necessary for fmt_checkbox to determine if people are in the list.
		if (in_array($this->mode, array("rules","people")) && count($this->pageids) > 0) {
			if (!isset($_SESSION['listsearchpreview'])) {
				$peopleSQL = " AND personid IN (" . implode(',', $this->pageids) . ")";
				$additionsSQL = "SELECT personid FROM listentry WHERE listid=? AND type='add' $peopleSQL";
				$skipsSQL = "SELECT personid FROM listentry WHERE listid=? AND type='negate' $peopleSQL";
				$this->pageaddids = QuickQueryList($additionsSQL, false, false, array($this->list->id));
				$this->pageremoveids = QuickQueryList($skipsSQL, false, false, array($this->list->id));
			}
			// DESTINATION DATA
			// Select static value "ordering" in order to order results as phone, email, sms
			$peopleCSV = implode(",", $this->pageids);
			$destinationQuery =
				"(select personid as pid,
					phone as destination,
					sequence as sequence,
					'phone' as type,
					'1' as ordering
					from phone ph
					where
					personid in ($peopleCSV)
					)
				union
				(select personid as pid2,
					email as destination,
					sequence as sequence,
					'email' as type,
					'2' as ordering
					from email
					where
					personid in ($peopleCSV)
					)";
			if (getSystemSetting("_hassms", false)) {
				$destinationQuery .= " union
					(select personid as pid3,
						sms as destination,
						sequence as sequence,
						'sms' as type,
						'3' as ordering
						from sms
						where
						personid in ($peopleCSV)
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
			
			// Reference from list.inc.php
			// Reference: $titles[5] = "Address";
			// Reference: $titles[6] = "Sequence";
			// Reference: $titles[7] = "Destination";
			// Use the placeholders from $this->generateCommonFields()
			// destination index 1 is destination value
			// destination index 2 is sequence
			// $destination index 3 i s it's type: phone, email, sms.
			$fullData = array();
			foreach ($this->data as $person) {
				$id = $person[1];
				$allBlank = true;
				if (isset($destinationData[$id])) {
					foreach($destinationData[$id] as $destination) {
						if (!empty($destination[1])) {
							$person[6] = $destination[2];
							$person[7] = $destination[1];
							$person[8] = $destination[3];
							$fullData[] = $person;
							$allBlank = false;
						}
					}
				}
				if ($allBlank) {
					$person[6] = _L("--None--");
					$person[7] = "";
					$person[8] = "";
					$fullData[] = $person;
				}
			}
			$this->data = $fullData;
		}
		$this->hasstats = true;
	}

	// @param $type: either 'phone', 'email', or 'sms'.
	// @param $specificPeople: limit search to just these people, an array of person IDs.
	function peopleWithDestination($type, $specificPeople = false) {
		$peopleSQL = "";
		if (is_array($specificPeople)) {
			if (empty($specificPeople))
				return array();
			$peopleSQL = "AND personid IN (" . implode(",", $specificPeople) . ")";
		}
		$search = $this->searchpeople[$type];
		return QuickQueryList("SELECT personid from $type where $type like ? $peopleSQL group by personid", false, false, array("%$search%"));
	}

		
	function generateCommonFieldsSQL() {
		$fields = FieldMap::getOptionalAuthorizedFieldMapsLike('f') + FieldMap::getOptionalAuthorizedFieldMapsLike('g');
		$fieldnumsAliased = array();
		foreach ($fields as $field) {
			if ($field->fieldnum[0] == 'f')
				$fieldnumsAliased[] = "p.$field->fieldnum";
			else {
				$i = substr($field->fieldnum, 1) + 0;
				$fieldnumsAliased[] = "(select group_concat(value separator ', ') from groupdata where fieldnum=$i and personid=p.id) as $field->fieldnum";
			}
		}
		if (!empty($fieldnumsAliased))
			$fieldsSQL = ", " . implode(',', $fieldnumsAliased);
		else
			$fieldsSQL = "";
			
		// NOTE: sequence and destination are placeholders to be filled when querying for destination data, which is in $this->render().
		return "
			p.id, pkey, $this->firstname, $this->lastname,
			concat(
				coalesce(a.addr1,''), ' ',
				coalesce(a.addr2,''), ' ',
				coalesce(a.city,''), ' ',
				coalesce(a.state,''), ' ',
				coalesce(a.zip,'')
			) as address,
			0 as sequence,
			0 as destination,
			0 as destinationtype
			$fieldsSQL
		";
	}
	
	//handles add,remove modes
	function renderManualEntries() {
		global $USER;
		$orderSQL = $this->orderby ? "order by " . $this->orderby : "";
		$limitSQL = $this->pagelimit >= 0 ? "limit $this->pageoffset,$this->pagelimit" : "";
		$leTypeSQL = $this->mode == "add" ? "AND le.type='add'" : "AND le.type='negate'";

		return Query("SELECT (le.type) AS entrytype,
				p.id, pkey, $this->firstname, $this->lastname, $this->language
			FROM person p
			LEFT JOIN listentry le ON (le.listid=? AND p.id=le.personid)
			WHERE
				NOT p.deleted
				$leTypeSQL
			$orderSQL
			$limitSQL
		", false, array($this->list->id));
	}

	function renderSearch($specificPeople = false, $searchRules = false, $searchorganizationids = false, $searchsectionids = false) {
		global $USER;
		
		if (is_array($specificPeople) && empty($specificPeople))
			return false;
		
		$commonfieldsSQL = $this->generateCommonFieldsSQL();
		$addressJoinSQL = "LEFT JOIN address a ON (a.personid = p.id)";
		$searchRulesSQL = $searchRules ? Rule::makeQuery($searchRules, "p") : "";
		$peopleSQL = is_array($specificPeople) ? "AND p.id IN (" . implode(",", $specificPeople) . ")" : "";
		$orderSQL = $this->orderby ? "order by " . $this->orderby : "";
	
		$limitSQL = $this->pagelimit >= 0 ? "limit $this->pageoffset,$this->pagelimit" : "";
	
		if (isset($_SESSION['listsearchpreview'])) {
			$listRules = $this->list->getListRules();
			$leJoinSQL = "LEFT JOIN listentry le ON (le.listid=? AND p.id = le.personid)";
			
			if (count($listRules)) {
				$combinedRulesSQL = Rule::makeQuery(array_merge($USER->rules(), $listRules), "p");
			} else if (count($this->list->getOrganizations()) == 0 && count($this->list->getSections()) == 0) {
				$combinedRulesSQL = "and 0";
			} else {
				$combinedRulesSQL = "";
			}
			
			// TODO: Take into account the user's organization/section restrictions.
			
			// Performs union between list rules and list additions.
			return Query("
					(SELECT SQL_CALC_FOUND_ROWS IFNULL(le.type,'rule') AS entrytype,
						$commonfieldsSQL
					FROM " . $this->list->getPersonSubquerySQL() . " p
						$leJoinSQL
						$addressJoinSQL
					WHERE
						NOT p.deleted
						AND p.userid IS NULL
						AND le.type IS NULL
						$combinedRulesSQL
						$peopleSQL)
				UNION ALL
					(SELECT (le.type) as entrytype,
						$commonfieldsSQL
					FROM person p
						$leJoinSQL
						$addressJoinSQL
					WHERE
						NOT p.deleted
						AND le.type='add'
						$peopleSQL)
				$orderSQL
				$limitSQL
			", false, array($this->list->id, $this->list->id));
		} else {
			// TODO: Need to take into account organization/section listentries for determine isinlist.
			$listrulesSQL = $this->list->getListRuleSQL();
			
			$userDataViewRestrictionsSQL = $USER->userSQL("p");
			
			return Query("
				SELECT
					SQL_CALC_FOUND_ROWS ($listrulesSQL) as isinlist,
					$commonfieldsSQL
				FROM " . Person::makePersonSubquery(
							$searchorganizationids ? $searchorganizationids : array(),
							$searchsectionids ? $searchsectionids : array()
						) . " p
					$addressJoinSQL
				WHERE
					p.userid IS null
					AND NOT p.deleted
					$userDataViewRestrictionsSQL
					$searchRulesSQL
					$peopleSQL
				$orderSQL
				$limitSQL
			");
		}
	}
	
	function calcStats () {
		global $USER;

		// if there are list rules, combine with the user rules for enrollment data integration
		if (count($this->list->getListRules()) > 0) {
			$allrules = array_merge($USER->rules(), $this->list->getListRules());
			$rulesql = Rule::makeQuery($allrules, "p");
		} else if (count($this->list->getOrganizations()) == 0 && count($this->list->getSections()) == 0) {
			$rulesql = "and 0"; // if there are no restrictions at all, then return 0 results.
		} else {
			$rulesql = "";
		}

		$this->totalremoved = $this->countRemoved();
		$this->totaladded = $this->countAdded();

		$this->total = $this->countEffectiveRule($rulesql) + $this->totaladded;
		if ($this->mode == "add") {
			$this->total = $this->totaladded;
		}
		if ($this->mode == "remove") {
			$this->total = $this->totalremoved;
		}

		$this->hasstats = true;
	}
	
	function countEffectiveRule ($rulesql) {
		$query = "
			select count(*)
				from " . $this->list->getPersonSubquerySQL() . " p
					left join listentry le on (le.personid=p.id and le.listid=?)
				where le.type is null and p.userid is null and not p.deleted $rulesql";

		return QuickQuery($query, false, array($this->list->id));
	}
	
	function countRemoved () {
		$query = "select count(*) from listentry le inner join person p on (p.id = le.personid) where le.type='negate' and le.listid = ?";
		return QuickQuery($query, false, array($this->list->id));
	}

	function countAdded () {
		$query = "select count(*) from listentry le inner join person p on (p.id = le.personid and not p.deleted) where  le.type='add' and le.listid = ?";
		return QuickQuery($query, false, array($this->list->id));
	}
}

?>
