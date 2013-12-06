<?php

/**
 * Stub out the objects/functions for various database operations
 *
 * Note that we don't want to spend a bunch of time simulating a database, we just want the function calls
 * to return something the way they might normally without causing an execution error.
 *
 */

class QueryRules {
	private $rules = array();

	/**
	 * Reset all the rules; should be done at the start of
	 * a new test, or at least the start of a new test class
	 */
	public function reset() {
		$this->rules = array();
	}

	/**
	 * Add a query rule iwth the supplied pattern and data
	 *
	 * @param string $pattern Regex pattern to match against $query in the
	 * apply function
	 * @param array $data Multidimensional array of query result data we
	 * want the apply function to return whenever this pattern is matched in
	 * a query
	 */
	public function add($pattern, $data) {
		$this->rules[] = array(
			'pattern' => $pattern,
			'data' => $data
		);
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
	public function apply($query) {

		// If there are no rules to apply, then the result data will be an empty array
		if (! count($this->rules)) return(array());

		// For each rule defined...
		foreach ($this->rules as $rule) {

			// If the pattern matches this query...
			if (preg_match($rule['pattern'], $query, $matches)) {
				return($rule['data']);
			}
		}

		// Well, no rules matched, so empty array data result it is
		print("QueryRules::apply() - No rule matching query: [{$query}]\n\n");
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

function Query($query) {
	global $queryRules;
	return(new QueryResult($queryRules->apply($query)));
}

function QuickQuery($query) {
	global $queryRules;
	return(new QueryResult($queryRules->apply($query)));
}

