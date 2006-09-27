<?


class Rule extends DBMappedObject {
	var $logical;
	var $fieldnum;
	var $op;
	var $val;

	function Rule ($id = NULL) {
		$this->_tablename = "rule";
		$this->_fieldlist = array("logical", "fieldnum", "op", "val");
		//call super's constructor
		DBMappedObject::DBMappedObject($id);
	}

	function makeQuery ($rulesarray, $alias, $fieldoverride = false) {
		$query = "";
		if(is_array($rulesarray))
			foreach ($rulesarray as $rule)
				$query .= $rule->toSql($alias, $fieldoverride);
		return $query;
	}

	function toSql ($alias = false, $fieldoverride = false) {
		$val = DBSafe($this->val);
		$sql = " " . $this->logical . " ";

		$f = ($alias ? "$alias.":"") . ($fieldoverride ? $fieldoverride : $this->fieldnum);

		switch ($this->op) {
			case "eq":
				$sql .= "$f='$val'";
				break;
			case "ne":
				$sql .= "$f!='$val'";
				break;
			case "gt":
				$sql .= "$f>'$val'";
				break;
			case "ge":
				$sql .= "$f>='$val'";
				break;
			case "lt":
				$sql .= "$f<'$val'";
				break;
			case "le":
				$sql .= "$f<='$val'";
				break;
			case "lk":
				$sql .= "$f like '$val'";
				break;
			case "sw":
				$sql .= "$f like '$val%'";
				break;
			case "ew":
				$sql .= "$f like '%$val'";
				break;
			case "cn":
				$sql .= "$f like '%$val%'";
				break;
			case "in":

				//split the values
				$values = explode("|",$this->val);
				if (count($values) > 0) {

					$sql .= "(";

					//make the values safe
					$safevales = array();
					foreach ($values as $index => $val) {
						$safevales[$index] = DBSafe($val);
					}
					//make a big or group of the possible values
					$sql .= "($f='" . implode($safevales, "' or $f='") . "')";

					//also check for nulls/blanks
					$nullvalues = array();
					foreach ($values as $val) {
						if ($val === "") {
							$sql .= " or $f is null";
							break;
						}
					}


					$sql .= ")";
				} else {
					$sql .= "0";
				}

				break;
			case "insubstr":
				//split the values
				$values = explode("|",$this->val);
				if (count($values) > 0) {
					//make the values safe
					foreach ($values as $index => $val) {
						$values[$index] = DBSafe($val);
					}
					//make a big or group of the possible values
					$sql .= "($f like'%" . implode($values, "%' or $f like '%") . "%')";
				} else {
					$sql .= "0";
				}


				break;
			case "reldate":
				$reldate = reldate($this->val);
				$sql .= "$f='$reldate'";
				break;
			default:
				$sql = "and 0"; //always default on the safe side
		}

		return $sql;
	}
}


$RULE_OPERATORS = array(
				'equals' => 'eq',
				'does not equal' => 'ne',
//				'is greater than' => 'gt',
//				'is greater than or equal to' => 'ge',
//				'is less than' => 'lt',
//				'is less than or equal to' => 'le',
				'is like' => 'lk',
				'starts with' => 'sw',
				'ends with' => 'ew',
				'contains' => 'cn',
				'is in' => 'in');
?>