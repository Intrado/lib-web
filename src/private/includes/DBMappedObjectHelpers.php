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
function DBFindMany($classname, $query, $alias = false, $args = false, $dbconnect = false, $distinct = false, $calcFoundRows = false) {
	return _DBFindPDO(true, $classname, $query, $alias, $args, $dbconnect, $distinct, $calcFoundRows);
}

function DBFind ($classname, $query, $alias = false, $args = false, $dbconnect = false, $distinct = false) {
	return _DBFindPDO(false, $classname, $query, $alias, $args, $dbconnect, $distinct);
}

function _DBFindPDO($isMany, $classname, $select, $alias = false, $args = false,
					$dbconnect = false, $distinct = false, $calcFoundRows = false) {
	//make a dummy object of this to get the field list
	$dummy = new $classname();

	$many = array();

	$query = "SELECT ";
	if ($calcFoundRows) {
		$query .= "SQL_CALC_FOUND_ROWS ";
	}
	if ($distinct) {
		$query .= "DISTINCT ";
	}
	$query .= generateFieldList(true,$dummy->_fieldlist,$alias) ." ". $select;
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

/**
 * @param $obj
 * @param array $options Indexed array with keys represent options. Such as
 *        'iso-dates' => Array(names...), specifies date fields should be converted to iso 8601-- used in API mode.
 *        'inject-id'=> true injects id field in resulting object.
 * @return array
 */
function cleanObjects($obj, $options = array()) {
	// Init with default values...
	//
	$cleanDatesFields = null;
	$injectId = false;

    if (is_array($options)) {
		$injectId = array_key_exists('inject-id', $options) && $options['inject-id'];

        if (array_key_exists('iso-dates', $options)) {
	        $cleanDatesFields = $options['iso-dates'];
        }
    }

    if (!is_object($obj) && !is_array($obj))
        return $obj;
    $simpleObj = array();
    if (is_object($obj)) {
        foreach ($obj->_fieldlist as $field) {
			// inject the id as first key in json object
			if($injectId && isset($obj->id)) {
				$simpleObj['id'] = 0+($obj->id);
			}

			if (is_object($obj->$field))
                $simpleObj[$field] = cleanObjects($obj->$field, $options);
            else {
                if ($cleanDatesFields && in_array($field, $cleanDatesFields)) {
                    $simpleObj[$field] = date(DATE_ISO8601, strtotime($obj->$field));
                } else {
                    $simpleObj[$field] = $obj->$field;
                }
            }
        }
    } else if (is_array($obj)) {
        foreach ($obj as $id => $item)
            $simpleObj[$id] = cleanObjects($item, $options);
    }
    return $simpleObj;
}