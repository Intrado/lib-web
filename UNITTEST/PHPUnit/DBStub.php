<?php

/**
 * Stub out the objects/functions for various database operations
 *
 * Note that we don't want to spend a bunch of time simulating a database, we just want the function calls
 * to return something the way they might normally without causing an execution error.
 *
 */

class QueryRules {
	private $rules;

	/**
	 * Constructor, nothing fancy
	 */
	public function QueryRules() {
		$this->reset();
	}

	/**
	 * Reset all the rules; should be done at the start of
	 * a new test, or at least the start of a new test class
	 */
	public function reset() {
		$this->rules = array();
	}

	/**
	 * Add a query rule with the supplied pattern and data
	 *
	 * add($pattern, $data);
	 * add($pattern, $args, $data);
	 *
	 * @param string $pattern Regex pattern to match against $query in the
	 * apply function
	 * @param array $data Multidimensional array of query result data we
	 * want the apply function to return whenever this pattern is matched in
	 * a query
	 * @param array $args When three arguments are passed, the second is an
	 * indexed array of arguments to be injected into a parameterized query
	 */
	public function add() {

		// Quasi-poly-mophism in PHP:
		switch (func_num_args()) {
			case 2:
				list($pattern, $data) = func_get_args();
				$args = array();
				break;

			case 3:
				list($pattern, $args, $data) = func_get_args();
				break;

			// Any other argument count is not supported
			default:
				print "QueryRules::add() called with an unsupported count of arguments\n";
				return;
		}

		// The distinct rule key is a combination of the pattern and the parameterization arguments
		$rulekey = $this->makeRulekey($pattern, $args);

		// If there is already a rule with this key
		if (isset($this->rules[$rulekey])) {

			// Add this data set to the pattern's result set
			$this->rules[$rulekey]['data'][] = $data;
		}
		else {
			// Add a new rule for this key
			$this->rules[$rulekey] = array(
				'pattern' => $pattern,
				'data' => array($data),
				'dataptr' => 0
			);
		}
	}

	/**
	 * Helper function to make a distinct rulekey for each query pattern / argument set supplied
	 */
	private function makeRulekey($pattern, $args) {

		// The default value for args in the query functions is boolean false (mixed type, ugh!)
		if (! is_array($args)) $args = array();

		// The distinct rule key is a combination of the pattern and the parameterization arguments
		$rulekey = md5($pattern . serialize($args));

		return($rulekey);
	}

	/**
	 * Apply any stubbed database query rules that are defined; the
	 * first match will result in returning whatever result array that
	 * the matching rule specifies
	 *
	 * @param string $query The SQL query that we want to match against all
	 * our rules' patterns
	 *
	 * @return array Data associated with whatever pattern matches first, or
	 * an empty array if there were no pattern matches
	 */
	public function apply($query, $args = array()) {

		// For each rule defined...
		if (count($this->rules)) {

			foreach ($this->rules as $rulekey => $rule) {

				// If the pattern matches this query...
				if (preg_match($rule['pattern'], $query, $matches)) {
		
					// And if the distinct rule key matchs the supplied arguments
					if ($rulekey == $this->makeRulekey($rule['pattern'], $args)) {

						// Get the data for this rule at its current data pointer location
						$data = $rule['data'][$rule['dataptr']];

						// Advance the data pointer location in a looping fashion
						$rule['dataptr'] = ($rule['dataptr'] + 1) % count($rule['data']);

						return($data);
					}
				}
			}
		}

		// Well, no rules matched, so empty array data result it is
		print("QueryRules::apply() - No rule matching query: [{$query}]\nargs: " . print_r($args, true) ."\n\n");
		return(array());
	}
}

global $queryRules;
$queryRules = new QueryRules();

class QueryResult {

	private $data;

	public function QueryResult($data = array()) {
		$this->data = $data;
	}

	public function fetch($format) {
		// If there are no results left to process then we're done
		if (! (is_array($this->data) && count($this->data))) return(array());

		$row = array_shift($this->data);

		switch ($format) {
			case PDO::FETCH_ASSOC:
				return($row);

			case PDO::FETCH_NUM:
				return(array_values($row));
		}

		return(array());
	}
}

// functions that are stubbed because real ones would actually touch the database server

function DBSafe($value) {

	// If it's a string, bas64 encode should pretty much always be a safe version of it
	if (is_string($value)) return(base64_encode($text));

	// Boolean must be a 1 or 0 only
	if (is_bool($value)) return($value ? 1 : 0);

	// Treat anything else as a numeric value
	return(intval($value));
}

function getStartEndDate() {
	return(array(time(),time()));
}

function Query($query, $dbconnect=false, $args=false) {
	global $queryRules;
	return(new QueryResult($queryRules->apply($query, $args)));
}

function QuickQuery($query, $dbconnect=false, $args=false) {
	global $queryRules;
	$queryResult = new QueryResult($queryRules->apply($query, $args));
	$row = $queryResult->fetch(PDO::FETCH_NUM);
	$value = $row[0];
	return($value);
}

function QuickQueryMultiRow($query, $assoc = false, $dbconnect = false, $args = false) {
	global $queryRules;
	$queryResult = new QueryResult($queryRules->apply($query, $args));
	$list = array();
	while ($row = $queryResult->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM)) $list[] = $row;
	return($list);
}

// functions lifted verbatim from db.inc.php

function DBGetRow ($result, $assoc = false) {
	return $result->fetch($assoc ? PDO::FETCH_ASSOC : PDO::FETCH_NUM);
}

