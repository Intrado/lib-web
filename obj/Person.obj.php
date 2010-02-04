<?

class Person extends DBMappedObject {

	var $userid;
	var $pkey;
	var $importid;
	var $lastimport;
	var $type = "system"; // enum (system, addressbook, manualadd, upload)
	var $deleted = 0;

	var $f01 = "";
	var $f02 = "";
	var $f03 = "";
	var $f04 = "";
	var $f05 = "";
	var $f06 = "";
	var $f07 = "";
	var $f08 = "";
	var $f09 = "";
	var $f10 = "";
	var $f11 = "";
	var $f12 = "";
	var $f13 = "";
	var $f14 = "";
	var $f15 = "";
	var $f16 = "";
	var $f17 = "";
	var $f18 = "";
	var $f19 = "";
	var $f20 = "";

	function Person ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "person";
		$this->_fieldlist = array("userid", "pkey", "importid", "lastimport", "type", "deleted",
									"f01", "f02", "f03", "f04", "f05", "f06", "f07", "f08", "f09", "f10",
									"f11", "f12", "f13", "f14", "f15", "f16", "f17", "f18", "f19", "f20");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	// $arg may be an either an instaceof Person, or just the personid.
	static function getFullName($arg) {
		$person = ($arg instanceof Person) ? $arg : new Person($arg);

		$fnamefield = FieldMap::getFirstNameField();
		$lnamefield = FieldMap::getLastNameField();

		return $person->$fnamefield . ' ' . $person->$lnamefield;
	}

	static function findPerson($custid,$key) {
		$query = "select id from person where pkey=? and not deleted";
		$id = QuickQuery($query, false, array($key));

		if ($id)
			return new Person($id);
		else
			return false;
	}
	
	// Returns a subquery on the 'person' or 'reportperson' table depending on $isjobreport.
	// Takes into consideration the provided organizations/sections and user organization/section restrictions.
	// If $sectionids is specified, then $organizationids is ignored because the section is more restrictive.
	// If $isjobreport is true, then use the 'reportperson' table, otherwise use the 'person' table.
	static function makePersonSubquery($organizationids = false, $sectionids = false, $isjobreport = false) {
		global $USER;
		
		static $associatedorganizationids = false;
		static $associatedsectionids = false;
		
		if ($associatedorganizationids === false) {
			// TODO: Need to make sure organization is not deleted?
			$associatedorganizationids = QuickQueryList("select organizationid from userassociation where type='organization' and userid = ?", false, false, array($USER->id));
		}
		
		if ($associatedsectionids === false) {
			$associatedsectionids = QuickQueryList("select sectionid from userassociation where type='section' and userid = ?", false, false, array($USER->id));
		}
		
		$findorganizations = count($associatedorganizationids) > 0 || ($organizationids && count($organizationids) > 0);
		$findsections = count($associatedsectionids) > 0 || ($sectionids && count($sectionids) > 0);
		
		$persontablename = $isjobreport ? 'reportperson' : 'person';
		$personidfield = $isjobreport ? 'personid' : 'id';
		
		if ($findorganizations || $findsections) {
			$sql = "(
				select {$persontablename}.*
				from {$persontablename}
					inner join personassociation pa on ({$persontablename}.{$personidfield} = pa.personid)
				where " . ($isjobreport ? "" : "not {$persontablename}.deleted and ");
				
			if ($findsections) {
				if ($sectionids)
					$combinedsectionids = count($associatedsectionids) > 0 ? array_intersect($sectionids, $associatedsectionids) : $sectionids;
				else
					$combinedsectionids = $associatedsectionids;
				
				if (count($combinedsectionids) > 0)
					$sql .= "pa.sectionid in (" . implode(",", $combinedsectionids) . ")";
				else
					$sql .= "0";
			} else {
				if ($organizationids)
					$combinedorganizationids = count($associatedorganizationids) > 0 ? array_intersect($organizationids, $associatedorganizationids) : $organizationids;
				else
					$combinedorganizationids = $associatedorganizationids;
				
				if (count($combinedorganizationids) > 0)
					$sql .= "pa.organizationid in (" . implode(",", $combinedorganizationids) . ")";
				else
					$sql .= "0";
			}
			
			$sql .= ")"; // Closing parenthesis for the subquery.
		} else {
			$sql = "{$persontablename}";
		}
		
		return $sql;
	}

	function getAddress () {
		return DBFind("Address", "from address where personid = '" . $this->id . "'");
	}

	function getPhones () {
		return DBFindMany("Phone", "from phone where personid = '" . $this->id . "'");
	}

	function getEmails () {
		return DBFindMany("Email", "from email where personid = '" . $this->id . "'");
	}

	function getSmses () {
		return DBFindMany("Sms", "from sms where personid = '" . $this->id . "'");
	}

}

?>