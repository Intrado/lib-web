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

	static function getSeparatorFieldMap($i) {
		$fieldmap = new FieldMap();
		$fieldmap->fieldnum = "sep".$i;
		$fieldmap->name = "----------";
		$fieldmap->options = "searchable,multisearch,disabled";
		return $fieldmap;
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

	static function getGradeField(){
		return QuickQuery("select fieldnum from fieldmap where options like '%grade%'");
	}

	// NOTE 'school' moved from Ffield to Gfield in release 6.1
	static function getSchoolField(){
		return QuickQuery("select fieldnum from fieldmap where options like '%school%'");
	}

	static function getStaffField(){
		$field = QuickQuery("select fieldnum from fieldmap where options like '%staff%'");
		if(!$field)
			$field = "c01";
		return $field;
	}

	static function getMapNames () {
		return FieldMap::getMapNamesLike("f%");
	}

	static function getMapNamesLike ($likewhat) {
		global $USER;
		$map = array();
		$query = "select name, fieldnum from fieldmap where fieldnum like '".$likewhat."' order by fieldnum";
		if ($result = Query($query)) {
			while ($row = DBGetRow($result)) {
				$map[$row[1]] = $row[0];
			}
		}
		return $map;
	}

	static function getAuthorizedMapNames () {
		return FieldMap::getAuthorizedMapNamesLike("f%");
	}

	static function getAuthorizedMapNamesLike ($likewhat) {
		global $USER;
		$map = array();
		$query = "select name, fieldnum from fieldmap where fieldnum like '".$likewhat."' order by fieldnum";
		if ($result = Query($query)) {
			while ($row = DBGetRow($result)) {
				if($USER->authorizeField($row[1]))
					$map[$row[1]] = $row[0];
			}
		}
		return $map;
	}

	static function getAuthorizedFieldMaps () {
		return FieldMap::getAuthorizedFieldMapsLike("f%");
	}

	static function getAuthorizedFieldMapsLike ($likewhat) {
		global $USER;
		$query = "from fieldmap where fieldnum like '".$likewhat."' order by fieldnum";
		$fieldmaps = DBFindMany("FieldMap", $query);
		foreach($fieldmaps as $key => $fieldmap)
			if(!$USER->authorizeField($fieldmap->fieldnum))
				unset($fieldmaps[$key]);
		return $fieldmaps;
	}

	// only return F-fields other than first/last name (do not return C-fields)
	static function getOptionalAuthorizedFieldMaps(){
		return FieldMap::getOptionalAuthorizedFieldMapsLike("f%");
	}
	static function getOptionalAuthorizedFieldMapsLike($likewhat){
		$fieldmaps = FieldMap::getAuthorizedFieldMapsLike($likewhat);
		foreach($fieldmaps as $index => $fieldmap){
			if($fieldmap->isOptionEnabled("firstname") || $fieldmap->isOptionEnabled("lastname")) {
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

			switch ($fieldnum[0]) {
				case "f" :
				$query = "insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "p.$fieldnum as value, "
							. "count(*) "
							. "from person p "
							. "where not p.deleted and p.type = 'system' "
							. "group by value";
				break;
				case "g" :
				$query = "insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "gd.value as value, "
							. "count(*) "
							. "from groupdata gd "
							. "where fieldnum=" . substr($fieldnum,1) . " "
							. "group by value";
				break;
				case "c" :
				// nothing, handled via enrollment import only
				break;
			}
			$count = QuickUpdate($query);
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