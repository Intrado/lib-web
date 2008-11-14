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
				$personsql = ($alias ? "and g.personid=$aliasid" : "");
				$sql .= "exists (select null from reportgroupdata g where g.fieldnum=".substr($this->fieldnum,1)." $personsql and g.jobid=j.id and ";
			} else {
				$aliasid = ($alias ? $alias . ".id" : "id");
				$personsql = ($alias ? "and g.personid=$aliasid" : "");
				$sql .= "exists (select null from groupdata g where g.fieldnum=".substr($this->fieldnum,1)." $personsql and ";
			}
			//override f to point to g field data
			$f = "g.value";
		}

		//now put together the value checks
		switch ($this->op) {
			//text
			case "eq":
				$sql .= "$f='$val'";
				break;
			case "ne":
				$sql .= "$f!='$val'";
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

			//numeric
			case "num_eq":
				$sql .= "($f regexp '[0-9]' and replace($f,'$','')=" . ($val + 0.0) . ")";
				break;
			case "num_ne":
				$sql .= "($f regexp '[0-9]' and replace($f,'$','')!=" . ($val + 0.0) . ")";
				break;
			case "num_gt":
				$sql .= "($f regexp '[0-9]' and replace($f,'$','')>" . ($val + 0.0) . ")";
				break;
			case "num_ge":
				$sql .= "($f regexp '[0-9]' and replace($f,'$','')>=" . ($val + 0.0) . ")";
				break;
			case "num_lt":
				$sql .= "($f regexp '[0-9]' and replace($f,'$','')<" . ($val + 0.0) . ")";
				break;
			case "num_le":
				$sql .= "($f regexp '[0-9]' and replace($f,'$','')<=" . ($val + 0.0) . ")";
				break;
			case "num_range":
				$values = explode("|",$this->val);
				$s = min($values[0] + 0.0,$values[1] + 0.0);
				$b = max($values[0] + 0.0,$values[1] + 0.0);
				$sql .= "($f regexp '[0-9]' and replace($f,'$','') between $s and $b)";
				break;
				
			//multisearch
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
			//date
			case "reldate":
				$reldate = reldate($this->val);
				$sql .= "$f='$reldate'";
				break;
			case "date_range":
				$values = explode("|",$this->val);
				$d1 = date('Y-m-d',strtotime($values[0]));
				$d2 = date('Y-m-d',strtotime($values[1]));
				$sql .= "(str_to_date($f, '%m/%d/%Y') between '$d1' and '$d2')";
				break;
			case "date_offset":
				$sql .= "$f=date_format(now() + interval " . ($this->val + 0) . " day,'%m/%d/%Y')";
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
	"text" => array (
		'eq' => 'equals',
		'ne' => 'does not equal',
		'sw' => 'starts with',
		'ew' => 'ends with',
		'cn' => 'contains'
	),
	"numeric" => array (
		'num_eq' => 'equals',
		'num_ne' => 'does not equal',
		'num_gt' => 'is greater than',
		'num_ge' => 'is greater than or equal to',
		'num_lt' => 'is less than',
		'num_le' => 'is less than or equal to',
		'num_range' => 'is between (inclusive)'
	),
	"reldate" => array (
		'reldate' => 'relative date',
		'date_offset' => 'is offset from today by (days)',
		'eq' => 'equals',
		'date_range' => 'is between (inclusive)'
	),
	"multisearch" => array (
		'in' => 'is in'
	)
);

?>
