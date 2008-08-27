<?

class RenderedList {

	var $list;
	var $data = array();

	var $firstname;
	var $lastname;
	var $language;

	var $searchrules = array();

	var $mode = "preview"; //add,remove,totals
	var $pageoffset = 0;
	var $pagelimit = 100;
	var $orderby = "";

	var $hasstats = false;
	var	$total = 0;
	var $totalrule = 0;
	var $totalremoved = 0;
	var $totaladded = 0;
	var $getflexfields = false;

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

	function setSearch ($rules) {
		$this->searchrules = $rules;
		$this->mode = "search";
	}

	function getPage ($offset, $limit, $getflexfields = false) {
		$this->pageoffset = $offset;
		$this->pagelimit = $limit;
		$this->getflexfields = $getflexfields;
		$this->render();

		return $this->data;
	}

	//generates the data
	function render ($getdata = true) {
		if ($this->mode == "search" || $this->mode == 'contacts')
			return $this->renderSearch($getdata);
		else
			return $this->renderList($getdata);
	}

	//handles preview,add,remove modes
	function renderList($getdata = true) {
		global $USER;

		// if there are list rules, combine with the user rules for association data integration
		if (count($this->list->getListRules()) > 0) {
			$allrules = array_merge($USER->rules(), $this->list->getListRules());
			$rulesql = Rule::makeQuery($allrules, "p");
		} else {
			$rulesql = "and 0"; // no list rules, no persons to render
		}

		$pagesql = "limit $this->pageoffset,$this->pagelimit";
		if ($this->pagelimit == -1)
			$pagesql = "";

		$listid = $this->list->id;
		$orderby = $this->orderby ? "order by " . $this->orderby : "";

		$pfields = "p.id";
		$contactfields = "";
		$sms = "";
		$smsquery = "";
		if(getSystemSetting("_hassms", false)){
			$sms = "s.sms,";
			$smsquery = " left join sms s on (s.personid = p.id and s.sequence=0) ";
		}
		if ($getdata) {
			$pfields .= ", p.pkey";
			$pfields .= ", p.$this->firstname, p.$this->lastname, p.$this->language";
			$contactfields = ",ph.phone,
								e.email,
								s.sms,
								concat(
									coalesce(a.addr1,''), ' ',
									coalesce(a.addr2,''), ' ',
									coalesce(a.city,''), ' ',
									coalesce(a.state,''), ' ',
									coalesce(a.zip,'')
								) as address";
		}

		//calc the stats
		if (!$this->hasstats) {
			$this->calcStats();
		}


		if ($this->mode == "preview") {
			$modesql1 = "and le.type is null"; //don't include removed items
			$modesql2 = "and le.type='A'";
		}
		if ($this->mode == "add") {
			$modesql1 = "and 0"; //don't include any rule or removed items
			$modesql2 = "and le.type='A'";
		}
		if ($this->mode == "remove") {
			$modesql1 = "and 0"; //only get the removed items
			$modesql2 = "and le.type = 'N'"; //and ignore adds
		}

		$query = "
			(select ifnull(le.type,'R') as entrytype,
			$pfields
			$contactfields
			from 		person p
			left join	listentry le on
								(p.id=le.personid and le.listid = $listid)
			";
		if ($getdata) {
			$query .="
			left join	phone ph on
								(ph.personid=p.id and ph.sequence=0)
			left join	email e on
								(e.personid=p.id  and e.sequence=0)
			left join sms s on
								(s.personid = p.id and s.sequence=0)
			left join	address a on
								(a.personid=p.id)
			";
		}
		$query .="
			where not p.deleted and p.userid is null $modesql1 $rulesql)

			union all

			(select (le.type) as entrytype,
			$pfields
			$contactfields
			from 		person p
			left join	listentry le on
								(le.listid = $listid and p.id=le.personid)
			";
		if ($getdata) {
			$query .="
			left join	phone ph on
								(ph.personid=p.id and ph.sequence=0)
			left join	email e on
								(e.personid=p.id  and e.sequence=0)
			left join sms s on
								(s.personid = p.id and s.sequence=0)
			left join	address a on
								(a.personid=p.id)
			";
		}
		$query .="
			where not p.deleted $modesql2 )

			$orderby
			$pagesql
			";

//echo "<br>renderList ". $query;

		//load page to memory
		$this->pageids = array();
		$this->pageruleids = array();
		$this->pageaddids = array();
		$this->pageremoveids = array();
		$data = array();
		if ($result = Query($query)) {
			while ($row = DBGetRow($result)) {
				$data[] = $row;
				$this->pageids[] = $row[1];
				if ($row[0] == "R")
					$this->pageruleids[] = $row[1];
				else if ($row[0] == 'A')
					$this->pageaddids[] = $row[1];
				else {
					$this->pageruleids[] = $row[1];
					$this->pageremoveids[] = $row[1];
				}
			}
		}

		$this->data = $data;
	}

