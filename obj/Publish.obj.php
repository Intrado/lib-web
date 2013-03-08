<?

class Publish extends DBMappedObject {

	var $userid;
	var $action;
	var $type;
	var $messagegroupid;
	var $listid;
	var $organizationid;

	function Publish ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "publish";
		$this->_fieldlist = array("userid", "action", "type", "messagegroupid", "listid", "organizationid");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// set the type id eg: messagegroupid for messagegroup publish objects
	function setTypeId ($id) {
		switch ($this->type) {
			case "messagegroup":
				$this->messagegroupid = $id;
				break;
			case "list":
				$this->listid = $id;
				break;
		}
		// no match, failed
		return false;
	}
	
	// look up publish objects by type, id, action and optionally userid
	static function _findPublishObjects($type, $id, $action, $userid = false) {
		$args = array($action, $id);
		
		$usersql = "";
		if ($userid !== false) {
			$usersql = "and userid = ?";
			$args[] = $userid;
		}
		
		switch ($type) {
			case "messagegroup":
				return DBFindMany("publish", "from publish where action = ? and type = 'messagegroup' and messagegroupid = ? $usersql", false, $args);
				break;
			case "list":
				return DBFindMany("publish", "from publish where action = ? and type = 'list' and listid = ? $usersql", false, $args);
				break;
			default:
				return false;
		}
		return false;
	}
	
	// get all the publications for a specific type and specific messagegroup or list id
	static function getPublications ($type, $id) {
		return Publish::_findPublishObjects($type, $id, 'publish');
	}
	
	// get all the subscriptions for a specific type and specific messagegroup or list id
	// optionally restrict to a specific user id to test if the user is subscribed to this type, id combination
	static function getSubscriptions ($type, $id, $userid = false) {
		return Publish::_findPublishObjects($type, $id, 'subscribe', $userid);
	}
	
	
	
	static function getSubscribableItems($subscribetype,$type = null,$start = null,$limit = null) {
		global $USER;
	
		$data = array();
	
		// look up the user's organization associations
		$userassociatedorgs = QuickQueryList("
				(select ua.organizationid as oid
				from userassociation ua
				where ua.userid = ? and ua.type = 'organization')
				UNION
				(select s.organizationid as oid
				from userassociation ua
				left join section s on
				(ua.sectionid = s.id)
				where ua.userid = ?  and ua.type = 'section')",
				false, false, array($USER->id, $USER->id));
	
		// build the argument array
		$args = array($USER->id);
	
		// create the sql that limits results by orgs, or doesn't depending on user associations
		$orgrestrictionsql = "";
		if (count($userassociatedorgs) == 0) {
			unset($userassociatedorgs);
			$orgrestrictionsql = "(p.organizationid is null or p.organizationid = 0)";
		} else if (count($userassociatedorgs) == 1 && $userassociatedorgs[0] === null) {
			// this user is restricted to sectionid 0 and has no additional associations that provide orgs
			unset($userassociatedorgs);
			$orgrestrictionsql = "p.organizationid is null";
		} else {
			// user has org restrictions, add them to the args array but skip null org (in user org associations)
			$orgcount = 0;
			foreach ($userassociatedorgs as $index => $orgid) {
				if ($orgid !== null) {
					$orgcount++;
					$args[] = $orgid;
				}
			}
			$orgrestrictionsql = "(p.organizationid is null or p.organizationid in (" . DBParamListString($orgcount) ."))";
		}
	
		$limitsql = null;
		if (isset($start) && isset($limit))
			$limitsql = "limit $start, $limit";
	
		if ($subscribetype == 'messagegroup') {
			$typesql = "";
			if ($type != null) {
				$typesql = " and mg.type = ?";
				$args[] = $type;
			}
			$data["items"] = QuickQueryMultiRow(
					"select " . (isset($limitsql)?"SQL_CALC_FOUND_ROWS":"") . "
					p.id as pubid, mg.id as id, mg.name as name, mg.description as description, mg.modified as modified, u.login as owner
					from publish p
					inner join messagegroup mg on
					(p.messagegroupid = mg.id and not mg.deleted)
					inner join user u on
					(p.userid = u.id)
					where p.userid != ?
					and action = 'publish'
					and $orgrestrictionsql
					$typesql
					group by id
					order by name, pubid
					" . (isset($limitsql)?$limitsql:""),
					true, false, $args);
	
			$data["total"] = QuickQuery("select FOUND_ROWS()");
	
			// get all this user's subscribed ids
			$data["subscribed"] = QuickQueryList("select messagegroupid, id from publish where action = 'subscribe' and type = 'messagegroup' and userid = ?", true, false, array($USER->id));
	
		} else if ($subscribetype == 'list') {
			$typesql = "";
			if ($type != null) {
				$typesql = " and l.type = ?";
				$args[] = $type;
			}
			$data["items"] = QuickQueryMultiRow(
					"select " . (isset($limitsql)?"SQL_CALC_FOUND_ROWS":"") . "
					p.id as pubid, l.id as id, l.name as name, l.description as description, l.modifydate as modified, u.login as owner
					from publish p
					inner join list l on
					(p.listid = l.id and not l.deleted)
					inner join user u on
					(p.userid = u.id)
					where p.userid != ?
					and action = 'publish'
					and $orgrestrictionsql
					$typesql
					group by id
					order by name, pubid
					" . (isset($limitsql)?$limitsql:""),
					true, false, $args);
	
			$data["total"] = QuickQuery("select FOUND_ROWS()");
	
			// get all this user's subscribed ids
			$data["subscribed"] = QuickQueryList("select listid, id from publish where action = 'subscribe' and type = 'list' and userid = ?", true, false, array($USER->id));
		}
	
		return $data;
	}	
}



?>