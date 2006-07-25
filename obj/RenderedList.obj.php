<?

class RenderedList {

	var $list;
	var $data = array();

	var $firstname;
	var $lastname;
	var $language;

	var $searchrules = array();

	var $mode = "preview"; //add,remove
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

		$usersql = $USER->userSQL("p", "pd");

		$pagesql = "limit $this->pageoffset,$this->pagelimit";
		if ($this->pagelimit == -1)
			$pagesql = "";

		$listid = $this->list->id;
		$orderby = $this->orderby ? "order by " . $this->orderby : "";

		$listsql = $this->list->getListRuleSQL();

		//get a list of the fieldmaps to show the persondata
		//$fieldmap = FieldMap::getMapNames();
		//$pdfields = DBMappedObject::getFieldList(false,array_keys($fieldmap), "pd");
		//if (strlen($pdfields) > 0)
		//	$pdfields = "," . $pdfields;
		$pfields = "p.id";
		$pdfields = "";
		$contactfields = "";
		if ($getdata) {
			$pfields .= ", p.pkey";
			$pdfields = ", pd.$this->firstname, pd.$this->lastname, pd.$this->language";
			$contactfields = ",ph.phone,
								e.email,
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
			$modesql2 = "";
		}
		if ($this->mode == "add") {
			$modesql1 = "and 0"; //don't include any rule or removed items
			$modesql2 = "";
		}
		if ($this->mode == "remove") {
			$modesql1 = "and le.type = 'N'"; //only get the removed items
			$modesql2 = "and 0"; //and ignore adds
		}

		$query = "
			(select ifnull(le.type,'R') as entrytype,
			$pfields
			$pdfields
			$contactfields
			from 		person p
			left join	persondata pd on
								(p.id=pd.personid)
			left join	listentry le on
								(p.id=le.personid and le.listid = $listid)
			";
		if ($getdata) {
			$query .="
			left join	phone ph on
								(ph.personid=p.id and ph.sequence=0)
			left join	email e on
								(e.personid=p.id  and e.sequence=0)
			left join	address a on
								(a.personid=p.id)
			";
		}
		$query .="
			where $usersql and p.userid is null $modesql1 and $listsql)

			union all

			(select (le.type) as entrytype,
			$pfields
			$pdfields
			$contactfields
			from 		person p
			left join	persondata pd on
								(p.id=pd.personid)
			left join	listentry le on
								(le.listid = $listid and p.id=le.personid)
			";
		if ($getdata) {
			$query .="
			left join	phone ph on
								(ph.personid=p.id and ph.sequence=0)
			left join	email e on
								(e.personid=p.id  and e.sequence=0)
			left join	address a on
								(a.personid=p.id)
			";
		}
		$query .="
			where p.customerid = $USER->customerid $modesql2 and le.type='A')

			$orderby
			$pagesql
			";

//echo $query;

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

		$usersql = $USER->userSQL("p", "pd");

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
			$searchsql = Rule::makeQuery($this->searchrules, "pd");
		else
			$searchsql = "";

		//should we calc the stats?
		if (!$this->hasstats)
			$statssql = "SQL_CALC_FOUND_ROWS";
		else
			$statssql = "";

		$pfields = "p.id";
		$pdfields = "";
		$contactfields = "";
		if ($getdata) {
			$pfields .= ", p.pkey";
			$pdfields = ", pd.$this->firstname,pd.$this->lastname, pd.$this->language";

			$contactfields = ",ph.phone,
								e.email,
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

					$contactfields .= ", pd.$key ";
				}
			}
		}

		$query = "
			select $statssql ($listsql) as isinlist,
			$pfields
			$pdfields
			$contactfields
			from person p left join persondata pd on (p.id=pd.personid)
		";
		if ($getdata) {
			$query .="
			left join	phone ph on
								(ph.personid=p.id and ph.sequence=0)
			left join	email e on
								(e.personid=p.id  and e.sequence=0)
			left join	address a on
								(a.personid=p.id)
			";
		}
		$query .="
			where $usersql
			and p.userid is null
			$searchsql
			$orderby
			$pagesql
		";

//echo $query;

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
		$query = "select personid from listentry where listid='$listid' and type='A' and personid in (" . implode(",",$this->pageids) . ")";
		$this->pageaddids = QuickQueryList($query);
		$query = "select personid from listentry where listid='$listid' and type='N' and personid in (" . implode(",",$this->pageids) . ")";
		$this->pageremoveids = QuickQueryList($query);

		$this->data = $data;
	}


	function calcStats () {
		global $USER;

		if ($this->mode == "preview") {
			$modesql1 = "and le.type is null"; //don't include removed items
			$modesql2 = "";
		}
		if ($this->mode == "add") {
			$modesql1 = "and 0"; //don't include any rule or removed items
			$modesql2 = "";
		}
		if ($this->mode == "remove") {
			$modesql1 = "and le.type = 'N'"; //only get the removed items
			$modesql2 = "and 0"; //and ignore adds
		}

		$usersql = $USER->userSQL("p", "pd");

		$listid = $this->list->id;

		$listsql = $this->list->getListRuleSQL();

		$query = "select sum(le.type is null), sum(le.type='N')
		from person p left join persondata pd on (p.id=pd.personid)
		left join listentry le on (le.personid=p.id and le.listid = $listid)
		where $usersql and p.userid is null and $listsql $modesql1
		";

		$stats = QuickQueryRow($query);
		$this->totalrule = $stats[0];
		$this->totalremoved = 0+$stats[1];

		$query = "select count(*)
		from person p , listentry le
		where p.customerid = $USER->customerid and le.listid = $listid and p.id=le.personid and le.type='A' $modesql2
		";
		$this->totaladded = QuickQuery($query);

		$this->total = $this->totalrule + $this->totaladded;
		$this->hasstats = true;
	}
}

?>