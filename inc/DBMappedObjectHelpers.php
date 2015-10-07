<?

function generateFieldList ($includeid = false, $fieldlist = NULL, $alias = false) {
	if ($includeid) {
		$list = array("id");
		$list = array_merge($list,$fieldlist);
	} else {
		$list = $fieldlist;
	}
	if ($alias) {
		return "`$alias`.`" . implode("`,`$alias`.`", $list) . "`";
	} else {
		return "`" . implode("`,`", $list) . "`";
	}
}

/**
 * @param string $classname
 * @param string $query from ... where ...
 * @param string $alias
 * @param array() $args
 * @param bool $dbconnect
 * @return DBMappedObject[]|bool
 */
function DBFindMany ($classname, $query, $alias = false, $args = false, $dbconnect = false, $distinct = false) {
	return _DBFindPDO(true, $classname, $query, $alias, $args, $dbconnect, $distinct);
}

function DBFind ($classname, $query, $alias = false, $args = false, $dbconnect = false, $distinct = false) {
	return _DBFindPDO(false, $classname, $query, $alias, $args, $dbconnect, $distinct);
}

function _DBFindPDO($isMany, $classname, $query, $alias=false, $args=false, $dbconnect = false, $distinct = false) {
	//make a dummy object of this to get the field list
	$dummy = new $classname();

	$many = array();

	$query = "select " . ($distinct ? 'distinct ' : '') . generateFieldList(true,$dummy->_fieldlist,$alias) ." ". $query;
	if ($result = Query($query, $dbconnect, $args)) {
		while ($row = DBGetRow($result)) {
			$newobj = new $classname();

			$newobj->id = $row[0];

			foreach ($dummy->_fieldlist as $index => $field) {
				if ($dummy->_allownulls && $row[$index+1] === NULL)
					$newobj->$field = NULL;
				else
					$newobj->$field = ($row[$index+1]);
			}

			$many[$newobj->id] = $newobj;
			if (!$isMany) break;
		}
	}
	if ($isMany) {
		return $many;
	} else {
		if (count($many) == 0)
			return false;
		// else return first row object
		return $many[$newobj->id];
	}
}

function cleanObjects ($obj) {
	if (!is_object($obj) && !is_array($obj))
		return $obj;
	$simpleObj = array();
	if (is_object($obj)) {
		foreach ($obj->_fieldlist as $field) {
			if (is_object($obj->$field))
				$simpleObj[$field] = cleanObjects($obj->$field);
			else
				$simpleObj[$field] = $obj->$field;
		}
	} else if (is_array($obj)) {
		foreach ($obj as $id => $item)
			$simpleObj[$id] = cleanObjects($item);
	}
	return $simpleObj;
}

?>
