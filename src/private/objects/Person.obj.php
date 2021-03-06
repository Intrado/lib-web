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

	function getSetting ($name, $defaultvalue = false, $refresh = false) {
		static $settings = null;

		if ($settings === null || $refresh) {
			$settings = array();
			if ($res = Query("select name,value from personsetting where personid='$this->id'")) {
				while ($row = DBGetRow($res)) {
					$settings[$row[0]] = $row[1];
				}
			}
		}

		if (isset($settings[$name]))
			return $settings[$name];
		else
			return $defaultvalue;
	}

	function setSetting ($name, $value) {
		$old = $this->getSetting($name,false,true);

		if ($old === false) {
			$settings[$name] = $value;
			if ($value)
				QuickUpdate("insert into personsetting (personid,name,value) values (?, ?, ?)",
					false, array($this->id, $name, $value));
		} else {
			if ($value !== false && $value !== '' && $value !== null) {
				QuickUpdate("update personsetting set value=? where personid=? and name=?",
					false, array($value, $this->id, $name));
			} else {
				QuickUpdate("delete from personsetting where personid=? and name=?",
					false, array($this->id, $name));

			}
		}
	}

}

?>