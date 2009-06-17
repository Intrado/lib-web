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
		return FieldMap::getFieldnumWithOption('firstname', 'f01');
	}

	static function getLastNameField() {
		return FieldMap::getFieldnumWithOption('lastname', 'f02');
	}

	static function getLanguageField() {
		return FieldMap::getFieldnumWithOption('language', 'f03');
	}

	static function getGradeField(){
		return FieldMap::getFieldnumWithOption('grade');
	}

	// NOTE 'school' moved from Ffield to Gfield in release 6.1
	static function getSchoolField(){
		return FieldMap::getFieldnumWithOption('school');
	}

	static function getStaffField(){
		return FieldMap::getFieldnumWithOption('staff', 'c01');
	}

	static function getFieldnumWithOption($option, $default = null) {
		$results = FieldMap::retrieveFieldMaps();

		foreach($results as $fieldnum => $fieldmap) {
			if (strpos($fieldmap->options, $option) !== false)
				return $fieldnum;
		}
		
		return $default;
	}
	
	static function getMapNames () {
		return FieldMap::getMapNamesLike('f');
	}

	static function getMapNamesLike ($firstletter, $authorized = false) {
		global $USER;
		
		$results = FieldMap::retrieveFieldMaps();
		
		$map = array();
		foreach($results as $fieldnum => $fieldmap) {
			if ($fieldnum[0] === $firstletter) {
				if ($authorized && !$USER->authorizeField($fieldnum))
					continue;
				$map[$fieldnum] = $fieldmap->name;
			}
		}
		return $map;
	}

	static function getAuthorizedMapNames () {
		return FieldMap::getAuthorizedMapNamesLike('f');
	}

	// Returns Associative Array of fieldnum => name
	static function getAuthorizedMapNamesLike ($firstletter) {
		return FieldMap::getMapNamesLike($firstletter, true);
	}
	
	// Returns Associative Array of fieldnum => name
	static function getSubscribeMapNames() {
		$results = FieldMap::retrieveFieldMaps();
		
		$map = array();
		foreach($results as $fieldnum => $fieldmap) {
			if (strpos($fieldmap->options, 'subscribe') !== false)
				$map[$fieldnum] = $fieldmap->name;
		}
		return $map;
	}
	
	// Gets only F fields
	// Returns Associative Array, indexed by fieldnum
	static function getAuthorizedFieldMaps () {
		return FieldMap::getAuthorizedFieldMapsLike('f');
	}
	
	// Gets F,G,C fields.
	// Returns Associative Array, indexed by fieldnum
	static function getAllAuthorizedFieldMaps ($onlysearchable = true) {
		global $USER;
		
		$fieldmaps = FieldMap::retrieveFieldMaps();
		foreach($fieldmaps as $fieldnum => $fieldmap) {
			if (!$USER->authorizeField($fieldnum) || ($onlysearchable && strpos($fieldmap->options, 'searchable') === false))
				unset($fieldmaps[$fieldnum]);
		}
		return $fieldmaps;
	}

	// Returns an associative array, indexed by fieldnum.
	static function getAuthorizedFieldMapsLike ($firstletter) {
		global $USER;
	
		$fieldmaps = FieldMap::retrieveFieldMaps();
		foreach($fieldmaps as $fieldnum => $fieldmap)
			if (!$USER->authorizeField($fieldnum) || $fieldnum[0] !== $firstletter)
				unset($fieldmaps[$fieldnum]);
		return $fieldmaps;
	}

	// only return F-fields other than first/last name (do not return C-fields)
	static function getOptionalAuthorizedFieldMaps(){
		return FieldMap::getOptionalAuthorizedFieldMapsLike('f');
	}
	static function getOptionalAuthorizedFieldMapsLike($firstletter){
		$fieldmaps = FieldMap::getAuthorizedFieldMapsLike($firstletter);
		foreach($fieldmaps as $index => $fieldmap){
			if($fieldmap->isOptionEnabled("firstname") || $fieldmap->isOptionEnabled("lastname")) {
				unset($fieldmaps[$index]);
			}
		}
		return $fieldmaps;
	}

	// Returns an indexed array
	static function getName ($fieldnum) {
		global $USER;
		
		$results = FieldMap::retrieveFieldMaps();
		
		$names = array();
		foreach ($results as $fieldmap)
			$names[] = $fieldmap->name;
		return $names;
	}

	// Returns an associative array, indexed by fieldnum.
	static function retrieveFieldMaps() {
		static $fieldmapscache = false;
		
		if (!$fieldmapscache) {
			$results = DBFindMany('FieldMap', 'from fieldmap order by fieldnum');
			$fieldmapscache = array();			
			foreach ($results as $fieldmap)
				$fieldmapscache[$fieldmap->fieldnum] = $fieldmap;
		}
		
		return $fieldmapscache;
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
	
	function addOption ($name) {
		if ($this->isOptionEnabled($name))
			return; // already added
		
		$this->options = $this->options . "," . $name;
		$this->optionsarray = explode(",",$this->options);
	}
	
	function removeOption ($name) {
		if (!$this->isOptionEnabled($name))
			return; // already removed
		
		$len = strlen($name);
		$pos = strpos($this->options, $name);
		if ($pos != 0) {
			$pos = $pos -1; // the comma
			$len = $len +1;
		}
			
		$this->options = substr($this->options, 0, $pos) . substr($this->options, $pos+$len);
		$this->optionsarray = explode(",",$this->options);
	}
}

?>