<?

/*
key = person.key
f01-20 persondata.f01-20
e1-2 = email sequence 1-2
a1-6 = address parts
p1-4 = phone sequence 1-5
*/

class FieldMap extends DBMappedObject {

	var $customerid;
	var $fieldnum;
	var $name;
	var $options;

	var $optionsarray = false;

	function FieldMap ($id = NULL) {
		$this->_tablename = "fieldmap";
		$this->_fieldlist = array("customerid", "fieldnum", "name","options");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function getFirstNameField() {
		return 'f01';
	}

	static function getLastNameField() {
		return 'f02';
	}

	static function getLanguageField() {
		return 'f03';
	}

	static function getMapNames () {
		global $USER;
		$map = array();
		$query = "select name,fieldnum from fieldmap where customerid='" . $USER->customerid . "'"
				." order by fieldnum";
		if ($result = Query($query)) {
			while ($row = DBGetRow($result)) {
				$map[$row[1]] = $row[0];
			}
		}
		return $map;
	}

	static function getAuthorizedMapNames () {
		global $USER;
		$map = array();
		$query = "select name,fieldnum from fieldmap where customerid='" . $USER->customerid . "'"
				." order by fieldnum";
		if ($result = Query($query)) {
			while ($row = DBGetRow($result)) {
				if($USER->authorizeField($row[1]))
					$map[$row[1]] = $row[0];
			}
		}
		return $map;
	}

	static function getAuthorizedFieldMaps () {
		global $USER;
		$fieldmaps = DBFindMany("FieldMap", "from fieldmap where customerid='" . $USER->customerid . "'");
		foreach($fieldmaps as $key => $fieldmap)
			if(!$USER->authorizeField($fieldmap->fieldnum))
				unset($fieldmaps[$key]);
		return $fieldmaps;
	}

	static function getName ($fieldnum) {
		global $USER;
		$query = "select name from fieldmap where customerid='" . $USER->customerid . "'"
				." and fieldnum = '$fieldnum'";
		return QuickQuery($query);
	}

	function isOptionEnabled ($name) {
		if (!$this->optionsarray) {
			$this->optionsarray = explode(",",$this->options);
		}

		return (in_array($name, $this->optionsarray));
	}
}

?>