	function renderSearch ($getdata = true) {
		global $USER;

		// NOTE usersql and listsql are used separately, thus we do not combine rules to form rulesql (as in renderList above)
		$usersql = $USER->userSQL("p");

		$pagesql = "limit $this->pageoffset,$this->pagelimit";
		if ($this->pagelimit == -1)
			$pagesql = "";

		$listid = $this->list->id;
		$orderby = $this->orderby ? "order by " . $this->orderby : "";

		$listsql = $this->list->getListRuleSQL();

		//compose rules for search
		if($this->searchrules === false)
			$searchsql = "and 0";
		elseif (count($this->searchrules) > 0)
			$searchsql = Rule::makeQuery($this->searchrules, "p");
		else
			$searchsql = "";

		//should we calc the stats?
		if (!$this->hasstats)
			$statssql = "SQL_CALC_FOUND_ROWS";
		else
			$statssql = "";

		$pfields = "p.id";
		$contactfields = "";
		if ($getdata) {
			$pfields .= ", p.pkey";
			$pfields .= ", p.$this->firstname,p.$this->lastname, p.$this->language";

			$contactfields = ",ph.phone,
								e.email,
								s.sms,
								concat(
									coalesce(a.addr1,''), ' ',
									coalesce(a.addr2,''), ' ',
									coalesce(a.city,''), ' ',
									coalesce(a.state,''), ' ',
									coalesce(a.zip,'')
								) as address";

			// Get ALL the flex fields when in 'contacts' mode
			if ($this->getflexfields) {
				$extraFields = FieldMap::getAuthorizedMapNames();
				// Start at the 3rd index since we already got the first 2 field names directly above
				$start = 0;
				foreach ($extraFields as $key => $value) {
					if ($key == $this->firstname || $key == $this->lastname || $key == $this->language) { // Ignore these since they are in the query already
						continue;
					}

					$contactfields .= ", p.$key ";
				}
			}
		}

		$query = "
			select $statssql ($listsql) as isinlist,
			$pfields
			$contactfields
			from person p
		";
		if ($getdata) {
			$query .="
			left join	phone ph on
								(ph.personid=p.id and ph.sequence=0)
			left join	email e on
								(e.personid=p.id  and e.sequence=0)
			left join sms s on
								(s.personid = p.id and s.sequence=0)
			left join	address a on
								(a.personid=p.id)
			";
		}
		$query .="
			where p.userid is null and not p.deleted
			$usersql
			$searchsql
			$orderby
			$pagesql
		";

//echo "<br>renderSearch ". $query;

		//load page to memory
		$this->pageids = array();
		$this->pageruleids = array();
		$this->pageaddids = array();
		$this->pageremoveids = array();
		$data = array();

		$result = Query($query);
		while ($row = DBGetRow($result)) {
			$data[] = $row;
			$this->pageids[] = $row[1];
			if ($row[0])
				$this->pageruleids[] = $row[1];
		}

		if (!$this->hasstats) {
			$query = "select found_rows()";
			$this->total = QuickQuery($query);
			$this->totalrule = 0;
			$this->totalremoved = 0;
			$this->totaladded = 0;
			$this->hasstats = true;
		}
		//now get a list of the people in the manual list entries
		if (count($this->pageids) > 0) {
			$query = "select personid from listentry where listid='$listid' and type='A' and personid in (" . implode(",",$this->pageids) . ")";
			$this->pageaddids = QuickQueryList($query);
			$query = "select personid from listentry where listid='$listid' and type='N' and personid in (" . implode(",",$this->pageids) . ")";
			$this->pageremoveids = QuickQueryList($query);
		}
		$this->data = $data;
	}


	function calcStats () {
		global $USER;

		// if there are list rules, combine with the user rules for association data integration
		if (count($this->list->getListRules()) > 0) {
			$allrules = array_merge($USER->rules(), $this->list->getListRules());
			$rulesql = Rule::makeQuery($allrules, "p");
		} else {
			$rulesql = "and 0"; // no list rules, no persons to render
		}

		$this->totalremoved = $this->countRemoved();
		$this->totaladded = $this->countAdded();

		$this->total = $this->countEffectiveRule($rulesql) + $this->totaladded;
		if ($this->mode == "add") {
			$this->total = $this->totaladded;;
		}
		if ($this->mode == "remove") {
			$this->total = $this->totalremoved;
		}

		$this->hasstats = true;
	}

	function countEffectiveRule ($rulesql) {
		$query = "select count(*)
				from person p
				left join listentry le on (le.personid=p.id and le.listid = " . $this->list->id . ")
				where le.type is null and p.userid is null and not p.deleted $rulesql
		";
//echo "<br>count ".$query;
		return QuickQuery($query);
	}

	function countRemoved () {
		$query = "select count(*) from listentry le inner join person p on (p.id = le.personid) where le.type='N' and le.listid = " . $this->list->id;
		return QuickQuery($query);
	}

	function countAdded () {
		$query = "select count(*) from listentry le inner join person p on (p.id = le.personid and not p.deleted) where  le.type='A' and le.listid = " . $this->list->id;
		return QuickQuery($query);
	}


}

?>