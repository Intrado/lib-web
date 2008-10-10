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

	function toSql ($alias = false, $fieldoverride = false, $isjobreport = false) {
		$val = DBSafe($this->val);
		$f = ($alias ? "$alias.":"") . ($fieldoverride ? $fieldoverride : $this->fieldnum);

		$sql = " " . $this->logical . " ";

		//check if this needs a subquery
		if (strpos($this->fieldnum, "g") === 0) {
			if ($isjobreport) {
				switch ($alias) {
					case "rp" :
					$aliasid = "rp.personid";
					break;
					case "p" :
					$aliasid = "p.id";
					break;
					default:
					$aliasid = ($alias ? $alias . ".id" : "id");
					break;
				}
				$sql .= "exists (select null from reportgroupdata g where g.fieldnum=".substr($this->fieldnum,1)." and g.personid=$aliasid and g.jobid=j.id and ";
			} else {
				$aliasid = ($alias ? $alias . ".id" : "id");
				$sql .= "exists (select null from groupdata g where g.fieldnum=".substr($this->fieldnum,1)." and g.personid=$aliasid and ";
			}
			//override f to point to g field data
			$f = "g.value";
		}

		//now put together the value checks
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
				$sql = " and 0 "; //always default on the safe side
		}

		//see if we need to close off a subquery parens
		if (strpos($this->fieldnum, "g") === 0) {
			$sql .= ")";
		}

		return $sql;
	}

	/**static functions**/

	//isreport flags that we need to join differently for group fields
	static function makeQuery ($rulesarray, $alias, $fieldoverride = false, $isreport = false) {
		$fquery = ""; // ffield
		$gquery = ""; // gfield
		$cquery = ""; // cfield
		if(is_array($rulesarray)) {
			foreach ($rulesarray as $rule) {
				if (strpos($rule->fieldnum, "f") === 0) {
					$fquery .= $rule->toSql($alias, $fieldoverride);
				}
				if (strpos($rule->fieldnum, "g") === 0) {
					//field override not used, but we still need to pass any alaias we're using for person record
					$gquery .=  $rule->toSql($alias, $fieldoverride, $isreport);
				}
				if (strpos($rule->fieldnum, "c") === 0) {
					if ($cquery == "") $cquery = " and exists (select null from enrollment a where ".$alias.".id=a.personid ";
					$cquery .= $rule->toSql("a");
				}
			}
		}
		if ($cquery != "") $cquery .= ")";

//echo "SQL " . $fquery . $gquery . $cquery;
		return $fquery . $gquery . $cquery;
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
