<?

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
	
	static function getSubscriberOrganizationFieldMap() {
		$fieldmap = new FieldMap();
		$fieldmap->fieldnum = "oid";
		$fieldmap->name = getSystemSetting("organizationfieldname","Organization");
		$fieldmap->options = "searchable,multisearch,subscribe,static";
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

	static function getFieldnumWithOption($option, $default = null) {
		$results = FieldMap::retrieveFieldMaps();

		foreach($results as $fieldnum => $fieldmap) {
			if (strpos($fieldmap->options, $option) !== false)
				return $fieldnum;
		}
		
		return $default;
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
	
	//returns array of names available for use in data field inserts in messages, both authorized and non authorized
	static function getFieldInsertNames () {
		return array_merge(FieldMap::getMapNamesLike('f'), FieldMap::getMapNamesLike('$'));
	}

	//returns array of authorized names available for use in data field inserts in messages
	static function getAuthorizeFieldInsertNames () {
		return array_merge(FieldMap::getAuthorizedMapNamesLike('f'), FieldMap::getMapNamesLike('$'));
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
			if ($fieldnum[0] !== $firstletter || !$USER->authorizeField($fieldnum))
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

	static function getName ($fieldnum) {
		$fieldmaps = FieldMap::retrieveFieldMaps();
		return $fieldmaps[$fieldnum]->name;
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
	
	
	static function getSystemVarValue($fieldnum) {
		switch ($fieldnum) {
			case "\$d01":
				return date("m/d/Y");
			case "\$d02":
				return date("m/d/Y", strtotime("tomorrow"));
			case "\$d03":
				return date("m/d/Y", strtotime("yesterday"));
		}
	}
	
	
	function updatePersonDataValues () {

		if ($this->isOptionEnabled("searchable") &&
			$this->isOptionEnabled("multisearch")) {

			$fieldnum = $this->fieldnum;

			// editlock=1 are subscriber static values
			$query = "delete from persondatavalues where fieldnum='$fieldnum' and editlock=0";
			QuickUpdate($query);

			$existingvalues = QuickQueryList("select value from persondatavalues where fieldnum=?", false, false, array($fieldnum));
			if (count($existingvalues) > 0) {
				$args = "?";
				$args .= str_repeat(",?", count($existingvalues)-1);
			}
			switch ($fieldnum[0]) {
				case "f" :
				$query = "insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "p.$fieldnum as value, "
							. "count(*) "
							. "from person p "
							. "where not p.deleted and p.type = 'system' "
							. "group by value";
				if (count($existingvalues) > 0) {
					$query = "insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "p.$fieldnum as value, "
							. "count(*) "
							. "from person p "
							. "where p.$fieldnum not in ($args) and not p.deleted and p.type = 'system' "
							. "group by value";
					
					$upquery = "update persondatavalues pdv "
							. "set refcount = (select count(*) from person p where p.$fieldnum = pdv.value "
							. "and not p.deleted and p.type = 'system') "
							. "where pdv.fieldnum='$fieldnum' "
							. "and pdv.value in ($args)";
					QuickUpdate($upquery, false, $existingvalues);
				}
				break;
				case "g" :
				$query = "insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "gd.value as value, "
							. "count(*) "
							. "from groupdata gd "
							. "where fieldnum=" . substr($fieldnum,1) . " "
							. "group by value";
				if (count($existingvalues) > 0) {
					$query = "insert into persondatavalues (fieldnum,value,refcount) "
							. "select '$fieldnum' as fieldnum, "
							. "gd.value as value, "
							. "count(*) "
							. "from groupdata gd "
							. "where fieldnum=" . substr($fieldnum,1)
							. " and value not in ($args) "
							. "group by value";
							
					$upquery = "update persondatavalues pdv "
							. "set refcount = (select count(*) from groupdata gd where gd.fieldnum='" . substr($fieldnum,1) . "' and gd.value = pdv.value) "
							. "where pdv.fieldnum='$fieldnum' and pdv.value in ($args)";
					QuickUpdate($upquery, false, $existingvalues);
				}
				break;
				case "c" :
				// nothing, handled via enrollment import only
				break;
			}
			if (count($existingvalues) > 0)
				$count = QuickUpdate($query, false, $existingvalues);
			else
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
	
	function updateFieldType($newtype) {
		// remove all types, then add the new type
		$this->removeOption('text');
		$this->removeOption('multisearch');
		$this->removeOption('reldate');
		$this->removeOption('numeric');
		$this->addOption($newtype);
	}
}

?>
