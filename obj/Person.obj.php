<?

class Person extends DBMappedObject {

	var $customerid;
	var $userid;
	var $pkey;
	var $importid;
	var $lastimport;
	var $type = "system"; // enum (system, addressbook, manualadd, upload)
	var $deleted = 0;

	function Person ($id = NULL) {
		$this->_allownulls = true;
		$this->_tablename = "person";
		$this->_fieldlist = array("customerid", "userid", "pkey", "importid", "lastimport", "type", "deleted");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function findPerson($custid,$key) {
		$query = "select id from person where customerid='$custid' and pkey='" . DBSafe($key) . "'";
		$id = QuickQuery($query);

		if ($id)
			return new Person($id);
		else
			return false;
	}

	function getData () {
		return DBFind('PersonData', "from persondata where personid=$this->id");
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