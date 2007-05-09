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
									"f11", "f12", "f13", "f14", "f15", "f16", "f17", "f18", "f19");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function findPerson($custid,$key) {
		$query = "select id from person where pkey='" . DBSafe($key) . "'";
		$id = QuickQuery($query);

		if ($id)
			return new Person($id);
		else
			return false;
	}

	function getAddress () {
		$query = "select id from address a where a.personid='" . $this->id . "'";
		$id = QuickQuery($query);
		if ($id)
			return new Address($id);
		else
			return false;
	}

	function getPhones () {
		$query = "select id,sequence from phone p where p.personid='" . $this->id . "'";
		$result = Query($query);

		$phones = array();
		while ($row = DBGetRow($result)) {
			$phones[$row[1]] = new Phone($row[0]);
		}

		return $phones;
	}

	function getEmails () {
		$query = "select id,sequence from email e where e.personid='" . $this->id . "'";
		$result = Query($query);

		$emails = array();
		while ($row = DBGetRow($result)) {
			$emails[$row[1]] = new Email($row[0]);
		}

		return $emails;
	}

}

?>