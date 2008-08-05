<?

/*
key = person.key
f01-20 persondata.f01-20
e1-2 = email sequence 1-2
a1-6 = address parts
p1-4 = phone sequence 1-5
*/

class FieldMap extends DBMappedObject {

	var $fieldnum;
	var $name;
	var $options;

	var $optionsarray = false;

	function FieldMap ($id = NULL) {
		$this->_tablename = "fieldmap";
		$this->_fieldlist = array("fieldnum", "name","options");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	static function getFirstNameField() {
		$field = QuickQuery("select fieldnum from fieldmap where options like '%firstname%'");
		if(!$field)
			$field = "f01";
		return $field;
	}

	static function getLastNameField() {
		$field = QuickQuery("select fieldnum from fieldmap where options like '%lastname%'");
		if(!$field)
			$field = "f02";
		return $field;
	}

	static function getLanguageField() {
		$field = QuickQuery("select fieldnum from fieldmap where options like '%language%'");
		if(!$field)
			$field = "f03";
		return $field;
	}

	static function getSchoolField(){
		return QuickQuery("select fieldnum from fieldmap where options like '%school%'");
	}
	static function getGradeField(){
		return QuickQuery("select fieldnum from fieldmap where options like '%grade%'");
	}
	static function getStaffField(){
		return QuickQuery("select fieldnum from fieldmap where options like '%staff%'");
	}

	static function getMapNames () {
		global $USER;
		$map = array();
		$query = "select name,fieldnum from fieldmap"
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
		$query = "select name,fieldnum from fieldmap"
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
		$fieldmaps = DBFindMany("FieldMap", "from fieldmap order by fieldnum");
		foreach($fieldmaps as $key => $fieldmap)
			if(!$USER->authorizeField($fieldmap->fieldnum))
				unset($fieldmaps[$key]);
		return $fieldmaps;
	}

	static function getOptionalAuthorizedFieldMaps(){
		$fieldmaps = FieldMap::getAuthorizedFieldMaps();
		foreach($fieldmaps as $index => $fieldmap){
			if($fieldmap->isOptionEnabled("firstname") || $fieldmap->isOptionEnabled("lastname")){
				unset($fieldmaps[$index]);
			}
		}
		return $fieldmaps;
	}

	static function getName ($fieldnum) {
		global $USER;
		$query = "select name from fieldmap where "
				." fieldnum = '$fieldnum'";
		return QuickQuery($query);
	}

	function updatePersonDataValues () {

		if ($this->isOptionEnabled("searchable") &&
			$this->isOptionEnabled("multisearch")) {

			$fieldnum = $this->fieldnum;

			$query = "delete from persondatavalues where fieldnum='$fieldnum '";
			QuickUpdate($query);

			$count = QuickUpdate("insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "p.$fieldnum as value, "
							. "count(*) "
							. "from person p "
							. "where not p.deleted and p.type = 'system' "
							. "group by value");
		}
	}



	function isOptionEnabled ($name) {
		if (!$this->optionsarray) {
			$this->optionsarray = explode(",",$this->options);
		}

		return (in_array($name, $this->optionsarray));
	}
}

?>